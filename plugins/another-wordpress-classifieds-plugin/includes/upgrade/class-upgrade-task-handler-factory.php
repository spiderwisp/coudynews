<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Factory for Upgrade Task Handler class.
 */
class AWPCP_Upgrade_Task_Handler_Factory {

    /**
     * @var AWPCP_Container
     */
    private $container;

    /**
     * Constructor.
     */
    public function __construct( $container ) {
        $this->container = $container;
    }

    /**
     * Loads an upgrade task handler using an instance of the provided class
     * name as the Task Runner.
     */
    public function get_task_handler( $task_runner_class ) {
        $task_runner = null;

        if ( isset( $this->container[ $task_runner_class ] ) ) {
            $task_runner = $this->container[ $task_runner_class ];
        }

        if ( $task_runner instanceof AWPCP_Upgrade_Task_Runner ) {
            return new AWPCP_Upgrade_Task_Handler(
                $task_runner,
                $this->container['UpgradeSessions'],
                awpcp_upgrade_tasks_manager()
            );
        }

        if ( method_exists( $task_runner, 'run_task' ) ) {
            return $task_runner;
        }
    }
}
