<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the current name of the AWPCP main page.
 */
function get_currentpagename() {
    return awpcp_get_page_name( 'main-page-name' );
}

/**
 * Check if the page identified by $refname exists.
 */
function awpcp_find_page($refname) {
    $page_id = awpcp_get_page_id_by_ref( $refname );

    if ( empty( $page_id ) ) {
        return false;
    }

    $page = get_page( $page_id );

    if ( ! is_object( $page ) || $page->ID != $page_id ) {
        return false;
    }

    return true;
}

/**
 * Get the id of a page by its name.
 */
function awpcp_get_page_id( $name ) {
    global $wpdb;

    if ( ! empty( $name ) ) {
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s",
                $name
            )
        );
    }

    return 0;
}

/**
 * @since 4.0.0
 */
function awpcp_get_page_by_ref( $ref ) {
    $page_id = awpcp_get_page_id_by_ref( $ref );

    if ( ! $page_id ) {
        return null;
    }

    return get_post( $page_id );
}

/**
 * Returns the ID of WP Page associated to a page-name setting.
 *
 * @param $refname the name of the setting that holds the name of the page
 */
function awpcp_get_page_id_by_ref( $refname ) {
    $plugin_pages_ids = awpcp_get_plugin_pages_ids();

    if ( isset( $plugin_pages_ids[ $refname ] ) ) {
        return intval( $plugin_pages_ids[ $refname ] );
    } else {
        return false;
    }
}

/**
 * Return the IDs of WP pages associated with AWPCP pages.
 *
 * @return array Array of Page IDs
 */
function awpcp_get_page_ids_by_ref( $refnames ) {
    $plugin_pages_ids = awpcp_get_plugin_pages_ids();
    $pages_ids = array();

    foreach ( $refnames as $refname ) {
        if ( isset( $plugin_pages_ids[ $refname ] ) ) {
            $pages_ids[] = $plugin_pages_ids[ $refname ];
        }
    }

    return $pages_ids;
}

/**
 * @since 3.5.3
 * @since 4.0.0     Uses Settings to get Page IDs.
 */
function awpcp_get_plugin_pages_ids() {
    $plugin_pages = array();

    foreach( awpcp_get_wordpress_pages_settings_translations() as $setting_name => $page_ref ) {
        $plugin_pages[ $page_ref ] = intval( awpcp()->settings->get_option( $setting_name ) );
    }

    return $plugin_pages;
}

/**
 * @since 4.0.0
 */
function awpcp_get_wordpress_pages_settings_translations() {
    return array(
        'main-plugin-page' => 'main-page-name',
        'show-listing-page' => 'show-ads-page-name',
        'submit-listing-page' => 'place-ad-page-name',
        'edit-listing-page' => 'edit-ad-page-name',
        'renew-listing-page' => 'renew-ad-page-name',
        'reply-to-listing-page' => 'reply-to-ad-page-name',
        'browse-listings-page' => 'browse-ads-page-name',
        'search-listings-page' => 'search-ads-page-name',
        'buy-subscription-page' => 'subscriptions-page-name',
        'payment-thnakyou-page' => 'payment-thankyou-page-name',
        'cancel-payment-page'   => 'payment-cancel-page-name',
    );
}

/**
 * @since 3.5.3
 * @since 4.0.0     Stores Page IDs using Settings.
 */
function awpcp_update_plugin_page_id( $page_ref, $page_id ) {
    $setting_name = awpcp_translate_page_ref_to_setting_name( $page_ref );

    if ( ! $setting_name ) {
        return false;
    }

    return awpcp()->settings->update_option( $setting_name, $page_id, true );
}

/**
 * @since 4.0.0
 */
function awpcp_translate_page_ref_to_setting_name( $page_ref ) {
    $settings_names = awpcp_get_wordpress_pages_settings_translations();

    return array_search( $page_ref, $settings_names, true );
}

if ( ! function_exists( 'is_awpcp_page' ) ) {
    /**
     * Check if the current page is one of the AWPCP pages.
     *
     * @since 3.4
     */
    function is_awpcp_page( $page_id = null ) {
        global $wp_the_query;

        if ( is_null( $page_id ) && ! $wp_the_query ) {
            return;
        }

        if ( is_null( $page_id ) ) {
            $page_id = $wp_the_query->get_queried_object_id();
        }

        $page_ref = array_search( intval( $page_id ), awpcp_get_plugin_pages_ids(), true );

        return $page_ref !== false;
    }
}

/**
 * @since 3.4
 */
function is_awpcp_admin_page() {
    if ( ! is_admin() ) {
        return false;
    }

    if ( ! empty( $_REQUEST['action'] ) && string_starts_with( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ), 'awpcp' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        return true;
    }

    if ( ! empty( $_REQUEST['page'] ) && string_starts_with( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'awpcp' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        return true;
    }

    return false;
}

function is_awpcp_browse_listings_page() {
    return awpcp_query()->is_browse_listings_page();
}

function is_awpcp_browse_categories_page() {
    return awpcp_query()->is_browse_categories_page();
}

function awpcp_get_browse_categories_page_url() {
    return awpcp_get_page_url( 'browse-ads-page-name' );
}

/**
 * @since 3.4
 */
function awpcp_get_browse_category_url_from_id( $category_id ) {
    try {
        $category = awpcp_categories_collection()->get( $category_id );
        $category_url = url_browsecategory( $category );
    } catch ( AWPCP_Exception $ex ) {
        $category_url = '';
    }

    return $category_url;
}

function url_browsecategory( $category ) {
    $permalinks = get_option('permalink_structure');

    $page_id = awpcp_get_page_id_by_ref( 'browse-ads-page-name' );
    $cat_id = $category->term_id;
    $cat_slug = sanitize_title( $category->name );

    if ( get_awpcp_option( 'seofriendlyurls' ) && $permalinks ) {
        $path = sprintf( '%s/%s/%s', get_page_uri( $page_id ), $cat_id, $cat_slug );
        $url_browsecats = awpcp_get_url_with_page_permastruct( $path );
    } else {
        if (!empty($permalinks)) {
            $params = array( 'awpcp_category_id' => "$cat_id/$cat_slug" );
        } else {
            $params = array( 'awpcp_category_id' => $cat_id );
        }

        $url_browsecats = add_query_arg( urlencode_deep( $params ), home_url( '?page_id=' . $page_id ) );
    }

    return $url_browsecats;
}

function url_placead() {
    return user_trailingslashit(awpcp_get_page_url('place-ad-page-name'));
}

function url_searchads() {
    return user_trailingslashit(awpcp_get_page_url('search-ads-page-name'));
}

function url_editad() {
    return user_trailingslashit(awpcp_get_page_url('edit-ad-page-name'));
}

/**
 * Return name of current AWPCP page.
 *
 * This is part of an effor to put all AWPCP functions under
 * the same namespace.
 */
function awpcp_get_main_page_name() {
    return awpcp_get_page_name('main-page-name');
}

/**
 * Always return the full URL, even if AWPCP main page
 * is also the home page.
 */
function awpcp_get_main_page_url() {
    return awpcp_get_page_url( 'main-page-name', true );
}

/**
 * Returns a link to an AWPCP page identified by $pagename.
 *
 * Always return the full URL, even if the page is set as
 * the homepage.
 *
 * The returned URL has no trailing slash. However, if the
 * $trailinghslashit parameter is set to true, the returned URL
 * will be passed through user_trailingslashit() function.
 *
 * If permalinks are disabled, the home url will have
 * a trailing slash.
 *
 * @since 2.0.7
 */
function awpcp_get_page_url($pagename, $trailingslashit=false) {
    $page_id = awpcp_get_page_id_by_ref( $pagename );
    return awpcp_get_page_link( $page_id, $trailingslashit );
}

/**
 * @since 3.0.2
 */
function awpcp_get_view_categories_url() {
    global $wp_rewrite;

    $permalink_structure = $wp_rewrite->get_page_permastruct();
    $main_page_id = awpcp_get_page_id_by_ref( 'main-page-name' );

    $base_url = get_permalink( $main_page_id, true );

    if ( $permalink_structure ) {
        $view_categories_page_name = get_awpcp_option( 'view-categories-page-name' );
        $view_categories_page_slug = sanitize_title( $view_categories_page_name );

        $pagename = sprintf( '%s/%s', get_page_uri( $main_page_id ), $view_categories_page_slug );
        $url = str_replace( '%pagename%', $pagename, $base_url );
    } else {
        $url = add_query_arg( 'layout', 2, $base_url );
    }

    return $url;
}

/**
 * Based on WP's _get_page_link().
 *
 * @since 3.6
 */
function awpcp_get_page_link( $page_or_page_id, $trailingslashit = false ) {
    global $wp_rewrite;

    $page = get_post( $page_or_page_id );

    if ( ! is_a( $page, 'WP_Post' ) ) {
        return '';
    }

    $permalink_structure = $wp_rewrite->get_page_permastruct();

    if ( !empty( $permalink_structure ) ) {
        $link = awpcp_get_url_with_page_permastruct( get_page_uri( $page ) );
        $link = $trailingslashit ? user_trailingslashit( $link, 'page' ) : rtrim( $link, '/' );
    } else {
        $link = home_url( '?page_id=' . $page->ID );
    }

    return $link;
}

/**
 * Necessary to generate custom URLs, like those for awpcpx endpoints,
 * that work when PATHINFO permalinks are enabled.
 *
 * @see https://codex.wordpress.org/Using_Permalinks#PATHINFO:_.22Almost_Pretty.22
 * @since 3.6
 */
function awpcp_get_url_with_page_permastruct( $path ) {
    global $wp_rewrite;

    $permalink_structure = $wp_rewrite->get_page_permastruct();

    $url = str_replace( '%pagename%', ltrim( $path, '/' ), $permalink_structure );
    $url = home_url( $url );

    return user_trailingslashit( $url );
}

/**
 * @since 3.4
 */
function awpcp_get_edit_listing_url( $listing, $context = 'display' ) {
    if ( awpcp_listing_authorization()->is_current_user_allowed_to_edit_listing( $listing ) ) {
        $url = awpcp_get_edit_listing_direct_url( $listing, $context );
    } elseif ( awpcp()->settings->get_option( 'requireuserregistration' ) ) {
        $url = awpcp_get_edit_listing_direct_url( $listing, $context );
    } else {
        $url = awpcp_get_edit_listing_generic_url();
    }

    return apply_filters( 'awpcp-edit-listing-url', $url, $listing );
}

/**
 * @since 3.4
 */
function awpcp_get_edit_listing_direct_url( $listing, $context = 'display' ) {
    return awpcp_get_edit_listing_page_url_with_listing_id( $listing );
}

/**
 * @since 3.7.8
 */
function awpcp_get_edit_listing_url_with_access_key( $listing ) {
    $args = array(
        'step' => 'verify-access-token',
        'access_token' => awpcp_create_edit_listing_access_token( $listing ),
    );

    return add_query_arg( $args, awpcp_get_page_url( 'edit-ad-page-name' ) );
}

function awpcp_create_edit_listing_access_token( $listing ) {
    $i = wp_nonce_tick();

    $nonce = substr( wp_hash( $i . '|' . $listing->ID, 'nonce' ), -12, 10 );
    $id_hash = substr( wp_hash( $nonce . '|' . $listing->ID, 'nonce' ), -12, 10 );

    return $nonce . $id_hash . '-' . $listing->ID;
}

function awpcp_verify_edit_listing_access_token( $access_token, $listing ) {
    $listing_id = $listing->ID;
    $i          = wp_nonce_tick();

    $nonce = substr( $access_token, 0, 10 );
    $expected_nonce = substr( wp_hash( $i . '|' . $listing_id, 'nonce' ), -12, 10 );

    if ( hash_equals( $nonce, $expected_nonce ) ) {
        return 'valid';
    }

    $expected_nonce = substr( wp_hash( ( $i - 1 ) . '|' . $listing_id, 'nonce' ), -12, 10 );

    if ( hash_equals( $nonce, $expected_nonce ) ) {
        return 'valid';
    }

    $id_hash = substr( $access_token, 10 );
    $expected_id_hash = substr( wp_hash( $nonce . '|' . $listing_id, 'nonce' ), -12, 10 );

    return hash_equals( $id_hash, $expected_id_hash ) ? 'expired' : 'invalid';
}

/**
 * @since 3.4
 */
function awpcp_get_edit_listing_page_url_with_listing_id( $listing ) {
    $use_seo_friendly_urls = get_awpcp_option( 'seofriendlyurls' );

    if ( $use_seo_friendly_urls && get_option( 'permalink_structure' ) ) {
        $page_id = awpcp_get_page_id_by_ref( 'edit-ad-page-name' );
        $base_url = get_permalink( $page_id, true );
        $pagename = sprintf( '%s/%d', get_page_uri( $page_id ), $listing->ID );
        $url = str_replace( '%pagename%', $pagename, $base_url );
    } else {
        $url = add_query_arg( 'id', $listing->ID, awpcp_get_page_url( 'edit-ad-page-name' ) );
    }

    return $url;
}

/**
 * @since 3.4
 */
function awpcp_get_edit_listing_generic_url() {
    return awpcp_get_page_url( 'edit-ad-page-name' );
}

/**
 * Returns a link that can be used to initiate the Ad Renewal process.
 *
 * @since 2.0.7
 */
function awpcp_get_renew_ad_url($ad_id) {
    $hash = awpcp_get_renew_ad_hash( $ad_id );

    $url = awpcp_get_page_url('renew-ad-page-name');

    $params = [
        'ad_id'    => $ad_id,
        'awpcprah' => $hash,
    ];

    return add_query_arg( urlencode_deep( $params ), $url );
}

/**
 * @since 3.0.2
 */
function awpcp_get_email_verification_url( $ad_id ) {
    $hash = awpcp_get_email_verification_hash( $ad_id );

    if ( get_option( 'permalink_structure' ) ) {
        return awpcp_get_url_with_page_permastruct( "/awpcpx/listings/verify/{$ad_id}/$hash" );
    } else {
        $params = array(
            'awpcpx' => true,
            'awpcp-module' => 'listings',
            'awpcp-action' => 'verify',
            'awpcp-ad' => $ad_id,
            'awpcp-hash' => $hash,
        );

        return add_query_arg( urlencode_deep( $params ), home_url( 'index.php' ) );
    }
}

/**
 * Returns a link to the page where visitors can contact the Ad's owner
 *
 * @since  3.0.0
 */
function awpcp_get_reply_to_ad_url($ad_id, $ad_title=null) {
    $use_seo_friendly_urls = get_awpcp_option( 'seofriendlyurls' );

    $page_id = awpcp_get_page_id_by_ref( 'reply-to-ad-page-name' );
    $base_url = get_permalink( $page_id, $use_seo_friendly_urls );
    $permalinks = get_option('permalink_structure');

    if (!is_null($ad_title)) {
        $title = sanitize_title($ad_title);
    } else {
        try {
            $listing = awpcp_listings_collection()->get( $ad_id );
            $title   = sanitize_title( awpcp_listing_renderer()->get_listing_title( $listing ) );
        } catch ( AWPCP_Exception $e ) {
            $title = '';
        }
    }

    if ( $use_seo_friendly_urls && get_option( 'permalink_structure' ) ) {
        $pagename = sprintf( '%s/%d/%s', get_page_uri( $page_id ), $ad_id, $title );
        $url = str_replace( '%pagename%', $pagename, $base_url );
    } else {
        $base_url = user_trailingslashit($base_url);
        $url = add_query_arg( array('i' => urlencode( $ad_id ) ), $base_url );
    }

    return $url;
}

/**
 * @since  3.0
 */
function awpcp_get_admin_panel_url() {
    return add_query_arg( 'page', 'awpcp.php', admin_url('admin.php'));
}

/**
 * @since 3.0.2
 * @since 4.0.0     Supports taking an array of query args.
 */
function awpcp_get_admin_settings_url( $params = false ) {
    $query_args = [
        'page' => 'awpcp-admin-settings',
    ];

    if ( false === $params ) {
        $params = [];
    }

    if ( is_string( $params ) ) {
        $params = [
            'g' => $params,
        ];
    }

    $query_args = array_filter( array_merge( $query_args, $params ), 'strlen' );

    return add_query_arg( $query_args, admin_url( 'admin.php' ) );
}

/**
 * @since 3.2.1
 */
function awpcp_get_admin_credit_plans_url() {
    return add_query_arg( 'page', 'awpcp-admin-credit-plans', admin_url( 'admin.php' ) );
}

/**
 * @since 3.2.1
 */
function awpcp_get_admin_fees_url() {
    return add_query_arg( 'page', 'awpcp-admin-fees', admin_url( 'admin.php' ) );
}

/**
 * @since 3.0.2
 */
function awpcp_get_admin_categories_url() {
    return add_query_arg( 'page', 'awpcp-admin-categories', admin_url( 'admin.php' ) );
}

/**
 * @since  3.0
 */
function awpcp_get_admin_upgrade_url() {
    return add_query_arg( 'page', 'awpcp-admin-upgrade', admin_url('admin.php'));
}

/**
 * Returns a link to Manage Listings
 *
 * @since 2.1.4
 */
function awpcp_get_admin_listings_url() {
    $params = [
        'post_type' => awpcp()->container['listing_post_type'],
    ];

    return add_query_arg( $params, admin_url( 'edit.php' ) );
}

/**
 * Return a link to the View page for the given listing.
 *
 * @since 4.0.4
 */
function awpcp_get_quick_view_listing_url( $listing ) {
    return get_permalink( $listing->ID );
}

/**
 * @since 3.4
 */
function awpcp_get_admin_form_fields_url() {
    return add_query_arg( 'page', 'awpcp-form-fields', admin_url( 'admin.php' ) );
}

/**
 * Returns a link to Ad Management (a.k.a User Panel).
 *
 * @since 2.0.7
 */
function awpcp_get_user_panel_url( $params=array() ) {
    return add_query_arg( urlencode_deep( $params ), awpcp_get_admin_listings_url() );
}

function awpcp_current_url() {
    return ( is_ssl() ? 'https://' : 'http://' ) . awpcp_get_server_value( 'HTTP_HOST' ) . awpcp_get_server_value( 'REQUEST_URI' );
}

/**
 * Builds WordPress ajax URL using the same domain used in the current request.
 *
 * @since 2.0.6
 */
function awpcp_ajaxurl($overwrite=false) {
    static $ajaxurl = false;

    if ($overwrite || $ajaxurl === false) {
        $request = awpcp_request();

        $ajaxurl = admin_url( 'admin-ajax.php' );
        $parts   = wp_parse_url( $ajaxurl );

        $ajaxurl = str_replace( $parts['host'], $request->domain(), $ajaxurl );
        $ajaxurl = str_replace( $parts['scheme'], $request->scheme(), $ajaxurl );
    }

    return $ajaxurl;
}
