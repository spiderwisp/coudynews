<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_categories_collection() {
    return new AWPCP_Categories_Collection(
        'awpcp_listing_category',
        awpcp_categories_registry(),
        awpcp_wordpress()
    );
}

class AWPCP_Categories_Collection {

    /**
     * @var string
     */
    private $taxonomy;

    /**
     * @var AWPCP_Categories_Registry
     */
    private $categories_registry;

    private $wordpress;

    /**
     * @param string $taxonomy  The name of the listings category taxonomy.
     */
    public function __construct( $taxonomy, $categories_registry, $wordpress ) {
        $this->taxonomy            = $taxonomy;
        $this->categories_registry = $categories_registry;
        $this->wordpress           = $wordpress;
    }

    /**
     * @since 4.0.0
     */
    public function get( $category_id ) {
        $category_id = $this->sanitize_category_id( $category_id );
        $category    = $this->get_category_by_id( $category_id );

        if ( $category === false || is_null( $category ) ) {
            /* translators: %d the category id */
            $message = __( 'No category was found with ID: %d', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $category_id ) ) );
        }

        return $this->prepare_category_object( $category );
    }

    /**
     * @since 4.0.0
     */
    private function sanitize_category_id( $category_id ) {
        if ( $category_id <= 0 ) {
            $message = __( 'The category ID must be a positive integer, {category_id} was given.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{category_id}', $category_id, $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return absint( $category_id );
    }

    /**
     * @since 4.0.0
     */
    private function get_category_by_id( $category_id ) {
        return $this->wordpress->get_term_by( 'id', $category_id, $this->taxonomy );
    }

    /**
     * @since 4.0.0
     */
    private function prepare_category_object( $result ) {
        $results = $this->prepare_categories_objects( [ $result ] );

        return $results[0];
    }

    /**
     * @since 4.0.0
     */
    private function prepare_categories_objects( $results ) {
        foreach ( $results as $term ) {
            $term->term_id = absint( $term->term_id );
        }

        return $results;
    }

    /**
     * We need to support OLD category's IDs for a while, in order to maintain
     * old shortcodes and URLs working.
     *
     * @since 4.0.0
     */
    public function get_category_with_old_id( $old_category_id ) {
        $old_category_id = $this->sanitize_category_id( $old_category_id );
        $new_categories  = $this->categories_registry->get_categories_registry();

        if ( ! isset( $new_categories[ $old_category_id ] ) ) {
            $message = __( 'No category was found with old ID: {old_category_id}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{old_category_id}', $old_category_id, $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $category = $this->get_category_by_id( $new_categories[ $old_category_id ] );

        if ( $category === false ) {
            $message = __( 'No category was found with ID: {category_id}, the replacement for category with old ID: {old_category_id}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{category_id}', $new_categories[ $old_category_id ], $message );
            $message = str_replace( '{old_category_id}', $old_category_id, $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return $this->prepare_category_object( $category );
    }

    public function get_category_by_name( $name ) {
        if ( empty( $name ) ) {
            /* translators: %s the submitted value */
            $message = __( 'The category name must be a non empty string, %s was given.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $name ) ) );
        }

        // See: https://core.trac.wordpress.org/ticket/11311#comment:14
        $sanitized_name = sanitize_term_field( 'name', $name, 0, $this->taxonomy, 'db' );
        $category = $this->wordpress->get_term_by( 'name', $sanitized_name, $this->taxonomy );

        if ( $category === false || is_null( $category ) ) {
            /* translators: %s is the category name */
            $message = __( 'No category was found with name: %s.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $name ) ) );
        }

        return $this->prepare_category_object( $category );
    }

    /**
     * @since 4.0.0
     */
    public function get_all() {
        return $this->find_categories();
    }

    public function find_categories( $args = array() ) {
        $results = $this->wordpress->get_terms( $this->prepare_categories_args( $args ) );

        if ( is_wp_error( $results ) ) {
            return array();
        }

        // If the results is an array of term objects, process each object first.
        if ( count( $results ) && property_exists( current( $results ), 'term_id' ) ) {
            return $this->prepare_categories_objects( $results );
        }

        return $results;
    }

    /**
     * @since 4.0.0
     */
    private function prepare_categories_args( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'meta_query' => [],
        ) );

        if ( ! isset( $args['orderby'] ) ) {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_awpcp_order';
        }

        return $args;
    }

    public function count_categories( $args = array() ) {
        $args = array_merge( $this->prepare_categories_args( $args ), array( 'fields' => 'count' ) );

        if ( 'meta_value_num' === $args['orderby'] && '_awpcp_order' === $args['meta_key'] ) {
            unset( $args['meta_key'] );
        }

        unset( $args['orderby'], $args['order'] );

        return intval( $this->wordpress->get_terms( $args ) );
    }

    public function get_hierarchy() {
        return $this->wordpress->get_term_hierarchy( $this->taxonomy );
    }

    public function find_by_listing_id( $listing_id ) {
        return $this->wordpress->get_object_terms( $listing_id, $this->taxonomy );
    }

    public function find_top_level_categories() {
        $categories = $this->find_categories();

        foreach ( array_keys( $categories ) as $index ) {
            if ( $categories[ $index ]->parent != 0 ) {
                unset( $categories[ $index ] );
            }
        }

        return $categories;
    }

    /**
     * @since 4.0.16
     */
    public function maybe_update_categories_order() {
        $total_count = $this->count_categories();
        $args = $this->prepare_categories_args();

        if ( 'meta_value_num' === $args['orderby'] && '_awpcp_order' === $args['meta_key'] ) {
            unset( $args['meta_key'] );
        }

        $ordered_count = intval( $this->wordpress->get_terms(
            array_merge(
                $args,
                array( 'fields' => 'count' )
            )
        ) );

        if ( $ordered_count < $total_count ) {
            unset( $args['orderby'], $args['order'] );

            $categories = $this->wordpress->get_terms( $args );

            if ( ! $categories ) {
                return;
            }

            foreach ( $categories as $term ) {
                $cat_order = get_term_meta( $term->term_id, '_awpcp_order', true );

                if ( $cat_order || 0 === $cat_order ) {
                    continue;
                }

                $this->wordpress->update_term_meta( $term->term_id, '_awpcp_order', 0 );
            }
        }
    }
}
