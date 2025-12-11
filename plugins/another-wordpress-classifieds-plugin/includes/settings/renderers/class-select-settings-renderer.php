<?php
/**
 * @package AWPCP\Settings\Renderers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_SelectSettingsRenderer {

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
    public function render_setting( $setting ) {
        $current = stripslashes( $this->settings->get_option( $setting['id'] ) );

        echo '<select id="' . esc_attr( $setting['id'] ) . '" name="awpcp-options[' . esc_attr( $setting['id'] ) . ']">';

        foreach ( $setting['options'] as $value => $label ) {
            if ( 0 === strcmp( $value, $current ) ) {
                 echo '<option value="' . esc_attr( $value ) . '" selected="selected">' . esc_html( $label ) . '</option>';
            } else {
                echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
            }
        }

        echo '</select><br/>';
        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
