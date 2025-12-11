<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Responsible for creating and using an individual Upgrade Task Handler
 * configured to run a specific upgrade task.
 *
 * @since 4.0.0
 */
class AWPCP_UpgradeTaskController {

    /**
     * @var object
     */
    private $tasks_manager;

    /**
     * @var object
     */
    protected $task_handler_factory;

    /**
     * @since 4.0.0
     */
    public function __construct( $tasks_manager, $task_handler_factory ) {
        $this->tasks_manager        = $tasks_manager;
        $this->task_handler_factory = $task_handler_factory;
    }

    /**
     * Load and run the specified upgrade task within the upgrade session
     * associated with the given context.
     *
     * @since 4.0.0
     *
     * @throws AWPCP_Exception If no task is found or the handler for the task
     *                         cannot be instantiated.
     */
    public function run_task( $task_slug, $context ) {
        $task = $this->tasks_manager->get_upgrade_task( $task_slug );

        if ( is_null( $task ) ) {
            throw new AWPCP_Exception( esc_html( sprintf( 'No task was found with identifier: %s.', $task_slug ) ) );
        }

        $task_handler = $this->task_handler_factory->get_task_handler( $task['handler'] );

        if ( is_null( $task_handler ) ) {
            throw new AWPCP_Exception( esc_html( sprintf( "The handler for task '%s' couldn't be instantiated.", $task_slug ) ) );
        }

        return $task_handler->run_task( $task_slug, $context );
    }
}
