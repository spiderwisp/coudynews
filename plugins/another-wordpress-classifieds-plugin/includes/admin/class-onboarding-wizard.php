<?php
/**
 * Onboarding Wizard Controller class.
 *
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the Onboarding Wizard page in the admin area.
 *
 * @since 4.3.5
 */
class AWPCP_OnboardingWizard {

    /**
     * The slug of the Onboarding Wizard page.
     *
     * @var string
     */
    const PAGE_SLUG = 'awpcp-onboarding-wizard';

    /**
     * Transient name used for managing redirection to the Onboarding Wizard page.
     *
     * @var string
     */
    const TRANSIENT_NAME = 'awpcp_activation_redirect';

    /**
     * Transient value associated with the redirection to the Onboarding Wizard page.
     * Used when activating a single plugin.
     *
     * @var string
     */
    const TRANSIENT_VALUE = 'awpcp-welcome';

    /**
     * Transient value associated with the redirection to the Onboarding Wizard page.
     * Used when activating multiple plugins at once.
     *
     * @var string
     */
    const TRANSIENT_MULTI_VALUE = 'awpcp-welcome-multi';

    /**
     * Option name for storing the redirect status for the Onboarding Wizard page.
     *
     * @var string
     */
    const REDIRECT_STATUS_OPTION = 'awpcp_welcome_redirect';

    /**
     * Option name for tracking if the onboarding wizard was skipped.
     *
     * @var string
     */
    const ONBOARDING_SKIPPED_OPTION = 'awpcp_onboarding_skipped';

    /**
     * Defines the initial step for redirection within the application flow.
     *
     * @var string
     */
    const INITIAL_STEP = 'consent-tracking';

    /**
     * Holds the URL to access the Onboarding Wizard's page.
     *
     * @var string
     */
    private static $page_url = '';

    /**
     * Path to views.
     *
     * @var string
     */
    private $view_path = '';

    /**
     * Initialize hooks for template page only.
     *
     * @since 4.3.5
     */
    public function load_admin_hooks() {
        $this->set_page_url();

        add_action( 'admin_init', array( $this, 'do_admin_redirects' ) );

        // Load page if admin page is Onboarding Wizard.
        $this->maybe_load_page();
    }

    /**
     * Performs a safe redirect to the welcome screen when the plugin is activated.
     * On single activation, we will redirect immediately.
     * When activating multiple plugins, the redirect is delayed until a BD page is loaded.
     *
     * @return void
     */
    public function do_admin_redirects() {
        $current_page = awpcp_get_var( array( 'param' => 'page' ) );

        // Prevent endless loop.
        if ( $current_page === self::PAGE_SLUG ) {
            return;
        }

        // Only do this for single site installs.
        if ( is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->mark_onboarding_as_skipped();
            return;
        }

        if ( $this->has_onboarding_been_skipped() ) {
            return;
        }

        $transient_value = get_transient( self::TRANSIENT_NAME );
        if ( ! in_array( $transient_value, array( self::TRANSIENT_VALUE, self::TRANSIENT_MULTI_VALUE ), true ) ) {
            return;
        }

        if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            /**
             * $_GET['activate-multi'] is set after activating multiple plugins.
             * In this case, change the transient value so we know for future checks.
             */
            set_transient( self::TRANSIENT_NAME, self::TRANSIENT_MULTI_VALUE, 60 );
            return;
        }

        if ( self::TRANSIENT_MULTI_VALUE === $transient_value && ! awpcp()->container['Admin']->is_awpcp_post_page() ) {
            // For multi-activations we want to only redirect when a user loads a BD page.
            return;
        }

        set_transient( self::TRANSIENT_NAME, 'no', 60 );

        // Prevent redirect with every activation.
        if ( $this->has_already_redirected() ) {
            return;
        }

        // Redirect to the onboarding wizard's initial step.
        $page_url = add_query_arg( 'step', self::INITIAL_STEP, self::$page_url );
        if ( wp_safe_redirect( esc_url_raw( $page_url ) ) ) {
            exit;
        }
    }

    /**
     * Initializes the Onboarding Wizard setup if on its designated admin page.
     *
     * @since 4.3.5
     *
     * @return void
     */
    public function maybe_load_page() {
        add_action( 'wp_ajax_awpcp_onboarding_consent_tracking', array( $this, 'ajax_consent_tracking' ) );

        if ( $this->is_onboarding_wizard_page() ) {
            $this->view_path = AWPCP_DIR . '/templates/admin/onboarding-wizard/';

            add_action( 'admin_menu', array( $this, 'menu' ), 99 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 15 );

            add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ), 999 );
            add_filter( 'awpcp-show-quick-start-guide-notice', '__return_false' );
        }
    }

    /**
     * Add Onboarding Wizard menu item to sidebar and define index page.
     *
     * @since 4.3.5
     *
     * @return void
     */
    public function menu() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $label = __( 'Onboarding Wizard', 'another-wordpress-classifieds-plugin' );

        add_submenu_page(
            'awpcp.php',
            awpcp_admin_page_title( $label ),
            $label,
            awpcp_admin_capability(),
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * Renders the Onboarding Wizard page in the WordPress admin area.
     *
     * @since 4.3.5
     *
     * @return void
     */
    public function render() {
        if ( $this->has_onboarding_been_skipped() ) {
            delete_option( self::ONBOARDING_SKIPPED_OPTION );
            $this->has_already_redirected();
        }

        $view_path = $this->get_view_path();

        echo awpcp_render_template( $view_path . 'index.tpl.php', array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'view_path' => $view_path,
            // Note: Add step parts in order.
            'step_parts' => array(
                'consent-tracking' => 'steps/consent-tracking-step.tpl.php',
                'success'          => 'steps/success-step.tpl.php',
            ),
        ) );
    }

    /**
     * Handle AJAX request to setup the "Never miss an important update" step.
     *
     * @since 4.3.5
     *
     * @return void
     */
    public function ajax_consent_tracking() {
        // Check permission and nonce.
        check_ajax_referer( 'awpcp_onboarding_nonce', 'nonce' );
        if ( ! awpcp_current_user_is_admin() ) {
            wp_send_json_error();
        }

        // TODO: Enable tracking setting here after adding tracking setting

        $this->subscribe_to_active_campaign();

        // Send response.
        wp_send_json_success();
    }

    /**
     * When the user consents to receiving news of updates, subscribe their email to ActiveCampaign.
     *
     * @since 4.3.5
     *
     * @return void
     */
    private function subscribe_to_active_campaign() {
        $user = wp_get_current_user();
        if ( empty( $user->user_email ) ) {
            return;
        }

        if ( ! self::should_send_email_to_active_campaign( $user->user_email ) ) {
            return;
        }

        $user_id    = $user->ID;
        $first_name = get_user_meta( $user_id, 'first_name', true );
        $last_name  = get_user_meta( $user_id, 'last_name', true );

        wp_remote_post(
            'https://feedback.strategy11.com/wp-admin/admin-ajax.php?action=frm_forms_preview&form=awp-onboarding',
            array(
                'body' => http_build_query(
                    array(
                        'form_key'       => 'awp-onboarding',
                        'frm_action'     => 'create',
                        'form_id'        => 22,
                        'item_key'       => '',
                        'item_meta[0]'   => '',
                        'item_meta[233]' => $user->user_email,
                        'item_meta[231]' => 'Source - AWP Plugin Onboarding',
                        'item_meta[234]' => is_string( $first_name ) ? $first_name : '',
                        'item_meta[235]' => is_string( $last_name ) ? $last_name : '',
                    )
                ),
            )
        );
    }

    /**
     * Try to skip any fake emails.
     *
     * @since 4.3.5
     *
     * @param string $email The user email.
     *
     * @return bool
     */
    private static function should_send_email_to_active_campaign( $email ) {
        $substrings = array(
            '@wpengine.local',
            '@example.com',
            '@localhost',
            '@local.dev',
            '@local.test',
            'test@gmail.com',
            'admin@gmail.com',

        );

        foreach ( $substrings as $substring ) {
            if ( false !== strpos( $email, $substring ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enqueues the Onboarding Wizard page scripts and styles.
     *
     * @since 4.3.5
     *
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_style( self::PAGE_SLUG, AWPCP_URL . '/resources/css/awpcp-onboarding-wizard.css', array(), AWPCP_VERSION );

        // Register and enqueue Onboarding Wizard script.
        wp_register_script( self::PAGE_SLUG, AWPCP_URL . '/resources/js/onboarding-wizard.js', array( 'wp-i18n' ), AWPCP_VERSION, true );
        wp_localize_script( self::PAGE_SLUG, 'awpcpOnboardingWizardVars', $this->get_js_variables() );
        wp_enqueue_script( self::PAGE_SLUG );
    }

    /**
     * Get the Onboarding Wizard JS variables as an array.
     *
     * @since 4.3.5
     *
     * @return array
     */
    private function get_js_variables() {
        return array(
            'NONCE'        => wp_create_nonce( 'awpcp_onboarding_nonce' ),
            'INITIAL_STEP' => self::INITIAL_STEP,
        );
    }

    /**
     * Adds custom classes to the existing string of admin body classes.
     *
     * The function appends a custom class to the existing admin body classes, enabling full-screen mode for the admin interface.
     *
     * @since 4.3.5
     *
     * @param string $classes Existing body classes.
     * @return string Updated list of body classes, including the newly added classes.
     */
    public function add_admin_body_classes( $classes ) {
        return $classes . ' awpcp-admin-full-screen';
    }

    /**
     * Checks if the Onboarding Wizard was skipped during the plugin's installation.
     *
     * @since 4.3.5
     * @return bool True if the Onboarding Wizard was skipped, false otherwise.
     */
    public function has_onboarding_been_skipped() {
        return get_option( self::ONBOARDING_SKIPPED_OPTION, false );
    }

    /**
     * Marks the Onboarding Wizard as skipped to prevent automatic redirects to the wizard.
     *
     * @since 4.3.5
     * @return void
     */
    public function mark_onboarding_as_skipped() {
        update_option( self::ONBOARDING_SKIPPED_OPTION, true );
    }

    /**
     * Check if the current page is the Onboarding Wizard page.
     *
     * @since 4.3.5
     *
     * @return bool True if the current page is the Onboarding Wizard page, false otherwise.
     */
    public function is_onboarding_wizard_page() {
        return awpcp()->container['Admin']->is_admin_page( self::PAGE_SLUG );
    }

    /**
     * Checks if the plugin has already performed a redirect to avoid repeated redirections.
     *
     * @return bool Returns true if already redirected, otherwise false.
     */
    private function has_already_redirected() {
        if ( get_option( self::REDIRECT_STATUS_OPTION ) ) {
            return true;
        }

        update_option( self::REDIRECT_STATUS_OPTION, AWPCP_VERSION );
        return false;
    }

    /**
     * Get the path to the Onboarding Wizard views.
     *
     * @since 4.3.5
     *
     * @return string Path to views.
     */
    public static function get_page_url() {
        return self::$page_url;
    }

    /**
     * Set the URL to access the Onboarding Wizard's page.
     *
     * @return void
     */
    private function set_page_url() {
        self::$page_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
    }

    /**
     * Get the path to the Onboarding Wizard views.
     *
     * @since 4.3.5
     *
     * @return string Path to views.
     */
    public function get_view_path() {
        return $this->view_path;
    }
}
