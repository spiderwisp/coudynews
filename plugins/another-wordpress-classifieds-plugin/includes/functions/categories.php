<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.4
 * @since 4.0.0     Replaced $hide_empty boolean parameter with optional callable $filter
 *                  $parameter.
 * @since 4.0.0     Added optional callable $callback parameter.
 */
function awpcp_build_categories_hierarchy( &$categories, $filter = null, $callback = null ) {
    // Backwards compatibility.
    if ( ! is_null( $filter ) && ! is_callable( $filter ) && $filter ) {
        return awpcp_build_non_empty_categories_hierarchy( $categories );
    } elseif ( ! is_null( $filter ) && ! is_callable( $filter ) ) {
        return __awpcp_build_categories_hierarchy( $categories, null, $callback );
    }

    return __awpcp_build_categories_hierarchy( $categories, $filter, $callback );
}

/**
 * @since 4.0.0
 */
function __awpcp_build_categories_hierarchy( $categories, $filter = null, $callback = null ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore
    $hierarchy = array( 'root' => array() );

    $filter_categories  = is_callable( $filter );
    $process_categories = is_callable( $callback );

    foreach ( $categories as $category ) {
        if ( $filter_categories && ! call_user_func( $filter, $category ) ) {
            continue;
        }

        $key = $category->parent === 0 ? 'root' : $category->parent;

        if ( $process_categories ) {
            $category = call_user_func( $callback, $category );
        }

        $hierarchy[ $key ][] = $category;
    }

    return $hierarchy;
}

/**
 * @since 4.0.0
 */
function awpcp_build_non_empty_categories_hierarchy( $categories, $callback = null ) {
    $filter = function( $category ) {
        return total_ads_in_cat( $category->term_id ) > 0;
    };

    return __awpcp_build_categories_hierarchy( $categories, $filter, $callback );
}

/**
 * @since 3.4
 */
function awpcp_organize_categories_by_id( &$categories ) {
    $organized = array();

    foreach ( $categories as $category ) {
        $organized[ $category->id ] = $category;
    }

    return $organized;
}

/**
 * @param $categories   Array of categories index by Category ID.
 * @since 3.4
 */
function awpcp_get_category_hierarchy( $category_id, &$categories ) {
    $category_parents = array();

    while ( $category_id > 0 && isset( $categories[ $category_id ] ) ) {
        $category_parents[] = $categories[ $category_id ];
        $category_id = $categories[ $category_id ]->parent;
    }

    return $category_parents;
}

/**
 * @since 3.4
 * @since 4.0.0     Accepts an array of selected categories.
 */
function awpcp_render_categories_dropdown_options( &$categories, &$hierarchy, $selected_categories, $level = 0 ) {
    $output = '';

    if ( ! is_array( $selected_categories ) ) {
        $selected_categories = array( $selected_categories );
    }

    $selected_categories = array_map( 'absint', $selected_categories );

    foreach ( $categories as $category ) {
        $output .= awpcp_render_categories_dropdown_option( $category, $selected_categories, $level );

        if ( isset( $hierarchy[ $category->term_id ] ) ) {
            $output .= awpcp_render_categories_dropdown_options( $hierarchy[ $category->term_id ], $hierarchy, $selected_categories, $level + 1 );
        }
    }

    return $output;
}

/**
 * @since 3.4
 * @since 4.0.0     Accepts an array of selected categories.
 */
function awpcp_render_categories_dropdown_option( $category, $selected_categories, $level ) {
    $category_name = esc_html( wp_unslash( $category->name ) );

    $attributes = [
        'class'     => 'dropdownparentcategory',
        'value'     => esc_attr( $category->term_id ),
    ];

    if ( $category->parent ) {
        $attributes['class'] = '';

        $category_name = sprintf( '%s%s', str_repeat( '&nbsp;', 3 * $level ), $category_name );
    }

    if ( in_array( $category->term_id, $selected_categories, true ) ) {
        $attributes['selected'] = 'selected';
    }

    if ( isset( $category->disabled ) && $category->disabled ) {
        $attributes['disabled'] = 'disabled';
    }

    return sprintf( '<option %s>%s</option>', awpcp_html_attributes( $attributes ), $category_name );
}

/**
 * @since 3.4
 */
function awpcp_get_count_of_listings_in_categories() {
    static $listings_count;

    if ( is_null( $listings_count ) ) {
        $listings_count = awpcp_count_listings_in_categories();
    }

    return $listings_count;
}

/**
 * @since 3.4
 * @since 4.0.0  Modified to work with custom post type and custom taxonomies.
 */
function awpcp_count_listings_in_categories() {
    $listings_count = array();

    foreach ( awpcp_categories_collection()->get_all() as $category ) {
        $listings_count[ $category->term_id ] = awpcp_count_listings_in_category( $category->term_id );
    }

    return $listings_count;
}

/**
 * TODO: Make sure other moduels (Like regions) are able to filter the query
 *       and their own parameters.
 *
 *       See the old implementation of awpcp_count_listings_in_categories
 *       (up to, at least, version 3.6.3.1).
 *
 * @since 4.0.0
 */
function awpcp_count_listings_in_category( $category_id ) {
    $children_categories = get_term_children( $category_id , 'awpcp_listing_category' );

    $listings_count = awpcp_listings_collection()->count_enabled_listings( array(
        'classifieds_query' => array('context' => 'public-listings'),
        'tax_query' => array(
            array(
                'taxonomy' => 'awpcp_listing_category',
                'field' => 'term_id',
                'terms' => array_merge( array( $category_id ), $children_categories ),
                'operator' => 'IN',
            ),
        ),
    ) );

    return $listings_count;
}

function total_ads_in_cat( $category_id ) {
    $listings_count = awpcp_get_count_of_listings_in_categories();
    return isset( $listings_count[ $category_id ] ) ? $listings_count[ $category_id ] : 0;
}
