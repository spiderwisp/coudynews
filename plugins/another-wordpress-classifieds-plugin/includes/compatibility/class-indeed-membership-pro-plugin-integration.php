<?php
/**
 * @package AWPCP\Compatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.4
 */
class AWPCP_IndeedMembershipProPluginIntegration {

    /**
     * @var AWPCP_Query
     */
    private $query;

    /**
     * @since 4.0.4
     */
    public function __construct( $query ) {
        $this->query = $query;
    }

    /**
     * @since 4.0.4
     */
    public function setup() {
        add_action( 'wp_enqueue_scripts', [ $this, 'maybe_dequeue_select2' ], 9999 );
    }

    /**
     * @since 4.0.4
     */
    public function maybe_dequeue_select2() {
        if ( $this->should_dequeue_select2() ) {
            wp_dequeue_style( 'ihc_select2_style' );
            wp_dequeue_script( 'ihc-select2' );
        }
    }

    /**
     * Determine whether we need to dequeue the select2 script included in
     * the Indeed Membership Pro plugin.
     *
     * We prefer to use our copy of Select2 if we are showing the Place Ad,
     * Edit Ad, Browse Ads or Search Ads pages.
     *
     * TODO: We should also dequeue the script if the Search Ads widget is
     * used on the current page.
     *
     * @since 4.0.4
     */
    private function should_dequeue_select2() {
        if ( $this->query->is_post_listings_page() ) {
            return true;
        }

        if ( $this->query->is_edit_listing_page() ) {
            return true;
        }

        if ( $this->query->is_browse_listings_page() ) {
            return true;
        }

        if ( $this->query->is_search_listings_page() ) {
            return true;
        }

        return false;
    }
}
