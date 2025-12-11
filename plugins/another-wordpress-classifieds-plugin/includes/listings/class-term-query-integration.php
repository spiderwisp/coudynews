<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class that integrates with WP_Term_Query to provide special orderby
 * cabailities for Listing Categories taxonomy.
 */
class AWPCP_TermQueryIntegration {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
    }

    /**
     * @since 4.0.0
     */
    public function terms_clauses( $clauses, $taxonomies, $args ) {
        if ( count( $taxonomies ) > 1 ) {
            return $clauses;
        }

        if ( ! in_array( $this->listing_category_taxonomy, $taxonomies, true ) ) {
            return $clauses;
        }

        if ( ! isset( $args['orderby'] ) || 'meta_value_num' !== $args['orderby'] ) {
            return $clauses;
        }

        if ( ! isset( $args['meta_key'] ) || '_awpcp_order' !== $args['meta_key'] ) {
            return $clauses;
        }

        $clauses['orderby'] = "{$clauses['orderby']} {$clauses['order']}, name";

        return $clauses;
    }
}
