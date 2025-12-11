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
class AWPCP_CategoriesSettingsRenderer {

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
        $params = array(
            'field_name'           => 'awpcp-options[' . esc_attr( $setting['id'] ) . ']',
            'selected'             => $this->settings->get_option( $setting['id'] ),
            'first_level_ul_class' => 'awpcp-categories-list',
            'no-cache'             => time(),
        );

        $checklist = awpcp_categories_checkbox_list_renderer()->render( $params );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        printf( '<div class="cat-checklist category-checklist">%s</div>', $checklist );
        echo '<span class="description">' . wp_kses_post( $setting['description'] ) . '</span>';
    }
}
