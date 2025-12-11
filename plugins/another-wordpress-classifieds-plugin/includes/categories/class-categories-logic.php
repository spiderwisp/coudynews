<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Categories Logic class.
 */
function awpcp_categories_logic() {
    return new AWPCP_Categories_Logic(
        AWPCP_CATEGORY_TAXONOMY,
        awpcp_listings_api(),
        awpcp_listings_collection(),
        awpcp_wordpress()
    );
}

class AWPCP_Categories_Logic {

    private $taxonomy;

    private $listings;
    private $listings_logic;
    private $wordpress;

    public function __construct( $taxonomy, $listings_logic, $listings, $wordpress ) {
        $this->taxonomy       = $taxonomy;
        $this->listings_logic = $listings_logic;
        $this->listings       = $listings;
        $this->wordpress      = $wordpress;
    }

    public function create_category( $category, $category_order = null ) {
        if ( is_array( $category ) ) {
            $category = (object) $category;
        }

        $data      = $this->get_category_data( $category, $category_order );
        $term_info = $this->wordpress->insert_term( $data['name'], $this->taxonomy, $data );

        if ( is_wp_error( $term_info ) ) {
            $message = __( 'There was an error trying to create a category: <error-message>', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<error-message>', $term_info->get_error_message(), $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( ! is_null( $data['order'] ) ) {
            $this->wordpress->update_term_meta( $term_info['term_id'], '_awpcp_order', $data['order'] );
        }

        /**
         * @since 3.3
         * TODO: fix handlers now that we pass more parameters
         */
        do_action( 'awpcp-category-added', $term_info['term_id'], $category );

        return $term_info['term_id'];
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception If the $category object has invalid data.
     */
    private function get_category_data( $category, $category_order ) {
        $category_data = [
            'order' => 0,
        ];

        if ( isset( $category->name ) && ! empty( $category->name ) ) {
            $category_data['name'] = $category->name;
        } elseif ( ! isset( $category->name ) || empty( $category->name ) ) {
            throw new AWPCP_Exception( esc_html__( 'The name of the Category is required.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( isset( $category->description ) && ! empty( $category->description ) ) {
            $category_data['description'] = $category->description;
        }

        if ( isset( $category->parent ) && isset( $category->term_id ) && $category->parent === $category->term_id ) {
            throw new AWPCP_Exception( esc_html__( 'The ID of the parent category and the ID of the category must be different.', 'another-wordpress-classifieds-plugin' ) );
        } elseif ( isset( $category->parent ) ) {
            $category_data['parent'] = $category->parent;
        }

        if ( ! is_null( $category_order ) ) {
            $category_data['order'] = intval( $category_order );
        }

        $category_data = apply_filters( 'awpcp-category-data', $category_data, $category );

        return $category_data;
    }

    public function update_category( $category, $category_order = null ) {
        if ( ! isset( $category->term_id ) ) {
            throw new AWPCP_Exception( esc_html__( 'There was an error trying to update a category. The ID of the category is required.', 'another-wordpress-classifieds-plugin' ) );
        }

        $data      = $this->get_category_data( $category, $category_order );
        $term_info = $this->wordpress->update_term( $category->term_id, $this->taxonomy, $data );

        if ( is_wp_error( $term_info ) ) {
            $message = __( 'There was an error trying to update a category: <error-message>.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<error-message>', $term_info->get_error_message(), $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( ! is_null( $data['order'] ) ) {
            $this->wordpress->update_term_meta( $category->term_id, '_awpcp_order', $data['order'] );
        }

        /**
         * @since 3.3
         * TODO: fix handlers now that we pass more parameters
         */
        do_action( 'awpcp-category-edited', $term_info['term_id'], $category );

        return $term_info['term_id'];
    }

    public function move_category( $category, $target_category ) {
        if ( $category->term_id === $target_category->term_id ) {
            $message = __( 'The category to be moved and the target category can not be the same.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $category->parent = $target_category->term_id;

        $this->update_category( $category );
    }

    public function delete_category_moving_listings_to( $category, $target_category ) {
        if ( $category->term_id === $target_category->term_id ) {
            throw new AWPCP_Exception( esc_html__( 'The move-to category and the category that is going to be deleted must be different.', 'another-wordpress-classifieds-plugin' ) );
        }

        // wp_delete_term() moves children terms to the parent of the
        // deleted term. Here we move the category that is going to be deleted
        // to the target category before deleting it, to take advantage
        // of that behaviour.
        $category->parent = $target_category->term_id;
        $this->update_category( $category );

        $category_deleted = $this->wordpress->delete_term(
            $category->term_id,
            $this->taxonomy,
            array( 'default' => $target_category->term_id )
        );

        do_action( 'awpcp-category-deleted', $category );

        return $category_deleted;
    }

    public function delete_category_and_associated_listings( $category, $target_category = null ) {
        if ( is_object( $target_category ) && $category->term_id === $target_category->term_id ) {
            throw new AWPCP_Exception( esc_html__( 'The move-to category and the category that is going to be deleted must be different.', 'another-wordpress-classifieds-plugin' ) );
        }

        $listings = $this->listings->find_listings(
            [
                'tax_query' => [
                    [
                        'taxonomy'         => $this->taxonomy,
                        'field'            => 'term_id',
                        'terms'            => $category->term_id,
                        'include_children' => false,
                    ],
                ],
            ]
        );

        try {
            foreach ( $listings as $listing ) {
                $this->listings_logic->delete_listing( $listing );
            }
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The category couldn't be deleted because there was an error trying to delete one of the associated listings: <error-message>", 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<error-message>', $e->getMessage(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( ! is_null( $target_category ) ) {
            // wp_delete_term() moves children terms to the parent of the
            // deleted term. Here we move the category that is going to be deleted
            // to the target category before deleting it, to take advantage
            // of that behaviour.
            $category->parent = $target_category->term_id;
            $this->update_category( $category );
        }

        $category_deleted = $this->wordpress->delete_term( $category->term_id, $this->taxonomy );

        do_action( 'awpcp-category-deleted', $category );

        return $category_deleted;
    }

    /**
     * @since 4.0.16
     */
    public function update_category_order( $category_id ) {
        if ( ! $category_id ) {
            return;
        }

        $category = get_term( $category_id, AWPCP_CATEGORY_TAXONOMY );

        if ( ! $category ) {
            return;
        }

        $this->update_category( $category );
    }
}
