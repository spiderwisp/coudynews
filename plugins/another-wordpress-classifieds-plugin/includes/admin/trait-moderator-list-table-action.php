<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common methods for moderators List Table Actions.
 */
trait AWPCP_ModeratorListTableActionTrait {

    /**
     * @var object
     */
    private $roles_and_capabilities;

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        if ( ! $this->roles_and_capabilities->current_user_is_moderator() ) {
            return false;
        }

        return $this->should_show_action_for_post( $post );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    abstract protected function should_show_action_for_post( $post );
}
