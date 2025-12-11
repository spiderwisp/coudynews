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
class AWPCP_TextareaSettingsRenderer {

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
        $value = stripslashes( $this->settings->get_option( $setting['id'] ) );

        echo '<textarea id="' . esc_attr( $setting['id'] ) . '" class="all-options" ';
        echo 'name="awpcp-options[' . esc_attr( $setting['id'] ) . ']">';
        echo esc_html( $value );
        echo '</textarea><br/>';
        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
