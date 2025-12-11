<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_http() {
    return new AWPCP_HTTP();
}

class AWPCP_HTTP {

    public function get( $url, $args = array() ) {
        $args = array_merge( array(
            'headers' => array(
                'user-agent' => awpcp_user_agent_header(),
            ),
        ) );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $this->handle_wp_error( $response, $url, $args );
        }

        $response_code    = wp_remote_retrieve_response_code( $response );

        if ( 403 === $response_code ) {
            $url_parts = wp_parse_url( $url );
            $host      = $url_parts['host'];

            $message  = '<strong>' . __( 'The server returned a 403 Forbidden error.', 'another-wordpress-classifieds-plugin' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= __( "It look's like your server is not authorized to make requests to <host>. Please <support-link>contact AWP Classifieds support</support-link> and ask them to add your IP address <ip-address> to the whitelist.", 'another-wordpress-classifieds-plugin' );
            $message .= '<br/><br/>';
            $message .= __( 'Include this error message with your report.', 'another-wordpress-classifieds-plugin' );

            $message = str_replace( '<host>', $host, $message );
            $message = str_replace( '<support-link>', '<a href="https://awpcp.com/contact">', $message );
            $message = str_replace( '</support-link>', '</a>', $message );
            $message = str_replace( '<ip-address>', awpcp_get_server_ip_address(), $message );

            throw new AWPCP_HTTP_Exception( wp_kses_post( $message ) );
        }

        return $response;
    }

    /**
     * Handle WordPress HTTP errors with fallback to alternative HTTP methods.
     */
    private function handle_wp_error( $response, $url, $args ) {
        $url_parts = wp_parse_url( $url );
        $host      = $url_parts['host'];

        $fallback_args = array_merge( $args, array(
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers'     => array_merge(
                isset( $args['headers'] ) ? $args['headers'] : array(),
                array(
                    'user-agent' => awpcp_user_agent_header(),
                )
            ),
        ) );

        $fallback_response = wp_remote_get( $url, $fallback_args );

        if ( is_wp_error( $fallback_response ) ) {
            $error_code    = $response->get_error_code();
            $error_message = $response->get_error_message();

            if ( 'http_request_failed' === $error_code ) {
                if ( strpos( $error_message, 'Could not resolve host' ) !== false ||
                    strpos( $error_message, 'Connection refused' ) !== false ||
                    strpos( $error_message, 'Connection timed out' ) !== false ) {

                    $message  = '<strong>' . __( 'It was not possible to establish a connection with <host>. The connection failed with the following error:', 'another-wordpress-classifieds-plugin' ) . '</strong>';
                    $message .= '<br/><br/>';
                    $message .= '<code>HTTP Error: ' . esc_html( $error_message ) . '</code>';
                    $message .= '<br/><br/>';
                    $message .= __( "It look's like your server is not authorized to make requests to <host>. Please <support-link>contact AWP Classifieds support</support-link> and ask them to add your IP address <ip-address> to the whitelist.", 'another-wordpress-classifieds-plugin' );
                    $message .= '<br/><br/>';
                    $message .= __( 'Include this error message with your report.', 'another-wordpress-classifieds-plugin' );

                    $message = str_replace( '<host>', $host, $message );
                    $message = str_replace( '<support-link>', '<a href="https://awpcp.com/contact">', $message );
                    $message = str_replace( '</support-link>', '</a>', $message );
                    $message = str_replace( '<ip-address>', awpcp_get_server_ip_address(), $message );

                    throw new AWPCP_HTTP_Exception( wp_kses_post( $message ) );
                }

                if ( strpos( $error_message, 'SSL' ) !== false ||
                    strpos( $error_message, 'TLS' ) !== false ||
                    strpos( $error_message, 'certificate' ) !== false ) {

                    $message = '<strong>' . __( 'It was not possible to establish a connection with <host>. A problem occurred in the SSL/TLS handshake:', 'another-wordpress-classifieds-plugin' ) . '</strong>';

                    $message .= '<br/><br/>';
                    $message .= '<code>HTTP Error: ' . esc_html( $error_message ) . '</code>';
                    $message .= '<br/><br/>';
                    $message .= __( 'To ensure the security of our systems and adhere to industry best practices, we require that your server uses a recent version of cURL and a version of OpenSSL that supports TLSv1.2 (minimum version with support is OpenSSL 1.0.1c).', 'another-wordpress-classifieds-plugin' );
                    $message .= '<br/><br/>';
                    $message .= __( 'Upgrading your system will not only allow you to communicate with our servers but also help you prepare your website to interact with services using the latest security standards.', 'another-wordpress-classifieds-plugin' );
                    $message .= '<br/><br/>';
                    $message .= __( 'Please contact your hosting provider and ask them to upgrade your system. Include this message if necesary.', 'another-wordpress-classifieds-plugin' );

                    $message = str_replace( '<host>', $host, $message );

                    throw new AWPCP_HTTP_Exception( wp_kses_post( $message ) );
                }
            }

            throw new AWPCP_HTTP_Exception( esc_html( $response->get_error_message() ) );
        }

        return $fallback_response;
    }
}
