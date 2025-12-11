<?php
/**
 * @package AWPCP\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajax handler for the Test SSL Client action.
 */
class AWPCP_TestSSLClientAjaxHandler {

    /**
     * @since 4.0.0
     */
    public function ajax() {
        awpcp_check_admin_ajax();

        $args = array(
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
            'sslverify'   => true,
            'headers'     => array(
                'Accept' => 'application/json',
            ),
        );

        $response = wp_remote_get( 'https://www.howsmyssl.com/a/check', $args );

        if ( is_wp_error( $response ) ) {
            die( 'HTTP Error: ' . esc_html( $response->get_error_message() ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            die( 'HTTP Error: ' . esc_html( $response_code ) . ' - ' . esc_html( wp_remote_retrieve_response_message( $response ) ) );
        }

        $data = wp_remote_retrieve_body( $response );

        if ( empty( $data ) ) {
            die( 'No response from remote server.' );
        }

        $json = json_decode( $data );

        if ( null === $json || ! isset( $json->given_cipher_suites, $json->tls_version, $json->rating ) ) {
            die( 'Invalid JSON response from remote server.' );
        }

        echo "Cipher Suites:\n" . esc_html( implode( ',', $json->given_cipher_suites ) ) . "\n\n";
        echo "TLS Version:\n" . esc_html( $json->tls_version ) . "\n\n";
        echo "Rating:\n" . esc_html( $json->rating );

        exit();
    }
}
