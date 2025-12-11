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
class AWPCP_ChoiceSettingsRenderer {

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
        $field_name = 'awpcp-options[' . $setting['id'] . '][]';
        $field_type = 'checkbox';

        if ( isset( $setting['multiple'] ) && empty( $setting['multiple'] ) ) {
            $field_type = 'radio';
        }

        // Selected values are stored as strings, but can be returned as integers
        // when the default value of the setting is returned by get_option().
        $selected = array_filter( array_map( 'strval', $this->settings->get_option( $setting['id'], array() ) ), 'strlen' );

        printf( '<input type="hidden" name="%s" value="">', esc_attr( $field_name ) );

        foreach ( $setting['choices'] as $value => $label ) {
            $id = "{$setting['id']}-$value";

            // Options values ($selected) are retrieved as strings.
            $checked = in_array( (string) $value, $selected, true ) ? 'checked' : '';

            echo '<input id="' . esc_attr( $id ) . '" ' .
                'type="' . esc_attr( $field_type ) . '" ' .
                'name="' . esc_attr( $field_name ) . '" ' .
                'value="' . esc_attr( $value ) . '" ' .
                esc_attr( $checked ) .
                '>';

            echo '&nbsp;';
            echo '<label for="' . esc_attr( $id ) . '">' . wp_kses_post( $label ) . '</label><br/>';
        }

        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
