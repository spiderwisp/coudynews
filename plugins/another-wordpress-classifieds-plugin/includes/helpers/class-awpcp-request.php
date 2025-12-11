<?php
/**
 * @package AWPCP\Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Request {

    /**
     * List extracted from http://stackoverflow.com/a/14536035/201354
     */
    private static $bot_user_agents_keywords = array(
        // https://developers.facebook.com/docs/sharing/best-practices#crawl
        'facebookexternalhit',
        'facebot',
        // https://support.google.com/webmasters/answer/1061943?hl=en
        'googlebot', 'mediapartners-google', 'adsbot-google',
        // http://www.bing.com/webmaster/help/which-crawlers-does-bing-use-8c184ec0
        'bingbot', 'msnbot', 'msnbot-media', 'adidxbot', 'bingpreview',
        // https://help.yahoo.com/kb/search/slurp-crawling-page-sln22600.html
        'yahoo! slurp',
        'crawler',
        'baiduspider',
        '80legs',
        'ia_archiver',
        'voyager',
        'curl',
        'wget',
    );

    /**
     * @tested
     * @since 3.0.2
     */
    public function method() {
        return strtoupper( awpcp_get_server_value( 'REQUEST_METHOD' ) );
    }

    /**
     * @since 3.6.6
     */
    public function scheme() {
        return is_ssl() ? 'https' : 'http';
    }

    /**
     * Returns the domain used in the current request, optionally replacing
     * the www part of the domain with $www_prefix_replacement.
     *
     * @since 3.3
     */
    public function domain( $include_www = true, $www_prefix_replacement = '' ) {
        $domain = $this->filter_input( 'HTTP_HOST' );

        // If the server runs on a port other than 80 then HTTP_HOST contains
        // the port. See https://stackoverflow.com/a/12046836.
        $port_position = strpos( $domain, ':' );

        if ( $port_position ) {
            $domain = substr( $domain, 0, $port_position );
        }

        if ( empty( $domain ) ) {
            $domain = isset( $_SERVER['SERVER_NAME'] ) ? wp_strip_all_tags( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
        }

        $should_replace_www = $include_www ? false : true;
        $domain_starts_with_www = substr( $domain, 0, 4 ) === 'www.';

        if ( $should_replace_www && $domain_starts_with_www ) {
            $domain = $www_prefix_replacement . substr( $domain, 4 );
        }

        return $domain;
    }

    /**
     * Filter external variable.
     *
     * A wrapper of PHP's filter_input that can be mocked during in tests.
     *
     * @since 4.0.0
     */
    private function filter_input( $var_name ) {
        return awpcp_get_server_value( $var_name );
    }

    /**
     * @param string $name      The name of the GET/POST parameter to get.
     * @param mixed  $default   Value return if the parameter was not sent.
     * @since 3.0.2
     * @deprecated 4.3
     */
    public function param( $name, $default = '' ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
        return isset( $_REQUEST[ $name ] ) ? wp_unslash( $_REQUEST[ $name ] ) : $default; // Input var okay.
    }

    /**
     * @tested
     * @since 3.0.2
     * @deprecated 3.2.3
     */
    public function get_param( $name, $default='' ) {
        _deprecated_function( __FUNCTION__, '3.2.3', 'get( $name, $default )' );
        return $this->get( $name, $default );
    }

    /**
     * @tested
     * @since 3.0.2
     * @deprecated 4.3
     */
    public function get( $name, $default='' ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
        return isset( $_GET[ $name ] ) ? $_GET[ $name ] : $default;
    }

    /**
     * @tested
     * @since 3.0.2
     * @deprecated 3.2.3
     */
    public function post_param( $name, $default='' ) {
        _deprecated_function( __FUNCTION__, '3.2.3', 'post( $name, $default )' );
        return $this->post( $name, $default );
    }

    /**
     * @since 3.3
     * @deprecated 4.3
     */
    public function all_request_params() {
        _deprecated_function( __METHOD__, '4.3' );
        // phpcs:ignore WordPress.Security.NonceVerification
        return $_REQUEST;
    }

    /**
     * @since 3.5.4
     * @deprecated 4.3
     */
    public function all_post_params() {
        _deprecated_function( __METHOD__, '4.3' );
        // phpcs:ignore WordPress.Security.NonceVerification
        return $_POST;
    }

    /**
     * @tested
     * @since 3.0.2
     * @deprecated 4.3
     */
    public function post( $param, $default = '', $sanitize = 'sanitize_text_field' ) {
        return awpcp_get_var( compact( 'param', 'default', 'sanitize' ), 'post' );
    }

    /**
     * @tested
     * @since 3.0.2
     */
    public function get_query_var( $name, $default='' ) {
        $value = get_query_var( $name );

        /*
         * Sometimes values are arrays.
         *
         * See https://github.com/drodenbaugh/awpcp/issues/2531.
         */
        if ( is_array( $value ) ) {
            return $value;
        }

        return strlen( $value ) === 0 ? $default : $value;
    }

    /**
     * @tested
     * @since 3.0.2
     */
    public function get_category_id() {
        $alternatives = [
            'params'     => [
                'awpcp_category_id',
                'category_id',
            ],
            'query_vars' => [
                'cid',
            ],
        ];

        return intval( $this->find_variable( $alternatives, 0 ) );
    }

    /**
     * @tested
     * @since 3.0.2
     */
    public function get_ad_id() {
        return $this->get_current_listing_id();
    }

    /**
     * @since 3.6.4
     */
    public function get_current_listing_id() {
        $listing_id = intval( $this->find_current_listing_id() );

        return apply_filters( 'awpcp-current-listing-id', $listing_id );
    }

    /**
     * @since 4.0.0
     */
    private function find_current_listing_id() {
        $alternatives = [
            'params'     => [
                'adid',
                'ad_id',
                'id',
                'listing_id',
                'i',
            ],
            'query_vars' => [
                'id',
            ],
        ];

        if ( 'awpcp_listing' === $this->get_query_var( 'post_type' ) ) {
            $alternatives['query_vars'][] = 'p';
        }

        return $this->find_variable( $alternatives );
    }

    /**
     * @since 4.0.0
     */
    private function find_variable( array $alternatives, $default = null ) {
        foreach ( $alternatives['params'] as $name ) {
            $value = $this->param( $name );

            if ( ! empty( $value ) ) {
                return $value;
            }
        }

        foreach ( $alternatives['query_vars'] as $name ) {
            $value = $this->get_query_var( $name );

            if ( ! empty( $value ) ) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @since 3.3
     */
    public function get_current_user() {
        return wp_get_current_user();
    }

    /**
     * @since 4.0.0
     */
    public function get_current_user_id() {
        return get_current_user_id();
    }

    public function is_bot() {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }

        $regexp = '/' . implode( '|', self::$bot_user_agents_keywords ) . '/';

        return (bool) preg_match( $regexp, strtolower( awpcp_get_server_value( 'HTTP_USER_AGENT' ) ) );
    }
}
