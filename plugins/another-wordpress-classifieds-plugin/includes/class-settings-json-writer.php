<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Settings_JSON_Writer.
 */
function awpcp_settings_json_writer() {
    return new AWPCP_Settings_JSON_Writer( awpcp_settings_api() );
}

/**
 * A class that updates the plugin's settings using the data
 * contained in a JSON file.
 */
class AWPCP_Settings_JSON_Writer {

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
     * Updates the plugins settings using the content of the
     * JSON file specified by $filename.
     *
     * @param string $filename Path to a JSON file with plugin settings.
     *
     * @throws AWPCP_Exception When the file cannot be read.
     */
    public function write( $filename ) {
        $json_content = file_get_contents( $filename ); // @codingStandardsIgnoreLine

        if ( false === $json_content ) {
            $message = __( 'There was a problem reading the content of the file. Please try again or contact customer support with a copy of the file.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $settings = json_decode( $json_content, true );

        if ( is_null( $settings ) ) {
            $message = __( 'There was a problem reading the content of the file. Are sure the file contains a JSON representation of your settings? Please try again or contact customer support with a copy of your file.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        foreach ( $settings as $name => $value ) {
            $this->settings->set_or_update_option( $name, $value );
        }
    }
}
