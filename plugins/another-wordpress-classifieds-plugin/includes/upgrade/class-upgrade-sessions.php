<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Upgrade_Sessions {

    private $tasks_manager;
    private $wordpress;

    public function __construct( $tasks_manager, $wordpress ) {
        $this->tasks_manager = $tasks_manager;
        $this->wordpress = $wordpress;
    }

    public function get_or_create_session( $context ) {
        $session_data = $this->wordpress->get_option( 'awpcp-upgrade-session-' . $context, false );

        if ( $session_data === false ) {
            $session_data = $this->get_default_session_data( $context );
        }

        return new AWPCP_Upgrade_Session( $session_data );
    }

    private function get_default_session_data( $context ) {
        return array(
            'context' => $context,
            'tasks' => array(),
            'completed' => false,
            'last_updated' => null,
        );
    }

    public function session_has_pending_tasks( $upgrade_session ) {
        foreach ( $upgrade_session->get_tasks() as $task => $metadata ) {
            if ( $this->tasks_manager->is_upgrade_task_enabled( $task ) ) {
                return true;
            }
        }

        return false;
    }

    public function save_session( $upgrade_session ) {
        $this->save_session_data_with_name(
            $upgrade_session,
            'awpcp-upgrade-session-' . $upgrade_session->get_context()
        );
    }

    private function save_session_data_with_name( $upgrade_session, $name ) {
        $session_data = array_merge(
            $upgrade_session->get_data(),
            array( 'last_updated' => current_time( 'timestamp' ) )
        );

        $this->wordpress->update_option( $name, $session_data, false );
    }

    public function archive_session( $upgrade_session ) {
        $context = $upgrade_session->get_context();

        $this->save_session_data_with_name(
            $upgrade_session,
            'awpcp-previous-upgrade-session-' . $context
        );

        $this->wordpress->delete_option( 'awpcp-upgrade-session-' . $context );
    }
}
