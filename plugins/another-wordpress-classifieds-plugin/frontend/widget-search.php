<?php
/**
 * @package AWPCP\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Search_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(false, __( 'AWPCP Search Ads', 'another-wordpress-classifieds-plugin'));
    }

    /**
     * @since 3.0
     */
    protected function defaults() {
        return apply_filters(
            'awpcp-search-listings-widget-defaults',
            array(
                'title' => '',
                'subtitle' => '',
                'show_keyword' => 1,
                'show_by' => 1,
                'show_category' => 1,
            )
        );
    }

    /**
     * @since 3.0.2
     */
    private function render_find_by_contact_name_field() {
        $names = awpcp_listings_meta()->get_meta_values( 'contact_name' );

        if ( empty( $names ) ) {
            $options = array();
        } else {
            $options = array_combine( $names, $names );
        }

        return $this->select(
            $options,
            __( 'Find ads by Contact Name', 'another-wordpress-classifieds-plugin'),
            'searchname',
            awpcp_get_var( array( 'param' => 'searchname', 'default' => null ) ),
            __( 'All Contact Names', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * @since 3.0
     */
    public function select($options, $label, $name, $selected=null, $default=null) {
        $id = 'awpcp-search-' . sanitize_title($label);

        if ( is_null( $default ) ) {
            $default = __( 'Select Option', 'another-wordpress-classifieds-plugin');
        }

        $label = awpcp_html_label(array(
            'text' => $label,
            'attributes' => array(
                'class' => 'awpcp-block-label',
                'for' => $id,
            ),
        ));

        $select = awpcp_html_select(array(
            'attributes' => array(
                'id' => $id,
                'name' => $name,
            ),
            'current-value' => $selected,
            'options' => array_merge( array( '' => $default ), $options ),
        ));

        return $label . $select;
    }

    public function widget( $args, $instance ) {
        extract($args);

        $instance = wp_parse_args($instance, $this->defaults());

        if ( !empty( $instance['subtitle'] ) ) {
            $title = $instance['title'] . '<br/><span class="widgetstitle">' . esc_html( $instance['subtitle'] ) . '</span>';
        } else {
            $title = $instance['title'] . '</span>';
        }

        echo '<div class="awpcp-search-listings-widget">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['before_widget'] . $args['before_title'] . esc_html( $title ) . $args['after_title'];
        echo '<form method="get" action="' . esc_url( url_searchads() ) . '">';

        $url_params = wp_parse_args( wp_parse_url( url_searchads(), PHP_URL_QUERY ) );
        foreach ( $url_params as $param => $value ) {
            echo '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( $value ) . '" />';
        }

        echo '<input type="hidden" name="awpcp-step" value="dosearch"/>';

        $keywordphrase = awpcp_get_var( array( 'param' => 'keywordphrase' ) );

        if ($instance['show_keyword'] == 1) {
            echo '<div class="awpcp-form-field">';
            echo '<label for="awpcp-search-keywordphrase" class="awpcp-block-label">' . esc_html__( 'Search by keyword', 'another-wordpress-classifieds-plugin') . '</label>';
            echo '<input id="awpcp-search-keywordphrase" type="text" name="keywordphrase" value="' . esc_attr($keywordphrase) . '">';
            echo '</div>';
        }

        if ($instance['show_by'] == 1) {
            echo '<div class="awpcp-form-field">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->render_find_by_contact_name_field();
            echo '</div>';
        }

        if ($instance['show_category'] == 1) {
            wp_enqueue_style( 'select2' );

            $label = __( 'Search by Category', 'another-wordpress-classifieds-plugin');
            $name = 'searchcategory';
            $selected = awpcp_get_var( array( 'param' => $name, 'default' => null ) );

            echo '<div class="awpcp-form-field">';
            awpcp_categories_selector()->show(
                array(
                    'context' => 'search',
                    'selected' => $selected,
                    'required' => false,
                    'multiple' => true,
                    'name' => $name,
                    'label' => $label,
                )
            );
            echo '</div>';
        }

        do_action( 'awpcp-search-listings-widget-form-field', $instance );

        echo '<div class="submit"><input class="button" type="submit" value="' . esc_attr__( 'Search', 'another-wordpress-classifieds-plugin' ) . '"></div>';
        echo '</form>';
        echo '</div>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = wp_strip_all_tags( $new_instance['title'] );
        $instance['subtitle'] = wp_strip_all_tags( $new_instance['subtitle'] );
        $instance['show_keyword'] = absint( $new_instance['show_keyword'] );
        $instance['show_by'] = absint( $new_instance['show_by'] );
        $instance['show_category'] = absint( $new_instance['show_category'] );
        return apply_filters( 'awpcp-search-listings-widget-update-widget', $instance, $new_instance );
    }

    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, $this->defaults() );

        $title         = wp_strip_all_tags( $instance['title'] );
        $subtitle      = wp_strip_all_tags( $instance['subtitle'] );
        $show_keyword  = absint( $instance['show_keyword'] );
        $show_by       = absint( $instance['show_by'] );
        $show_category = absint( $instance['show_category'] );

        $additional_fields = apply_filters( 'awpcp-search-listings-widget-additional-form-fields', '', $instance, $this );

        include(AWPCP_DIR . '/frontend/templates/widget-search-form.tpl.php');
        return '';
    }
}
