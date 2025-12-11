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
class AWPCP_ButtonSettingsRenderer {

    /**
     * @since 4.0.0
     */
    public function render_setting( $setting, $config ) {
        add_thickbox();
        echo '<a href="#TB_inline?&width=500&height=130&modal=true&inlineId=pop-' . esc_attr( $setting['id'] ) . '" class="button-secondary thickbox"';

        if ( ! empty( $config ) ) {
            echo 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '" ';
        }

        $nonce = wp_create_nonce( 'reset-default' );
        echo '>' . esc_html( $setting['default'] ) . '</a>';
        echo strlen( $setting['description'] ) > 20 ? '<br/>' : '&nbsp;';
        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
        echo '<div id="pop-' . esc_attr( $setting['id'] ) . '" style="display:none">';
        echo '<h2 style="text-align: center">';
        esc_html_e( 'Resetting to the default layout will cause any custom HTML layout changes you\'ve made to be lost. Are you sure?', 'another-wordpress-classifieds-plugin' );
        echo '</h2>';
        echo '<p style="text-align: center">';
        echo '<button  class="button-secondary TB_closeWindowButton">';
        esc_html_e( 'Cancel', 'another-wordpress-classifieds-plugin' );
        echo '</button> ';
        echo '<button data-nonce="' . esc_attr( $nonce ) . '" id="' . esc_attr( $setting['id'] ) . '" class="button-primary">';
        esc_html_e( 'Reset', 'another-wordpress-classifieds-plugin' );
        echo '</button>';
        echo '</p>';
        echo '</div>';
    }
}
