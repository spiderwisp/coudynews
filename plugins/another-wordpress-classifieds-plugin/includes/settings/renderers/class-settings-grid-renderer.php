<?php
/**
 * @package AWPCP\Settings\Renderers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders a grid of settings.
 */
class AWPCP_SettingsGridRenderer {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_SettingsManager
     */
    private $settings_manager;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings, $settings_manager, $template_renderer ) {
        $this->settings          = $settings;
        $this->settings_manager  = $settings_manager;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function render_setting( $setting ) {
        foreach ( $setting['rows'] as $key => $values ) {
            for ( $i = count( $values ) - 1; $i > 0; $i-- ) {
                $inner_setting = $this->settings_manager->get_setting( $values[ $i ] );

                if ( ! $inner_setting ) {
                    continue;
                }

                $setting['rows'][ $key ][ $i ] = [
                    'id'                 => "$key-{$values[ $i ]}",
                    'label'              => $inner_setting['name'],
                    'name'               => $this->settings->setting_name . '[' . $values[ $i ] . ']',
                    'value'              => $this->settings->get_option( $values[ $i ] ),
                    'screen_reader_text' => $this->generate_screen_reader_text( $setting, $key, $i ),
                ];
            }
        }

        $template = '/admin/settings/settings-grid.tpl.php';

        $params = [
            'columns'           => $setting['columns'],
            'rows'              => $setting['rows'],
            'number_of_columns' => count( $setting['columns'] ),
            'echo'              => true,
        ];

        echo '</td></tr>';
        echo '<tr class="awpcp-setting-' . esc_attr( str_replace( '_', '-', $setting['id'] ) ) . '-grid awpcp-settings-grid awpcp-settings-row">';
        echo '<td colspan="2">';
        $this->template_renderer->render_template( $template, $params );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo apply_filters( "awpcp_after_settings_grid_{$setting['id']}", '', $setting );
    }

    /**
     * @since 4.0.0
     */
    private function generate_screen_reader_text( $setting, $row_key, $column ) {
        /* translators: screen reader text label for individual settings on a settings grid */
        $label = __( '<setting-name> setting for <field-name> field', 'another-wordpress-classifieds-plugin' );

        $label = str_replace( '<setting-name>', $setting['columns'][ $column ], $label );
        $label = str_replace( '<field-name>', $setting['rows'][ $row_key ][0], $label );

        return $label;
    }
}
