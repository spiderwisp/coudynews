<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the IDs of the pages used by the AWPCP plugin.
 */
function exclude_awpcp_child_pages($excluded=array()) {
    global $wpdb, $table_prefix;

    $awpcp_page_id = awpcp_get_page_id_by_ref('main-page-name');

    if (empty($awpcp_page_id)) {
        return array();
    }

    $child_pages = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM %i WHERE post_parent=%d AND post_content LIKE %s",
            $table_prefix . 'posts',
            $awpcp_page_id,
            '%AWPCP%'
        )
    );

    if ( is_array( $child_pages ) ) {
        return array_merge( $child_pages, $excluded );
    }
    return $excluded;
}

// PROGRAM FUNCTIONS

/**
 * Return an array of refnames for pages associated with one or more
 * rewrite rules.
 *
 * @since 2.1.3
 * @return array Array of page refnames.
 */
function awpcp_pages_with_rewrite_rules() {
    return array(
        'main-page-name',
        'show-ads-page-name',
        'reply-to-ad-page-name',
        'edit-ad-page-name',
        'browse-ads-page-name',
    );
}

/**
 * Register AWPCP query vars
 */
function awpcp_query_vars($query_vars) {
    $vars = array(
        // API
        'awpcpx',
        'awpcp-module',
        'awpcp-action',
        'module',
        'action',

        // Payments API
        'awpcp-txn',

        // Listings API
        'awpcp-ad',
        'awpcp-hash',

        // misc
        'awpcp-custom',
        "cid",
        "id",
        "layout",
        "regionid",
    );

    return array_merge($query_vars, $vars);
}

/**
 * @since 3.2.1
 */
function awpcp_rel_canonical_url() {
    global $wp_the_query;

    if ( ! is_singular() ) {
        return false;
    }

    $page_id = $wp_the_query->get_queried_object_id();
    if ( ! $page_id ) {
        return false;
    }

    if ( $page_id != awpcp_get_page_id_by_ref( 'show-ads-page-name' ) ) {
        return false;
    }

    $ad_id = intval( awpcp_get_var( array( 'param' => 'id' ) ) );
    $ad_id = empty( $ad_id ) ? intval( get_query_var( 'id' ) ) : $ad_id;

    if ( empty( $ad_id ) ) {
        $url = get_permalink( $page_id );
    } else {
        $url = url_showad( $ad_id );
    }

    return $url;
}

/**
 * Set canonical URL to the Ad URL when in viewing on of AWPCP Ads.
 *
 * @since unknown
 * @since 3.2.1 logic moved to awpcp_rel_canonical_url()
 */
function awpcp_rel_canonical() {
    $url = awpcp_rel_canonical_url();

    if ( $url ) {
        echo "<link rel='canonical' href='" . esc_url( $url ) . "' />\n";
    } else {
        rel_canonical();
    }
}

/**
 * Overwrittes WP canonicalisation to ensure our rewrite rules
 * work, even when the main AWPCP page is also the front page or
 * when the requested page slug is 'awpcp'.
 *
 * Required for the View Categories and Classifieds RSS rules to work
 * when AWPCP main page is also the front page.
 *
 * http://wordpress.stackexchange.com/questions/51530/rewrite-rules-problem-when-rule-includes-homepage-slug
 */
function awpcp_redirect_canonical($redirect_url, $requested_url) {
    global $wp_query;

    $awpcp_rewrite = false;
    $ids = awpcp_get_page_ids_by_ref(awpcp_pages_with_rewrite_rules());

    // do not redirect requests to AWPCP pages with rewrite rules
    if ( is_page() && in_array( awpcp_get_var( array( 'param' => 'page_id', 'default' => 0 ) ), $ids ) ) {
        $awpcp_rewrite = true;

        // do not redirect requests to the front page, if any of the AWPCP pages
        // with rewrite rules is the front page.
    } elseif (is_page() && !is_feed() && isset($wp_query->queried_object) &&
        'page' == get_option( 'show_on_front' ) && in_array( $wp_query->queried_object->ID, $ids ) &&
        $wp_query->queried_object->ID == get_option( 'page_on_front' )
    ) {
        $awpcp_rewrite = true;
    }

    if ( $awpcp_rewrite ) {
        // Fix for #943.
        $requested_host = wp_parse_url( $requested_url, PHP_URL_HOST );
        $redirect_host  = wp_parse_url( $redirect_url, PHP_URL_HOST );

        if ( $requested_host != $redirect_host ) {
            if ( strtolower( $redirect_host ) == ( 'www.' . $requested_host ) ) {
                return str_replace( $requested_host, 'www.' . $requested_host, $requested_url );
            } elseif ( strtolower( $requested_host ) == ( 'www.' . $redirect_host ) ) {
                return str_replace( 'www.', '', $requested_url );
            }
        }

        return $requested_url;
    }

    return $redirect_url;
}

function awpcp_esc_attr($text) {
    // WP adds slashes to all request variables
    $text = stripslashes($text);
    // AWPCP adds more slashes
    $text = stripslashes($text);
    $text = esc_attr($text);
    return $text;
}

function awpcp_esc_textarea($text) {
    $text = stripslashes($text);
    $text = stripslashes($text);
    $text = esc_textarea($text);
    return $text;
}

/**
 * @since 3.3
 */
function awpcp_apply_function_deep( $function, $value ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $data ) {
            $value[ $key ] = awpcp_apply_function_deep( $function, $data );
        }
    } elseif ( is_object( $value ) ) {
        $vars = get_object_vars( $value );
        foreach ( $vars as $key => $data ) {
            $value->{$key} = awpcp_apply_function_deep( $function, $data );
        }
    } elseif ( is_string( $value ) ) {
        $value = call_user_func( $function, $value );
    }

    return $value;
}

/**
 * @since 3.3
 */
function awpcp_strip_all_tags_deep( $string ) {
    return awpcp_apply_function_deep( 'wp_strip_all_tags', $string );
}

/**
 * @since 3.0.2
 */
function awpcp_strptime_replacement( $date, $format ) {
    $masks = array(
        '%d' => '(?P<d>[0-9]{2})',
        '%m' => '(?P<m>[0-9]{2})',
        '%y' => '(?P<y>[0-9]{2})',
        '%Y' => '(?P<Y>[0-9]{4})',
        '%H' => '(?P<H>[0-9]{2})',
        '%M' => '(?P<M>[0-9]{2})',
        '%S' => '(?P<S>[0-9]{2})',
        // usw..
    );

    $regexp = "#" . strtr( preg_quote( $format, '#' ), $masks ) . "#";
    if ( ! preg_match( $regexp, $date, $out ) ) {
        return false;
    }

    $unparsed = preg_replace( $regexp, '', $date );

    if ( isset( $out['y'] ) && strlen( $out['y'] ) ) {
        $out['Y'] = ( $out['y'] > 69 ? 1900 : 2000 ) + absint( $out['y'] );
    }

    $ret = array(
        'tm_sec' => (int) awpcp_array_data( 'S', 0, $out),
        'tm_min' => (int) awpcp_array_data( 'M', 0, $out),
        'tm_hour' => (int) awpcp_array_data( 'H', 0, $out),
        'tm_mday' => (int) awpcp_array_data( 'd', 0, $out),
        'tm_mon' => awpcp_array_data( 'm', 0, $out) ? awpcp_array_data( 'm', 0, $out) - 1 : 0,
        'tm_year' => awpcp_array_data( 'Y', 0, $out) > 1900 ? awpcp_array_data( 'Y', 0, $out) - 1900 : 0,
        'unparsed' => $unparsed,
    );

    return $ret;
}

/**
 * @since 3.0
 */
function awpcp_date_formats() {
    static $translations;

    if ( ! is_array( $translations ) ) {
        $translations = array(
            'd' => 'dd',
            'j' => 'd',
            's' => null,
            'l' => 'DD',
            'D' => 'D',
            'm' => 'mm',
            'n' => 'm',
            'F' => 'MM',
            'M' => 'M',
            'Y' => 'yy',
            'y' => 'y',
            'c' => 'ISO_8601',
            'r' => 'RFC_822',
        );
    }

    return $translations;
}

/**
 * @since 3.0
 */
function awpcp_time_formats() {
    static $translations;

    if ( ! is_array( $translations ) ) {
        $translations = array(
            'a' => 'p',
            'A' => 'P',
            'g' => 'h',
            'h' => 'hh',
            'G' => 'H',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'T' => null,
            'c' => null,
            'r' => null,
        );
    }

    return $translations;
}

/**
 * Translates PHP date format strings to jQuery Datepicker format.
 * @since 3.0
 */
function awpcp_datepicker_format($format) {
    return _awpcp_replace_format($format, awpcp_date_formats());
}

/**
 * Translates PHP time format strings to jQuery TimePicker format.
 * @since 3.0
 */
function awpcp_timepicker_format($format) {
    return _awpcp_replace_format($format, awpcp_time_formats());
}

/**
 * @since 3.0
 */
function _awpcp_replace_format($format, $translations) {
    $pattern = join( '|', array_map( 'preg_quote', array_keys( $translations ) ) );

    preg_match_all( "/$pattern/s", $format, $matches );

    $processed = array();
    foreach ( $matches[0] as $match ) {
        if ( ! isset( $processed[ $match ] ) ) {
            $format = str_replace( $match, $translations[ $match ], $format );
            $processed[ $match ] = true;
        }
    }

    return $format;
}

/**
 * @since 3.0
 */
function awpcp_get_date_format() {
    return get_awpcp_option('date-format');
}

/**
 * @since 3.0
 */
function awpcp_get_time_format() {
    return get_awpcp_option('time-format');
}

/**
 * @since 3.0
 */
function awpcp_get_datetime_format() {
    $format = get_awpcp_option('date-time-format');
    $format = str_replace('<date>', '******', $format);
    $format = str_replace('<time>', '*^*^*^', $format);
    $format = preg_replace('/(\w)/', '\\\\$1', $format);
    $format = str_replace('******', awpcp_get_date_format(), $format);
    $format = str_replace('*^*^*^', awpcp_get_time_format(), $format);
    return $format;
}

/**
 * @since 4.0.0
 */
function awpcp_get_datetime_formats() {
    return [
        'american' => array(
            'date'   => 'm/d/Y',
            'time'   => 'h:i:s',
            'format' => '<date> <time>',
        ),
        'european' => array(
            'date'   => 'd/m/Y',
            'time'   => 'H:i:s',
            'format' => '<date> <time>',
        ),
        'custom' => array(
            'date'   => 'l F j, Y',
            'time'   => 'g:i a T',
            'format' => '<date> at <time>',
        ),
    ];
}

/**
 * Returns the given date as MySQL date string, Unix timestamp or
 * using a custom format.
 *
 * If $date is null or an empty string, then the function uses the timestamp
 * of current time in the blog's configured timezone as the timestamp to
 * generate formated dates.
 *
 * @since 3.0.2
 * @param $format 'mysql', 'timestamp', 'awpcp', 'awpcp-date', 'awpcp-time'
 *                or first arguemnt for date() function.
 */
function awpcp_datetime( $format='mysql', $date=null ) {
    if ( is_null( $date ) || strlen( $date ) === 0 ) {
        $timestamp = current_time( 'timestamp' );
    } elseif ( is_string( $date ) ) {
        $timestamp = strtotime( $date );
    } else {
        $timestamp = $date;
    }

    switch ( $format ) {
        case 'mysql':
            return gmdate( 'Y-m-d H:i:s', $timestamp );
        case 'timestamp':
            return $timestamp;
        case 'time-elapsed':
            return sprintf(
                // translators: %s is the human-readable time difference (e.g., "2 hours", "3 days")
                __( '%s ago', 'another-wordpress-classifieds-plugin' ),
                human_time_diff( strtotime( $date ) )
            );
        case 'awpcp':
            return date_i18n( awpcp_get_datetime_format(), $timestamp );
        case 'awpcp-date':
            return date_i18n( awpcp_get_date_format(), $timestamp );
        case 'awpcp-time':
            return date_i18n( awpcp_get_time_format(), $timestamp );
        default:
            return date_i18n( $format, $timestamp );
    }
}

function awpcp_set_datetime_date( $datetime, $date ) {
    $base_timestamp = strtotime( $datetime ? $datetime : '' );
    $base_year_month_day_timestamp = strtotime( gmdate( 'Y-m-d', strtotime( $datetime ? $datetime : '' ) ) );
    $time_of_the_day_in_seconds = $base_timestamp - $base_year_month_day_timestamp;

    $target_year_month_day_timestamp = strtotime( gmdate( 'Y-m-d', strtotime( $date ) ) );

    $new_datetime_timestamp = $target_year_month_day_timestamp + $time_of_the_day_in_seconds;

    return awpcp_datetime( 'mysql', $new_datetime_timestamp );
}

function awpcp_extend_date_to_end_of_the_day( $datetime ) {
    $next_day = strtotime( '+ 1 days', $datetime );
    $zero_hours_next_day = strtotime( gmdate( 'Y-m-d', $next_day ) );
    $end_of_the_day = $zero_hours_next_day - 1;

    return $end_of_the_day;
}

function awpcp_is_mysql_date( $date ) {
    $regexp = '/^\d{4}-\d{1,2}-\d{1,2}(\s\d{1,2}:\d{1,2}(:\d{1,2})?)?$/';
    return preg_match( $regexp, $date ) === 1;
}

function awpcp_is_array_of_arrays( $array ) {
    if ( ! is_array( $array ) ) {
        return false;
    }

    $array_keys = array_keys( $array );

    return is_array( $array[ $array_keys[ 0 ] ] );
}

/**
 * Returns a WP capability required to be considered an AWPCP admin.
 *
 * http://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table
 *
 * @since 2.0.7
 */
function awpcp_admin_capability() {
    $capabilities = awpcp_roles_and_capabilities()->get_administrator_capabilities();

    return array_shift( $capabilities );
}

/**
 * We are using read as an alias for edit_classifieds_listings. If a user can `read`,
 * he or she can `edit_classifieds_listings`.
 *
 * @since 4.0.0
 */
function awpcp_user_capability() {
    return 'read';
}

/**
 * @since 3.3.2
 */
function awpcp_admin_roles_names() {
    return awpcp_roles_and_capabilities()->get_administrator_roles_names();
}

/**
 * Check if current user is an Administrator according to
 * AWPCP settings.
 */
function awpcp_current_user_is_admin() {
    return awpcp_roles_and_capabilities()->current_user_is_administrator();
}

/**
 * @since 3.4
 */
function awpcp_current_user_is_moderator() {
    return awpcp_roles_and_capabilities()->current_user_is_moderator();
}

function awpcp_user_is_admin($id) {
    return awpcp_roles_and_capabilities()->user_is_administrator( $id );
}

/**
 * Check the nonce and user role.
 *
 * @since 4.3.2
 */
function awpcp_check_admin_ajax() {
    check_ajax_referer( 'awpcp_ajax', 'nonce' );
    if ( ! awpcp_current_user_is_admin() ) {
        wp_die( esc_html__( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
    }
}

function awpcp_get_grid_item_css_class($classes, $pos, $columns, $rows) {
    if ($pos < $columns)
        $classes[] = 'first-row';
    if ($pos >= (($rows - 1) * $columns))
        $classes[] = 'last-row';
    if ($pos == 0 || $pos % $columns == 0)
        $classes[] = 'first-column';
    if (($pos + 1) % $columns == 0)
        $classes[] = 'last-column';
    return $classes;
}

/**
 * @since 3.0
 * @param array  $config
 * @param string $url
 * @return string HTML
 */
function awpcp_pagination($config, $url) {
    if ( ! is_admin() && function_exists( 'wp_pagenavi' ) && isset( $config['query'] ) ) {
        $args = [
            'query' => $config['query'],
            'echo'  => false,
        ];

        return wp_pagenavi( $args );
    }

    $blacklist = array(
        'offset',
        'results',
        'PHPSESSID',
        'aeaction',
        'cat_ID',
        'action',
        'aeaction',
        'category_name',
        'category_parent_id',
        'createeditadcategory',
        'deletemultiplecategories',
        'movedeleteads',
        'moveadstocategory',
        'category_to_delete',
        'tpname',
        'category_icon',
        'sortby',
        'adid',
        'picid',
        'adkey',
        'editemail',
        'awpcp_ads_to_action',
        'post_type',
        'TCM_PostShown',
        'TCM_SnippetsWrittenIds',
        'TCM_SnippetsWrittenMd5',
        'TCM_Cache_Query_2_',
    );

    // phpcs:ignore WordPress.Security.NonceVerification
    $params = array_merge($_GET, $_POST);
    foreach ($blacklist as $param) {
        unset($params[$param]);
    }

    extract(shortcode_atts(
        [
            'offset'         => 0,
            'results'        => 10,
            'total'          => 10,
            'show_dropdown'  => true,
            'dropdown_label' => __( 'Ads per page:', 'another-wordpress-classifieds-plugin' ),
            'dropdown_name'  => 'results',
        ],
        $config
    ));

    $items = array();
    $radius = 2;

    if ( $results > 0 ) {
        $pages = ceil($total / $results);
        $page = floor($offset / $results) + 1;
    } else {
        $pages = 1;
        $page = 1;
    }

    $summary = __( 'Page {current_page_number} of {number_of_pages}', 'another-wordpress-classifieds-plugin' );
    $summary = str_replace( '{current_page_number}', $page, $summary );
    $summary = str_replace( '{number_of_pages}', $pages, $summary );

    $items[] = '<span class="awpcp-pagination-summary">' . esc_html( $summary ) . '</span>';

    if ( ( $page - $radius ) > 2 ) {
        $items[] = awpcp_render_pagination_item( '&laquo;&laquo;', 1, $results, $params, $url );
    }

    if ( $page > 1 ) {
        $items[] = awpcp_render_pagination_item( '&laquo;', $page - 1, $results, $params, $url );
    }

    for ($i=1; $i <= $pages; $i++) {
        $less    = $i < ( $page - $radius );
        $greater = $i > ( $page + $radius );
        if ( $page == $i ) {
            $items[] = sprintf( '<span class="awpcp-pagination-links--link">%d</span>', $i );
        } elseif ( ! $less && ! $greater ) {
            $items[] = awpcp_render_pagination_item( $i, $i, $results, $params, $url );
        }
    }

    if ( $page < ( $pages - 1 ) ) {
        $items[] = awpcp_render_pagination_item( '&raquo;', $page + 1, $results, $params, $url );
    }

    if ( ( $page + $radius ) < ( $pages - 1 ) ) {
        $items[] = awpcp_render_pagination_item( '&raquo;&raquo;', $pages, $results, $params, $url );
    }

    $unique_id  = str_replace( [ ' ', '.' ], '-', microtime() );
    $pagination = implode( '', $items );
    $options = awpcp_pagination_options( $results );

    ob_start();
        include(AWPCP_DIR . '/frontend/templates/listings-pagination.tpl.php');
        $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

function awpcp_render_pagination_item( $label, $page, $results_per_page, $params, $url ) {
    $params = array_merge(
        $params,
        array(
            'offset' => ( $page - 1 ) * $results_per_page,
            'results' => $results_per_page,
        )
    );

    $url = add_query_arg( urlencode_deep( $params ), $url );

    return sprintf( '<a class="awpcp-pagination-links--link" href="%s">%s</a>', esc_url( $url ), $label );
}

/**
 * @since 3.2.1
 */
function awpcp_pagination_options( $selected=10 ) {
    $options = get_awpcp_option( 'pagination-options' );
    return awpcp_build_pagination_options( $options, $selected );
}

/**
 * @since 3.3.2
 */
function awpcp_build_pagination_options( $options, $selected ) {
    array_unshift( $options, 0 );

    for ( $i = count( $options ) - 1; $i >= 0; $i-- ) {
        if ( $options[ $i ] < $selected ) {
            array_splice( $options, $i + 1, 0, $selected );
            break;
        }
    }

    $options_without_zero = array_filter( $options, 'intval' );

    return array_combine( $options_without_zero , $options_without_zero );
}

/**
 * @since 3.3.2
 */
function awpcp_default_pagination_options( $selected = 10 ) {
    $default_options = awpcp()->settings->get_option_default_value( 'pagination-options' );
    return awpcp_build_pagination_options( $default_options, $selected );
}

function awpcp_get_categories() {
    return awpcp_categories_collection()->find_categories();
}

function awpcp_get_categories_ids() {
    static $categories;

    if (!is_array($categories)) {
        $categories = awpcp_get_properties( awpcp_get_categories(), 'term_id' );
    }

    return $categories;
}

/**
 * @since 3.0
 */
function awpcp_get_comma_separated_categories_list($categories=array(), $threshold=5) {
    $names = awpcp_get_properties( $categories, 'name' );
    return awpcp_get_comma_separated_list( $names, $threshold, __( 'None', 'another-wordpress-classifieds-plugin' ) );
}

/**
 * @since 3.0
 */
function awpcp_get_comma_separated_list($items=array(), $threshold=5, $none='') {
    $items = array_filter( $items, 'strlen' );
    $count = count( $items );

    if ( $count > $threshold ) {
        // translators: %1$s is a comma-separated list of items, %2$d is the number of additional items
        $message = _x( '%1$s and %2$d more.', 'comma separated list of things', 'another-wordpress-classifieds-plugin' );
        $items = array_splice( $items, 0, $threshold - 1 );
        return sprintf( $message, join( ', ', $items ), $count - $threshold + 1 );
    } elseif ( $count > 0 ) {
        return sprintf( '%s.', join( ', ', $items ) );
    } else {
        return $none;
    }
}

/**
 * @since 3.3.1
 * @since 4.0.0     Added support for returning a different set of fields for
 *                  the Search form using the $context parameter.
 */
function awpcp_get_enabled_region_fields( $context = null ) {
    if ( 'search' === $context ) {
        return [
            'country' => get_awpcp_option( 'display_country_field_on_search_form' ),
            'state'   => get_awpcp_option( 'display_state_field_on_search_form' ),
            'city'    => get_awpcp_option( 'display_city_field_on_search_form' ),
            'county'  => get_awpcp_option( 'display_county_field_on_search_form' ),
        ];
    }

    return array(
        'country' => get_awpcp_option( 'displaycountryfield' ),
        'state' => get_awpcp_option( 'displaystatefield' ),
        'city' => get_awpcp_option( 'displaycityfield' ),
        'county' => get_awpcp_option( 'displaycountyvillagefield' ),
    );
}

/**
 * @since 3.0.2
 */
function awpcp_default_region_fields( $context='details', $enabled_fields = null ) {
    $enabled_fields = is_null( $enabled_fields ) ? awpcp_get_enabled_region_fields() : $enabled_fields;
    $show_city_field_before_county_field = get_awpcp_option( 'show-city-field-before-county-field' );

    $always_shown = in_array( $context, array( 'details', 'search', 'user-profile' ), true );
    $can_be_required = $context !== 'search';
    $_fields = array();

    if ( $enabled_fields['country'] ) {
        $required = $can_be_required && ( (bool) get_awpcp_option( 'displaycountryfieldreqop' ) );
        $_fields['country'] = array(
            'type' => 'country',
            'label' => __( 'Country', 'another-wordpress-classifieds-plugin') . ( $required ? '*' : '' ),
            'help' => __( 'separate countries by commas', 'another-wordpress-classifieds-plugin'),
            'required' => $required,
            'alwaysShown' => $always_shown,
        );
    }
    if ( $enabled_fields['state'] ) {
        $required = $can_be_required && ( (bool) get_awpcp_option( 'displaystatefieldreqop' ) );
        $_fields['state'] = array(
            'type' => 'state',
            'label' => __( 'State/Province', 'another-wordpress-classifieds-plugin') . ( $required ? '*' : '' ),
            'help' => __( 'separate states by commas', 'another-wordpress-classifieds-plugin'),
            'required' => $required,
            'alwaysShown' => $always_shown,
        );
    }
    if ( $enabled_fields['city'] ) {
        $required = $can_be_required && ( (bool) get_awpcp_option( 'displaycityfieldreqop' ) );
        $_fields['city'] = array(
            'type' => 'city',
            'label' => __( 'City', 'another-wordpress-classifieds-plugin') . ( $required ? '*' : '' ),
            'help' => __( 'separate cities by commas', 'another-wordpress-classifieds-plugin'),
            'required' => $required,
            'alwaysShown' => $always_shown,
        );
    }
    if ( $enabled_fields['county'] ) {
        $required = $can_be_required && ( (bool) get_awpcp_option( 'displaycountyvillagefieldreqop' ) );
        $_fields['county'] = array(
            'type' => 'county',
            'label' => __( 'County/Village/Other', 'another-wordpress-classifieds-plugin') . ( $required ? '*' : '' ),
            'help' => __( 'separate counties by commas', 'another-wordpress-classifieds-plugin'),
            'required' => $required,
            'alwaysShown' => $always_shown,
        );
    }

    if ( ! $show_city_field_before_county_field ) {
        $fields = array();
        foreach( array( 'country', 'state', 'county', 'city' ) as $field ) {
            if ( isset( $_fields[ $field ] ) ) {
                $fields[ $field ] = $_fields[ $field ];
            }
        }
    } else {
        $fields = $_fields;
    }

    return $fields;
}

/**
 * @param string      $value
 * @param bool        $use_names
 * @param bool|string $show      If has a value, the function will echo the list of countries.
 *
 * @return string
 */
function awpcp_country_list_options( $value = '', $use_names = true, $show = false ) {
    $countries = array(
        'US' => 'United States',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan Republic',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BB' => 'Barbados',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'C2' => 'China',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'CD' => 'Democratic Republic of the Congo',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'SV' => 'El Salvador',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'GA' => 'Gabon Republic',
        'GM' => 'Gambia',
        'DE' => 'Germany',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea Bissau',
        'GY' => 'Guyana',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LS' => 'Lesotho',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MN' => 'Mongolia',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn Islands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'CG' => 'Republic of the Congo',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'KN' => 'Saint Kitts and Nevis Anguilla',
        'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'São Tomé and Príncipe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'KR' => 'South Korea',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SH' => 'St. Helena',
        'LC' => 'St. Lucia',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen Islands',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'UY' => 'Uruguay',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City State',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Islands (British)',
        'WF' => 'Wallis and Futuna Islands',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
    );

    $options = '<option value="">' .
        esc_html__( '-- Choose a Country --', 'another-wordpress-classifieds-plugin') .
        '</option>';

    foreach ( apply_filters( 'awpcp_country_list_options_countries', $countries ) as $code => $name) {
        $option_value = $use_names ? $name : $code;

        $options .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $option_value ),
            selected( $value, $option_value, false ),
            esc_html( $name )
        );
    }

    if ( $show ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $options;
        return '';
    }

    return $options;
}

/**
 * AWPCP misc functions
 */

function awpcp_array_insert($array, $index, $key, $item, $where='before') {
    $all = array_merge($array, array($key => $item));
    $keys = array_keys($array);
    $p = array_search( $index, $keys, true );

    if ( $p !== false ) {
        if ($where === 'before')
            array_splice($keys, max($p, 0), 0, $key);
        elseif ($where === 'after')
            array_splice($keys, min($p+1, count($keys)), 0, $key);

        $array = array();
        // Create items array in proper order. The code below was the only
        // way I found to insert an item in an arbitrary position of an
        // array preserving keys. array_splice dropped the key of the inserted
        // value.
        foreach($keys as $key) {
            $array[$key] = $all[$key];
        }
    }

    return $array;
}

function awpcp_array_insert_before($array, $index, $key, $item) {
    return awpcp_array_insert($array, $index, $key, $item, 'before');
}

function awpcp_array_insert_after($array, $index, $key, $item) {
    return awpcp_array_insert($array, $index, $key, $item, 'after');
}

/**
 * @since 3.7.6
 */
function awpcp_array_insert_first( $array, $item_key, $item ) {
    $all_keys = array_keys( $array );
    $first_key = array_shift( $all_keys );

    return awpcp_array_insert( $array, $first_key, $item_key, $item, 'before' );
}

/**
 * Inserts a menu item after one of the existing items.
 *
 * This function should be used by plugins when handling
 * the awpcp_menu_items filter.
 *
 * @param $items array  Existing items
 * @param $after string key of item we want to place the new item after
 * @param $key   string New item's key
 * @param $item  array  New item's description
 */
function awpcp_insert_menu_item($items, $after, $key, $item) {
    return awpcp_array_insert_after($items, $after, $key, $item);
}

/**
 * Insert a submenu item in a WordPress admin menu, after an
 * existing item.
 *
 * Menu item should have already been added using add_submenu_page
 * or a similar function.
 *
 * @param $slug  string Slug for the item to insert.
 * @param $after string Slug of the item to insert after.
 */
function awpcp_insert_submenu_item_after($menu, $slug, $after) {
    global $submenu;

    $items = isset($submenu[$menu]) ? $submenu[$menu] : array();
    $to    = -1;
    $from  = -1;

    foreach ($items as $k => $item) {
        // insert after Fees
        if (strcmp($item[2], $after) === 0)
            $to = $k;
        if (strcmp($item[2], $slug) === 0)
            $from = $k;
    }

    if ($to >= 0 && $from >= 0) {
        array_splice($items, $to + 1, 0, array($items[$from]));
        // current was added at the end of the array using add_submenu_page
        unset($items[$from + 1]);
        // use array_filter to restore array keys
        $submenu[$menu] = array_filter($items);
    }
}

/**
 * @since 2.1.4
 * @since 4.0.0     Gets the name of page directly from the post object.
 */
function awpcp_get_page_name( $page_ref ) {
    $page_id = awpcp_get_page_id_by_ref( $page_ref );

    if ( ! $page_id ) {
        return '';
    }

    $page = get_page( $page_id );

    if ( ! isset( $page->post_title ) ) {
        return '';
    }

    return $page->post_title;
}

/**
 * @since 3.0.2
 */
function awpcp_get_renew_ad_hash( $ad_id ) {
    return md5( sprintf( 'renew-ad-%d-%s', $ad_id, wp_salt() ) );
}

/**
 * @since 3.0.2
 */
function awpcp_verify_renew_ad_hash( $ad_id, $hash ) {
    return strcmp( awpcp_get_renew_ad_hash( $ad_id ), $hash ) === 0;
}

/**
 * @since 3.0.2
 */
function awpcp_get_email_verification_hash( $ad_id ) {
    return wp_hash( sprintf( 'verify-%d', $ad_id ) );
}

/**
 * @since 3.0.2
 */
function awpcp_verify_email_verification_hash( $ad_id, $hash ) {
    return strcmp( awpcp_get_email_verification_hash( $ad_id ) , $hash ) === 0;
}

/**
 * @since 3.0-beta
 */
function awpcp_get_blog_name($decode_html=true) {
    $blog_name = get_option('blogname');

    if (empty($blog_name)) {
        $blog_name = _x('Classifieds Website', 'default blog title', 'another-wordpress-classifieds-plugin');
    }

    if ( $decode_html ) {
        $blog_name = html_entity_decode( $blog_name, ENT_QUOTES, 'UTF-8' );
    }

    return $blog_name;
}

/**
 * @since 4.3
 *
 * @param array $args - Includes 'param' and 'sanitize'.
 *
 * @return array|string|int|float|mixed
 */
function awpcp_get_var( $args, $type = 'request' ) {
    $defaults = array(
        'sanitize' => 'sanitize_text_field',
        'default'  => '',
    );
    $args     = wp_parse_args( $args, $defaults );
    $value    = $args['default'];
    if ( $type === 'get' ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
        $value = isset( $_GET[ $args['param'] ] ) ? wp_unslash( $_GET[ $args['param'] ] ) : $value;
    } elseif ( $type === 'post' ) {
        // phpcs:ignore Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
        $value = isset( $_POST[ $args['param'] ] ) ? wp_unslash( $_POST[ $args['param'] ] ) : $value;
    } else {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
        $value = isset( $_REQUEST[ $args['param'] ] ) ? wp_unslash( $_REQUEST[ $args['param'] ] ) : $value;
    }

    awpcp_sanitize_value( $args['sanitize'], $value );

    return $value;
}

/**
 * @since 4.3
 *
 * @param string $sanitize
 * @param array|string $value
 */
function awpcp_sanitize_value( $sanitize, &$value ) {
    if ( empty( $sanitize ) ) {
        return;
    }
    if ( is_array( $value ) ) {
        $temp_values = $value;
        foreach ( $temp_values as $k => $v ) {
            awpcp_sanitize_value( $sanitize, $value[ $k ] );
        }
    } else {
        $value = call_user_func( $sanitize, $value );
    }
}

/**
 * @since 4.3.3
 *
 * @param string $value
 */
function awpcp_get_server_value( $value ) {
    return isset( $_SERVER[ $value ] ) ? wp_strip_all_tags( wp_unslash( $_SERVER[ $value ] ) ) : '';
}

function awpcp_array_data($name, $default, $from=array()) {
    $value = isset($from[$name]) ? $from[$name] : null;

    if (is_array($value) && count($value) > 0) {
        return $value;
    } elseif (!empty($value)) {
        return $value;
    }

    return $default;
}

/**
 * Taken and adapted from: http://stackoverflow.com/a/6795671/201354
 */
function awpcp_array_filter_recursive( $input, $callback = null ) {
    foreach ( $input as &$value ) {
        if ( is_array( $value ) ) {
            $value = awpcp_array_filter_recursive( $value, $callback );
        }
    }

    if ( is_callable( $callback ) ) {
        return array_filter( $input, $callback );
    } else {
        return array_filter( $input );
    }
}

/**
 * Alternative to array_merge_recursive that keeps numeric keys.
 *
 * @since 3.4
 */
function awpcp_array_merge_recursive( $a, $b ) {
    $merged = $a;

    foreach ( $b as $key => $value ) {
        if ( isset( $merged[ $key ] ) && is_array( $merged[$key] ) && is_array( $value ) ) {
            $merged[ $key ] = awpcp_array_merge_recursive( $merged[ $key ], $value );
        } else {
            $merged[ $key ] = $value;
        }
    }

    return $merged;
}

function awpcp_get_property($object, $property, $default='') {
    if ( is_object( $object ) && ( isset( $object->$property ) || array_key_exists( $property, get_object_vars( $object ) ) ) ) {
        return $object->$property;
    } elseif ( is_array( $object ) && isset( $object[ $property ] ) ) {
        return $object[ $property ];
    }
    return $default;
}

function awpcp_get_properties($objects, $property, $default='') {
    $results = array();
    foreach ($objects as $object) {
        $results[] = awpcp_get_property($object, $property, $default);
    }
    return $results;
}

function awpcp_get_object_property_from_alternatives( $object, $alternatives, $default = '' ) {
    foreach ( (array) $alternatives as $key ) {
        $value = awpcp_get_property( $object, $key );

        if ( strlen( $value ) == 0 ) {
            continue;
        }

        return $value;
    }

    return $default;
}

/**
 * Input:
 *  Array
 *  (
 *      [a] => dosearch
 *      [keywordphrase] =>
 *      [searchcategory] =>
 *      [searchname] =>
 *      [searchpricemin] => 0
 *      [searchpricemax] => 0
 *      [regions] => Array
 *          (
 *              [0] => Array
 *                  (
 *                      [country] => Colombia
 *                      [state] => Boyacá
 *                      [city] => Tunja
 *                  )
 *
 *              [1] => Array
 *                  (
 *                      [country] => Colombia
 *                      [state] => Antioquia
 *                      [city] => Medellín
 *                  )
 *
 *              [2] => Array
 *                  (
 *                      [country] => Colombia
 *                      [state] => Boyacá
 *                      [city] => Tunja
 *                  )
 *
 *          )
 *
 *      [awpcp-test-min] =>
 *      [awpcp-test-max] =>
 *      [awpcp-select_list] =>
 *  )
 *
 * Output:
 * Array
 * (
 *      [a] => dosearch
 *      [keywordphrase] =>
 *      [searchcategory] =>
 *      [searchname] =>
 *      [searchpricemin] => 0
 *      [searchpricemax] => 0
 *      [regions[0][country]] => Colombia
 *      [regions[0][state]] => Boyacá
 *      [regions[0][city]] => Tunja
 *      [regions[1][country]] => Colombia
 *      [regions[1][state]] => Antioquia
 *      [regions[1][city]] => Medellín
 *      [regions[2][country]] => Colombia
 *      [regions[2][state]] => Boyacá
 *      [regions[2][city]] => Tunja
 *      [awpcp-test-min] =>
 *      [awpcp-test-max] =>
 *      [awpcp-select_list] =>
 * )
 *
 * XXX: Could it be replaced by WP's _http_build_query somehow?
 *
 * @since 3.0.2
 */
function awpcp_flatten_array($array) {
    if ( is_array( $array ) ) {
        $flat = array();
        _awpcp_flatten_array( $array, array(), $flat );
        return $flat;
    } else {
        return $array;
    }
}

/**
 * @since 3.0.2
 */
function _awpcp_flatten_array($array, $path=array(), &$return=array()) {
    if ( is_array( $array ) ) {
        foreach ( $array as $key => $value) {
            _awpcp_flatten_array( $value, array_merge( $path, array( $key ) ), $return );
        }
    } elseif ( count( $path ) > 0 ){
        $first = $path[0];
        if ( count( $path ) > 1 ) {
            $return[ $first . '[' . join('][', array_slice( $path, 1 ) ) . ']'] = $array;
        } else {
            $return[ $first ] = $array;
        }
    }
}

/**
 * Parses 'yes', 'true', 'no', 'false', 0, 1 into bool values.
 *
 * @since  2.1.3
 * @param mixed $value value to parse
 * @return bool
 */
function awpcp_parse_bool($value) {
    $lower = strtolower($value);
    if ($lower === 'true' || $lower === 'yes')
        return true;
    if ($lower === 'false' || $lower === 'no')
        return false;
    return $value ? true : false;
}

function awpcp_get_currency_code() {
    $currency_code = get_awpcp_option( 'currency-code' );

    if ( function_exists( 'mb_strtoupper' ) ) {
        return mb_strtoupper( $currency_code );
    } else {
        return strtoupper( $currency_code );
    }
}

/**
 * @since 3.4
 */
function awpcp_currency_symbols() {
    return array(
        '$' => array( 'CAD', 'AUD', 'NZD', 'SGD', 'HKD', 'USD', 'MXN' ),
        '¥' => array( 'JPY' ),
        '€' => array( 'EUR' ),
        '£' => array( 'GBP' ),
        '₽;' => array( 'RUB' ),
        'R$' => array( 'BRL' ),
        'Kč' => array( 'CZK' ),
        'kr.' => array( 'DKK' ),
        '₪' => array( 'ILS' ),
        'RM' => array( 'MYR' ),
        'kr' => array( 'NOK', 'SEK' ),
        '₱' => array( 'PHP' ),
        'CHF' => array( 'CHF' ),
        'NT$' => array( 'TWD' ),
        '฿' => array( 'THB' ),
        '₺' => array( 'TRY' ),
    );
}

/**
 * @since 3.0
 */
function awpcp_format_money( $value, $show_free = false ) {
    if ( ! $value && $show_free ) {
        return __( 'Free', 'another-wordpress-classifieds-plugin' );
    }
    return awpcp_get_formmatted_amount(
        $value,
        awpcp_get_default_formatted_amount_template()
    );
}

/**
 * @since 4.0.0
 */
function awpcp_get_default_formatted_amount_template() {
    if ( get_awpcp_option( 'show-currency-symbol' ) != 'do-not-show-currency-symbol' ) {
        $show_currency_symbol = true;
    } else {
        $show_currency_symbol = false;
    }

    return awpcp_get_formatted_amount_template( $show_currency_symbol );
}

/**
 * @access private
 * @since 4.0.0
 */
function awpcp_get_formatted_amount_template( $show_currency_symbol ) {
    $symbol_position = get_awpcp_option( 'show-currency-symbol' );
    $currency_symbol = $show_currency_symbol ? awpcp_get_currency_symbol() : '';

    if ( get_awpcp_option( 'include-space-between-currency-symbol-and-amount' ) ) {
        $separator = ' ';
    } else {
        $separator = '';
    }

    if ( $show_currency_symbol && $symbol_position == 'show-currency-symbol-on-left' ) {
        $formatted = "{$currency_symbol}{$separator}<amount>";
    } elseif ( $show_currency_symbol && $symbol_position == 'show-currency-symbol-on-right' ) {
        $formatted = "<amount>{$separator}{$currency_symbol}";
    } else {
        $formatted = '<amount>';
    }

    return $formatted;
}

/**
 * XXX: Referenced in FAQ: https://awpcp.com/forum/faq/why-doesnt-my-currency-code-change-when-i-set-it/
 */
function awpcp_get_currency_symbol() {
    $currency_symbol = get_awpcp_option( 'currency-symbol' );

    if ( ! empty( $currency_symbol ) ) {
        return $currency_symbol;
    }

    $currency_code = awpcp_get_currency_code();

    foreach ( awpcp_currency_symbols() as $currency_symbol => $currency_codes ) {
        if ( in_array( $currency_code, $currency_codes, true ) ) {
            return $currency_symbol;
        }
    }

    return $currency_code;
}

/**
 * @access private
 */
function awpcp_get_formmatted_amount( $value, $template ) {
    if ( $value < 0 ) {
        return '(' . str_replace( '<amount>', awpcp_format_number( $value ), $template ) . ')';
    } else {
        return str_replace( '<amount>', awpcp_format_number( $value ), $template );
    }
}

function awpcp_format_number( $value, $decimals = null ) {
    return awpcp_get_formatted_number( $value, $decimals = get_awpcp_option( 'show-decimals' ) ? 2 : 0 );
}

/**
 * @access private
 */
function awpcp_get_formatted_number( $value, $decimals = 0 ) {
    $thousands_separator = get_awpcp_option( 'thousands-separator' );
    $decimal_separator = get_awpcp_option( 'decimal-separator' );

    $formatted = number_format( abs( $value ), $decimals, '~', '^' );
    $formatted = str_replace( '~', $decimal_separator, $formatted );
    $formatted = str_replace( '^', $thousands_separator, $formatted );

    return $formatted;
}

function awpcp_format_money_without_currency_symbol( $value ) {
    return awpcp_get_formmatted_amount(
        $value,
        awpcp_get_formatted_amount_template_without_currency_symbol()
    );
}

/**
 * @access private
 */
function awpcp_get_formatted_amount_template_without_currency_symbol() {
    return awpcp_get_formatted_amount_template( false );
}

function awpcp_format_integer( $value ) {
    return awpcp_get_formatted_number( $value, $decimals = 0 );
}

/**
 * @since 3.7.5
 */
function awpcp_parse_number( $value, $decimal_separator = false, $thousands_separator = false ) {
    if ( strlen( $value ) === 0 ) return false;

    $thousands_separator = $thousands_separator ? $thousands_separator : get_awpcp_option('thousands-separator');
    $decimal_separator = $decimal_separator ? $decimal_separator : get_awpcp_option('decimal-separator');

    $pattern = '/^-?(?:\d+|\d{1,3}(?:' . preg_quote( $thousands_separator ) . '\\d{3})+)?(?:' . preg_quote( $decimal_separator ) . '\\d+)?$/';

    if ( preg_match( $pattern, $value ) ) {
        $value = str_replace($thousands_separator, '', $value);
        $value = str_replace($decimal_separator, '.', $value);
        $number = floatval($value);
    } else {
        $number = false;
    }

    return $number;
}

/**
 * @since 3.0
 */
function awpcp_parse_money($value, $decimal_separator=false, $thousands_separator=false) {
    return awpcp_parse_number( $value, $decimal_separator, $thousands_separator );
}

/**
 * @since 2.1.4
 */
function awpcp_get_flash_messages() {
    if ( ! is_user_logged_in() ) {
        global $awp_messages;
        return $awp_messages ? $awp_messages : array();
    }

    $messages = get_user_option( 'awpcp-messages', get_current_user_id() );
    return $messages ? $messages : array();
}

/**
 * @since 2.1.4
 */
function awpcp_update_flash_messages($messages) {
    if (is_user_logged_in()) {
        return update_user_option(get_current_user_id(), 'awpcp-messages', $messages);
    } else {
        global $awp_messages;
        $awp_messages = $messages;
        return true;
    }
}

/**
 * @since 2.1.4
 */
function awpcp_clear_flash_messages() {
    if ( ! is_user_logged_in() ) {
        global $awp_messages;
        $awp_messages = array();
        return true;
    }

    return delete_user_option( get_current_user_id(), 'awpcp-messages' );
}

function awpcp_flash( $message, $class = array( 'awpcp-updated', 'notice', 'notice-info', 'updated') ) {
    $messages = awpcp_get_flash_messages();

    if ( ! awpcp_is_duplicated_flash_message( $messages, $message, $class ) ) {
        $messages[] = array( 'message' => $message, 'class' => (array) $class );
        awpcp_update_flash_messages( $messages );
    }
}

/**
 * @since 4.0.0
 */
function awpcp_flash_error( $message, $class = array( 'awpcp-error', 'notice', 'notice-error' ) ) {
    awpcp_flash( $message, $class );
}

/**
 * @since 4.0.0
 */
function awpcp_flash_warning( $message ) {
    awpcp_flash( $message, [ 'awpcp-warning', 'notice', 'notice-warning' ] );
}

function awpcp_is_duplicated_flash_message( $messages, $message, $class ) {
    foreach ( $messages as $m ) {
        if ( strcmp( $m['message'], $message ) == 0 ) {
            return true;
        }
    }

    return false;
}

/**
 */
function awpcp_print_messages() {
    // The function is expected to be called only once per request. However,
    // due to special circumstances it is possible that the function is called
    // twice or more, usually with the results from the last call being the ones
    // shown to the user. In those cases, the messages would be lost unless we
    // cache the messages during the request. That's why we use a static $messages
    // variable.
    static $messages = null;
    $messages = is_null($messages) ? awpcp_get_flash_messages() : $messages;

    foreach ($messages as $message) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_print_message($message['message'], $message['class']);
    }

    awpcp_clear_flash_messages();
}

function awpcp_print_form_errors( $errors ) {
    foreach ( $errors as $index => $error ) {
        if ( is_numeric( $index ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_message( $error, array( 'awpcp-error', 'notice', 'notice-error' ) );
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_message( $error, array( 'awpcp-error', 'notice', 'notice-error', 'ghost' ) );
        }
    }
}

function awpcp_print_message( $message, $class = array( 'awpcp-updated', 'notice', 'notice-info' ) ) {
    if ( is_string( $class ) ) {
        $class = array( $class );
    }

    if ( ! is_array( $class ) ) {
        $class = array();
    }

    $class = array_merge(array('awpcp-message'), $class);
    return '<div class="' . esc_attr( join( ' ', $class ) ) . '">' .
        '<p>' . wp_kses_post( $message ) . '</p>' .
        '</div>';
}

function awpcp_print_error($message) {
    return awpcp_print_message( $message, array( 'awpcp-error', 'notice', 'notice-error', 'error' ) );
}

/**
 * @since 4.0.0
 */
function awpcp_render_info_message( $message, $classes = [] ) {
    $default_classes = [ 'awpcp-message-info', 'notice', 'notice-info' ];

    return awpcp_print_message( $message, array_merge( $default_classes, $classes ) );
}

/**
 * @since 4.0.0
 */
function awpcp_render_success_message( $message, $classes = [] ) {
    $default_classes = [ 'awpcp-message-success', 'notice', 'notice-success' ];

    return awpcp_print_message( $message, array_merge( $default_classes, $classes ) );
}

/**
 * @since 4.0.0
 */
function awpcp_render_error_message( $message, $classes = [] ) {
    $default_classes = [ 'awpcp-message-error', 'awpcp-error', 'notice', 'notice-error' ];

    return awpcp_print_message( $message, array_merge( $default_classes, $classes ) );
}

/**
 * @since 4.0.0
 */
function awpcp_render_dismissible_success_message( $message ) {
    return awpcp_render_success_message( $message, [ 'is-dismissible' ] );
}

/**
 * @since 4.0.0
 */
function awpcp_render_dismissible_error_message( $message ) {
    return awpcp_print_message( $message, array( 'awpcp-error', 'notice', 'notice-error', 'is-dismissible' ) );
}

/**
 * @since 3.7.4
 */
function awpcp_render_warning( $message ) {
    return awpcp_print_message( $message, array( 'awpcp-warning', 'notice', 'notice-warning' ) );
}

function awpcp_validate_error($field, $errors) {
    $error = awpcp_array_data($field, '', $errors);
    if (empty($error))
        return '';
    return '<label for="' . $field . '" generated="true" class="error" style="">' . $error . '</label>';
}

/**
 * @since 4.3.3
 */
function awpcp_show_form_error( $field, $errors ) {
    echo wp_kses_post(
        awpcp_form_error( $field, $errors )
    );
}

function awpcp_form_error( $field, $errors ) {
    $error = awpcp_array_data($field, '', $errors);
    return empty($error) ? '' : '<span class="awpcp-error">' . $error . '</span>';
}

function awpcp_form_help_text( $field_id, $help_text ) {
    if ( empty( $help_text ) ) {
        return null;
    }

    $params = array(
        'text' => $help_text,
        'attributes' => array(
            'for' => $field_id,
            'class' => array( 'helptext', 'awpcp-form-helptext' ),
        ),
    );

    return awpcp_html_label( $params );
}

/**
 * @since 4.0.0
 */
function awpcp_html_attributes( $attributes ) {
    $output = array();

    if ( isset( $attributes['class'] ) && is_array( $attributes['class'] ) ) {
        $attributes['class'] = implode( ' ', array_filter( $attributes['class'], 'strlen' ) );
    }

    foreach ( $attributes as $name => $value ) {
        $output[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( $value ) );
    }

    return implode( ' ', $output );
}

function awpcp_html_hidden_fields( $fields ) {
    $output = array();

    foreach ( array_filter( awpcp_flatten_array( $fields ) ) as $name => $value ) {
        if ( is_object( $value ) ) {
            continue;
        }

        $output[] = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
    }

    return implode( "\n", $output );
}

/**
 * @since 4.0.0
 */
function awpcp_html_image( $params ) {
    $params = wp_parse_args( $params, array(
        'attributes' => array(
            'alt' => null,
            'title' => null,
            'src' => null,
            'width' => null,
            'height' => null,
            'style' => null,
        ),
    ) );

    $attributes = rtrim( ' ' . awpcp_html_attributes( $params['attributes'] ) );
    $element = str_replace( '<attributes>', $attributes, '<img<attributes>/>' );

    return $element;
}

/**
 * @since 3.6
 */
function awpcp_html_label( $params ) {
    $params = wp_parse_args( $params, array(
        'text' => null,
        'attributes' => array(
            'for' => null,
        ),
    ) );

    $attributes = rtrim( ' ' . awpcp_html_attributes( $params['attributes'] ) );

    $element = '<label <attributes>><text></label>';
    $element = str_replace( '<attributes>', $attributes, $element );
    $element = str_replace( '<text>', wp_kses_post( $params['text'] ), $element );

    return $element;
}

/**
 * @since 3.6
 */
function awpcp_html_text_field( $params ) {
    $params = awpcp_parse_html_params(
        $params,
        array(
            'required' => null,
            'readonly' => null,
        )
    );

    if ( $params['readonly'] ) {
        $attributes['readonly'] = 'readonly';
    }

    $params['attributes']['type'] = 'text';

    return awpcp_html_input( $params );
}

/**
 * @since 3.6
 */
function awpcp_html_input( $params ) {
    $attributes = rtrim( ' ' . awpcp_html_attributes( $params['attributes'] ) );
    $element = str_replace( '<attributes>', $attributes, '<input <attributes>/>' );
    return $element;
}

/**
 * @since 3.6
 */
function awpcp_html_radio( $params ) {
    $params = awpcp_parse_html_params(
        $params,
        array(
            'current-value' => null,
            'disabled' => null,
        ),
        array(
            'value' => null,
        )
    );

    if ( $params['disabled'] ) {
        $params['attributes']['disabled'] = 'disabled';
    }

    if ( $params['current-value'] === $params['attributes']['value'] ) {
        $params['attributes']['checked'] = 'checked';
    }

    $params['attributes']['type'] = 'radio';

    return awpcp_html_input( $params );
}

/**
 * @since 3.6
 */
function awpcp_html_select( $params ) {
    $params = awpcp_parse_html_params( $params );

    $attributes = rtrim( ' ' . awpcp_html_attributes( $params['attributes'] ) );

    $element = '<select <select-attributes>><options></select>';
    $element = str_replace( '<select-attributes>', $attributes, $element );
    $element = str_replace( '<options>', awpcp_html_options( $params ), $element );

    return $element;
}

/**
 * @since 3.6
 */
function awpcp_html_options( $params ) {
    $params = wp_parse_args( $params, array(
        'current-value' => null,
        'options' => array(),
    ) );

    $options = '';

    foreach ( $params['options'] as $value => $text ) {

        if ( strcmp( $value, $params['current-value'] ) === 0 ) {
            $attributes = array( 'value' => $value, 'selected' => 'selected' );
        } else {
            $attributes = array( 'value' => $value );
        }

        $options .= '<option ' . awpcp_html_attributes( $attributes ) . '>' .
            esc_html( $text ) .
            '</option>';
    }

    return $options;
}

/**
 * @since 3.6
 */
function awpcp_parse_html_params( $params, $default_params = array(), $default_attributes = array() ) {
    $params = wp_parse_args(
        $params,
        wp_parse_args(
            $default_params,
            array(
                'required' => null,
                'attributes' => array(),
            )
        )
    );

    $params['attributes'] = awpcp_parse_html_attributes( $params['attributes'], $default_attributes );

    if ( $params['required'] ) {
        $attributes['class'][] = 'required';
    }

    return $params;
}

function awpcp_parse_html_attributes( $attributes, $default_attributes = array() ) {
    $attributes = wp_parse_args(
        $attributes,
        wp_parse_args(
            $default_attributes,
            array(
                'class' => array(),
            )
        )
    );

    if ( ! is_array( $attributes['class'] ) ) {
        $attributes['class'] = explode( ' ', $attributes['class'] );
    }

    return $attributes;
}

/**
 * @since 3.6
 */
function awpcp_html_postbox_handle( $params ) {
    $default_params = array(
        'heading_attributes' => array(),
        'span_attributes' => array(),
        'heading_class' => 'hndle',
        'heading_tag' => null,
        'content' => '',
        'echo'               => false,
    );

    $params = wp_parse_args( $params, $default_params );
    $params['heading_attributes'] = awpcp_parse_html_attributes( $params['heading_attributes'] );
    $params['span_attributes'] = awpcp_parse_html_attributes( $params['span_attributes'] );

    if ( ! in_array( $params['heading_class'], $params['heading_attributes']['class'], true ) ) {
        $params['heading_attributes']['class'][] = $params['heading_class'];
    }

    if ( is_null( $params['heading_tag'] ) ) {
        $params['heading_tag'] = awpcp_html_admin_second_level_heading_tag();
    }

    $heading = $params['heading_tag'];
    $element = '<' . esc_attr( $heading ) . ' ' . awpcp_html_attributes( $params['heading_attributes'] ) . '>' .
        '<span ' . awpcp_html_attributes( $params['span_attributes'] ) . '>' .
            wp_kses_post( $params['content'] ) .
        '</span>' .
        '</' . esc_attr( $heading ) . '>';

    if ( $params['echo'] ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $element;
    }

    return $element;
}

/**
 * @since 3.6
 */
function awpcp_html_admin_first_level_heading( $params ) {
    $params['tag'] = awpcp_html_admin_first_level_heading_tag();
    return awpcp_html_heading( $params );
}

/**
 * See:
 * - https://make.wordpress.org/core/2015/07/31/headings-in-admin-screens-change-in-wordpress-4-3/
 *
 * @since 3.6
 */
function awpcp_html_admin_first_level_heading_tag() {
    if ( version_compare( get_bloginfo('version'), '4.3-beta1', '<' ) ) {
        return 'h2';
    } else {
        return 'h1';
    }
}

/**
 * @since 3.6
 */
function awpcp_html_admin_second_level_heading( $params ) {
    $params['tag'] = awpcp_html_admin_second_level_heading_tag();
    return awpcp_html_heading( $params );
}

/**
 * See:
 * - https://make.wordpress.org/core/2015/10/28/headings-hierarchy-changes-in-the-admin-screens/
 *
 * @since 3.6
 */
function awpcp_html_admin_second_level_heading_tag() {
    if ( version_compare( get_bloginfo('version'), '4.4-beta1', '<' ) ) {
        return 'h3';
    } else {
        return 'h2';
    }
}

/**
 * @since 4.0.0
 *
 * @return string
 */
function awpcp_html_admin_third_level_heading( $params ) {
    $params['tag'] = awpcp_html_admin_third_level_heading_tag();
    return awpcp_html_heading( $params );
}

/**
 * @since 4.0.0
 */
function awpcp_html_admin_third_level_heading_tag() {
    if ( version_compare( get_bloginfo('version'), '4.4-beta1', '<' ) ) {
        return 'h4';
    } else {
        return 'h3';
    }
}

/**
 * @access private
 * @since 3.6
 *
 * @return string
 */
function awpcp_html_heading( $params ) {
    $default_params = array(
        'tag'        => 'h1',
        'attributes' => array(),
        'content'    => '',
        'echo'       => false,
    );

    $params = wp_parse_args( $params, $default_params );
    $params['attributes'] = awpcp_parse_html_attributes( $params['attributes'] );

    $element = '<<heading-tag> <heading-attributes>><content></<heading-tag>>';
    $element = str_replace( '<heading-tag>', esc_attr( $params['tag'] ), $element );
    $element = str_replace( '<heading-attributes>', awpcp_html_attributes( $params['attributes'] ), $element );
    $element = str_replace( '<content>', $params['content'], $element );

    if ( $params['echo'] ) {
        echo wp_kses_post( $element );
        return '';
    }

    return $element;
}

function awpcp_uploaded_file_error($file) {
    $upload_errors = array(
        UPLOAD_ERR_OK           => __("No errors.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_INI_SIZE     => __("The file is larger than upload_max_filesize.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_FORM_SIZE    => __("The file is larger than form MAX_FILE_SIZE.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_PARTIAL      => __("The file was only partially uploaded.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_NO_FILE      => __("No file was uploaded.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_NO_TMP_DIR   => __("Missing temporary directory.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_CANT_WRITE   => __("Can't write file to disk.", 'another-wordpress-classifieds-plugin'),
        UPLOAD_ERR_EXTENSION    => __( 'The file upload was stopped by extension.', 'another-wordpress-classifieds-plugin' ),
    );
    $error = sanitize_text_field( wp_unslash( $file['error'] ) );
    return array( $error, $upload_errors[ $error ] );
}

function awpcp_get_file_extension( $filename ) {
    return strtolower( awpcp_utf8_pathinfo( $filename, PATHINFO_EXTENSION ) );
}

/**
 * Recursively remove a directory.
 * @since 3.0.2
 */
function awpcp_rmdir($dir) {
    $wp_filesystem = awpcp_get_wp_filesystem();
    if ( ! $wp_filesystem ) {
        return false;
    }

    if ( $wp_filesystem->is_dir( $dir ) ) {
        $objects = $wp_filesystem->dirlist( $dir );
        if ( $objects ) {
            foreach ( $objects as $object => $object_info ) {
                if ( $object != "." && $object != ".." ) {
                    $object_path = $dir . "/" . $object;
                    if ( $wp_filesystem->is_dir( $object_path ) ) {
                        awpcp_rmdir( $object_path );
                    } else {
                        $wp_filesystem->delete( $object_path );
                    }
                }
            }
        }

        $wp_filesystem->rmdir( $dir );
    }
}

/**
 * @since 3.0.2
 */
function awpcp_directory_permissions() {
    return intval( get_awpcp_option( 'upload-directory-permissions', '0755' ), 8 );
}

/**
 * @since 2.0.7
 */
function awpcp_table_exists($table) {
    global $wpdb;
    $result = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
    );
    return strcasecmp($result, $table) === 0;
}

/**
 * TODO: move memoization to where the information is needed. Having it here is the perfect
 * scenarion for hard to track bugs.
 * @since  2.1.4
 */
function awpcp_column_exists($table, $column) {
    static $column_exists = array();

    if ( ! isset( $column_exists[ "$table-$column" ] ) ) {
        $column_exists[ "$table-$column" ] = awpcp_check_if_column_exists( $table, $column );
    }

    return $column_exists[ "$table-$column" ];
}

/**
 * @since 3.4
 */
function awpcp_check_if_column_exists( $table, $column ) {
    global $wpdb;

    $suppress_errors = $wpdb->suppress_errors();
    $result = $wpdb->query(
        $wpdb->prepare( "SELECT %i FROM %i", $column, $table )
    );
    $wpdb->suppress_errors( $suppress_errors );

    return $result !== false;
}

/** Email functions */

/**
 * Extracted from class-phpmailer.php (PHPMailer::EncodeHeader).
 *
 * XXX: This may be necessary only for email addresses used in the Reply-To header.
 *
 * @since 3.0.2
 */
function awpcp_encode_address_name($str) {
    return awpcp_phpmailer()->encodeHeader( $str, 'phrase' );
}

/**
 * Returns or creates an instance of PHPMailer.
 *
 * Extracted from wp_mail()'s code.
 *
 * @since 3.4
 */
function awpcp_phpmailer() {
    global $phpmailer;

    // (Re)create it, if it's gone missin.
    // Add support for WP5.5 PHPMailer changes.
    if (  version_compare( get_bloginfo('version'), '5.5', '>=' ) ) {
        if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            $phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
        }
    } elseif ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';

        /** @phpstan-ignore-next-line */
        $phpmailer = new PHPMailer( true );
    }

    // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $phpmailer->CharSet = apply_filters( 'wp_mail_charset', get_bloginfo( 'charset' ) );

    return $phpmailer;
}

/**
 * @since 3.0.2
 */
function awpcp_format_email_address($address, $name) {
    return awpcp_encode_address_name( $name ) . " <" . $address . ">";
}

/**
 * @since 3.7.1
 */
function awpcp_format_recipient_address( $email_address, $name = false ) {
    if ( $name && get_awpcp_option( 'include-recipient-name-in-email-address' ) ) {
        return awpcp_format_email_address( $email_address, $name );
    }

    return $email_address;
}

/**
 * Return the email address that should receive the notifications intented for
 * administrator users.
 *
 * @since 3.0
 * @return string email address
 */
function awpcp_admin_recipient_email_address() {
    $email_address = get_awpcp_option( 'admin-recipient-email' );
    if ( empty( $email_address ) ) {
        $email_address = get_option( 'admin_email' );
    }

    return $email_address;
}

/**
 * Return the email address used as the sender for email notifications.
 *
 * @since 3.0
 * @return string email address
 */
function awpcp_admin_sender_email_address($include_contact_name=false) {
    if ( awpcp_get_option( 'sent-emails-using-wordpress-email-address' ) ) {
        $email_address = sprintf( 'wordpress@%s', awpcp_request()->domain( false ) );
    } elseif ( strlen( get_awpcp_option( 'awpcpadminemail' ) ) > 0 ) {
        $email_address = get_awpcp_option( 'awpcpadminemail' );
    } else {
        $email_address = get_option( 'admin_email' );
    }

    return $email_address;
}

/**
 * @since 4.0.0
 */
function awpcp_admin_sender_name() {
    if ( awpcp_get_option( 'sent-emails-using-wordpress-email-address' ) ) {
        $sender_name = 'WordPress';
    } else {
        $sender_name = awpcp_get_blog_name();
    }

    return $sender_name;
}

/**
 * Return the name and email address of the account that appears as the sender in
 * email notifications.
 *
 * @since 3.0
 * @return string name <email@address>
 */
function awpcp_admin_email_from() {
    $email_address = awpcp_admin_sender_email_address();
    $sender_name = awpcp_admin_sender_name();

    return awpcp_format_email_address( $email_address, $sender_name );
}

/**
 * Return the name and email address of the account that should receive notifications intented for
 * administrator users.
 *
 * @since 3.0
 * @return string name <email@address>
 */
function awpcp_admin_email_to() {
    return awpcp_format_recipient_address( awpcp_admin_recipient_email_address(), awpcp_get_blog_name() );
}

function awpcp_moderators_email_to() {
    $email_addresses = array();

    $users = awpcp_users_collection()->find( array(
        'fields' => array( 'public_name', 'user_email' ),
        'role'   => 'awpcp-moderator',
    ) );

    foreach ( $users as $user ) {
        $email_addresses[] = awpcp_format_recipient_address( $user->user_email, $user->public_name );
    }

    return $email_addresses;
}

/**
 * @since  2.1.4
 */
function awpcp_ad_enabled_email( $listing ) {
    $listing_renderer = awpcp_listing_renderer();

    $listing_title = $listing_renderer->get_listing_title( $listing );
    $contact_name = $listing_renderer->get_contact_name( $listing );
    $contact_email = $listing_renderer->get_contact_email( $listing );

    // user email
    $mail = new AWPCP_Email();
    $mail->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );
    /* translators: %s is the listing title */
    $mail->subject = sprintf( __( 'Your Ad "%s" has been approved', 'another-wordpress-classifieds-plugin'), $listing_title );

    $template = AWPCP_DIR . '/frontend/templates/email-ad-enabled-user.tpl.php';
    $mail->prepare( $template, compact( 'listing', 'listing_title', 'contact_name' ) );

    $mail->send();
}

/**
 * @since 3.0.2
 */
function awpcp_ad_updated_user_email( $ad, $message ) {
    $admin_email = awpcp_admin_recipient_email_address();

    $listing_renderer = awpcp_listing_renderer();

    $listing_title = $listing_renderer->get_listing_title( $ad );
    $access_key = $listing_renderer->get_access_key( $ad );
    $contact_name = $listing_renderer->get_contact_name( $ad );
    $contact_email = $listing_renderer->get_contact_email( $ad );

    $mail = new AWPCP_Email();
    $mail->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );
    // translators: %s is the listing title
    $mail->subject = sprintf( __( 'Your Ad "%s" has been successfully updated', 'another-wordpress-classifieds-plugin' ), $listing_title );

    $template = AWPCP_DIR . '/frontend/templates/email-ad-updated-user.tpl.php';
    $mail->prepare( $template, compact( 'ad', 'listing_title', 'access_key', 'contact_email', 'admin_email', 'message' ) );

    return $mail;
}

function awpcp_ad_updated_email( $ad, $message ) {
    // user email
    $mail = awpcp_ad_updated_user_email( $ad, $message );
    return $mail->send();
}

function awpcp_ad_awaiting_approval_email($ad, $ad_approve, $images_approve) {
    $listing_renderer = awpcp_listing_renderer();

    // admin email
    $params = array( 'action' => 'edit', 'post' => $ad->ID );
    $manage_images_url = add_query_arg( urlencode_deep( $params ), admin_url( 'post.php' ) );

    $messages = array();

    if ( false == $ad_approve && $images_approve ) {
        // translators: %s is the listing title
        $subject = __( 'Images on Ad "%s" are awaiting approval', 'another-wordpress-classifieds-plugin' );

        $messages[] = sprintf(
            // translators: %1$s is the listing title, %2$s is the URL to manage images
            __( 'Images on Ad "%1$s" are awaiting approval. You can approve the images going to the Manage Images section for that Ad and clicking the "Enable" button below each image. Click here to continue: %2$s.', 'another-wordpress-classifieds-plugin' ),
            $listing_renderer->get_listing_title( $ad ),
            $manage_images_url
        );
    } else {
        // translators: %s is the listing title
        $subject = __( 'The Ad "%s" is awaiting approval', 'another-wordpress-classifieds-plugin' );

        // translators: %1$s is the listing title, %2$s is the URL for managing listing
        $message = __( 'The Ad "%1$s" is awaiting approval. You can approve the Ad going to the Classified edit section and clicking the "Publish" button. Click here to continue: %2$s.', 'another-wordpress-classifieds-plugin');

        $url = awpcp_get_quick_view_listing_url( $ad );

        $messages[] = sprintf( $message, $listing_renderer->get_listing_title( $ad ), $url );

        if ( $images_approve ) {
            $messages[] = sprintf(
                // translators: %s is the URL to manage images
                __( 'Additionally, You can approve the images going to the Manage Images section for that Ad and clicking the "Enable" button below each image. Click here to continue: %s.', 'another-wordpress-classifieds-plugin' ),
                $manage_images_url
            );
        }
    }

    $mail = new AWPCP_Email();
    $mail->to[] = awpcp_admin_email_to();
    $mail->subject = sprintf( $subject, $listing_renderer->get_listing_title( $ad ) );

    $template = AWPCP_DIR . '/frontend/templates/email-ad-awaiting-approval-admin.tpl.php';
    $mail->prepare( $template, compact( 'messages' ) );

    $mail->send();
}

/** Table Helper related functions */

function awpcp_register_column_headers($screen, $columns, $sortable=array()) {
    $wp_list_table = new AWPCP_List_Table($screen, $columns, $sortable);
}

function awpcp_print_column_headers($screen, $id = true, $sortable=array()) {
    $wp_list_table = new AWPCP_List_Table($screen, array(), $sortable);
    $wp_list_table->print_column_headers($id);
}

/**
 * @since 3.3
 */
function awpcp_enqueue_main_script() {
    wp_enqueue_script( 'awpcp' );
}

/**
 * @since 3.3
 */
function awpcp_maybe_add_thickbox() {
    awpcp_maybe_include_lightbox_script();
    awpcp_maybe_include_lightbox_style();
}

/**
 * @since 4.0.0
 */
function awpcp_maybe_include_lightbox_script() {
    if ( ! get_awpcp_option( 'awpcp_thickbox_disabled' ) ) {
        wp_enqueue_script( 'awpcp-lightgallery' );
    }
}

/**
 * @since 4.0.0
 */
function awpcp_maybe_include_lightbox_style() {
    if ( ! get_awpcp_option( 'awpcp_thickbox_disabled' ) ) {
        wp_enqueue_style( 'awpcp-lightgallery' );
    }
}

/**
 * @since 3.6.4
 */
function awpcp_is_textdomain_loaded( $text_domain ) {
    return ! is_a( get_translations_for_domain( $text_domain ), 'NOOP_Translations' );
}

function awpcp_utf8_strlen( $string ) {
    if ( function_exists( 'mb_strlen' ) ) {
        return mb_strlen( $string, 'UTF-8' );
    } else {
        return preg_match_all( '(.)su', $string, $matches );
    }
}

function awpcp_utf8_substr( $string, $start, $length=null ) {
    if ( function_exists( 'mb_substr' ) ) {
        return mb_substr( $string, $start, $length, 'UTF-8' );
    } else {
        return awpcp_utf8_substr_pcre( $string, $start, $length );
    }
}

function awpcp_utf8_substr_pcre( $string, $start, $length=null ) {
    if ( is_null( $length ) ) {
        $length = awpcp_utf8_strlen( $string ) - $start;
    }

    if ( preg_match_all( '/.{' . $start . '}(.{' . $length . '})/su', $string, $matches ) ) {
        return $matches[1][0];
    } else {
        return '';
    }
}

function awpcp_remove_utf8_non_characters( $content ) {
    //remove EFBFBD (Replacement Character)
    $content = trim( str_replace( "\xEF\xBF\xBD", '', $content ) );
    // remove BOM character
    $content = trim( str_replace( "\xEF\xBB\xBF", '', $content ) );
    $content = trim( str_replace( "\xEF\xBF\xBE", '', $content ) );

    return $content;
}

function awpcp_maybe_convert_to_utf8( $content ) {
    if ( ! function_exists( 'iconv' ) ) {
        return $content;
    }

    $encoding = awpcp_detect_encoding( $content );

    if ( $encoding && 'UTF-8' != $encoding ) {
        $converted_content = iconv( $encoding, 'UTF-8', $content );
    } else {
        $converted_content = $content;
    }

    return $converted_content;
}

/**
 * @since 3.6
 */
function awpcp_detect_encoding( $content ) {
    static $encodings = array(
        'UTF-8', 'ASCII',
        'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
        'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
        'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
        'Windows-1251', 'Windows-1252', 'Windows-1254',
    );

    if ( function_exists( 'mb_detect_encoding' ) ) {
        return mb_detect_encoding( $content, $encodings, true );
    } else {
        return awpcp_mb_detect_encoding( $content, $encodings );
    }
}

/**
 * http://php.net/manual/en/function.mb-detect-encoding.php#113983
 * @since 3.6.0
 */
function awpcp_mb_detect_encoding( $string, $encodings ) {
    if ( ! function_exists( 'iconv' ) ) {
        return false;
    }

    foreach ( $encodings as $encoding ) {
        $sample = iconv( $encoding, $encoding, $string );
        if ( md5( $sample ) == md5( $string ) ) {
            return $encoding;
        }
    }

    return false;
}

/**
 * from http://stackoverflow.com/a/4459219/201354.
 *
 * @since 3.3
 */
function awpcp_utf8_pathinfo( $path, $path_parts_types = null ) {
    $modified_path = awpcp_add_path_prefix( $path );
    $path_parts = is_null( $path_parts_types ) ? pathinfo( $modified_path ) : pathinfo( $modified_path, $path_parts_types );
    $path_parts = awpcp_remove_path_prefix( $path_parts, $path_parts_types );

    return $path_parts;
}

function awpcp_add_path_prefix( $path, $prefix = '_629353a' ) {
    if ( strpos( $path, '/' ) === false ) {
        $modified_path = $prefix . $path;
    } else {
        $modified_path = str_replace( '/', "/$prefix", $path );
    }

    return $modified_path;
}

function awpcp_remove_path_prefix( $path_parts, $path_part_type, $prefix = '_629353a' ) {
    if ( is_array( $path_parts ) ) {
        foreach ( $path_parts as $key => $value ) {
            $path_parts[ $key ] = str_replace( $prefix, '', $value );
        }
    } elseif ( is_string( $path_parts ) ) {
        $path_parts = str_replace( $prefix, '', $path_parts );
    }

    return $path_parts;
}

function awpcp_utf8_basename( $path, $suffix = null ) {
    $modified_path = awpcp_add_path_prefix( $path );
    $basename = basename( $modified_path );
    return awpcp_remove_path_prefix( $basename, PATHINFO_BASENAME );
}

/**
 * TODO: provide a function that takes a path and one that doesn't. Remove
 *      file_exists; the former should only be called with valid paths.
 *
 * @param string    $path           Path to the file whose unique filename needs to be generated.
 * @param string    $filename       Target filename. The unique filename will be as similar as
 *                                  possible to this name.
 * @param array     $directories    The generated name must be unique in all directories in this array.
 * @since 3.4
 */
function awpcp_unique_filename( $path, $filename, $directories ) {
    $pathinfo = awpcp_utf8_pathinfo( $filename );

    $name = awpcp_sanitize_file_name( $pathinfo['filename'] );
    $extension = $pathinfo['extension'];
    $wp_filesystem = awpcp_get_wp_filesystem();
    $file_size = ( $wp_filesystem && $wp_filesystem->exists( $path ) ) ? $wp_filesystem->size( $path ) : 0;
    $timestamp = microtime();
    $salt = wp_salt();
    $counter = 0;

    do {
        $hash = hash( 'crc32b', "$name-$extension-$file_size-$timestamp-$salt-$counter" );
        $new_filename = "$name-$hash.$extension";
        ++$counter;
    } while ( awpcp_is_filename_already_used( $new_filename, $directories ) );

    return $new_filename;
}

/**
 * Remove characters not removed by sanitize_file_name, that are not invalid in OS,
 * but cause problems with URLs.
 *
 * See: https://github.com/drodenbaugh/awpcp/issues/1222#issuecomment-119742743
 *
 * @since 4.0.0
 */
function awpcp_sanitize_file_name( $filename ) {
    $sanitize_file_name = sanitize_file_name( $filename );
    $sanitize_file_name = str_replace( '^', '', $sanitize_file_name );
    return $sanitize_file_name;
}

/**
 * @since 3.4
 */
function awpcp_is_filename_already_used( $filename, $directories ) {
    $wp_filesystem = awpcp_get_wp_filesystem();
    if ( ! $wp_filesystem ) {
        return false;
    }

    foreach ( $directories as $directory ) {
        if ( $wp_filesystem->exists( "$directory/$filename" ) ) {
            return true;
        }
    }

    return false;
}

/**
 * @since 3.3
 */
function awpcp_register_activation_hook( $__FILE__, $callback ) {
    $file = plugin_basename( $__FILE__ );
    register_activation_hook( $file, $callback );
}

function awpcp_register_deactivation_hook( $__FILE__, $callback ) {
    $file = plugin_basename( $__FILE__ );
    register_deactivation_hook( $file, $callback );
}

/**
 * @since 4.0.0
 */
function awpcp_unregister_widget_if_exists( $widget_class ) {
    global $wp_widget_factory;

    if ( ! is_object( $wp_widget_factory ) ) {
        return;
    }

    if ( isset( $wp_widget_factory->widgets[ 'AWPCP_LatestAdsWidget' ] ) ) {
        unregister_widget("AWPCP_LatestAdsWidget");
    }
}

/**
 * @since 3.4
 */
function awpcp_are_images_allowed() {
    $allowed_image_extensions = array_filter( awpcp_get_option( 'allowed-image-extensions', array() ) );
    return count( $allowed_image_extensions ) > 0;
}

function add_slashes_recursive( $variable ) {
    if (is_string($variable)) {
        return addslashes($variable);
    } elseif (is_array($variable)) {
        foreach($variable as $i => $value) {
            $variable[$i] = add_slashes_recursive($value);
        }
    }

    return $variable;
}

function string_contains_string_at_position($haystack, $needle, $pos = 0, $case=true) {
    if ($case) {
        $result = (strpos($haystack, $needle, 0) === $pos);
    } else {
        $result = (stripos($haystack, $needle, 0) === $pos);
    }
    return $result;
}

function string_starts_with($haystack, $needle, $case=true) {
    return string_contains_string_at_position($haystack, $needle, 0, $case);
}

function string_ends_with($haystack, $needle, $case=true) {
    return string_contains_string_at_position($haystack, $needle, (strlen($haystack) - strlen($needle)), $case);
}

/**
 * @since new-release
 */
function awpcp_get_option( $option, $default = '', $reload = false ) {
    return get_awpcp_option( $option, $default, $reload );
}

function get_awpcp_option($option, $default='', $reload=false) {
    return awpcp()->settings->get_option( $option, $default, $reload );
}

function clean_field($foo) {
    return add_slashes_recursive($foo);
}

function isValidURL($url) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

/**
 * @since 3.0.2
 */
function awpcp_is_valid_email_address($email) {
    return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
}

/**
 * @since 3.4
 */
function awpcp_is_email_address_allowed( $email_address ) {
    $wildcard = 'BCCsyfxU6HMXyyasic6t';
    $pattern = '[a-zA-Z0-9-]*';

    $domains_whitelist = str_replace( '*', $wildcard, get_awpcp_option( 'ad-poster-email-address-whitelist' ) );
    $domains_whitelist = preg_quote( $domains_whitelist );
    $domains_whitelist = str_replace( $wildcard, $pattern, $domains_whitelist );
    $domains_whitelist = str_replace( "{$pattern}\.", "(?:{$pattern}\.)?", $domains_whitelist );
    $domains_whitelist = array_filter( explode( "\n", $domains_whitelist ) );
    $domains_whitelist = array_map( 'trim', $domains_whitelist );

    $domains_pattern = '/' . implode( '|', $domains_whitelist ) . '/';

    if ( empty( $domains_whitelist ) ) {
        return true;
    }

    $domain = substr( $email_address, strpos( $email_address, '@' ) + 1 );

    if ( preg_match( $domains_pattern, $domain ) ) {
        return true;
    }

    return false;
}

function create_ad_postedby_list($name) {
    $names = awpcp_listings_meta()->get_meta_values( 'contact_name' );
    return awpcp_html_options( array( 'current-value' => $name, 'options' => array_combine( $names, $names ) ) );
}

function awpcp_strip_html_tags( $text ) {
    // Remove invisible content
    $text = preg_replace(
        array(
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
            // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0",
        ),
        $text
    );
    return wp_strip_all_tags( $text );
}
// END FUNCTION

// Override the SMTP settings built into WP if the admin has enabled that feature
function awpcp_phpmailer_init_smtp( $phpmailer ) {
    // smtp not enabled?
    $enabled = get_awpcp_option('usesmtp');
    if ( !$enabled || 0 == $enabled ) return;
    $hostname = get_awpcp_option('smtphost');
    $port = get_awpcp_option('smtpport');
    $username = get_awpcp_option('smtpusername');
    $password = get_awpcp_option('smtppassword');
    // host and port not set? gotta have both.
    if ( '' == trim( $hostname ) || '' == trim( $port ) )
        return;
    // still got defaults set? can't use those.
    if ( 'mail.example.com' == trim( $hostname ) ) return;
    if ( 'smtp_username' == trim( $username ) ) return;

    // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $phpmailer->Mailer = 'smtp';
    // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $phpmailer->Host = $hostname;
    // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $phpmailer->Port = $port;
    // If there's a username and password then assume SMTP Auth is necessary and set the vars:
    if ( '' != trim( $username )  && '' != trim( $password ) ) {
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName
        $phpmailer->SMTPAuth = true;
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName
        $phpmailer->Username = $username;
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName
        $phpmailer->Password = $password;
    }
    // that's it!
}

function awpcp_format_email_sent_datetime() {
    $time = date_i18n( awpcp_get_datetime_format(), current_time( 'timestamp' ) );
    // translators: %s is the formatted date and time
    return sprintf( __( 'Email sent %s.', 'another-wordpress-classifieds-plugin' ), $time );
}

/**
 * Make sure the IP isn't a reserved IP address.
 */
function awpcp_validip($ip) {

    if (!empty($ip) && ip2long($ip)!=-1) {

        $reserved_ips = array(
            array( '0.0.0.0', '2.255.255.255' ),
            array( '10.0.0.0', '10.255.255.255' ),
            array( '127.0.0.0', '127.255.255.255' ),
            array( '169.254.0.0', '169.254.255.255' ),
            array( '172.16.0.0', '172.31.255.255' ),
            array( '192.0.2.0', '192.0.2.255' ),
            array( '192.168.0.0', '192.168.255.255' ),
            array( '255.255.255.0', '255.255.255.255' ),
        );

        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max))
            return false;
        }

        return true;

    } else {

        return false;

    }
}

/**
 * @since 4.0.0     Rewrote to use use filter_var() and wp_unslash().
 */
function awpcp_getip() {
    $variables    = [];
    $alternatives = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ( $alternatives as $variable ) {
        if ( ! empty( $_SERVER[ $variable ] ) ) {
            $variables[ $variable ] = awpcp_get_server_value( $variable );
        }
    }

    // HTTP_X_FORWARDED_FOR sometimes is a comma separated list of IP addresses:
    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For.
    //
    // Let's turn that into an array of IP addresses instead.
    if ( isset( $variables['HTTP_X_FORWARDED_FOR'] ) ) {
        $variables['HTTP_X_FORWARDED_FOR'] = array_map( 'trim', explode( ',', $variables['HTTP_X_FORWARDED_FOR'] ) );
    }

    foreach ( $variables as $values ) {
        foreach ( (array) $values as $value ) {
            $filtered_value = filter_var( $value, FILTER_VALIDATE_IP );

            // awpcp_validip() also checks that the IP address is not a reserved one.
            if ( ! empty( $filtered_value ) && awpcp_validip( $filtered_value ) ) {
                return $filtered_value;
            }
        }
    }
}

/**
 * TODO: Update this to work with listing objects to reduce database queries.
 */
function awpcp_get_ad_share_info( $id ) {
    try {
        $ad = awpcp_listings_collection()->get( $id );
    } catch ( AWPCP_Exception $e ) {
        return null;
    }

    $info = array();

    $info['url']         = url_showad( $id );
    $info['title']       = stripslashes( $ad->post_title );
    $info['description'] = wp_strip_all_tags( stripslashes( $ad->post_content ) );

    $info['description'] = str_replace( array( "\r", "\n", "\t" ), ' ', $info['description'] );
    $info['description'] = preg_replace( '/ {2,}/', ' ', $info['description'] );
    $info['description'] = trim( $info['description'] );

    if ( awpcp_utf8_strlen( $info['description'] ) > 300 ) {
        $info['description'] = awpcp_utf8_substr( $info['description'], 0, 300 ) . '...';
    }

    $info['images'] = array();

    $info['published-time'] = awpcp_datetime( 'Y-m-d', $ad->post_date );
    $info['modified-time']  = awpcp_datetime( 'Y-m-d', $ad->post_modified );

    $attachment_properties = awpcp_attachment_properties();

    $images = awpcp_attachments_collection()->find_visible_attachments( array( 'post_parent' => $ad->ID ) );

    foreach ( $images as $image ) {
        $info['images'][] = $attachment_properties->get_image_url( $image, 'large' );
    }

    return $info;
}

/**
 * @since 3.6.6
 */
function awpcp_user_agent_header() {
    // translators: %1$s is WordPress version, %2$s is plugin version
    $user_agent = 'WordPress %s / Another WordPress Classifieds Plugin %s';
    $user_agent = sprintf( $user_agent, get_bloginfo( 'version' ), $GLOBALS['awpcp_db_version'] );
    return $user_agent;
}

/**
 * @since 3.7.6
 */
function awpcp_get_curl_info() {
    if ( ! in_array( 'curl', get_loaded_extensions(), true ) ) {
        return __( 'Not Installed', 'another-wordpress-classifieds-plugin' );
    }

    if ( ! function_exists( 'curl_version' ) ) {
        return __( 'Installed', 'another-wordpress-classifieds-plugin' );
    }

    $curl_info = curl_version();

    $output[] = "Version: {$curl_info['version']}";

    if ( $curl_info['features'] & CURL_VERSION_SSL ) {
        $output[] = __( 'SSL Support: Yes.', 'another-wordpress-classifieds-plugin' );
    } else {
        $output[] = __( 'SSL Support: No.', 'another-wordpress-classifieds-plugin' );
    }

    $output[] = __( 'OpenSSL version:', 'another-wordpress-classifieds-plugin' ) . ' ' . $curl_info['ssl_version'];

    return implode( '<br>', $output );
}

/**
 * @since 3.7.8
 */
function awpcp_get_server_ip_address() {
    $ip_address = get_transient( 'awpcp-server-ip-address' );

    if ( $ip_address ) {
        return $ip_address;
    }

    $ip_address = awpcp_get_server_ip_address_from_httpbin();

    if ( is_null( $ip_address ) ) {
        $ip_address = '(unknown)';
    }

    set_transient( 'awpcp-server-ip-address', $ip_address, HOUR_IN_SECONDS );

    return $ip_address;
}

/**
 * @since 3.8.4
 */
function awpcp_get_server_ip_address_from_httpbin() {
    $response = wp_remote_get( 'https://httpbin.org/ip' );

    if ( is_wp_error( $response ) ) {
        return null;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ) );

    if ( ! isset( $body->origin ) ) {
        return null;
    }

    return $body->origin;
}

/**
 * Outputs or retrieves SVG content.
 *
 * @since 4.3.5
 * @param string $path       Relative or absolute path to the SVG file.
 * @param bool   $use_images Whether to prepend the images directory to the path. Default true.
 * @param bool   $echo       Whether to echo the output or return it. Default true.
 */
function awpcp_inline_svg( $path, $use_images = true, $echo = true ) {
    if ( $use_images ) {
        $path = AWPCP_DIR . '/resources/images/' . $path;
    }

    if ( ! is_readable( $path ) ) {
        return '';
    }

    // Ensure it's an SVG file.
    $file_info = pathinfo( $path );
    if ( 'svg' !== strtolower( $file_info['extension'] ) ) {
        return '';
    }

    $content = file_get_contents( $path );

    if ( false === $content ) {
        return '';
    }

    if ( $echo ) {
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $content;
    }
}

/**
 * Get the file chmod.
 *
 * @since 4.4.2
 *
 * @return int
 */
function awpcp_get_file_chmod() {
    return defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
}

/**
 * Get the directory chmod.
 *
 * @since 4.4.2
 *
 * @return int
 */
function awpcp_get_dir_chmod() {
    return defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755;
}
