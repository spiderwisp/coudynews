<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the cache of the list of categories shown on frontend pages.
 */
class AWPCP_CategoriesListCache {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    private $categories;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy, $categories ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->categories = $categories;
    }

    /**
     * @since 4.0.0
     */
    public function clear() {
        $transient_keys = get_option( 'awpcp-categories-list-cache-keys', array() );

        foreach ( $transient_keys as $transient_key ) {
            delete_transient( $transient_key );
        }

        if ( $transient_keys ) {
            delete_option( 'awpcp-categories-list-cache-keys' );
        }

        if( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && is_admin() ) {
            $this->categories->maybe_update_categories_order();
        }
    }

    /**
     * Handler for the set_object_terms action.
     *
     * @since 4.0.0
     */
    public function on_set_object_terms( $object_id, $terms, $term_taxonomy_ids, $taxonomy ) {
        if ( $this->listing_category_taxonomy !== $taxonomy ) {
            return;
        }

        $this->clear();
    }
}
