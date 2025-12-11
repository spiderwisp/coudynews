<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Upgrade_Task_Ajax_Handler extends AWPCP_AjaxHandler {

    /**
     * @var object
     */
    private $task_controller;

    public function __construct( $task_controller, $null, $response ) {
        parent::__construct( $response );

        $this->task_controller = $task_controller;
    }

    public function ajax() {
        $task_slug = awpcp_get_var( array( 'param' => 'action' ) );
        $context   = awpcp_get_var( array( 'param' => 'context' ) );

        try {
            list( $records_count, $records_left ) = $this->task_controller->run_task( $task_slug, $context );
        } catch ( AWPCP_Exception $e ) {
            return $this->error_response( $e->getMessage() );
        }

        return $this->progress_response( $records_count, $records_left );
    }
}
