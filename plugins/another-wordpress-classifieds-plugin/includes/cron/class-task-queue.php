<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_task_queue() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_TaskQueue( awpcp_tasks_collection(), awpcp()->settings );
    }

    return $instance;
}

class AWPCP_TaskQueue {

    private $tasks;
    private $settings;
    private $wp_filesystem;

    public function __construct( $tasks, $settings ) {
        $this->tasks         = $tasks;
        $this->settings      = $settings;
        $this->wp_filesystem = awpcp_get_wp_filesystem();

        if ( ! $this->wp_filesystem ) {
            throw new AWPCP_Exception( esc_html__( 'Unable to initialize WordPress file system.', 'another-wordpress-classifieds-plugin' ) );
        }
    }

    public function add_task( $name, $metadata ) {
        $this->tasks->create_task( $name, $metadata );
        $this->schedule_next_task_queue_event_if_necessary();
    }

    private function schedule_next_task_queue_event_if_necessary( $next_event_timestamp = null ) {
        $next_scheduled_event_timestamp = $this->get_next_scheduled_event_timestamp();
        $next_event_timestamp           = is_null( $next_event_timestamp ) ? time() + 30 : $next_event_timestamp;

        if ( $next_scheduled_event_timestamp && ( $next_scheduled_event_timestamp < $next_event_timestamp ) ) {
            return;
        }

        if ( $next_scheduled_event_timestamp && ( $next_scheduled_event_timestamp - $next_event_timestamp <= 60 ) ) {
            return;
        }

        wp_schedule_single_event( $next_event_timestamp, 'awpcp-task-queue-event', array( 'created_at' => $next_event_timestamp ) );
    }

    /**
     * A modified version of wp_next_scheduled that doesn't takes into account
     * the parameters passed to the callback.
     */
    private function get_next_scheduled_event_timestamp() {
        $crons = _get_cron_array();

        if ( empty( $crons ) ) {
            return false;
        }

        foreach ( $crons as $timestamp => $cron ) {
            if ( isset( $cron['awpcp-task-queue-event'] ) ) {
                return $timestamp;
            }
        }

        return false;
    }

    public function task_queue_event() {
        if ( ! $this->get_lock() ) {
            return;
        }

        $this->process_next_active_task();
        $this->maybe_schedule_next_task_queue_event();

        $this->release_lock();
    }

    private function maybe_schedule_next_task_queue_event() {
        try {
            $next_task = $this->tasks->get_next_task();
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        $next_event_local_timestamp = strtotime( $next_task->get_execute_after_date() );
        $next_event_timestamp       = $next_event_local_timestamp - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        $this->schedule_next_task_queue_event_if_necessary( $next_event_timestamp );
    }

    private function get_lock() {
        $lockfile   = $this->get_lock_file();
        $file_chmod = awpcp_get_file_chmod();
        $dir_chmod  = awpcp_get_dir_chmod();

        if ( ! $this->wp_filesystem->exists( $lockfile ) ) {
            if ( $this->wp_filesystem->is_dir( dirname( $lockfile ) ) ) {
                return $this->wp_filesystem->put_contents( $lockfile, '', $file_chmod );
            }

            if ( $this->wp_filesystem->mkdir( dirname( $lockfile ), $dir_chmod, true ) ) {
                return $this->wp_filesystem->put_contents( $lockfile, '', $file_chmod );
            }

            return false;
        }

        if ( time() - $this->wp_filesystem->mtime( $lockfile ) > 30 * 60 ) {
            $this->wp_filesystem->delete( $lockfile );
            return $this->wp_filesystem->put_contents( $lockfile, '', $file_chmod );
        }

        return false;
    }

    private function get_lock_file() {
        return implode( DIRECTORY_SEPARATOR, array( $this->settings->get_runtime_option( 'awpcp-uploads-dir' ), 'task-queue.lock' ) );
    }

    private function process_next_active_task() {
        try {
            $this->process_task( $this->tasks->get_next_active_task() );
        } catch ( AWPCP_Exception $e ) {
            // TODO: Throw different exceptions so that we can handle them differently.
            if ( $e->getMessage() !== 'There are no more tasks.' ) {
                trigger_error( esc_html( $e->format_errors() ) );
            }
            return;
        }
    }

    private function process_task( $task ) {
        if ( ! $this->run_task( $task ) ) {
            $task->retry();
        }

        if ( $task->is_delayed() || $task->is_failing() ) {
            $this->tasks->update_task( $task );
        } elseif ( $task->failed() || $task->is_complete() ) {
            $this->tasks->update_task( $task );
        }
    }

    private function run_task( $task ) {
        try {
            $exit_code = apply_filters( "awpcp-task-{$task->get_name()}", false, $task );
        } catch ( AWPCP_Exception $e ) {
            $exit_code = false;
        }

        return $exit_code;
    }

    private function release_lock() {
        $lockfile = $this->get_lock_file();

        if ( $this->wp_filesystem->exists( $lockfile ) ) {
            return $this->wp_filesystem->delete( $lockfile );
        }

        return false;
    }
}
