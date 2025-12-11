<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create the CAPTCHA provider selected on the plugin settings.
 */
class AWPCP_CAPTCHAProviderFactory {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings, $request ) {
        $this->settings = $settings;
        $this->request  = $request;
    }

    /**
     * @since 4.0.0
     */
    public function get_captcha_provider() {
        $provider_type = $this->settings->get_option( 'captcha-provider' );

        if ( 'recaptcha' === $provider_type ) {
            return $this->get_recaptcha_v2_provider();
        }

        if ( 'reCAPTCHAv3' === $provider_type ) {
            return $this->get_recaptcha_v3_provider();
        }

        return $this->get_default_captcha_provider();
    }

    /**
     * @since 4.0.0
     */
    private function get_recaptcha_v2_provider() {
        $delegate = new AWPCP_ReCAPTCHAv2( $this->request );

        return $this->get_recaptcha_provider( $delegate );
    }

    /**
     * @since 4.0.0
     */
    private function get_recaptcha_provider( $delegate ) {
        $site_key   = $this->settings->get_option( 'recaptcha-public-key' );
        $secret_key = $this->settings->get_option( 'recaptcha-private-key' );

        return new AWPCP_ReCAPTCHAProvider( $site_key, $secret_key, $delegate );
    }

    /**
     * @since 4.0.0
     */
    private function get_recaptcha_v3_provider() {
        $delegate = new AWPCP_ReCAPTCHAv3( $this->request );

        return $this->get_recaptcha_provider( $delegate );
    }

    /**
     * @since 4.0.0
     */
    private function get_default_captcha_provider() {
        $max = $this->settings->get_option( 'math-captcha-max-number' );

        return new AWPCP_DefaultCAPTCHAProvider( $max );
    }
}
