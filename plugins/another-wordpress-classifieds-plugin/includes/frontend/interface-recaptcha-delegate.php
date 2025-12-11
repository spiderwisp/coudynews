<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface classes handling the specifics of reCAPTCHA implementations.
 */
interface AWPCP_ReCAPTCHADelegate {

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts( $site_key );

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_recaptcha( $site_key );

    /**
     * @since 4.0.0
     */
    public function get_recaptcha_html( $site_key );

    /**
     * @since 4.0.0
     */
    public function get_recaptcha_response();

    /**
     * @since 4.0.0
     */
    public function get_verification_error_message( $error_message );

    /**
     * @since 4.0.0
     */
    public function process_error_codes( array $error_codes );

    /**
     * @since 4.0.0
     */
    public function handle_successful_response( $response );
}
