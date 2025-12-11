<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.6.1
 */
function awpcp_settings_api() {
    return awpcp()->container['Settings'];
}

/**
 * Allows access to stored values of the plugin's settings.
 */
class AWPCP_Settings_API {

    private $runtime_settings = array();

    public $setting_name = 'awpcp-options';
    public $options = array();

    /**
     * @var AWPCP_SettingsManager
     */
    private $settings_manager;

    public function __construct( $settings_manager ) {
        $this->settings_manager = $settings_manager;

        $this->load();
    }

    public function load() {
        $options = get_option( $this->setting_name );

        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $this->options = $options;
    }

    private function save_settings() {
        return update_option( $this->setting_name, $this->options );
    }

    /**
     * @since 4.0.0     Updated to use Settings Manager.
     */
    public function get_option( $name, $default = '', $reload = false ) {
        if ( $reload ) {
            $this->load();
        }

        if ( isset( $this->options[ $name ] ) ) {
            return $this->prepare_option_value( $name, $this->options[ $name ] );
        }

        $default_value = $this->get_option_default_value( $name );

        if ( ! is_null( $default_value ) ) {
            return $this->prepare_option_value( $name, $default_value );
        }

        return $this->prepare_option_value( $name, $default );
    }

    /**
     * @since 4.0.0
     */
    private function prepare_option_value( $name, $value ) {
        // TODO: Provide a method for filtering options and move there the code below.
        $strip_slashes_from = [
            'awpcpshowtheadlayout',
            'sidebarwidgetaftertitle',
            'sidebarwidgetbeforetitle',
            'sidebarwidgetaftercontent',
            'sidebarwidgetbeforecontent',
            'adsense',
            'displayadlayoutcode',
        ];

        if ( in_array( $name, $strip_slashes_from, true ) ) {
            $value = stripslashes_deep( $value );
        }

        if ( ! is_array( $value ) ) {
            $value = trim( $value );
        }

        return $value;
    }

    /**
     * @since 4.0.0     Updated to use Settings Manager.
     */
    public function get_option_default_value( $name ) {
        $setting = $this->settings_manager->get_setting( $name );

        if ( isset( $setting['default'] ) ) {
            return $setting['default'];
        }

        return null;
    }

    /**
     * @since 3.0.1
     */
    public function get_option_label( $name ) {
        $setting = $this->settings_manager->get_setting( $name );

        if ( isset( $setting['name'] ) ) {
            return $setting['name'];
        }

        return null;
    }

    /**
     * @param $force boolean - true to update unregistered options
     */
    public function update_option( $name, $value, $force = false ) {
        if ( $force || array_key_exists( $name, $this->options ) ) {
            $this->options[ $name ] = $value;
            $this->save_settings();
            return true;
        }

        return false;
    }

    /**
     * @since 3.2.2
     */
    public function set_or_update_option( $name, $value ) {
        $this->options[ $name ] = $value;
        return $this->save_settings();
    }

    /**
     * @since 3.3
     */
    public function option_exists( $name ) {
        return isset( $this->options[ $name ] );
    }

    public function set_runtime_option( $name, $value ) {
        $this->runtime_settings[ $name ] = $value;
    }

    public function get_runtime_option( $name, $default = '' ) {
        if ( isset( $this->runtime_settings[ $name ] ) ) {
            return $this->runtime_settings[ $name ];
        }

        return $default;
    }
}
