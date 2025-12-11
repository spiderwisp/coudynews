<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return AWPCP_Categories_Renderer_Data_Provider
 */
function awpcp_categories_renderer_data_provider() {
    return new AWPCP_Categories_Renderer_Data_Provider(
        awpcp_categories_collection()
    );
}

class AWPCP_Categories_Renderer_Data_Provider {

    private $categories;

    public function __construct( $categories ) {
        $this->categories = $categories;
    }

    public function get_categories( $params ) {
        if ( ! is_null( $params['category_id'] ) && $params['show_children_categories'] ) {
            try {
                $category_ids     = $params['category_id'];
                $categories_found = array();
                foreach ( $category_ids as $category_id ) {
                    $parent_category  = $this->categories->get( $category_id );
                    $child_categories = $this->categories->find_categories( array( 'child_of' => $category_id ) );
                    array_push( $categories_found, $parent_category, ...$child_categories );
                }
            } catch ( AWPCP_Exception $e ) {
                $categories_found = array();
            }
        } elseif ( ! is_null( $params['category_id'] ) ) {
            $categories_found = $this->categories->find_categories( array( 'include' => $params['category_id'] ) );
        } elseif ( is_null( $params['parent_category_id'] ) && $params['show_children_categories'] ) {
            $categories_found = $this->categories->get_all();
        } elseif ( is_null( $params['parent_category_id'] ) ) {
            $categories_found = $this->categories->find_top_level_categories();
        } else {
            $categories_found = $this->categories->find_categories( array( 'child_of' => $params['parent_category_id'] ) );
        }

        $selected_categories = array();

        foreach ( $categories_found as $category ) {
            $category->listings_count = total_ads_in_cat( $category->term_id );
            if ( $params['show_empty_categories'] || $category->listings_count > 0 ) {
                $selected_categories[] = $category;
            }
        }

        return $selected_categories;
    }
}
