<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_CategoriesWidget extends WP_Widget {

    public function __construct() {
        $description = __( 'Displays a list of Ad categories.', 'another-wordpress-classifieds-plugin');
        parent::__construct( 'awpcp-categories', __( 'AWPCP Categories', 'another-wordpress-classifieds-plugin' ), array('description' => $description));
    }

    protected function defaults() {
        $defaults = array(
            'title' => __( 'Ad Categories', 'another-wordpress-classifieds-plugin'),
            'hide-empty' => 0,
            'show-parents-only' => 0,
            'show-ad-count' => get_awpcp_option( 'showadcount' ),
        );

        return $defaults;
    }

    public function widget($args, $instance) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['before_widget'];

        // do not show empty titles
        $title = apply_filters('widget_title', $instance['title']);
        if ( ! empty( $title ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $params = array(
            'show_empty_categories' => $instance['hide-empty'] ? false : true,
            'show_children_categories' => $instance['show-parents-only'] ? false : true,
            'show_listings_count' => $instance['show-ad-count'],
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_categories_renderer_factory()->create_list_renderer()->render( $params );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['after_widget'];
    }

    public function form($instance) {
        $instance = array_merge($this->defaults(), $instance);
        include(AWPCP_DIR . '/frontend/templates/widget-categories-form.tpl.php');
        return '';
    }

    public function update($new_instance, $old_instance) {
        $instance['title'] = wp_strip_all_tags( $new_instance['title'] );
        $instance['hide-empty'] = intval( $new_instance['hide-empty'] );
        $instance['show-parents-only'] = intval( $new_instance['show-parents-only'] );
        $instance['show-ad-count'] = intval( $new_instance['show-ad-count'] );
        return $instance;
    }
}
