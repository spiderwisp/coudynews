<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Executes the upgrade tasks runners responsible for updating the database on
 * every new version.
 *
 * This class is responsible for storing the results (remaining records, records
 * processed and ID of the last record processed) of the task being executed.
 *
 * The task runners (objects implementing AWPCP_Upgrade_Task_Runner), on the other
 * hand, are responsible of doing the work:
 *
 * - Calculating the number of records still pending to be upgraded.
 * - Retrieving pending records.
 * - Performing the necessary actions to upgrade the record's information to
 *   work with the new version of the plugin or the module.
 */
class AWPCP_Upgrade_Task_Handler {

    private $implementation;
    private $upgrade_sessions;
    private $tasks_manager;

    public function __construct( AWPCP_Upgrade_Task_Runner $implementation, $upgrade_sessions, $tasks_manager ) {
        $this->implementation   = $implementation;
        $this->upgrade_sessions = $upgrade_sessions;
        $this->tasks_manager    = $tasks_manager;
    }

    public function run_task( $task, $context ) {
        $upgrade_session = $this->upgrade_sessions->get_or_create_session( $context );

        $last_item_id = $upgrade_session->get_task_metadata( $task, 'last_item_id', 0 );
        $result       = $this->run_task_step( $task, $last_item_id );

        $this->update_task_metadata_with_step_result( $task, $result, $upgrade_session );
        $this->disable_upgrade_task_if_there_are_no_more_records( $task, $result );
        $this->archive_upgrade_session_if_there_are_no_more_tasks( $upgrade_session );

        return array( $result['pending_items_count_before'], $result['pending_items_count_now'] );
    }

    private function run_task_step( $task, $last_item_id ) {
        $pending_items_count_before = $this->implementation->count_pending_items( $last_item_id );
        $pending_items              = $this->implementation->get_pending_items( $last_item_id );

        if ( method_exists( $this->implementation, 'before_step' ) ) {
            $this->implementation->before_step();
        }

        foreach ( $pending_items as $item ) {
            $last_item_id = $this->implementation->process_item( $item, $last_item_id );
        }

        $pending_items_count_now = $this->implementation->count_pending_items( $last_item_id );

        return array(
            'last_item_id'               => $last_item_id,
            'pending_items_count_before' => $pending_items_count_before,
            'pending_items_count_now'    => $pending_items_count_now,
        );
    }

    private function update_task_metadata_with_step_result( $task, $result, $upgrade_session ) {
        $items_count = $upgrade_session->get_task_metadata( $task, 'items_count', 0 );

        if ( $items_count === 0 ) {
            $items_count = (int) $result['pending_items_count_before'];

            $upgrade_session->set_task_metadata( $task, 'items_count', $items_count );
        }

        $items_processed = (int) $items_count - (int) $result['pending_items_count_now'];

        $upgrade_session->set_task_metadata( $task, 'items_processed', $items_processed );
        $upgrade_session->set_task_metadata( $task, 'last_item_id', $result['last_item_id'] );

        $this->upgrade_sessions->save_session( $upgrade_session );
    }

    private function disable_upgrade_task_if_there_are_no_more_records( $task, $result ) {
        if ( (int) $result['pending_items_count_now'] === 0 ) {
            $this->tasks_manager->disable_upgrade_task( $task );
        }
    }

    private function archive_upgrade_session_if_there_are_no_more_tasks( $upgrade_session ) {
        if ( ! $this->upgrade_sessions->session_has_pending_tasks( $upgrade_session ) ) {
            $this->upgrade_sessions->archive_session( $upgrade_session );
        }
    }
}
