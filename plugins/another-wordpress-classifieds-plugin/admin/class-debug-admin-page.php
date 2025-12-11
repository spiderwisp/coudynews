<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for AWPCP_DebugAdminPage.
 */
function awpcp_debug_admin_page() {
    return new AWPCP_DebugAdminPage(
        awpcp(),
        awpcp()->settings,
        awpcp()->container['SettingsManager'],
        awpcp()->container['TemplateRenderer']
    );
}

/**
 * Renders the Debug admin page.
 */
// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_DebugAdminPage {

    /**
     * @var AWPCP
     */
    private $plugin;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_SettingsManager
     */
    private $settings_manager;

    /**
     * @var AWPCP_Template_Renderer
     */
    protected $template_renderer;

    /**
     * @var wpdb
     */
    private $db;

    /**
     * Constructor.
     */
    public function __construct( $plugin, $settings, $settings_manager, $template_renderer ) {
        $this->plugin            = $plugin;
        $this->settings          = $settings;
        $this->settings_manager  = $settings_manager;
        $this->template_renderer = $template_renderer;
        $this->db                = $GLOBALS['wpdb'];
    }

    /**
     * @since 4.0.0
     */
    public function on_load() {
        if ( 'debug page' !== awpcp_get_var( array( 'param' => 'download' ) ) ) {
            return;
        }

        $this->export_to_json();
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-debug' );
    }

    /**
     * Allow users to download Debug Info as an JSON file.
     *
     * @since 2.0.7
     */
    public function export_to_json() {
        $content = [
            'environment'     => $this->get_environment_data(),
            'plugin-info'     => $this->get_plugin_info_data(),
            'plugin-pages'    => $this->get_plugin_pages_data(),
            'plugin-settings' => $this->get_plugin_settings(),
            'rewrite-rules'   => $this->get_rewrite_rules_data(),
        ];

        $current_date = gmdate( DATE_ATOM, current_time( 'timestamp' ) );

        $filename = sprintf( 'awpcp-debug-info-%s.json', $current_date );
        $filename = str_replace( ':', '', $filename );

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );

        echo wp_json_encode( $content, JSON_PRETTY_PRINT );

        exit;
    }

    /**
     * Renders an HTML page with AWPCP informaiton useful for debugging tasks.
     *
     * @since 2.0.7
     */
    public function dispatch() {
        $current_section = $this->get_current_section();

        switch ( $current_section ) {
            case 'plugin-info':
                $content = $this->render_plugin_info_section();
                break;
            case 'plugin-pages':
                $content = $this->render_plugin_pages_section();
                break;
            case 'environment':
                $content = $this->render_environment_section();
                break;
            case 'plugin-settings':
                $content = $this->render_plugin_settings_section();
                break;
            case 'rewrite-rules':
                $content = $this->render_rewrite_rules_section();
                break;
            default:
                $content = '';
        }

        $params = array(
            'sections'        => [
                'plugin-info'     => _x( 'Plugin Info', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'plugin-pages'    => _x( 'Plugin Pages', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'plugin-settings' => _x( 'Plugin Settings', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'environment'     => _x( 'Environment', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'rewrite-rules'   => _x( 'Rewrite Rules', 'debug page', 'another-wordpress-classifieds-plugin' ),
            ],
            'current_section' => $current_section,
            'content'         => $content,
            'current_url'     => awpcp_current_url(),
        );

        $template = 'admin/debug/debug-admin-page.tpl.php';

        return awpcp_render_template( $template, $params );
    }

    /**
     * @since 4.0.0
     * @return string
     */
    private function get_current_section() {
        return awpcp_get_var( array( 'param' => 'awpcp-section', 'default' => 'plugin-info' ) );
    }

    /**
     * @since 4.0.0
     */
    private function render_plugin_info_section() {
        $params = $this->get_plugin_info_data();

        return $this->template_renderer->render_template( 'admin/debug/plugin-info-debug-section.tpl.php', $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_plugin_info_data() {
        global $awpcp_db_version;

        $properties = [
            'plugin-version' => [
                'label' => _x( 'Plugin Version', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'value' => $awpcp_db_version,
            ],
        ];

        $premium_modules = [];

        foreach ( $this->plugin->get_premium_modules_information() as $plugin ) {
            if ( ! defined( $plugin['version'] ) ) {
                continue;
            }

            $premium_modules[] = [
                'name'    => $plugin['name'],
                'version' => constant( $plugin['version'] ),
            ];
        }

        return compact( 'properties', 'premium_modules' );
    }

    /**
     * @since 4.0.0
     */
    private function render_plugin_pages_section() {
        $params = [
            'plugin_pages' => $this->get_plugin_pages_data(),
        ];

        $template = 'admin/debug/plugin-pages-debug-section.tpl.php';

        return $this->template_renderer->render_template( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_plugin_pages_data() {
        $plugin_pages = [];

        foreach ( awpcp_get_plugin_pages_ids() as $ref => $page_id ) {
            $page_title = '';
            $page_url   = '';

            if ( $page_id ) {
                $page_title = get_the_title( $page_id );
                $page_url   = awpcp_get_page_link( $page_id );
            }

            $plugin_pages[] = [
                'reference'  => $ref,
                'page_title' => $page_title,
                'page_url'   => $page_url,
                'page_id'    => $page_id,
            ];
        }

        return $plugin_pages;
    }

    /**
     * @since 4.0.0
     */
    private function render_environment_section() {
        $params = [
            'properties'    => $this->get_environment_data(),
            'show_ssl_test' => function_exists( 'curl_init' ),
        ];

        return $this->template_renderer->render_template( 'admin/debug/environment-debug-section.tpl.php', $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_environment_data() {
        $properties = [
            'wordpress-version' => [
                'label' => __( 'WordPress version', 'another-wordpress-classifieds-plugin' ),
                'value' => get_bloginfo( 'version' ),
            ],
            'os'                => [
                'label' => __( 'OS', 'another-wordpress-classifieds-plugin' ),
                'value' => php_uname( 's' ) . ' ' . php_uname( 'r' ) . ' ' . php_uname( 'm' ),
            ],
            'apache-version'    => [
                'label' => __( 'Apache Version', 'another-wordpress-classifieds-plugin' ),
                'value' => $this->get_apache_version(),
            ],
            'php-version'       => [
                'label' => __( 'PHP Version', 'another-wordpress-classifieds-plugin' ),
                'value' => phpversion(),
            ],
            'mysql-version'     => [
                'label' => __( 'MySQL Version', 'another-wordpress-classifieds-plugin' ),
                'value' => $this->get_mysql_version(),
            ],
            'curl-version'      => [
                'label' => __( 'cURL Version', 'another-wordpress-classifieds-plugin' ),
                'value' => $this->get_curl_version(),
            ],
            'curl-ssl-version'  => [
                'label' => __( 'cURL SSL Version', 'another-wordpress-classifieds-plugin' ),
                'value' => $this->get_curl_ssl_version(),
            ],
            'curl-cacert'       => [
                'label' => _x( "cURL's alternate CA info (cacert.pem)", 'debug page', 'another-wordpress-classifieds-plugin' ),
                'value' => file_exists( AWPCP_DIR . '/cacert.pem' ) ? _x( 'Exists', 'alternate CA info for cURL', 'another-wordpress-classifieds-plugin' ) : _x( 'Missing', 'alternate CA info for cURL', 'another-wordpress-classifieds-plugin' ),
            ],
            'paypal-connection' => [
                'label' => _x( 'PayPal Connection', 'debug page', 'another-wordpress-classifieds-plugin' ),
                'value' => $this->get_paypal_connection_results(),
            ],
        ];

        return $properties;
    }

    /**
     * @since 4.0.0
     */
    private function get_apache_version() {
        if ( ! function_exists( 'apache_get_version' ) ) {
            return null;
        }

        return apache_get_version();
    }

    /**
     * @since 4.0.0
     */
    private function get_mysql_version() {
        $mysql_version = $this->db->get_var( 'SELECT @@version' );
        $sql_mode      = $this->db->get_var( 'SELECT @@sql_mode' );

        if ( $sql_mode ) {
            return "{$mysql_version} ({$sql_mode})";
        }

        return $mysql_version;
    }

    /**
     * @since 4.0.0
     */
    private function get_curl_version() {
        return $this->get_curl_version_element( 'version' );
    }

    /**
     * @since 4.0.0
     */
    private function get_curl_version_element( $element ) {
        if ( ! function_exists( 'curl_version' ) ) {
            return __( 'N/A', 'another-wordpress-classifieds-plugin' );
        }

        $version = curl_version();

        if ( ! isset( $version[ $element ] ) ) {
            return __( 'N/A', 'another-wordpress-classifieds-plugin' );
        }

        return $version[ $element ];
    }

    /**
     * @since 4.0.0
     */
    private function get_curl_ssl_version() {
        return $this->get_curl_version_element( 'ssl_version' );
    }

    /**
     * @since 4.0.0
     */
    private function get_paypal_connection_results() {
        $errors   = [];
        $response = awpcp_paypal_verify_received_data( [], $errors );

        if ( 'INVALID' === $response ) {
            return _x( 'Working', 'debug page', 'another-wordpress-classifieds-plugin' );
        }

        $results = [ _x( 'Not Working', 'debug page', 'another-wordpress-classifieds-plugin' ) ];

        foreach ( (array) $errors as $error ) {
            $results[] = $error;
        }

        return implode( '<br/>', $results );
    }

    /**
     * @since 4.0.0
     */
    private function render_plugin_settings_section() {
        $params = [
            'plugin_settings' => $this->get_plugin_settings(),
        ];

        $template = 'admin/debug/plugin-settings-debug-section.tpl.php';

        return $this->template_renderer->render_template( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_plugin_settings() {
        $all_settings = $this->settings_manager->get_settings();

        $safe_settings['awpcp_installationcomplete'] = get_option( 'awpcp_installationcomplete' );
        $safe_settings['awpcp_pagename_warning']     = get_option( 'awpcp_pagename_warning' );
        $safe_settings['widget_awpcplatestads']      = get_option( 'widget_awpcplatestads' );
        $safe_settings['awpcp_db_version']           = get_option( 'awpcp_db_version' );

        foreach ( $all_settings as $setting ) {
            $safe_settings[ $setting['id'] ] = $this->sanitize_setting_value( $this->settings->get_option( $setting['id'] ) );
        }

        foreach ( $this->get_blacklisted_options() as $setting_name ) {
            unset( $safe_settings[ $setting_name ] );
        }

        return $safe_settings;
    }

    /**
     * @since 4.0.0     Removed setting name argument.
     */
    private function sanitize_setting_value( $value ) {
        static $hosts_regexp = '';
        static $email_regexp = '';

        if ( empty( $hosts_regexp ) ) {
            $hosts = array_unique(
                array(
                    wp_parse_url( home_url(), PHP_URL_HOST ),
                    wp_parse_url( site_url(), PHP_URL_HOST ),
                )
            );

            $hosts_regexp = '/' . preg_quote( join( '|', $hosts ), '/' ) . '/';
            $email_regexp = '/[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})/';
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        $sanitized = ( is_object( $value ) || is_array( $value ) ) ? print_r( $value, true ) : $value;

        // Remove Website domain.
        $sanitized = preg_replace( $hosts_regexp, '<host>', $sanitized );

        // Remove email addresses.
        $sanitized = preg_replace( $email_regexp, '<email>', $sanitized );

        return $sanitized;
    }

    /**
     * @since 4.0.0
     */
    private function get_blacklisted_options() {
        // TODO: add other settings from premium modules.
        return array(
            'tos',
            'admin-recipient-email',
            'awpcpadminemail',
            'paypalemail',
            '2checkout',
            'smtphost',
            'smtpport',
            'smtpusername',
            'smtppassword',
            'googlecheckoutmerchantID',
            'googlecheckoutsandboxseller',
            'googlecheckoutbuttonurl',
            'authorize.net-login-id',
            'authorize.net-transaction-key',
            'paypal-pro-username',
            'paypal-pro-password',
            'paypal-pro-signature',
        );
    }

    /**
     * @since 4.0.0
     */
    private function render_rewrite_rules_section() {
        $params = [
            'rules' => $this->get_rewrite_rules_data(),
        ];

        $template = 'admin/debug/rewrite-rules-debug-section.tpl.php';

        return $this->template_renderer->render_template( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_rewrite_rules_data() {
        global $wp_rewrite;

        return (array) $wp_rewrite->wp_rewrite_rules();
    }
}
