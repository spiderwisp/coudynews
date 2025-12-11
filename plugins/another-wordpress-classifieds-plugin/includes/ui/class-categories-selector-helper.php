<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_categories_selector_helper() {
    return new AWPCP_Categories_Selector_Helper();
}

class AWPCP_Categories_Selector_Helper {

    public function get_params( $params = array() ) {
        $hide_empty_categories = awpcp_get_option( 'hide-empty-categories-dropdown' );

        $params = wp_parse_args( $params, array(
            'context' => 'default',
            'name' => 'category',
            'label' => __( 'Ad Category', 'another-wordpress-classifieds-plugin' ),
            'placeholder'   => null,
            'required' => true,
            'selected' => null,
            'multiple' => false,
            'auto'          => true,
            'hide_empty' => awpcp_parse_bool( $hide_empty_categories ),
            'disable_parent_categories' => false,
            'mode'          => 'basic',
            'payment_terms' => array(),
        ) );

        if ( $params['multiple'] ) {
            $params['name'] = $params['name'] . '[]';
        }

        if ( ! is_array( $params['selected'] ) && ! empty( $params['selected'] ) ) {
            $params['selected'] = array( $params['selected'] );
        } elseif ( ! is_array( $params['selected'] ) ) {
            $params['selected'] = array();
        }

        return $params;
    }

    /**
     * @since 4.0.0     Replaced $hide_empty boolean parameter with optional callable $filter
     *                  $parameter.
     * @since 4.0.0     Added optional callable $callback parameter.
     */
    public function build_categories_hierarchy( $categories, $filter = null, $callback = null ) {
        return awpcp_build_categories_hierarchy( $categories, $filter, $callback );
    }

    /**
     * @since 4.0.0
     */
    public function build_non_empty_categories_hierarchy( $categories, $callback = null ) {
        return awpcp_build_non_empty_categories_hierarchy( $categories, $callback );
    }

    public function get_categories_parents( $categories, &$all_categories ) {
        $categories_parents = array();
        $all_categories_parents = array();

        foreach ( $all_categories as $item ) {
            $all_categories_parents[ $item->term_id ] = $item->parent;
        }

        foreach ( $categories as $category_id ) {
            $categories_parents[] = $this->get_category_parents(
                $category_id, $all_categories_parents
            );
        }

        return $categories_parents;
    }

    private function get_category_parents( $category_id, &$all_categories_parents ) {
        $category_parents = array();

        $parent_id = $category_id;
        while ( $parent_id != 0 && isset( $all_categories_parents[ $parent_id ] ) ) {
            $category_parents[] = $parent_id;
            $parent_id          = $all_categories_parents[ $parent_id ];
        }

        return array_reverse( $category_parents );
    }
}
