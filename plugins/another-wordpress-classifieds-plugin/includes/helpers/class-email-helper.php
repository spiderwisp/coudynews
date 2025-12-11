<?php
/**
 * @package AWPCP\Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Prepares Email instances loading content from email-template setting.
 */
class AWPCP_EmailHelper {

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
     * @throws AWPCP_Exception  If specified setting doesn't exist or the value
     *                          is not properly formatted.
     */
    public function prepare_email_from_template_setting( $setting, $replacement ) {
        $template = $this->settings->get_option( $setting );

        if ( ! isset( $template['subject'] ) || ! isset( $template['body'] ) ) {
            throw new AWPCP_Exception( 'Email template not found or invalid.' );
        }

        foreach ( $replacement as $name => $value ) {
            $template['subject'] = str_replace( '{' . $name . '}', $value, $template['subject'] );
            $template['body']    = str_replace( '{' . $name . '}', $value, $template['body'] );
        }

        $email = new AWPCP_Email();

        $email->subject = $template['subject'];
        $email->body    = $template['body'];

        return $email;
    }
}
