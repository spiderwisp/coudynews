<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implementation for reCAPTCHA integration.
 *
 * The specifics of v2 and v3 types of integration are handled separately
 * through delegate objects.
 */
class AWPCP_ReCAPTCHAProvider implements AWPCP_CAPTCHAProviderInterface {

    /**
     * @var string
     */
    private $site_key;

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var AWPCP_ReCAPTCHAv2|AWPCP_ReCAPTCHAv3 Delegate
     */
    private $delegate;

    /**
     * @var bool
     */
    private $echo = false;

    public function __construct( $site_key, $secret_key, $delegate ) {
        $this->site_key   = $site_key;
        $this->secret_key = $secret_key;
        $this->delegate   = $delegate;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show() {
        $this->echo = true;
        $this->render();
        $this->echo = false;
    }

    public function render() {
        if ( empty( $this->site_key ) ) {
            return $this->missing_key_message();
        }

        $this->delegate->enqueue_scripts( $this->site_key );

        if ( $this->echo ) {
            $this->delegate->show_recaptcha( $this->site_key );
            return;
        }

        return $this->delegate->get_recaptcha_html( $this->site_key );
    }

    private function missing_key_message() {
        /* translators: %s will become an A HTML tag pointing to reCAPTCHA admin console. */
        $message = __( 'To use reCAPTCHA you must get an API key from %s.', 'another-wordpress-classifieds-plugin' );
        $link    = sprintf( '<a href="%1$s">%1$s</a>', 'https://www.google.com/recaptcha/admin' );

        return sprintf( $message, $link );
    }

    public function validate() {
        if ( empty( $this->secret_key ) ) {
            throw new AWPCP_Exception( esc_html( $this->missing_key_message() ) );
        }

        $response = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'body' => [
                    'secret'   => $this->secret_key,
                    'response' => $this->delegate->get_recaptcha_response(),
                    filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ),
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            $message = $this->delegate->get_verification_error_message( $response->get_error_message() );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $json = json_decode( $response['body'], true );

        if ( isset( $json['error-codes'] ) ) {
            $error_message = $this->delegate->process_error_codes( $json['error-codes'] );
            $message       = $this->delegate->get_verification_error_message( $error_message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( empty( $json['success'] ) ) {
            $message = __( "Your answers couldn't be verified by the reCAPTCHA server.", 'another-wordpress-classifieds-plugin' );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return $this->delegate->handle_successful_response( $json );
    }
}
