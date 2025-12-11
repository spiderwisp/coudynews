<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface for List Table Actions that should be loaded conditionally.
 *
 * @since 4.0.0
 */
interface AWPCP_ConditionalListTableActionInterface {

    /**
     * Determines whether the action needs to be loaded on this request.
     *
     * Actions that should be available to administrator or moderator users
     * only use this method to prevent them from being shown to subscribers.
     *
     * @since 4.0.0
     */
    public function is_needed();
}
