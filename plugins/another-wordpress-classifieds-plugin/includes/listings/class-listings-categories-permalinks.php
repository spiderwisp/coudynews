<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ListingsCategoriesPermalinks {

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
    public function filter_term_link( $link, $term, $taxonomy ) {
        if ( $taxonomy === $this->listing_category_taxonomy ) {
            return url_browsecategory( $term );
        }

        return $link;
    }
}
