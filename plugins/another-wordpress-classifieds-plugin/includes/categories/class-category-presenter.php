<?php
/**
 * @package AWPCP\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles formatting and other data manipulation for category objects.
 *
 * @since 4.0.0
 */
class AWPCP_CategoryPresenter {

    /**
     * @var AWPCP_Categories_Collection
     */
    private $categories_collection;

    /**
     * @since 4.0.0
     */
    public function __construct( $categories_collection ) {
        $this->categories_collection = $categories_collection;
    }

    /**
     * @since 4.0.0
     */
    public function get_full_name( $category ) {
        if ( ! $category->parent ) {
            return $category->name;
        }

        $full_name = get_term_meta( $category->term_id, '_awpcp_full_name', true );

        if ( $full_name ) {
            return $full_name;
        }

        $names = [ $category->name ];

        do {
            $parent = $this->categories_collection->get( $category->parent );

            if ( $parent ) {
                $names[] = $parent->name;
            }

            $category = $parent;
        } while ( $category && $category->parent );

        return implode( ': ', array_reverse( $names ) );
    }
}
