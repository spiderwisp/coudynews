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
class AWPCP_TextfieldSettingsRenderer {

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
        $value = esc_html( stripslashes( $this->settings->get_option( $setting['id'] ) ) );
        $type  = 'text';

        if ( 'password' === $setting['type'] ) {
            $type = 'password';
        }

        echo '<input id="' . esc_attr( $setting['id'] ) . '" class="regular-text" ';
        echo 'value="' . esc_attr( $value ) . '" type="' . esc_attr( $type ) . '" ';
        echo 'name="awpcp-options[' . esc_attr( $setting['id'] ) . ']" ';

        if ( ! empty( $setting['readonly'] ) ) {
            echo 'disabled="disabled" ';
        }

        if ( ! empty( $config ) ) {
            echo 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '" ';
        }

        echo '/>';
        echo strlen( $setting['description'] ) > 20 ? '<br/>' : '&nbsp;';
        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
