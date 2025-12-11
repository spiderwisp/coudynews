<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core plugin and premium modules routines that need to be executed
 * asynchronously must implement this interface.
 *
 * An instance of Upgrade_Task_Handler takes an object that implements this
 * interface and calls the methods defined below to process each one of the
 * items that need to be upgraded.
 */
interface AWPCP_Upgrade_Task_Runner {

    /**
     * Returns the number of all the items that still need to be upgraded.
     */
    public function count_pending_items( $last_item_id );

    /**
     * Returns the items that need to be upgraded and should be processed in
     * the current step of the upgrade routine.
     *
     * @param mixed $last_item_id   An identifier for the last item processed
     *                              in process_item().
     */
    public function get_pending_items( $last_item_id );

    /**
     * Performs the necessary actions to upgrade the information associated
     * with the current $item.
     *
     * @param mixed $item           Usually a data record that needs to be
     *                              upgraded.
     * @param mixed $last_item_id   An identifier for the last item processed.
     */
    public function process_item( $item, $last_item_id );
}
