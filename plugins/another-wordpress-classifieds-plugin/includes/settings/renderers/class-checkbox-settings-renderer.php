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
class AWPCP_CheckboxSettingsRenderer {

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
    public function render_setting( $setting, $config ) {
        echo '<input type="hidden" value="0" name="awpcp-options[' . esc_attr( $setting['id'] ) . ']" ';

        if ( ! empty( $config ) ) {
            echo 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '"';
        }
        echo '>';

        echo '<input id="' . esc_attr( $setting['id'] ) . '" value="1" ';
        echo 'type="checkbox" name="awpcp-options[' . esc_attr( $setting['id'] ) . ']" ';

        $value = intval( $this->settings->get_option( $setting['id'] ) );
        if ( $value ) {
            echo 'checked="checked" ';
        }

        if ( ! empty( $config ) ) {
            echo 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '"';
        }
        echo '>';

        echo '<label for="' . esc_attr( $setting['id'] ) . '">';
        echo '&nbsp;<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
        echo '</label>';
    }
}
