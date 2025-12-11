<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Settings_JSON_Reader.
 */
function awpcp_settings_json_reader() {
    return new AWPCP_Settings_JSON_Reader( awpcp_settings_api() );
}

/**
 * A class that returns a JSON representation of the plugin's settings.
 */
class AWPCP_Settings_JSON_Reader {

    /**
     * @var object
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param object $settings An instance of SettingsAPI.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Returns a string representing a JSON object with all the plugin's
     * settings as properties.
     *
     * @return string
     */
    public function read_all() {
        return wp_json_encode( $this->settings->options, JSON_PRETTY_PRINT );
    }
}
