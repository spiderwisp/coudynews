<?php
/**
 * @since 3.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_display_listings( $query_vars, $context, $options ) {
    $options = wp_parse_args( $options, array(
        'page' => false,
        'show_intro_message' => false,
        'show_menu_items' => false,
        'show_category_selector' => false,
        'show_pagination' => false,
        'featured' => false,
        'classifieds_bar_components' => array(),
        'before_content' => '',
        'before_pagination' => '',
        'before_list' => '',
        'after_pagination' => '',
        'after_content' => '',
    ) );

    if ( has_action( 'awpcp_browse_ads_template_action' ) || has_filter( 'awpcp_browse_ads_template_filter' ) ) {
        do_action( 'awpcp_browse_ads_template_action' );
        return apply_filters( 'awpcp_browse_ads_template_filter', '' );
    }

    // Correctly set the context for the featured ads module.
    if ( isset( $query_vars['context'] ) && ! isset( $query_vars['classifieds_query'] ) ) {
        $query_vars['classifieds_query'] = [
            'context' => $query_vars['context'],
        ];
    }

    $results_per_page = awpcp_get_results_per_page( $query_vars );
    $results_offset   = awpcp_get_results_offset( $results_per_page, $query_vars );

    $query_vars['posts_per_page'] = $results_per_page;
    $query_vars['paged']          = 1 + floor( $results_offset / $results_per_page );

    unset( $query_vars['results'], $query_vars['limit'], $query_vars['offset'] );

    $listings_collection = awpcp_listings_collection();

    if ($context === 'user-listings-shortcode') {
        $listings = $listings_collection->find_user_listings( $query_vars['author'], $query_vars );
    }
    else {
        $listings = $listings_collection->find_enabled_listings( $query_vars );
    }
    $query    = $listings_collection->get_last_query();

    $before_content = apply_filters( 'awpcp-content-before-listings-page', $options['before_content'], $context );

    $before_pagination = array();
    if ( $options['show_category_selector'] ) {
        $before_pagination[15]['category-selector'] = awpcp_categories_switcher()->render( [ 'required' => false ] );
    }
    if ( is_array( $options['before_pagination'] ) ) {
        $before_pagination = awpcp_array_merge_recursive( $before_pagination, $options['before_pagination'] );
    } else {
        $before_pagination[20]['user-content'] = $options['before_pagination'];
    }
    $before_pagination = apply_filters( 'awpcp-content-before-listings-pagination', $before_pagination, $context, $listings, $query_vars );
    ksort( $before_pagination );
    $before_pagination = awpcp_flatten_array( $before_pagination );

    $before_list = apply_filters( 'awpcp-content-before-listings-list', $options['before_list'], $context );

    $top_pagination    = '';
    $bottom_pagination = '';
    $items = array();

    if ( $query->found_posts > 0 ) {
        if ( $options['show_pagination'] ) {
            $top_pagination_options = array(
                'query'         => $query,
                'results'       => $results_per_page,
                'offset'        => $results_offset,
                'total'         => $query->found_posts,
                'show_dropdown' => false,
            );

            $bottom_pagination_options = $top_pagination_options;
            unset( $bottom_pagination_options['show_dropdown'] );

            $top_pagination    = awpcp_pagination( $top_pagination_options, awpcp_current_url() );
            $bottom_pagination = awpcp_pagination( $bottom_pagination_options, awpcp_current_url() );
        }

        $items = awpcp_render_listings_items( $listings, $context, $options );
    }

    $after_pagination = array( 'user-content' => $options['after_pagination'] );
    $after_pagination = apply_filters( 'awpcp-content-after-listings-pagination', $after_pagination, $context );

    $after_content = apply_filters( 'awpcp-content-after-listings-page', $options['after_content'], $context );

    ob_start();
    include( AWPCP_DIR . '/templates/frontend/listings.tpl.php' );
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

/**
 * The number of results to show in each page is calculated considering the
 * following sources. We will use the first value that is available.
 *
 * 1. The value of a 'posts_per_page' entry in the $query_vars argument.
 * 2. The value of a 'limit' entry in the $query_vars argument.
 * 3. The value of a 'results' entry in the $query_vars argument.
 * 4. The value of a 'results' GET or POST variable.
 * 5. The value of the 'adresultsperpage' option.
 * 6. 10
 *
 * @param array $query_vars     Array of WP_Query query vars that will be used
 *                              elsewhere to load the listings to show on this
 *                              page.
 * @since 4.0.0
 */
function awpcp_get_results_per_page( $query_vars = [] ) {
    $pagination_options = get_awpcp_option('pagination-options', 10);
    $pagination_options = (array) $pagination_options;
    $max_results        = max($pagination_options) ? max($pagination_options) : 10;
    $per_page           = awpcp_get_var(
        array(
            'param'   => 'adresultsperpage',
            'default' => get_awpcp_option( 'adresultsperpage', 10 ),
        )
    );
    $results_per_page   = awpcp_get_var(
        array(
            'param'    => 'results',
            'default'  => $per_page,
            'sanitize' => 'intval',
        )
    );
    if ($results_per_page > $max_results) {
        $results_per_page = $max_results;
    }

    if ( ! empty( $query_vars['results'] ) ) {
        $results_per_page = intval( $query_vars['results'] );
    }

    if ( ! empty( $query_vars['limit'] ) ) {
        $results_per_page = intval( $query_vars['limit'] );
    }

    if ( ! empty( $query_vars['posts_per_page'] ) ) {
        $results_per_page = intval( $query_vars['posts_per_page'] );
    }

    // The number of results per page should always be a number greater or
    // equal than 1.
    return max( $results_per_page, 1 );
}

/**
 * The results offset is calculated considering the following sources. We
 * will use the first value that is available.
 *
 * 1. The value of an 'offset' entry in the $query_vars argument.
 * 2. The value of a 'paged' entry in the $query_vars argument.
 * 3. The value of an 'offset' GET or POST variable.
 * 4. The value of the 'paged' query var.
 * 5. The value of the 'page' query var.
 *
 * @param int   $results_per_page   Used to calculate the offset from a page
 *                                  number.
 * @param array $query_vars         Array of WP_Query query vars that will be
 *                                  used elsewhere to load the listings to show
 *                                  on this page.
 * @since 4.0.0
 */
function awpcp_get_results_offset( $results_per_page, $query_vars = [] ) {
    $results_offset = 0;
    $page           = intval( get_query_var( 'page' ) );

    if ( $page > 0 ) {
        $results_offset = ( $page - 1 ) * $results_per_page;
    }

    $paged = intval( get_query_var( 'paged' ) );

    if ( $paged > 0 ) {
        $results_offset = ( $paged - 1 ) * $results_per_page;
    }

    $results_offset = awpcp_get_var(
        array(
            'param'    => 'offset',
            'default'  => $results_offset,
            'sanitize' => 'intval',
        )
    );
    $paged          = isset( $query_vars['paged'] ) ? intval( $query_vars['paged'] ) : 0;

    if ( $paged > 0 ) {
        $results_offset = ( $paged - 1 ) * $results_per_page;
    }

    if ( isset( $query_vars['offset'] ) ) {
        $results_offset = intval( $query_vars['offset'] );
    }

    return $results_offset;
}

/**
 * @since 3.4
 */
function awpcp_display_listings_in_page( $query, $context, $options = array() ) {
    $options = wp_parse_args( $options, array(
        'show_intro_message' => true,
        'show_menu_items' => true,
        'show_category_selector' => ! awpcp_get_option( 'hide-categories-selector', false ),
        'show_pagination' => true,
    ) );

    return awpcp_display_listings( $query, $context, $options );
}

/**
 * Handler for the {@see 'awpcp-content-before-listing-page'} filter.
 *
 * @since 4.0.0
 */
function awpcp_insert_classifieds_bar_before_listing_page( $content_before_page ) {
    return $content_before_page . awpcp_render_classifieds_bar();
}

/**
 * Returns the HTML code for the Classifieds Bar component.
 *
 * If called from the Admin Dashboard, then it returns an empty string
 * to avoid the component to be shown on the Quick View admin page.
 *
 * @since 4.0.0
 *
 * @param array       $components The components to show in the Classifieds Bar.
 * @param bool|string $echo       Whether to echo the output or return it.
 *
 * @return string|void
 */
function awpcp_render_classifieds_bar( $components = array(), $echo = false ) {
    if ( is_admin() ) {
        return '';
    }

    if ( $echo ) {
        awpcp_classifieds_bar()->show( $components );
        return;
    }

    return awpcp_classifieds_bar()->render( $components );
}

/**
 * Handles AWPCPSHOWAD shortcode.
 *
 * @param integer $adid         An Ad ID.
 * @param boolean $omitmenu
 * @param boolean $preview      True if the function is used to show an ad just
 *                              after it was posted to the website.
 * @param boolean $send_email   If true and $preview=true, a success email will be
 *                              send to the admin and poster user.
 *
 * @deprecated 4.0.0 Use an instance of Listings Content Renderer instead.
 *
 * @return string Show Ad page content.
 */
function showad( $adid=null, $omitmenu=false, $preview=false, $send_email=true, $show_messages=true ) {
    _deprecated_function( __FUNCTION__, '4.0', 'Use an instance of Listings Content Renderer instead.' );

    global $wpdb;

    awpcp_maybe_add_thickbox();
    wp_enqueue_script('awpcp-page-show-ad');

    $awpcp = awpcp();
    $listing_renderer = awpcp_listing_renderer();

    $awpcp->js->set( 'page-show-ad-flag-ad-nonce', wp_create_nonce('flag_ad') );

    $awpcp->js->localize( 'page-show-ad', array(
        'flag-confirmation-message' => __( 'Are you sure you want to flag this ad?', 'another-wordpress-classifieds-plugin' ),
        'flag-success-message' => __( 'This Ad has been flagged.', 'another-wordpress-classifieds-plugin' ),
        'flag-error-message'        => __( 'An error occurred while trying to flag the Ad.', 'another-wordpress-classifieds-plugin' ),
    ) );

    // phpcs:ignore WordPress.Security.NonceVerification
    $preview      = $preview === true || 'preview' === awpcp_array_data('adstatus', '', $_GET);
    $is_moderator = awpcp_current_user_is_moderator();
    $messages     = array();

    $permastruc = get_option('permalink_structure');
    if (!isset($adid) || empty($adid)) {
        if (isset($_REQUEST['adid']) && !empty($_REQUEST['adid'])) { // phpcs:ignore WordPress.Security.NonceVerification
            $adid = awpcp_get_var( array( 'param' => 'adid' ) );
        } elseif (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) { // phpcs:ignore WordPress.Security.NonceVerification
            $adid = awpcp_get_var( array( 'param' => 'id' ) );
        } elseif (isset($permastruc) && !empty($permastruc)) {
            $adid = get_query_var( 'id' );
        } else {
            $adid = 0;
        }
    }

    $adid = absint( $adid );

    if (!empty($adid)) {
        // filters to provide alternative method of storing custom
        // layouts (e.g. can be outside of this plugin's directory)
        if ( has_action( 'awpcp_single_ad_template_action' ) || has_filter( 'awpcp_single_ad_template_filter' ) ) {
            do_action( 'awpcp_single_ad_template_action' );
            return apply_filters( 'awpcp_single_ad_template_filter', '' );

        } else {
            try {
                $ad = awpcp_listings_collection()->get( $adid );
            } catch ( AWPCP_Exception $e ) {
                $ad = null;
            }

            if (is_null($ad)) {
                $message = __( 'Sorry, that listing is not available. Please try browsing or searching existing listings.', 'another-wordpress-classifieds-plugin' );
                return '<div id="classiwrapper">' . awpcp_print_error($message) . '</div><!--close classiwrapper-->';
            }

            if ( $ad->post_author > 0 && $ad->post_author == wp_get_current_user()->ID ) {
                $is_ad_owner = true;
            } else {
                $is_ad_owner = false;
            }

            $content_before_page = apply_filters( 'awpcp-content-before-listing-page', '' );
            $content_after_page = apply_filters( 'awpcp-content-after-listing-page', '' );

            $output = '<div id="classiwrapper">%s<!--awpcp-single-ad-layout-->%s</div><!--close classiwrapper-->';
            $output = sprintf( $output, $content_before_page, $content_after_page );

            if (!$is_moderator && !$is_ad_owner && !$preview && $listing_renderer->is_disabled( $ad ) ) {
                $message = __( 'The Ad you are trying to view is pending approval. Once the Administrator approves it, it will be active and visible.', 'another-wordpress-classifieds-plugin');
                return str_replace( '<!--awpcp-single-ad-layout-->', awpcp_print_error( $message ), $output );
            }

            $verified = awpcp_get_var( array( 'param' => 'verified' ) );
            if ( $verified && $listing_renderer->is_verified( $ad ) ) {
                $messages[] = awpcp_print_message( __( 'Your email address was successfully verified.', 'another-wordpress-classifieds-plugin' ) );
            }

            if ($show_messages && $is_moderator && $listing_renderer->is_disabled( $ad ) ) {
                $message = __( 'This Ad is currently disabled until the Administrator approves it. Only you (the Administrator) and the author can see it.', 'another-wordpress-classifieds-plugin');
                $messages[] = awpcp_print_error($message);
            } else if ( $show_messages && ( $is_ad_owner || $preview ) && ! $listing_renderer->is_verified( $ad ) ) {
                $message = __( 'This Ad is currently disabled until you verify the email address used for the contact information. Only you (the author) can see it.', 'another-wordpress-classifieds-plugin');
                $messages[] = awpcp_print_error($message);
            } else if ( $show_messages && ( $is_ad_owner || $preview ) && $listing_renderer->is_disabled( $ad ) ) {
                $message = __( 'This Ad is currently disabled until the Administrator approves it. Only you (the author) can see it.', 'another-wordpress-classifieds-plugin');
                $messages[] = awpcp_print_error($message);
            }

            $layout = awpcp_get_listing_single_view_layout( $ad );
            $layout = awpcp_do_placeholders( $ad, $layout, 'single' );

            $output = str_replace( '<!--awpcp-single-ad-layout-->', join('', $messages) . $layout, $output );
            $output = apply_filters('awpcp-show-ad', $output, $adid);

            if ( ! awpcp_request()->is_bot() ) {
                awpcp_listings_api()->increase_visits_count( $ad );
            }
        }
    } else {
        $query = array(
            'posts_per_page' => awpcp_get_var(
                array(
                    'param'    => 'results',
                    'default'  => get_awpcp_option( 'adresultsperpage', 10 ),
                    'sanitize' => 'absint',
                )
            ),
            'offset'         => awpcp_get_var(
                array( 'param' => 'offset', 'default' => 0, 'sanitize' => 'absint' )
            ),
            'orderby' => get_awpcp_option( 'groupbrowseadsby' ),
        );

        $output = awpcp_display_listings_in_page( $query, 'show-listing' );
    }

    return $output;
}

/**
 * @since 3.0
 */
function awpcp_get_ad_location($ad_id, $country=false, $county=false, $state=false, $city=false) {
    $places = array();

    if (!empty($city)) {
        $places[] = $city;
    }
    if (!empty($county)) {
        $places[] = $county;
    }
    if (!empty($state)) {
        $places[] = $state;
    }
    if (!empty($country)) {
        $places[] = $country;
    }

    if (!empty($places)) {
        $location = sprintf('%s: %s', __("Location",'another-wordpress-classifieds-plugin'), join(', ', $places));
    } else {
        $location = '';
    }

    return $location;
}

function awpcp_get_listing_single_view_layout( $listing ) {
    $layout = get_awpcp_option( 'awpcpshowtheadlayout' );

    if ( empty( $layout ) ) {
        $layout = awpcp()->settings->get_option_default_value( 'awpcpshowtheadlayout' );
    }

    $layout = apply_filters( 'awpcp-single-ad-layout', $layout, $listing );

    if ( get_awpcp_option( 'allow-wordpress-shortcodes-in-single-template' ) ) {
        $layout = do_shortcode( $layout );
    }

    return $layout;
}
