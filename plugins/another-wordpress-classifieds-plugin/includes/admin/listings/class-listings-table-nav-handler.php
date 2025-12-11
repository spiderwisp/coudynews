<?php
/**
 * @package AWPCP\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for the tablenav section of the listings table.
 */
class AWPCP_ListingsTableNavHandler {

    /**
     * @var AWPCP_HTML_Renderer
     */
    private $html_renderer;

    /**
     * @param AWPCP_HTML_Renderer $html_renderer  An instance of HTML Renderer.
     * @since 4.0.0
     */
    public function __construct( $html_renderer ) {
        $this->html_renderer = $html_renderer;
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        $selected_category = $this->get_selected_category();

        if ( $selected_category ) {
            $query->query_vars['classifieds_query']['category']                                = $selected_category;
            $query->query_vars['classifieds_query']['include_listings_in_children_categories'] = false;
        }

        $selected_date_filter = $this->get_selected_date_filter();

        if ( $selected_date_filter ) {
            $this->add_date_filter_query_vars( $query, $selected_date_filter );
        }
    }

    /**
     * @since 4.0.0
     */
    private function get_selected_category() {
        return awpcp_get_var( array( 'param' => 'awpcp_category_id', 'sanitize' => 'absint' ) );
    }

    /**
     * @since 4.0.0
     */
    private function get_selected_date_filter() {
        return awpcp_get_var( array( 'param' => 'awpcp_date_filter', 'sanitize' => 'sanitize_key' ) );
    }

    /**
     * @since 4.0.0
     */
    private function add_date_filter_query_vars( $query, $selected_date_filter ) {
        $selected_date_range = $this->get_selected_date_range();

        if ( empty( $selected_date_range['start'] ) || empty( $selected_date_range['end'] ) ) {
            return;
        }

        if ( 'published_date' === $selected_date_filter ) {
            $query->query_vars['date_query'][] = [
                'after'     => $selected_date_range['start'],
                'before'    => $selected_date_range['end'],
                'inclusive' => true,
            ];
        } elseif ( 'renewed_date' === $selected_date_filter ) {
            $this->add_date_range_meta_query( $query, '_awpcp_renewed_date', $selected_date_range );
        } elseif ( 'start_date' === $selected_date_filter ) {
            $this->add_date_range_meta_query( $query, '_awpcp_start_date', $selected_date_range );
        } elseif ( 'end_date' === $selected_date_filter ) {
            $this->add_date_range_meta_query( $query, '_awpcp_end_date', $selected_date_range );
        }
    }

    /**
     * @since 4.0.0
     */
    private function get_selected_date_range() {
        return [
            'start' => trim( awpcp_get_var( array( 'param' => 'awpcp_date_range_start', 'sanitize' => 'sanitize_key' ) ) ),
            'end'   => trim( awpcp_get_var( array( 'param' => 'awpcp_date_range_end', 'sanitize' => 'sanitize_key' ) ) ),
        ];
    }

    /**
     * @since 4.0.0
     */
    private function add_date_range_meta_query( $query, $meta_key, $date_range ) {
        $query->query_vars['meta_query'][] = [
            'relation' => 'AND',
            'after'    => [
                'key'     => $meta_key,
                'value'   => $date_range['start'],
                'compare' => '>=',
                'type'    => 'DATE',
            ],
            'before'   => [
                'key'     => $meta_key,
                'value'   => $date_range['end'],
                'compare' => '<=',
                'type'    => 'DATE',
            ],
        ];
    }

    /**
     * @since 4.0.0
     */
    public function restrict_listings() {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_date_filter() . $this->render_categories_selector();
    }

    /**
     * @since 4.0.0
     */
    public function render_date_filter() {
        $selected_date_filter = $this->get_selected_date_filter();
        $selected_date_range  = $this->get_selected_date_range();

        $date_filter = [
            [
                '#type'       => 'label',
                '#attributes' => [
                    'class' => [
                        'screen-reader-text',
                    ],
                    'for'   => 'awpcp-date-filter',
                ],
                '#content'    => __( 'Select date field', 'another-wordpress-classifieds-plugin' ),
            ],
            [
                '#type'       => 'select',
                '#attributes' => [
                    'id'   => 'awpcp-date-filter',
                    'name' => 'awpcp_date_filter',
                ],
                '#options'    => [
                    ''               => __( 'All dates', 'another-wordpress-classifieds-plugin' ),
                    'start_date'     => __( 'Start Date', 'another-wordpress-classifieds-plugin' ),
                    'end_date'       => __( 'End Date', 'another-wordpress-classifieds-plugin' ),
                    'renewed_date'   => __( 'Renewed Date', 'another-wordpress-classifieds-plugin' ),
                    'published_date' => __( 'Published Date', 'another-wordpress-classifieds-plugin' ),
                ],
                '#value'      => $selected_date_filter,
            ],
            [
                '#type'       => 'label',
                '#attributes' => [
                    'class' => [
                        'screen-reader-text',
                    ],
                    'for'   => 'awpcp-date-range',
                ],
                '#content'    => __( 'Select date range', 'another-wordpress-classifieds-plugin' ),
            ],
            [
                '#type'       => 'input',
                '#attributes' => [
                    'id'           => 'awpcp-date-range',
                    'class'        => $selected_date_filter ? '' : 'awpcp-hidden',
                    'type'         => 'text',
                    'name'         => 'awpcp_date_range_placeholder',
                    'autocomplete' => 'off',
                    'data-locale'  => get_locale(),
                ],
            ],
            [
                '#type'       => 'input',
                '#attributes' => [
                    'type'  => 'hidden',
                    'name'  => 'awpcp_date_range_start',
                    'value' => $selected_date_range['start'],
                ],
            ],
            [
                '#type'       => 'input',
                '#attributes' => [
                    'type'  => 'hidden',
                    'name'  => 'awpcp_date_range_end',
                    'value' => $selected_date_range['end'],
                ],
            ],
        ];

        return $this->html_renderer->render_elements( $date_filter );
    }

    /**
     * @since 4.0.0
     */
    public function render_categories_selector() {
        $selected_category = $this->get_selected_category();

        $params = array(
            'label'       => false,
            'name'        => 'awpcp_category_id',
            'placeholder' => _x( 'All categories', 'category filter placeholder', 'another-wordpress-classifieds-plugin' ),
            'selected'    => $selected_category,
        );

        return awpcp_categories_selector()->render( $params );
    }
}
