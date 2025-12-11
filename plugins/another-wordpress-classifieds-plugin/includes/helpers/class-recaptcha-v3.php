<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integration with reCAPTCHA v3.
 */
class AWPCP_ReCAPTCHAv3 implements AWPCP_ReCAPTCHADelegate {

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @var bool
     */
    protected $echo = false;

    /**
     * @since 4.0.0
     */
    public function __construct( $request ) {
        $this->request = $request;
    }

    /**
     * @since 3.9.4
     */
    public function enqueue_scripts( $site_key ) {
        $url = 'https://www.google.com/recaptcha/api.js?onload={callback}&render={site_key}';
        $url = str_replace( '{callback}', 'AWPCPreCAPTCHAonLoadCallback', $url );
        $url = str_replace( '{site_key}', $site_key, $url );

        wp_enqueue_script( 'awpcp-recaptcha', $url, [ 'awpcp' ], 'v3', true );
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_recaptcha( $site_key ) {
        $this->echo = true;
        $this->get_recaptcha_html( $site_key );
        $this->echo = false;
    }

    /**
     * @since 3.9.4
     */
    public function get_recaptcha_html( $site_key ) {
        $template  = '<div class="awpcp-recaptcha-action" data-name="awpcp_submit" data-sitekey="' . esc_attr( $site_key ) . '">';
        $template .= '<input type="hidden" name="awpcp_recaptcha_v3_response" />';
        $template .= '</div>';

        if ( $this->echo ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $template;
            return;
        }

        return $template;
    }

    /**
     * @since 3.9.4
     */
    public function get_recaptcha_response() {
        return $this->request->post( 'awpcp_recaptcha_v3_response' );
    }

    /**
     * @since 4.0.0
     */
    public function get_verification_error_message( $error_message ) {
        $message = __( 'There was an error trying to analyze the current interaction with reCAPTCHA. <reCAPTCHA-error>', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<reCAPTCHA-error>', $error_message, $message );

        return $message;
    }

    /**
     * @since 4.0.0
     */
    public function process_error_codes( array $error_codes ) {
        $errors = array();

        foreach ( $error_codes as $error_code ) {
            switch ( $error_code ) {
                case 'missing-input-secret':
                    $errors[] = _x( 'The secret parameter is missing.', 'recaptcha-error', 'another-wordpress-classifieds-plugin' );
                    break;
                case 'invalid-input-secret':
                    $errors[] = _x( 'The secret parameter is invalid or malformed.', 'recaptcha-error', 'another-wordpress-classifieds-plugin' );
                    break;
                case 'missing-input-response':
                    $errors[] = _x( 'reCAPTCHA score was not included.', 'recaptcha-error', 'another-wordpress-classifieds-plugin' );
                    break;
                case 'invalid-input-response':
                default:
                    $errors[] = _x( "reCAPTCHA couldn't analyze the current interaction.", 'recaptcha-error', 'another-wordpress-classifieds-plugin' );
                    break;
            }
        }

        return implode( ' ', $errors );
    }

    /**
     * @since 3.9.4
     * @throws AWPCP_Exception If score is less than or equal to the threshold.
     */
    public function handle_successful_response( $response ) {
        $threshold = floatval( awpcp_get_option( 'recaptcha-v3-score-threshold', 0.5 ) );
        $score     = floatval( $response['score'] );

        if ( $score <= $threshold ) {
            $message = __( 'The current interaction was not approved by reCAPTCHA. Please try again.', 'another-wordpress-classifieds-plugin' );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return true;
    }
}
