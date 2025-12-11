<?php
/**
 * The handlers for the plugin shortcodes and frontend pages are
 * defined and some of them implemented in this file.
 *
 * @package AWPCP/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// TODO: Do we really need to requires these files here?
require_once AWPCP_DIR . '/frontend/shortcode-raw.php';

require_once AWPCP_DIR . '/frontend/page-renew-ad.php';
require_once AWPCP_DIR . '/frontend/page-show-ad.php';
require_once AWPCP_DIR . '/frontend/page-reply-to-ad.php';
require_once AWPCP_DIR . '/frontend/page-search-ads.php';
require_once AWPCP_DIR . '/frontend/page-browse-ads.php';

/**
 * Configures handlers for plugin shortcodes and frontend pages.
 */
class AWPCP_Pages {
    private $output = array();

    public $container;

    public $meta;

    public $show_ad;

    public $browse_ads;

    public $place_ad_page = null;

    public $edit_ad_page = null;

    public $renew_ad_page = null;

    public function __construct( $container ) {
        $this->container = $container;

        $this->meta       = $this->container['Meta'];
        $this->show_ad    = $this->container['ShowListingPage'];
        $this->browse_ads = awpcp_browse_listings_page();

        // fix for theme conflict with ThemeForest themes.
        new AWPCP_RawShortcode();

        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // Page shortcodes.
        add_shortcode( 'AWPCPPLACEAD', array( $this, 'place_ad' ) );
        add_shortcode( 'AWPCPEDITAD', array( $this, 'edit_ad' ) );
        add_shortcode( 'AWPCP-RENEW-AD', array( $this, 'renew_ad' ) );
        add_shortcode( 'AWPCPSEARCHADS', array( $this, 'search_ads' ) );
        add_shortcode( 'AWPCPREPLYTOAD', array( $this, 'reply_to_ad' ) );

        // These shortcodes are no longer used, but kept for backwards compatibility.
        add_shortcode( 'AWPCPPAYMENTTHANKYOU', array( $this, 'noop' ) );
        add_shortcode( 'AWPCPCANCELPAYMENT', array( $this, 'noop' ) );

        add_shortcode( 'AWPCPBROWSECATS', array( $this->browse_ads, 'dispatch' ) );
        add_shortcode( 'AWPCPBROWSEADS', array( $this->browse_ads, 'dispatch' ) );

        add_shortcode( 'AWPCPSHOWAD', array( $this, 'show_ad' ) );
        add_shortcode( 'AWPCPCLASSIFIEDSUI', 'awpcpui_homescreen' );

        add_shortcode( 'AWPCPLATESTLISTINGS', array( $this, 'listings_shortcode' ) );
        add_shortcode( 'AWPCPRANDOMLISTINGS', array( $this, 'random_listings_shortcode' ) );
        add_shortcode( 'AWPCPSHOWCAT', array( $this, 'category_shortcode' ) );
        add_shortcode( 'AWPCPUSERLISTINGS', array( $this, 'user_listings_shortcode' ) );

        add_shortcode( 'AWPCPBUYCREDITS', array( $this, 'buy_credits' ) );

        add_action( 'wp_ajax_awpcp-flag-ad', array( $this, 'ajax_flag_ad' ) );
        add_action( 'wp_ajax_nopriv_awpcp-flag-ad', array( $this, 'ajax_flag_ad' ) );

        do_action( 'awpcp_setup_shortcode' );
    }

    public function noop() {
        return '';
    }

    public function place_ad() {
        awpcp_maybe_add_thickbox();
        wp_enqueue_script( 'awpcp-page-show-ad' );
        if ( ! isset( $this->output['place-ad'] ) ) {
            do_action( 'awpcp-shortcode', 'place-ad' );

            if ( is_null( $this->place_ad_page ) ) {
                $this->place_ad_page = $this->container['SubmitListingPage'];
            }

            $this->output['place-ad'] = $this->place_ad_page->dispatch();
        }

        return $this->output['place-ad'];
    }

    public function edit_ad() {
        if ( ! isset( $this->output['edit-ad'] ) ) {
            do_action( 'awpcp-shortcode', 'edit-ad' );

            if ( is_null( $this->edit_ad_page ) ) {
                $this->edit_ad_page = $this->container['EditListingPage'];
            }

            $this->output['edit-ad'] = $this->edit_ad_page->dispatch();
        }

        return $this->output['edit-ad'];
    }

    public function renew_ad() {
        if ( is_null( $this->renew_ad_page ) ) {
            $this->renew_ad_page = awpcp_renew_listing_page();
        }

        return is_null( $this->renew_ad_page ) ? '' : $this->renew_ad_page->dispatch();
    }

    public function show_ad() {
        if ( ! isset( $this->output['show-ad'] ) ) {
            $this->output['show-ad'] = $this->show_ad->dispatch();
        }

        return $this->output['show-ad'];
    }

    public function search_ads() {
        if ( ! isset( $this->output['search-ads'] ) ) {
            $this->output['search-ads'] = awpcp_search_listings_page()->dispatch();
        }

        return $this->output['search-ads'];
    }

    public function reply_to_ad() {
        if ( ! isset( $this->output['reply-to-ad'] ) ) {
            do_action( 'awpcp-shortcode', 'reply-to-ad' );

            $page                        = awpcp_reply_to_listing_page();
            $this->output['reply-to-ad'] = $page->dispatch();
        }

        return $this->output['reply-to-ad'];
    }

    /**
     * @since 3.0.2
     */
    public function buy_credits() {
        static $output = null;
        if ( is_null( $output ) ) {
            $output = awpcp_buy_credits_page()->dispatch();
        }
        return $output;
    }

    /* Shortcodes */

    /**
     * @param array $attrs {
     *      Optional. An array of shortcode attributes.
     *
     *      @type int  $user_id If defined, the shortcode will show listings
     *                          owned by this user only. Default {@see get_current_user_id()}.
     *      @type bool $menu    Whether to show the menu items above the list of listings.
     *      @type int  $limit   The number of listings to show. Default to number
     *                          of ads per page configured in the plugin settings.
     * }
     */
    public function user_listings_shortcode( $attrs ) {
        if ( ! get_awpcp_option( 'requireuserregistration' ) && ! is_user_logged_in() ) {
            $message = __( 'this shortcode only works with registered users.', 'another-wordpress-classifieds-plugin' );
            return awpcp_print_message( $message );
        }
        if ( get_awpcp_option( 'requireuserregistration' ) && ! is_user_logged_in() ) {
            return awpcp_login_form();
        }

        wp_enqueue_script( 'awpcp' );

        $attrs = shortcode_atts(
            array(
                'user_id' => get_current_user_id(),
                'menu'    => true,
                'limit'   => null,
            ),
            $attrs
        );

        $user_id = absint( $attrs['user_id'] );

        if ( $user_id === 0 ) {
            return '';
        }

        $query = array(
            'context' => 'public-listings',
            'author'  => $user_id,
        );

        if ( ! is_null( $attrs['limit'] ) ) {
            $query['limit'] = absint( $attrs['limit'] );
        }

        $options = array(
            'show_menu_items' => awpcp_parse_bool( $attrs['menu'] ),
            'show_pagination' => true,
        );

        return awpcp_display_listings( $query, 'user-listings-shortcode', $options );
    }

    public function listings_shortcode( $attrs ) {
        wp_enqueue_script( 'awpcp' );

        $default_attrs = array(
            'menu'         => true,
            'pagination'   => false,
            'limit'        => 10,
            'sortfeatured' => false,
        );

        $attrs           = shortcode_atts( $default_attrs, $attrs );
        $show_menu       = awpcp_parse_bool( $attrs['menu'] );
        $show_pagination = awpcp_parse_bool( $attrs['pagination'] );
        $limit           = absint( $attrs['limit'] );
        $featured_on_top = awpcp_parse_bool( $attrs['sortfeatured'] );

        if ( ! function_exists('awpcp_featured_ads') ) {
            $featured_on_top = false;
        }

        $query = array(
            'context' => 'public-listings',
            'limit'   => $limit,
        );

        $options = array(
            'show_menu_items' => $show_menu,
            'show_pagination' => $show_pagination,
            'featured_on_top' => $featured_on_top,
        );

        return awpcp_display_listings( $query, 'latest-listings-shortcode', $options );
    }

    public function random_listings_shortcode( $attrs ) {
        wp_enqueue_script( 'awpcp' );

        $attrs = shortcode_atts(
            array(
                'category' => null,
                'menu'     => true,
                'limit'    => 10,
            ),
            $attrs
        );

        $categories = array_filter( array_map( 'absint', explode( ',', $attrs['category'] ) ) );
        $show_menu  = awpcp_parse_bool( $attrs['menu'] );
        $limit      = absint( $attrs['limit'] );

        $query = array(
            'context'     => 'public-listings',
            'category_id' => $categories,
            'orderby'     => 'random',
            'limit'       => $limit,
        );

        $options = array(
            'show_menu_items' => $show_menu,
        );

        return awpcp_display_listings( $query, 'random-listings-shortcode', $options );
    }

    public function category_shortcode( $attrs ) {
        wp_enqueue_script( 'awpcp' );

        $cache_key = crc32( maybe_serialize( $attrs ) );

        if ( ! isset( $this->output[ $cache_key ] ) ) {
            $this->output[ $cache_key ] = awpcp_category_shortcode()->render( $attrs );
        }

        return $this->output[ $cache_key ];
    }

    /* Ajax handlers */

    /**
     * @since unknown
     */
    public function ajax_flag_ad() {
        $response = 0;

        if ( check_ajax_referer( 'flag_ad', 'nonce' ) ) {
            try {
                $ad       = awpcp_listings_collection()->get(
                    awpcp_get_var(
                        array( 'param' => 'ad', 'default' => 0, 'sanitize' => 'intval' )
                    )
                );
                $response = awpcp_listings_api()->flag_listing( $ad );
            } catch ( AWPCP_Exception $e ) {
                $response = 0;
            }
        }

        echo $response; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        die();
    }
}

function awpcpui_homescreen() {
    global $classicontent;

    $awpcppagename = sanitize_title( get_currentpagename() );

    if ( ! isset( $classicontent ) || empty( $classicontent ) ) {
        $classicontent = awpcpui_process( $awpcppagename );
    }
    return $classicontent;
}

function awpcpui_process( $awpcppagename ) {
    global $hasregionsmodule;

    $output = '';

    $awpcppage = get_currentpagename();
    if ( ! isset( $awpcppagename ) || empty( $awpcppagename ) ) {
        $awpcppagename = sanitize_title( $awpcppage, $post_ID = '' );
    }

    $layout = intval( get_query_var( 'layout' ) );

    $isadmin = awpcp_current_user_is_admin();

    awpcp_enqueue_main_script();

    $isclassifiedpage = checkifclassifiedpage();

    if ( ! $isclassifiedpage && $isadmin ) {
        $output .= __( 'Hi admin, you need to select the page for your classifieds.', 'another-wordpress-classifieds-plugin' );
        $settings_link = admin_url( 'admin.php?page=awpcp-admin-settings&g=pages-settings' );
        $output .= ' <a href="' . esc_url( $settings_link ) . '">' .
            __( 'Choose pages now', 'another-wordpress-classifieds-plugin' ) .
            '</a>';

    } elseif ( ! $isclassifiedpage ) {
        $output .= __( 'You currently have no classifieds', 'another-wordpress-classifieds-plugin' );

    } elseif ( $layout === 2 ) {
        $output .= awpcp_display_the_classifieds_page_body( $awpcppagename );

    } else {
        $output .= awpcp_load_classifieds( $awpcppagename );
    }

    return $output;
}

function awpcp_load_classifieds( $awpcppagename ) {
    if ( get_awpcp_option( 'main_page_display' ) ) {
        $query = array(
            'context' => 'public-listings',
            'limit'   => awpcp_get_var(
                array(
                    'param'    => 'results',
                    'default'  => get_awpcp_option( 'adresultsperpage', 10 ),
                    'sanitize' => 'absint',
                )
            ),
            'offset'  => awpcp_get_var(
                array(
                    'param'    => 'offset',
                    'default'  => 0,
                    'sanitize' => 'absint',
                )
            ),
            'orderby' => get_awpcp_option( 'groupbrowseadsby' ),
        );

        $output = awpcp_display_listings_in_page( $query, 'main-page' );
    } else {
        $output = awpcp_display_the_classifieds_page_body( $awpcppagename );
    }

    return $output;
}

function awpcp_display_the_classifieds_page_body( $awpcppagename ) {
    global $hasregionsmodule;

    $output = '';

    if ( ! isset( $awpcppagename ) || empty( $awpcppagename ) ) {
        $awpcppage     = get_currentpagename();
        $awpcppagename = sanitize_title( $awpcppage, $post_ID = '' );
    }

    $output   .= '<div id="classiwrapper">';
    $uiwelcome = stripslashes_deep( get_awpcp_option( 'uiwelcome' ) );
    $output   .= "<div class=\"uiwelcome\">$uiwelcome</div>";

    // Show the menu items.
    $output .= awpcp_render_classifieds_bar();

    if ( function_exists( 'awpcp_region_control_selector' ) && get_awpcp_option( 'show-region-selector', true ) ) {
        $output .= awpcp_region_control_selector();
    }

    $output .= '<div class="classifiedcats">';

    // Display the categories.
    $params  = array(
        'show_in_columns'          => get_awpcp_option( 'view-categories-columns' ),
        'show_empty_categories'    => ! get_awpcp_option( 'hide-empty-categories' ),
        'show_children_categories' => true,
        'show_listings_count'      => get_awpcp_option( 'showadcount' ),
        'show_sidebar'             => true,
    );
    $output .= awpcp_categories_renderer_factory()->create_list_renderer()->render( $params );

    $output .= '</div>';

    $output .= '</div>';

    return $output;
}

function awpcp_menu_items() {
    $params = array(
        'show-create-listing-button'  => get_awpcp_option( 'show-menu-item-place-ad' ),
        'show-edit-listing-button'    => get_awpcp_option( 'show-menu-item-edit-ad' ),
        'show-browse-listings-button' => get_awpcp_option( 'show-menu-item-browse-ads' ),
        'show-search-listings-button' => get_awpcp_option( 'show-menu-item-search-ads' ),
    );

    $menu_items = array_filter( awpcp_get_menu_items( $params ), 'is_array' );

    $navigation_attributes = array(
        'class' => array( 'awpcp-navigation', 'awpcp-menu-items-container', 'clearfix' ),
    );

    if ( get_awpcp_option( 'show-mobile-menu-expanded' ) ) {
        $navigation_attributes['class'][] = 'toggle-on';
    }

    $template = AWPCP_DIR . '/frontend/templates/main-menu.tpl.php';
    $params   = compact( 'menu_items', 'navigation_attributes' );

    return awpcp_render_template( $template, $params );
}

function awpcp_get_menu_items( $params ) {
    $items = array();

    $user_is_allowed_to_place_ads = ! get_awpcp_option( 'onlyadmincanplaceads' ) || awpcp_current_user_is_admin();
    $show_place_ad_item           = $user_is_allowed_to_place_ads && $params['show-create-listing-button'];
    $show_browse_ads_item         = $params['show-browse-listings-button'];
    $show_search_ads_item         = $params['show-search-listings-button'];

    if ( $show_place_ad_item ) {
        $place_ad_url          = awpcp_get_page_url( 'place-ad-page-name' );
        $place_ad_page_name    = awpcp_get_page_name( 'place-ad-page-name' );
        $items['post-listing'] = array(
            'url'   => $place_ad_url,
            'title' => esc_html( $place_ad_page_name ),
        );
    }

    if ( awpcp_should_show_edit_listing_menu( $params ) ) {
        $edit_listing_menu_item = awpcp_get_edit_listing_menu_item();
    } else {
        $edit_listing_menu_item = null;
    }

    if ( $edit_listing_menu_item ) {
        $items['edit-listing'] = $edit_listing_menu_item;
    }

    if ( $show_browse_ads_item ) {
        if ( is_awpcp_browse_listings_page() || is_awpcp_browse_categories_page() ) {
            if ( get_awpcp_option( 'main_page_display' ) ) {
                $browse_cats_url = awpcp_get_view_categories_url();
            } else {
                $browse_cats_url = awpcp_get_main_page_url();
            }

            $view_categories_page_name = get_awpcp_option( 'view-categories-page-name' );

            if ( $view_categories_page_name ) {
                $items['browse-listings'] = array(
                    'url'   => $browse_cats_url,
                    'title' => esc_html( $view_categories_page_name ),
                );
            }
        } else {
            $browse_ads_page_name     = awpcp_get_page_name( 'browse-ads-page-name' );
            $browse_ads_url           = awpcp_get_page_url( 'browse-ads-page-name' );
            $items['browse-listings'] = array(
                'url'   => $browse_ads_url,
                'title' => esc_html( $browse_ads_page_name ),
            );
        }
    }

    if ( $show_search_ads_item ) {
        $search_ads_page_name     = awpcp_get_page_name( 'search-ads-page-name' );
        $search_ads_url           = awpcp_get_page_url( 'search-ads-page-name' );
        $items['search-listings'] = array(
            'url'   => $search_ads_url,
            'title' => esc_html( $search_ads_page_name ),
        );
    }

    /**
     * @param array $items {
     *     An associative array of menu items.
     *
     *     @type array $menu_id {
     *         The definition of a single menu item.
     *
     *         @type string $title The title of the menu item. Must be passed through esc_html().
     *         @type string $url   The raw URL (do not use esc_url() or esc_attr()) associated with this menu item.
     *     }
     * }
     */
    $items = apply_filters( 'awpcp_menu_items', $items );

    return $items;
}

function awpcp_should_show_edit_listing_menu( $params ) {
    if ( get_awpcp_option( 'onlyadmincanplaceads' ) && ! awpcp_current_user_is_admin() ) {
        return false;
    }

    if ( ! $params['show-edit-listing-button'] ) {
        return false;
    }

    if ( awpcp_query()->is_edit_listing_page() && awpcp_request()->get_current_listing_id() ) {
        return false;
    }

    return true;
}

function awpcp_get_edit_listing_menu_item() {
    $listings      = awpcp_listings_collection();
    $authorization = awpcp_listing_authorization();
    $request       = awpcp_request();
    $settings      = awpcp()->settings;

    try {
        $listing = $listings->get( $request->get_current_listing_id() );
    } catch ( AWPCP_Exception $e ) {
        $listing = null;
    }

    if ( is_object( $listing ) && $authorization->is_current_user_allowed_to_edit_listing( $listing ) ) {
        $edit_ad_url = awpcp_get_edit_listing_direct_url( $listing );
    } elseif ( ! $settings->get_option( 'requireuserregistration' ) ) {
        $edit_ad_url = awpcp_get_edit_listing_generic_url();
    } else {
        $edit_ad_url = null;
    }

    if ( is_null( $edit_ad_url ) ) {
        return null;
    } else {
        $edit_ad_page_name = awpcp_get_page_name( 'edit-ad-page-name' );

        return array(
            'url'   => $edit_ad_url,
            'title' => esc_html( $edit_ad_page_name ),
        );
    }
}
