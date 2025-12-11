<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages settings for the plugin and premium modules.
 */
class AWPCP_SettingsManager {

    /**
     * @var array
     */
    private $groups = [];

    /**
     * @var array
     */
    private $subgroups = [];

    /**
     * @var array
     */
    private $sections = [];

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @since 4.0.0
     */
    public function get_settings_groups() {
        return $this->groups;
    }

    /**
     * @since 4.0.0
     */
    public function get_settings_group( $group_id ) {
        if ( isset( $this->groups[ $group_id ] ) ) {
            return $this->groups[ $group_id ];
        }

        return null;
    }

    /**
     * @since 4.0.0
     */
    public function add_settings_group( $params ) {
        $group = wp_parse_args( $params, [
            'id'       => null,
            'name'     => null,
            'priority' => 10,
        ] );

        $group['subgroups'] = [];

        $this->groups[ $params['id'] ] = $group;
    }

    /**
     * @since 4.0.0
     */
    public function get_settings_subgroups() {
        return $this->subgroups;
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If no group was specified or it doesn't exist.
     */
    public function add_settings_subgroup( $params ) {
        $subgroup = wp_parse_args( $params, [
            'id'       => null,
            'name'     => null,
            'priority' => 10,
            'parent'   => null,
        ] );

        if ( empty( $subgroup['parent'] ) ) {
            throw new AWPCP_Exception( esc_html( "No parent specified for settings subgroup {$subgroup['name']}." ) );
        }

        if ( ! isset( $this->groups[ $subgroup['parent'] ] ) ) {
            throw new AWPCP_Exception( esc_html( "Settings group {$subgroup['parent']} doesn't exist." ) );
        }

        $subgroup['sections'] = [];

        $this->groups[ $subgroup['parent'] ]['subgroups'][] = $subgroup['id'];

        $this->subgroups[ $subgroup['id'] ] = $subgroup;
    }

    /**
     * @since 4.0.0
     */
    public function get_settings_sections() {
        return $this->sections;
    }

    /**
     * @since 4.0.0
     */
    public function get_settings_section( $section_id ) {
        if ( isset( $this->sections[ $section_id ] ) ) {
            return $this->sections[ $section_id ];
        }

        return null;
    }

    /**
     * TODO: Provide a replacement for the callback paramater that was available
     * on add_section().
     *
     * @since 4.0.0
     * @throws AWPCP_Exception  If no subgroup was specified or it doesn't exist.
     */
    public function add_settings_section( $params ) {
        $section = wp_parse_args( $params, [
            'id'          => null,
            'name'        => null,
            'priority'    => 10,
            'description' => '',
            'subgroup'    => null,
        ] );

        if ( empty( $section['subgroup'] ) ) {
            throw new AWPCP_Exception( esc_html( "No subgroup specified for settings section {$section['id']}." ) );
        }

        if ( ! isset( $this->subgroups[ $section['subgroup'] ] ) ) {
            throw new AWPCP_Exception( esc_html( "Settings subgroup {$section['subgroup']} doesn't exist." ) );
        }

        $section['settings'] = [];

        $this->subgroups[ $section['subgroup'] ]['sections'][] = $section['id'];

        $this->sections[ $section['id'] ] = $section;
    }

    /**
     * @since 4.0.0
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * @since 4.0.0
     */
    public function get_setting( $setting_id ) {
        if ( isset( $this->settings[ $setting_id ] ) ) {
            return $this->settings[ $setting_id ];
        }

        return [];
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If the section was specified or it doesn't exist.
     */
    public function add_setting( $params ) {
        // Support legacy function signature :(.
        if ( func_num_args() > 1 ) {
            $args  = func_get_args();
            $extra = isset( $args[6] ) ? $args[6] : [];

            $params = array_merge( [
                'id'          => $args[1],
                'name'        => $args[2],
                'type'        => $args[3],
                'default'     => $args[4],
                'description' => isset( $args[5] ) ? $args[5] : '',
                'section'     => $args[0],
            ], $extra );
        }

        $setting = wp_parse_args( $params, [
            'id'          => null,
            'name'        => null,
            'type'        => null,
            'description' => '',
            'default'     => null,
            'validation'  => [],
            'behavior'    => [],
            'section'     => null,
        ] );

        if ( empty( $setting['section'] ) ) {
            throw new AWPCP_Exception( esc_html( "No section specified for setting {$setting['id']}." ) );
        }

        if ( ! isset( $this->sections[ $setting['section'] ] ) ) {
            throw new AWPCP_Exception( esc_html( "Settings section {$setting['section']} doesn't exist." ) );
        }

        $this->sections[ $setting['section'] ]['settings'][] = $setting['id'];

        $this->settings[ $setting['id'] ] = apply_filters( 'awpcp_add_setting', $setting, $setting['id'], $this );
    }

    /**
     * Generates frontend configuration for the given setting.
     */
    public function get_setting_configuration( $setting ) {
        $config = [
            'validation' => [
                'rules'    => [],
                'messages' => [],
            ],
            'behavior'   => $setting['behavior'],
        ];

        foreach ( $setting['validation'] as $validator => $params ) {
            if ( isset( $params['message'] ) ) {
                $config['validation']['messages'][ $validator ] = $params['message'];
                unset( $params['message'] );
            }

            $config['validation']['rules'][ $validator ] = $params;
        }

        return $config;
    }

    /**
     * @since 4.0.0
     */
    public function register_settings() {
        do_action( 'awpcp_register_settings', $this );

        $comparator = function( $element_a, $element_b ) {
            if ( $element_a['priority'] === $element_b['priority'] ) {
                return strcmp( $element_a['name'], $element_b['name'] );
            }

            return $element_a['priority'] - $element_b['priority'];
        };

        uasort( $this->groups, $comparator );
        uasort( $this->subgroups, $comparator );
        uasort( $this->sections, $comparator );
    }

    /**
     * @since 4.0.0
     * @deprecated 4.0.0    Use add_settings_section() instead.
     */
    public function add_section( $group, $name, $slug, $priority, $callback = null ) {
        $this->add_settings_section( [
            'id'       => $slug,
            'name'     => $name,
            'priority' => $priority,
            'callback' => $callback,
            'subgroup' => $group,
        ] );
    }
}
