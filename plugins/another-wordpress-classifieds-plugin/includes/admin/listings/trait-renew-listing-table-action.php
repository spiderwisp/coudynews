<?php
/**
 * Common methods for the Renew Listing table actions available for subscribers
 * and moderators.
 *
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
trait AWPCP_RenewListingTableAction {

    /**
     * @since 4.0.0
     *
     * @param object $post  An instance of WP_Post.
     */
    public function should_show_action_for( $post ) {
        return $this->listing_renderer->has_expired_or_is_about_to_expire( $post );
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'fa fa-redo';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Renew', 'listing row action', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     *
     * @param object $post  An instance of WP_Post.
     */
    public function get_label( $post ) {
        return $this->get_title();
    }
}
