<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for URL Backwards Compatiblity Redirection Helper.
 */
function awpcp_url_backwards_compatibility_redirection_helper() {
    $container = awpcp()->container;

    return new AWPCP_URL_Backwards_Compatibility_Redirection_Helper(
        $container['listing_post_type'],
        awpcp_categories_registry(),
        $container['CategoriesCollection'],
        $container['ListingsCollection'],
        awpcp_query(),
        $container['Settings'],
        $container['Request']
    );
}

/**
 * Redirect URLs that include IDs used before 4.0 to URLs that use the
 * corresponding ID from listings stored as custom post types.
 *
 * @since 4.0.0
 */
class AWPCP_URL_Backwards_Compatibility_Redirection_Helper {

    private $post_type;
    private $categories_registry;
    private $categories;
    private $listings;
    private $query;
    private $settings;
    private $request;

    public function __construct( $post_type, $categories_registry, $categories, $listings, $query, $settings, $request = null ) {
        $this->post_type           = $post_type;
        $this->categories_registry = $categories_registry;
        $this->categories          = $categories;
        $this->listings            = $listings;
        $this->query               = $query;
        $this->settings            = $settings;
        $this->request             = $request;
    }

    /**
     * Redirect frontend requests that try to show a single listing using an old ID.
     *
     * A handler for the {@see 'parse_request'} action.
     *
     * @since 4.0.0
     */
    public function maybe_redirect_from_old_listing_url( $query ) {
        $vars                 = $query->query_vars;
        $requested_listing_id = null;

        // 1. A query for a single awpcp_listing.
        // 2 & 3. A query for a single awpcp_listing shown through the Show Ad page.
        if ( ! empty( $vars['post_type'] ) && $this->post_type === $vars['post_type'] && ! empty( $vars['p'] ) ) {
            $requested_listing_id = $vars['p'];
        } elseif ( ! empty( $vars['id'] ) && ! empty( $vars['page_id'] ) && $this->get_show_listing_page_id() === intval( $vars['page_id'] ) ) {
            $requested_listing_id = $vars['id'];
        } elseif ( ! empty( $vars['id'] ) && ! empty( $vars['pagename'] ) && $this->get_show_listing_page_uri() === $vars['pagename'] ) {
            $requested_listing_id = $vars['id'];
        }

        /**
         * A filter with a very long name to allow premium modules to help identify the
         * requested listing ID.
         *
         * @since 4.0.0
         */
        $requested_listing_id = apply_filters(
            'awpcp_url_backwards_compatibility_redirection_helper_requested_listing_id',
            $requested_listing_id
        );

        if ( ! $requested_listing_id ) {
            return;
        }

        try {
            $listing = $this->listings->get_listing_with_old_id( $requested_listing_id );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        return $this->redirect( get_permalink( $listing ) );
    }

    /**
     * @since 4.0.0
     */
    private function get_show_listing_page_id() {
        return intval( $this->settings->get_option( 'show-listing-page' ) );
    }

    /**
     * @since 4.0.0
     */
    private function get_show_listing_page_uri() {
        $page_id = $this->get_show_listing_page_id();

        return $page_id ? get_page_uri( $page_id ) : null;
    }

    /**
     * Redirect frontend requests that try to filter ads using an old category
     * ID or are trying to renew a listing using an old ID.
     *
     * A handler for the {@see 'template_redirect'} action.
     */
    public function maybe_redirect_frontend_request() {
        if ( awpcp_get_var( array( 'param' => 'awpcp-no-redirect' ) ) ) {
            return;
        }

        if ( $this->query->is_browse_listings_page() || $this->query->is_browse_categories_page() ) {
            $this->maybe_redirect_browse_listings_request();
            return;
        }

        if ( $this->query->is_renew_listing_page() ) {
            $this->maybe_redirect_renew_listing_request( $this->request->get_current_listing_id() );
            return;
        }
    }

    /**
     * Redirect frontend requests that include an old category ID.
     *
     * @since 4.0.0
     */
    private function maybe_redirect_browse_listings_request() {
        $requested_category_id  = intval( $this->request->get_category_id() );
        $equivalent_category_id = $this->get_equivalent_category_id( $requested_category_id );

        if ( $requested_category_id === $equivalent_category_id ) {
            return;
        }

        $category = $this->categories->get( $equivalent_category_id );

        return $this->redirect( url_browsecategory( $category ) );
    }

    private function get_equivalent_category_id( $category_id ) {
        $categories_registry = $this->categories_registry->get_categories_registry();

        if ( isset( $categories_registry[ $category_id ] ) ) {
            return intval( $categories_registry[ $category_id ] );
        }

        return intval( $category_id );
    }

    private function redirect( $redirect_url ) {
        global $wp_version;

        // Before 5.1.0, wp_safe_redirect() always returned null, making it
        // impossible to know whether the call to wp_redirect() worked or not.
        //
        // See https://github.com/WordPress/WordPress/blob/94b592ac684d7cc9a82b5ba42161d320a4c329f4/wp-includes/pluggable.php#L1334.

        if ( version_compare( $wp_version, '5.1.0', '<' ) && wp_redirect( esc_url_raw( $redirect_url ), 301 ) ) {
            exit();
        }

        if ( version_compare( $wp_version, '5.1.0', '>=' ) && wp_safe_redirect( $redirect_url, 301 ) ) {
            exit();
        }
    }

    private function maybe_redirect_renew_listing_request( $old_listing_id ) {
        $renew_hash = awpcp_get_var( array( 'param' => 'awpcprah' ) );

        if ( ! awpcp_verify_renew_ad_hash( $old_listing_id, $renew_hash ) ) {
            return;
        }

        try {
            $listing = $this->listings->get_listing_with_old_id( $old_listing_id );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        return $this->redirect( awpcp_get_renew_ad_url( $listing->ID ) );
    }

    /**
     * Redirect admin request that are trying to renew a listing using an old ID.
     */
    public function maybe_redirect_admin_request() {
        if ( awpcp_get_var( array( 'param' => 'awpcp-no-redirect' ) ) ) {
            return;
        }

        if ( strcmp( awpcp_get_var( array( 'param' => 'action' ) ), 'renew' ) === 0 ) {
            $this->maybe_redirect_renew_listing_request( $this->request->get_current_listing_id() );
            return;
        }
    }
}
