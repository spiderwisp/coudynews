<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Configures list table to show current user listings only for non-moderators users.
 */
class AWPCP_ListTableRestrictions {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var object
     */
    private $roles_and_capabilities;

    /**
     * @var object
     */
    private $request;

    /**
     * @since 4.0.0
     *
     * @param string $listing_post_type      The identifier for the classifieds
     *                                       custom post type.
     * @param object $roles_and_capabilities An instance of Listing Authorization.
     * @param object $request                An instance of Request.
     */
    public function __construct( $listing_post_type, $roles_and_capabilities, $request ) {
        $this->listing_post_type      = $listing_post_type;
        $this->roles_and_capabilities = $roles_and_capabilities;
        $this->request                = $request;
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( $this->roles_and_capabilities->current_user_is_moderator() ) {
            return;
        }

        $query->query_vars = $this->maybe_filter_by_author( $query->query_vars );
    }

    /**
     * Add the author query var if not already set to ensure only listings
     * owned by the current user are included.
     *
     * @since 4.0.6
     */
    public function maybe_filter_by_author( $query_vars ) {
        if ( empty( $query_vars['author'] ) ) {
            $query_vars['author'] = $this->request->get_current_user_id();
        }

        return $query_vars;
    }

    /**
     * @since 4.0.6
     */
    public function maybe_add_count_listings_query_filter() {
        if ( ! $this->roles_and_capabilities->current_user_is_moderator() ) {
            add_filter( 'awpcp_count_listings_query', [ $this, 'maybe_filter_by_author' ] );
        }
    }

    /**
     * @since 4.0.6
     */
    public function maybe_remove_count_listings_query_filter() {
        if ( ! $this->roles_and_capabilities->current_user_is_moderator() ) {
            remove_filter( 'awpcp_count_listings_query', [ $this, 'maybe_filter_by_author' ] );
        }
    }

    /**
     * Filter the result of wp_count_posts() to show accurate view numbers for
     * subscribers users and other users that can see listings they own only.
     *
     * Inspired by wp_count_posts().
     *
     * @since 4.0.6
     */
    public function filter_posts_count( $counts, $type ) {
        global $wpdb;

        if ( $this->roles_and_capabilities->current_user_is_moderator() ) {
            return $counts;
        }

        if ( $this->listing_post_type !== $type ) {
            return $counts;
        }

        $counts_array    = get_object_vars( $counts );
        $counts_array    = array_fill_keys( array_keys( $counts_array ), 0 );
        $counts          = (object) $counts_array;
        $current_user_id = $this->request->get_current_user_id();

        $cache_key = "posts-{$this->listing_post_type}_{$current_user_id}";
        $results   = wp_cache_get( $cache_key, 'awpcp-counts' );

        if ( ! is_array( $results ) ) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_status, COUNT( * ) AS num_posts
                    FROM {$wpdb->posts}
                    WHERE post_type = %s AND post_author = %d GROUP BY post_status",
                    $type,
                    $current_user_id
                ),
                ARRAY_A
            );

            // This function is called on the Classifieds Ads admin page only.
            // This cache should prevent the query from being executed twice or
            // more on a single request, but we don't want to store the results
            // between requests to avoid having to add extra logic to invalidate
            // the cache.
            wp_cache_set( $cache_key, $results, 'awpcp-counts', 30 );
        }

        foreach ( $results as $row ) {
            $counts->{$row['post_status']} = $row['num_posts'];
        }

        return $counts;
    }
}
