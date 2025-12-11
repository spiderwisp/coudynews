<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles integration between plugin settings and WordPress Settings API.
 */
class AWPCP_SettingsIntegration {

    /**
     * @var array
     */
    private $page_hooks;

    /**
     * @var AWPCP_SettingsManager
     */
    private $settings_manager;

    /**
     * @var AWPCP_SettingsValidator
     */
    private $settings_validator;

    /**
     * @var AWPCP_SettingsRenderer
     */
    private $settings_renderer;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $page_hooks, $settings_manager, $settings_validator, $settings_renderer, $settings ) {
        $this->page_hooks         = $page_hooks;
        $this->settings_manager   = $settings_manager;
        $this->settings_validator = $settings_validator;
        $this->settings_renderer  = $settings_renderer;
        $this->settings           = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function setup() {
        register_setting(
            $this->settings->setting_name,
            $this->settings->setting_name,
            [
                'sanitize_callback' => [ $this->settings_validator, 'sanitize_settings' ],
            ]
        );

        foreach ( $this->page_hooks as $page_hook ) {
            add_action( $page_hook, [ $this, 'add_settings_sections' ] );
        }
    }

    /**
     * @since 4.0.0
     */
    public function add_settings_sections() {
        foreach ( $this->settings_manager->get_settings_groups() as $group ) {
            $this->add_settings_sections_for_group( $group );
        }
    }

    /**
     * @since 4.0.0
     */
    private function add_settings_sections_for_group( $group ) {
        $subgroups = $this->settings_manager->get_settings_subgroups();

        foreach ( $group['subgroups'] as $subgroup_id ) {
            $this->add_settings_sections_for_subgroup( $subgroups[ $subgroup_id ] );
        }
    }

    /**
     * @since 4.0.0
     */
    private function add_settings_sections_for_subgroup( $subgroup ) {
        $sections = $this->settings_manager->get_settings_sections();

        // $sections is sorted by priority, $subgroup['sections'] is not.
        $sections_ids = array_intersect( array_keys( $sections ), $subgroup['sections'] );

        foreach ( $sections_ids as $section_id ) {
            $this->add_settings_sections_for_section( $sections[ $section_id ] );
        }
    }

    /**
     * @since 4.0.0
     */
    private function add_settings_sections_for_section( $section ) {
        add_settings_section(
            $section['id'],
            $section['name'],
            [ $this->settings_renderer, 'render_settings_section' ],
            $section['subgroup']
        );

        foreach ( $section['settings'] as $setting_id ) {
            $setting = $this->settings_manager->get_setting( $setting_id );

            if ( ! $this->settings_renderer->is_renderer_available( $setting ) ) {
                continue;
            }

            $css_class = str_replace( '_', '-', "awpcp-setting-{$setting['id']}" );

            add_settings_field(
                $setting['id'],
                $setting['name'],
                [ $this->settings_renderer, 'render_setting' ],
                $section['subgroup'],
                $section['id'],
                [
                    'setting_id' => $setting_id,
                    'class'      => implode( ' ', [ $css_class, 'awpcp-settings-row' ] ),
                ]
            );
        }
    }
}
