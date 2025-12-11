<?php
/**
 * @package AWPCP\Settings\Renderers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Allows user to select WordPress pages as the value for plugin settings.
 */
class AWPCP_WordPressPageSettingsRenderer {

    public $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Handler for awpcp_register_settings action.
     */
    public function render_setting( $setting ) {
        $dropdown_params = array(
            'name'              => $this->settings->setting_name . '[' . $setting['id'] . ']',
            'selected'          => $this->settings->get_option( $setting['id'], 0 ),
            'show_option_none'  => _x( '— Select —', 'page settings', 'another-wordpress-classifieds-plugin' ),
            'option_none_value' => 0,
            'echo'              => false,
        );

        printf(
            /* translators: %1$s is a dropdown with existing pages, %2$s is a link to create a new page */
            esc_html_x( 'Select existing page %1$s -or- %2$s', 'page settings', 'another-wordpress-classifieds-plugin' ),
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_dropdown_pages( $dropdown_params ),
            '<a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=page' ) ) . '">' .
                esc_html__( 'Create Page', 'another-wordpress-classifieds-plugin' ) .
                '</a>'
        );

        echo '<br/>';

        printf(
            '<span class="description">%s</span>',
            wp_kses_post( $setting['description'] )
        );
    }
}
