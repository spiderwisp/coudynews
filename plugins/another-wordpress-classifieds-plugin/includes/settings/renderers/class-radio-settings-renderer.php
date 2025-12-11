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
class AWPCP_RadioSettingsRenderer {

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
        $current = esc_html( stripslashes( $this->settings->get_option( $setting['id'] ) ) );
        $options = array();

        if ( is_array( $setting['options'] ) ) {
            $options = $setting['options'];
        }

        if ( is_callable( $setting['options'] ) ) {
            $options = call_user_func( $setting['options'] );
        }

        foreach ( $options as $key => $label ) {
            $value = $key;

            if ( is_array( $label ) ) {
                $value = $label['value'];
                $label = $label['label'];
            }

            $id = "{$setting['id']}-$key";
            echo '<input id="' . esc_attr( $id ) . '"type="radio" value="' . esc_attr( $value ) . '" ';
            echo 'name="awpcp-options[' . esc_attr( $setting['id'] ) . ']" ';

            if ( 0 === strcmp( (string) $key, $current ) ) {
                echo 'checked="checked"';
            }
            echo '>';
            echo ' <label for="' . esc_attr( $id ) . '">' . wp_kses_post( $label ) . '</label>';

            echo '<br/>';
        }

        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
