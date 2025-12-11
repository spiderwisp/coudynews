<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ImageRenderer {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function render_attachment_thumbnail( $attachment_id, $attributes = [] ) {
        if ( ! isset( $attributes['sizes'] ) ) {
            $attributes['sizes'] = $this->calculate_thumbnail_image_sizes();
        }

        return wp_get_attachment_image( $attachment_id, 'awpcp-thumbnail', false, $attributes );
    }

    /**
     * @since 4.0.0
     */
    private function calculate_thumbnail_image_sizes() {
        $default_size = $this->get_thumbnail_default_size();

        return "(max-width: 767px) 100vw, (max-width: 1023px) 33.33vw, $default_size";
    }

    /**
     * @since 4.0.0
     */
    private function get_thumbnail_default_size() {
        $thumbnails_per_row = intval( $this->settings->get_option( 'display-thumbnails-in-columns' ) );

        if ( $thumbnails_per_row ) {
            return round( 100 / $thumbnails_per_row, 2 ) . 'vw';
        }

        $image_sizes = wp_get_additional_image_sizes();

        if ( ! isset( $image_sizes['awpcp-thumbnail'] ) ) {
            return $this->settings->get_option( 'imgthumbwidth' ) . 'px';
        }

        return "{$image_sizes['awpcp-thumbnail']['width']}px";
    }
}
