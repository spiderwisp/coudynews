<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Category Shortcode class.
 */
function awpcp_category_shortcode() {
    return new AWPCP_CategoryShortcode();
}

class AWPCP_CategoryShortcode {

    private $categories_renderer_factory;
    private $categories;

    public function __construct() {
        $this->categories_renderer_factory = awpcp_categories_renderer_factory();
        $this->categories                  = awpcp_categories_collection();
    }

    public function render( $attrs ) {
        $attrs = $this->get_shortcode_attrs( $attrs );

        $output = apply_filters( 'awpcp-category-shortcode-content-replacement', null, $attrs );

        if ( is_null( $output ) ) {
            return $this->render_shortcode_content( $attrs );
        }

        return $output;
    }

    private function get_shortcode_attrs( $attrs ) {
        if ( ! isset( $attrs['show_categories_list'] ) && isset( $attrs['children'] ) ) {
            $attrs['show_categories_list'] = $attrs['children'];
        }

        $attrs = shortcode_atts(
            array(
                'id'                   => 0,
                'children'             => true,
                'items_per_page'       => null,
                'show_categories_list' => true,
            ),
            $attrs
        );

        $attrs['children']             = awpcp_parse_bool( $attrs['children'] );
        $attrs['show_categories_list'] = awpcp_parse_bool( $attrs['show_categories_list'] );

        // $attrs['id'] must be an array at the end. If that's not the case anymore, please
        // update render_categories_list() and get_categories() to handle single values.
        if ( strpos( $attrs['id'], ',' ) ) {
            $attrs['id'] = explode( ',', $attrs['id'] );
        } else {
            $attrs['id'] = array( $attrs['id'] );
        }

        return $attrs;
    }

    private function render_shortcode_content( $attrs ) {
        wp_enqueue_style( 'select2' );

        $categories = $this->get_categories( $attrs['id'] );

        if ( ! $categories ) {
            return __( 'No category was found with the IDs provided. Please provide at least one valid category ID.', 'another-wordpress-classifieds-plugin' );
        }

        $categories_ids = wp_list_pluck( $categories, 'term_id' );
        $options        = [];

        if ( $attrs['show_categories_list'] ) {
            $options = array(
                'before_pagination' => array(
                    10 => array(
                        'categories-list' => $this->render_categories_list( $categories_ids ),
                    ),
                ),
            );
        }

        // TODO: Is the include_listings_in_children_categories parameter supported?
        $query = [
            'classifieds_query' => [
                'context'                                 => 'public-listings',
                'category'                                => $categories_ids,
                'include_listings_in_children_categories' => $attrs['children'],
            ],
            'orderby'           => get_awpcp_option( 'groupbrowseadsby' ),
        ];

        $posts_per_page = awpcp_get_var( array( 'param' => 'results' ) );

        if ( $posts_per_page ) {
            $query['posts_per_page'] = $posts_per_page;
        } elseif ( isset( $attrs['items_per_page'] ) ) {
            $query['posts_per_page'] = $attrs['items_per_page'];
        }

        // Required so awpcp_display_ads shows the name of the current category.
        if ( count( $categories_ids ) === 1 ) {
            $_REQUEST['awpcp_category_id'] = $categories_ids[0];
        }

        return awpcp_display_listings_in_page( $query, 'category-shortcode', $options );
    }

    /**
     * @since 4.0.0
     */
    private function get_categories( $categories_ids ) {
        $categories = [];

        foreach ( $categories_ids as $category_id ) {
            try {
                try {
                    $categories[] = $this->categories->get( $category_id );
                } catch ( AWPCP_Exception $e ) {
                    $categories[] = $this->categories->get_category_with_old_id( $category_id );
                }
            } catch ( AWPCP_Exception $e ) {
                continue;
            }
        }

        // This method must return an array. If that's not the case anymore, please
        // update render_categories_list() to handle single values.
        return $categories;
    }

    private function render_categories_list( array $categories_ids ) {

        $categories_list_params['category_id'] = $categories_ids;
        // Display the categories.
        $categories_list_params['show_in_columns']          = get_awpcp_option( 'view-categories-columns' );
        $categories_list_params['show_empty_categories']    = get_awpcp_option( 'hide-empty-categories' );
        $categories_list_params['show_children_categories'] = true;
        $categories_list_params['show_listings_count']      = get_awpcp_option( 'showadcount' );
        $categories_list_params['show_sidebar']             = true;

        $categories_renderer = $this->categories_renderer_factory->create_list_renderer();

        return $categories_renderer->render( $categories_list_params );
    }
}
