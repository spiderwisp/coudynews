<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP {

    public $installer = null;

    public $admin = null; // Admin section
    public $panel = null; // User Ad Management panel
    public $pages = null; // Frontend pages

    public $modules_manager;
    public $modules_updater;
    public $plugin_integrations;
    public $settings = null;
    public $settings_manager;
    public $payments = null;
    public $listings;
    public $js = null;

    public $container;

    public $version;

    public $rewrite_rules;

    public $router;

    public $upgrade_tasks;

    public $manual_upgrades;

    public $compatibility;

    public function __construct( $container ) {
        global $awpcp_db_version;

        $this->container = $container;
        $this->version = $awpcp_db_version;
    }

    public function bootstrap() {
        $this->settings_manager = $this->container['SettingsManager'];
        $this->js = AWPCP_JavaScript::instance();

        // TODO: Fix activation hook
        // awpcp_register_activation_hook( AWPCP_FILE, array( $this->installer, 'activate' ) );
    }

    /**
     * Check if AWPCP DB version corresponds to current AWPCP plugin version.
     *
     * @deprecated since 3.0.2
     */
    public function updated() {
        _deprecated_function( __FUNCTION__, '3.0.2', 'AWPCP::is_updated()' );
        return false;
    }

    /**
     * Check if AWPCP DB version corresponds to current AWPCP plugin version.
     */
    public function is_up_to_date() {
        global $awpcp_db_version;
        $installed = get_option('awpcp_db_version', '');
        // if installed version is greater than plugin version
        // not sure what to do. Downgrade is not currently supported.
        return version_compare($installed, $awpcp_db_version) === 0;
    }

    /**
     * Single entry point for AWPCP plugin.
     *
     * This is functional but still a work in progress...
     */
    public function setup() {
        global $wpdb;

        $this->container->configure( $this->get_container_configurations() );

        $this->installer     = awpcp_installer();
        $this->rewrite_rules = awpcp_plugin_rewrite_rules();

        // Stored options are loaded when the settings API is instatiated.
        $this->settings = $this->container['Settings'];

        $this->upgrade_tasks   = $this->container['UpgradeTasksManager'];
        $this->manual_upgrades = $this->container['ManualUpgradeTasks'];

        $this->router   = awpcp_router();
        $this->payments = awpcp_payments_api();
        $this->listings = awpcp_listings_api();

        $this->manual_upgrades->register_upgrade_tasks();

        $this->admin = awpcp_admin_panel();
        $this->panel = awpcp_user_panel();

        $this->compatibility = new AWPCP_Compatibility();
        $this->compatibility->load_plugin_integrations();

        $this->plugin_integrations = new AWPCP_Plugin_Integrations();

        if (!$this->is_up_to_date()) {
            update_option( 'awpcp-flush-rewrite-rules', true );
            $this->installer->install_or_upgrade();
        }

        if (!$this->is_up_to_date()) {
            return;
        }

        $this->register_settings_handlers();

        $this->container['OnboardingWizard']->load_admin_hooks();

        // register rewrite rules when the plugin file is loaded.
        // generate_rewrite_rules or rewrite_rules_array hooks are
        // too late to add rules using add_rewrite_rule function
        add_action( 'page_rewrite_rules', array( $this->rewrite_rules, 'add_rewrite_rules' ) );
        add_filter('query_vars', 'awpcp_query_vars');

        add_action( 'activated_plugin', array( $this->plugin_integrations, 'maybe_enable_plugin_integration' ), 10, 2 );
        add_action( 'deactivated_plugin', array( $this->plugin_integrations, 'maybe_disable_plugin_integration' ), 10, 2 );

        add_action( 'generate_rewrite_rules', [ $this->container['CategoriesListCache'], 'clear' ] );

        add_action( 'awpcp-configure-routes', array( $this->admin, 'configure_routes' ) );
        add_action( 'awpcp-configure-routes', array( $this->panel, 'configure_routes' ) );

        /**
         * The setup_nav method in BuddyPressListingsComponent needs to run
         * after register_settings().
         *
         * Make sure to update setup_component() on premium-modules/awpcp-buddypress-listings/includes/class-buddypress-listings-loader.php
         * if you ever change the priority for this action.
         */
        add_action( 'init', [ $this->settings_manager, 'register_settings' ], 5 );

        // TODO: Make sure to update permastruct for custom post types before generating rewrite rules.
        //
        //       If the slug of a page, the permalink structure or anything else
        //       that affects the permastruct for the custom post type changes
        //       after 'init', calling flush_rewrite_rules will generate the wrong
        //       set of rules unless we update the permalinks accordingly.
        //
        //       Perhaps delaying rewrite rules generation until next request makes
        //       makes more sense.
        $custom_post_types = awpcp_custom_post_types();
        add_action( 'init', array( $custom_post_types, 'register_custom_post' ), 5 );
        add_action( 'awpcp-installed', array( $custom_post_types, 'create_default_category' ) );

        $listing_permalinks = $this->container['ListingsPermalinks'];

        add_action( 'registered_post_type', array( $listing_permalinks, 'update_post_type_permastruct' ), 10, 2 );
        add_action( 'parse_request', array( $listing_permalinks, 'maybe_set_current_post' ) );
        add_action( 'post_type_link', array( $listing_permalinks, 'filter_post_type_link' ), 10, 4 );

        $listings_categories_permalinks = $this->container['ListingsCategoriesPermalinks'];

        add_filter( 'term_link', [ $listings_categories_permalinks, 'filter_term_link' ], 10, 3 );

        add_action( 'init', array( $this, 'register_plugin_integrations' ), 4 );
        add_action( 'init', array( $this->compatibility, 'load_plugin_integrations_on_init' ), 4 );
        add_action( 'init', array( $this->plugin_integrations, 'load_plugin_integrations' ), AWPCP_LOWEST_FILTER_PRIORITY );
        add_action( 'init', array($this, 'init' ), 4 );
        add_action( 'init', [ $this, 'register_scripts' ], AWPCP_LOWEST_FILTER_PRIORITY );
        add_action( 'init', array($this, 'register_custom_style'), AWPCP_LOWEST_FILTER_PRIORITY );

        // XXX: This is really a hack. We should get the priorities on order or
        //      come up with a better name for this method.
        add_action( 'init', array( $this, 'first_time_verifications' ), 5 );

        add_action('admin_notices', array($this, 'admin_notices'));

        add_action('awpcp_register_settings', array($this, 'register_settings'));
        add_action( 'awpcp-register-payment-term-types', array( $this, 'register_payment_term_types' ) );
        add_action( 'awpcp-register-payment-methods', array( $this, 'register_payment_methods' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );
        add_action( 'wp_footer', array( $this, 'localize_scripts' ), 15000 );
        add_action( 'admin_footer', array( $this, 'localize_scripts' ), 15000 );

        if ( get_option( 'awpcp-activated' ) && $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin' ) ) ) {
            delete_option( 'awpcp-activated' );
            wp_safe_redirect( awpcp_get_admin_upgrade_url() );
            exit;
        }

        // some upgrade operations can't be done in background.
        // if one those is pending, we will disable all other features
        // until the user executes the upgrade operaton
        $has_pending_blocking_manual_upgrades = $this->upgrade_tasks->has_pending_tasks(
            array(
                'context'  => 'plugin',
                'blocking' => true,
            )
        );

        if ( ! $has_pending_blocking_manual_upgrades ) {
            $this->pages = new AWPCP_Pages( $this->container );

            add_action( 'awpcp-process-payment-transaction', array( $this, 'process_transaction_update_payment_status' ) );
            add_action( 'awpcp-process-payment-transaction', array( $this, 'process_transaction_notify_wp_affiliate_platform' ) );

            add_action( 'wp_ajax_awpcp-get-regions-options', array( $this, 'get_regions_options' ) );
            add_action( 'wp_ajax_nopriv_awpcp-get-regions-options', array( $this, 'get_regions_options' ) );

            // actions and filters from functions_awpcp.php
            add_action('phpmailer_init','awpcp_phpmailer_init_smtp');

            add_action('widgets_init', array($this, 'register_widgets'));

            awpcp_schedule_activation();

        }
    }

    public function register_settings_handlers() {
        $general_settings = new AWPCP_GeneralSettings();
        add_action( 'awpcp_register_settings', array( $general_settings, 'register_settings' ) );
        add_filter( 'awpcp_validate_settings_general-settings', array( $general_settings, 'validate_group_settings' ), 10, 2 );
        add_filter( 'awpcp_validate_settings_general-settings', array( $general_settings, 'validate_general_settings' ), 10, 2 );
        add_filter( 'awpcp_validate_settings_facebook-settings', [ $general_settings, 'validate_facebook_settings' ] );
        add_filter( 'awpcp_validate_settings_subgroup_date-time-format-settings', array( $general_settings, 'validate_date_time_format_settings' ), 10, 2 );
        add_filter( 'awpcp_validate_settings_subgroup_registration-settings',[ $general_settings, 'validate_registration_settings' ] );
        add_filter( 'awpcp_validate_settings_subgroup_currency-format-settings',[ $general_settings, 'validate_currency_settings' ] );

        $pages_settings = $this->container['PagesSettings'];
        add_action( 'awpcp_register_settings', [ $pages_settings, 'register_settings' ] );
        add_action( 'awpcp_settings_validated_pages-settings', [ $pages_settings, 'page_settings_validated' ] );

        $listings_settings = $this->container['ListingsSettings'];
        add_action( 'awpcp_register_settings', [ $listings_settings, 'register_settings' ] );
        add_action( 'awpcp_settings_validated_subgroup_seo-settings', [ $listings_settings, 'seo_settings_validated' ], 10, 3 );

        $listings_moderation_settings = new AWPCP_ListingsModerationSettings( $this->settings );
        add_filter( 'awpcp_validate_settings', array( $listings_moderation_settings, 'validate_all_settings' ), 10, 2 );
        add_filter( 'awpcp_validate_settings_listings-settings', array( $listings_moderation_settings, 'validate_group_settings' ), 10, 2 );

        $payment_settings = $this->container['PaymentSettings'];
        add_action( 'awpcp_register_settings', array( $payment_settings, 'register_settings' ) );
        add_filter( 'awpcp_validate_settings_subgroup_credit-system-settings', array( $payment_settings, 'validate_credit_system_settings' ) );
        add_filter( 'awpcp_validate_settings_payment-settings', array( $payment_settings, 'validate_group_settings' ), 10, 2 );
        add_filter( 'awpcp_validate_settings_payment-settings', [ $payment_settings, 'validate_payment_settings' ], 10, 2 );

        $files_settings = awpcp_files_settings();
        add_action( 'awpcp_register_settings', array( $files_settings, 'register_settings') );

        $appearance_settings = $this->container['DisplaySettings'];
        add_action( 'awpcp_register_settings', [ $appearance_settings, 'register_settings' ] );

        $email_settings = $this->container['EmailSettings'];
        add_action( 'awpcp_register_settings', [ $email_settings, 'register_settings' ] );
        add_filter( 'awpcp_validate_settings_email-settings', [ $email_settings, 'validate_email_settings' ] );
    }

    /**
     * @since 4.0.0
     */
    public function register_settings_renderers( $renderers ) {
        $renderers['checkbox']       = $this->container['CheckboxSettingsRenderer'];
        $renderers['select']         = $this->container['SelectSettingsRenderer'];
        $renderers['textarea']       = $this->container['TextareaSettingsRenderer'];
        $renderers['radio']          = $this->container['RadioSettingsRenderer'];
        $renderers['textfield']      = $this->container['TextfieldSettingsRenderer'];
        $renderers['button']         = new AWPCP_ButtonSettingsRenderer();
        $renderers['password']       = $this->container['TextfieldSettingsRenderer'];
        $renderers['choice']         = $this->container['ChoiceSettingsRenderer'];
        $renderers['categories']     = $this->container['CategoriesSettingsRenderer'];
        if ( method_exists( $this->container, 'offsetExists' ) && $this->container->offsetExists( 'LicenseSettingsRenderer' ) ) {
            $renderers['license'] = $this->container['LicenseSettingsRenderer'];
        }
        $renderers['wordpress-page'] = $this->container['WordPressPageSettingsRenderer'];
        $renderers['settings-grid']  = $this->container['SettingsGridRenderer'];
        $renderers['email-template'] = $this->container['EmailTemplateSettingsRenderer'];

        return $renderers;
    }

    public function setup_runtime_options() {
        $this->settings->set_runtime_option( 'easy-digital-downloads-store-url', 'https://awpcp.com' );
        $this->settings->set_runtime_option( 'image-mime-types', array( 'image/png', 'image/jpeg', 'image/jpg', 'image/gif' ) );

        $uploads_dir_name = $this->settings->get_option( 'uploadfoldername', 'uploads' );
        $uploads_dir = implode( DIRECTORY_SEPARATOR, array( rtrim( WP_CONTENT_DIR, DIRECTORY_SEPARATOR ), $uploads_dir_name, 'awpcp' ) );
        $uploads_url = implode( '/', array( rtrim( WP_CONTENT_URL, '/' ), $uploads_dir_name, 'awpcp' ) );

        $this->settings->set_runtime_option( 'awpcp-uploads-dir', $uploads_dir );
        $this->settings->set_runtime_option( 'awpcp-uploads-url', $uploads_url );
    }

    public function setup_javascript_data() {
        $this->js->set(
            'show-popup-if-user-did-not-upload-files',
            (bool) $this->settings->get_option( 'show-popup-if-user-did-not-upload-files' )
        );

        $this->js->set( 'overwrite-contact-information-on-user-change', (bool) $this->settings->get_option( 'overwrite-contact-information-on-user-change' ) );
        $this->js->set( 'date-format', awpcp_datepicker_format( $this->settings->get_option( 'date-format') ) );
        $this->js->set( 'datetime-formats', awpcp_get_datetime_formats() );
    }

    public function register_plugin_integrations() {
        $this->plugin_integrations->add_plugin_integration(
            'mashsharer/mashshare.php',
            'awpcp_mashshare_plugin_integration'
        );

        $this->plugin_integrations->add_plugin_integration(
            'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php',
            'awpcp_simple_facebook_opengraph_tags_plugin_integration'
        );

        $this->plugin_integrations->add_plugin_integration(
            'jetpack/jetpack.php',
            'awpcp_jetpack_plugin_integration'
        );

        $this->plugin_integrations->add_plugin_integration(
            'complete-open-graph/complete-open-graph.php',
            'awpcp_complete_open_grap_plugin_integration'
        );
    }

    public function init() {
        global $wpdb;
        $wpdb->query('SET SQL_BIG_SELECTS=1');
        $query_integration = $this->container['QueryIntegration'];

        // Execute later to allow Listing Table Views to add query parameters.
        add_action( 'parse_query', [ $query_integration, 'parse_query' ], 5 );
        add_action( 'pre_get_posts', array( $query_integration, 'pre_get_posts' ), 100 );
        add_filter( 'posts_where', array( $query_integration, 'posts_where' ), 10, 2 );
        add_filter( 'posts_clauses', array( $query_integration, 'posts_clauses' ), 10, 2 );

        $term_query_integration = $this->container['TermQueryIntegration'];

        add_filter( 'terms_clauses', [ $term_query_integration, 'terms_clauses' ], 10, 3 );

        $this->container['DeleteListingEventListener']->register();
        $this->container['RemoveListingRegionsService']->register();

        $remove_listing_attachments_service = $this->container['RemoveListingAttachmentsService'];

        add_action( 'before_delete_post', [ $remove_listing_attachments_service, 'enqueue_attachments_to_be_removed' ], 10, 1 );
        add_action( 'after_delete_post', [ $remove_listing_attachments_service, 'remove_attachments' ], 10, 1 );

        $listing_payment_transaction_handler = awpcp_listing_payment_transaction_handler();
        add_action( 'awpcp-transaction-status-updated', array( $listing_payment_transaction_handler, 'transaction_status_updated' ), 20, 2 );
        add_filter( 'awpcp-process-payment-transaction', array( $listing_payment_transaction_handler, 'process_payment_transaction' ), 20 );

        $handler = $this->container['UpdatePaymentTerm'];
        add_filter( 'awpcp_before_approve_ad', array( $handler, 'maybe_prevent_ad_approval' ) );

        $handler = awpcp_renew_listing_payment_transaction_handler();
        add_action( 'awpcp-transaction-status-updated', array( $handler, 'process_payment_transaction' ), 20 );
        add_filter( 'awpcp-process-payment-transaction', array( $handler, 'process_payment_transaction' ), 20 );

        // load resources always required
        $facebook_cache_helper = awpcp_facebook_cache_helper();
        add_action( 'awpcp-clear-ad-facebook-cache', array( $facebook_cache_helper, 'handle_clear_cache_event_hook' ), 10, 1 );

        $send_to_facebook_helper = $this->container['SendListingToFacebookHelper'];
        add_action( 'awpcp-send-listing-to-facebook', array( $send_to_facebook_helper, 'send_listing_to_facebook' ) );

        $categories_list_cache = $this->container['CategoriesListCache'];

        add_action( 'awpcp_clear_categories_list_cache', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-place-ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_approve_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_edit_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_disable_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_delete_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_after_trash_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp_after_untrash_ad', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-category-added', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-category-edited', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-category-deleted', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-pages-updated', [ $categories_list_cache, 'clear' ] );
        add_action( 'awpcp-listings-imported', [ $categories_list_cache, 'clear' ] );
        add_action( 'set_object_terms', [ $categories_list_cache, 'on_set_object_terms' ], 10, 4 );
        $contact_information_controller = awpcp_user_profile_contact_information_controller();
        add_action( 'show_user_profile', array( $contact_information_controller, 'show_contact_information_fields' ) );
        add_action( 'personal_options_update', array( $contact_information_controller, 'save_contact_information' ) );

        add_filter( 'awpcp-listing-actions', array( $this, 'register_listing_actions' ), 10, 2 );

        // load resources required both in front end and admin screens, but not during ajax calls.
        if ( ! wp_doing_ajax() ) {
            $facebook_integration = awpcp_facebook_integration();
            add_action( 'awpcp-place-ad', array( $facebook_integration, 'on_ad_modified' ) );
            add_action( 'awpcp_approve_ad', array( $facebook_integration, 'on_ad_modified' ) );
            add_action( 'awpcp_edit_ad', array( $facebook_integration, 'on_ad_modified' ) );
            add_action( 'awpcp-listing-facebook-cache-cleared', array( $facebook_integration, 'on_ad_facebook_cache_cleared' ) );
        }

        add_action( 'wp_loaded', [ $this, 'wp_loaded' ] );

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            $task_queue = awpcp_task_queue();
            add_action( 'awpcp-task-queue-event', array( $task_queue, 'task_queue_event' ) );
            add_action( 'awpcp-task-queue-cron', array( $task_queue, 'task_queue_event' ) );
        } elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $this->ajax_setup();
        } elseif ( is_admin() ) {
            $this->admin_setup();

            // load resources required in admin screens only
            add_action( 'edit_user_profile', array( $contact_information_controller, 'show_contact_information_fields' ) );
            add_action( 'edit_user_profile_update', array( $contact_information_controller, 'save_contact_information' ) );

            $pointers_manager = awpcp_pointers_manager();
            add_action( 'admin_enqueue_scripts', array( $pointers_manager, 'register_pointers' ) );
            add_action( 'admin_enqueue_scripts', array( $pointers_manager, 'setup_pointers' ) );

            $helper = awpcp_url_backwards_compatibility_redirection_helper();
            add_action( 'wp_loaded', array( $helper, 'maybe_redirect_admin_request' ) );

            $page_events = $this->container['WordPressPageEvents'];
            add_action( 'post_updated', array( $page_events, 'post_updated' ), 10, 3 );

            if ( awpcp_current_user_is_admin() ) {
                // load resources required in admin screens only, visible to admin users only.
                add_action( 'admin_notices', array( awpcp_fee_payment_terms_notices(), 'dispatch' ) );
                add_action( 'admin_notices', array( awpcp_credit_plans_notices(), 'dispatch' ) );
                add_action( 'admin_notices', array( awpcp_delete_browse_categories_page_notice(), 'maybe_show_notice' ) );
                add_action( 'admin_notices', array( awpcp_missing_paypal_merchant_id_setting_notice(), 'maybe_show_notice' ) );

                // TODO: do we really need to execute this every time the plugin settings are saved?
                if ( class_exists( 'AWPCP_License_Settings_Update_Handler' ) ) {
                    $handler = new AWPCP_License_Settings_Update_Handler();
                    add_action( 'update_option_' . $this->settings->setting_name, array( $handler, 'process_settings' ), 10, 2 );
                }

                if ( class_exists( 'AWPCP_License_Settings_Actions_Request_Handler' ) ) {
                    $handler = new AWPCP_License_Settings_Actions_Request_Handler();
                    add_action( 'wp_redirect', array( $handler, 'dispatch' ) );
                }
            }
        } else {
            // load resources required in frontend screens only.
            add_action( 'wp', array( $this->container['LoopIntegration'], 'setup' ) );

            add_action( 'template_redirect', array( awpcp_secure_url_redirection_handler(), 'dispatch' ) );
            add_action( 'template_redirect', array($this, 'disable_oembeds' ));

            // TODO: This is not necessary for new installations
            $helper = awpcp_url_backwards_compatibility_redirection_helper();
            add_action( 'parse_request', array( $helper, 'maybe_redirect_from_old_listing_url' ) );
            add_action( 'template_redirect', array( $helper, 'maybe_redirect_frontend_request' ) );

            add_action( 'template_redirect', array( awpcp_authentication_redirection_handler(), 'maybe_redirect' ) );
            add_action( 'template_redirect', array( awpcp_browse_categories_page_redirection_handler(), 'maybe_redirect' ) );

            $filter = awpcp_wordpress_status_header_filter();
            add_filter( 'status_header', array( $filter, 'filter_status_header' ), 10, 4 );

            $listings_content = $this->container['ListingsContent'];
            add_filter( 'pre_handle_404', [$this, 'return_pending_post'], 10, 2 );
            add_filter( 'pre_handle_404', [$this, 'redirect_deleted_ads'], 10, 2 );
            add_filter( 'pre_handle_404', [$this, 'expired_ads'], 10, 2 );
            add_filter( 'the_content', array( $listings_content, 'filter_content' ) );

            // Remove shortcodes from listing content
            add_filter( 'the_content', array( $listings_content, 'filter_content_with_shortcodes' ), 7 );

            add_filter( 'awpcp-content-before-listing-page', 'awpcp_insert_classifieds_bar_before_listing_page' );
        }

        add_filter( 'awpcp-content-placeholders', array( $this, 'register_content_placeholders' ) );

        $listing_form_fields = awpcp_listing_form_fields();
        add_filter( 'awpcp_listing_details_form_fields', array( $listing_form_fields, 'register_listing_details_form_fields' ), 10, 1 );
        add_filter( 'awpcp_listing_date_form_fields', array( $listing_form_fields, 'register_listing_date_form_fields' ), 10, 2 );

        if ( get_option( 'awpcp-store-browse-categories-page-information' ) ) {
            $this->store_browse_categories_page_information();
        }

        if ( get_option( 'awpcp-maybe-fix-browse-categories-page-information' ) ) {
            $this->maybe_fix_browse_categories_page_information();
        }

        $this->register_notification_handlers();
    }

    /**
     * Make sure disabled posts are returned in the posts array
     * in order to avoid a not found page and display a message instead.
     *
     * @param bool     $handle_404
     * @param WP_Query $wp_query
     *
     * @return bool
     */
    public function redirect_deleted_ads( $handle_404, $wp_query ) {
        $classifieds_page_url = awpcp_get_main_page_url();
        if ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] === AWPCP_LISTING_POST_TYPE && $wp_query->post_count === 0 && get_awpcp_option( '301redirection' ) && $classifieds_page_url ) {
            wp_safe_redirect( $classifieds_page_url, 301 );
            exit();
        }

        return $handle_404;
    }

    /**
     * Returns pending ad to avoid WordPress default 404 handling and display a message instead.
     */
    public function return_pending_post( $handle_404, $query ) {
        $post_id = $query->get( 'p' );
        $post_type = $query->get('post_type');
        $post    = get_post( $post_id );
        // get our post instead and return it as the result...
        if ( ! empty( $post ) && $post_type === AWPCP_LISTING_POST_TYPE && $post->post_status === 'pending' ) {
            $query->posts = array($post);
            $query->post = $post;
            $query->post_count = 1;
            return true;
        }

        return false;
    }

    /**
     * Return expired ad so owner can renew.
     *
     * @return bool
     */
    public function expired_ads( $handle_404, $query ) {
        $post_id = $query->get( 'p' );
        $post_type = $query->get('post_type');
        if ( $post_type !== AWPCP_LISTING_POST_TYPE ) {
            return false;
        }

        $author_id = get_current_user_id();
        $post    = get_post( $post_id );
        $expired = get_post_meta($post_id, '_awpcp_expired', true);
        // get our post instead and return it as the result...
        if ( $post && $expired && absint( $post->post_author ) === $author_id && $post->post_status === 'disabled' ) {
            $query->posts = array($post);
            $query->post = $post;
            $query->post_count = 1;
            return true;
        }

        return false;
    }

    /**
     * Disables oembeds when displaying single awpcp listings and html is not allowed.
     */
    public function disable_oembeds() {
        if ( is_singular( AWPCP_LISTING_POST_TYPE ) && !get_awpcp_option('allowhtmlinadtext') ) {
            remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
        }
    }

    /**
     * Verifications that need to be done after the plugin is installed or updated,
     * and/or after all settings have been loaded/defined and custom post types/taxonmies
     * have been registered.
     */
    public function first_time_verifications() {
        if ( get_option( 'awpcp-installed' ) ) {
            do_action( 'awpcp-installed' );

            update_option( 'awpcp-installed', false );
        }

        if ( get_option( 'awpcp-installed-or-upgraded' ) ) {
            $this->plugin_integrations->discover_supported_plugin_integrations();

            $roles_and_capabilities = awpcp_roles_and_capabilities();
            add_action( 'wp_loaded', array( $roles_and_capabilities, 'setup_roles_capabilities' ) );

            awpcp_pages_creator()->restore_missing_pages();

            update_option( 'awpcp-installed-or-upgraded', false );
        }

        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_personal_data_exporters' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_personal_data_erasers' ) );

        if ( get_option( 'awpcp-flush-rewrite-rules' ) ) {
            flush_rewrite_rules();

            update_option( 'awpcp-flush-rewrite-rules', false );
        }
    }

    private function ajax_setup() {
        add_action( 'admin_init', [ $this, 'register_ajax_handlers' ] );
        add_action( 'admin_init', [ $this, 'register_listing_actions_handlers' ] );
        add_action( 'admin_init', [ $this, 'register_categories_actions_handlers' ] );
    }

    /**
     * Needs to run after awpcp-configure-routes actions is fired to give premium
     * modules enough time to register handlers for their Ajax actions.
     *
     * @since 4.0.0
     */
    public function register_ajax_handlers() {
        // register ajax request handler for pending upgrade tasks
        $task_handler = $this->container['UpgradeTaskAjaxHandler'];

        foreach ( $this->upgrade_tasks->get_pending_tasks() as $slug => $task ) {
            add_action( "wp_ajax_$slug", array( $task_handler, 'ajax' ) );
        }

        // load resources required to handle Ajax requests only.
        $handler = $this->container['CreateEmptyListingAjaxHandler'];
        add_action( 'wp_ajax_awpcp_create_empty_listing', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_create_empty_listing', [ $handler, 'ajax' ] );

        $handler = $this->container['UpdateListingOrderAjaxHandler'];
        add_action( 'wp_ajax_awpcp_update_listing_order', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_update_listing_order', [ $handler, 'ajax' ] );

        $handler = $this->container['UpdateSubmitListingSectionsAjaxHandler'];
        add_action( 'wp_ajax_awpcp_update_submit_listing_sections', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_update_submit_listing_sections', [ $handler, 'ajax' ] );

        $handler = $this->container['SaveListingInformationAjaxHandler'];
        add_action( 'wp_ajax_awpcp_save_listing_information', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_save_listing_information', [ $handler, 'ajax' ] );

        $handler = $this->container['GenerateListingPreviewAjaxHandler'];
        add_action( 'wp_ajax_awpcp_generate_listing_preview', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_generate_listing_preview', [ $handler, 'ajax' ] );

        $handler = $this->container['ExecuteListingActionAjaxHandler'];
        add_action( 'wp_ajax_awpcp_execute_listing_action', [ $handler, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_awpcp_execute_listing_action', [ $handler, 'ajax' ] );

        $handler = awpcp_users_autocomplete_ajax_handler();
        add_action( 'wp_ajax_awpcp-autocomplete-users', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-autocomplete-users', array( $handler, 'ajax' ) );

        $handler = awpcp_set_attachment_as_featured_ajax_handler();
        add_action( 'wp_ajax_awpcp-set-file-as-primary', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-set-file-as-primary', array( $handler, 'ajax' ) );

        $handler = awpcp_update_file_enabled_status_ajax_handler();
        add_action( 'wp_ajax_awpcp-update-file-enabled-status', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-update-file-enabled-status', array( $handler, 'ajax' ) );

        $handler = awpcp_delete_attachment_ajax_handler();
        add_action( 'wp_ajax_awpcp-delete-file', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-delete-file', array( $handler, 'ajax' ) );

        $handler = awpcp_update_attachment_allowed_status_ajax_handler();
        add_action( 'wp_ajax_awpcp-approve-file', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-approve-file', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_awpcp-reject-file', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-reject-file', array( $handler, 'ajax' ) );

        $handler = awpcp_upload_listing_media_ajax_handler();
        add_action( 'wp_ajax_awpcp-upload-listing-media', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-upload-listing-media', array( $handler, 'ajax' ) );

        $handler = awpcp_upload_generated_thumbnail_ajax_handler();
        add_action( 'wp_ajax_awpcp-upload-generated-thumbnail', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-upload-generated-thumbnail', array( $handler, 'ajax' ) );

        $handler = awpcp_update_form_fields_order_ajax_handler();
        add_action( 'wp_ajax_awpcp-update-form-fields-order', array( $handler, 'ajax' ) );

        add_action( 'awpcp-file-handlers', array( $this, 'register_file_handlers' ) );

        $handler = awpcp_drip_autoresponder_ajax_handler();
        add_action( 'wp_ajax_awpcp-autoresponder-user-subscribed', array( $handler, 'ajax' ) );
        add_action( 'wp_ajax_awpcp-autoresponder-dismissed', array( $handler, 'ajax' ) );

        $handler = awpcp_add_credit_plan_ajax_handler();
        add_action( 'wp_ajax_awpcp-credit-plans-add', array( $handler, 'ajax') );

        $handler = awpcp_edit_credit_plan_ajax_handler();
        add_action( 'wp_ajax_awpcp-credit-plans-edit', array( $handler, 'ajax' ) );

        $handler = awpcp_delete_credit_plan_ajax_handler();
        add_action( 'wp_ajax_awpcp-credit-plans-delete', array( $handler, 'ajax' ) );

        // fees admin
        $handler = awpcp_delete_fee_ajax_handler();
        add_action( 'wp_ajax_awpcp-fees-delete', array( $handler, 'ajax' ) );

        $handler = awpcp_default_layout_ajax_handler();
        add_action( 'wp_ajax_awpcp-layout-default', array( $handler, 'ajax' ) );

        $handler = new AWPCP_Import_Listings_Ajax_Handler();
        add_action( 'wp_ajax_awpcp-import-listings', array( $handler, 'ajax' ) );

        $handler = awpcp_dismiss_notice_ajax_handler();
        add_action( 'wp_ajax_awpcp-dismiss-notice', array( $handler, 'ajax' ) );

        add_action( 'wp_ajax_awpcp-test-ssl-client', [ awpcp()->container['TestSSLClientAjaxHandler'], 'ajax' ] );

        $ajax_request_handler = awpcp_ajax_request_handler( $this->router->get_routes() );
        $this->router->register_ajax_request_handler( $ajax_request_handler );

        $export_csv = $this->container['ExportListingsAdminPage'];
        add_action( 'wp_ajax_awpcp-csv-export', [ $export_csv, 'ajax' ] );

        $handler = $this->container['UpdatePaymentTerm'];
        add_action( 'wp_ajax_awpcp-update-payment-term', array( $handler, 'ajax' ) );

        $view_counter = $this->container['ListingsViewCounter'];
        add_action( 'wp_ajax_awpcp-ad-count-view', array( $view_counter, 'ajax' ) );
        add_action( 'wp_ajax_nopriv_awpcp-ad-count-view', array( $view_counter, 'ajax' ) );
    }

    /**
     * @since 4.0.0
     */
    public function register_listing_actions_handlers() {
        add_filter( 'awpcp-custom-listing-action-delete-ad', array( $this->container['DeleteListingActionHandler'], 'do_action' ), 10, 2 );
    }

    /**
     * @since 4.0.16
     */
    public function register_categories_actions_handlers() {
        add_action( 'created_' . AWPCP_CATEGORY_TAXONOMY, array( awpcp_categories_logic(), 'update_category_order' ), 10, 1 );
    }

    /**
     * Called from init() admin requests.
     *
     * @since 4.0.0
     */
    public function admin_setup() {
        add_action( 'admin_init', array( $this->container['Admin'], 'admin_init' ) );
        add_action( 'admin_init', [ $this->container['SettingsIntegration'], 'setup' ] );

        add_action( 'load-options-reading.php', function () {
            $integration = $this->container['ReadingSettingsIntegration'];

            add_action( 'wp_dropdown_pages', [ $integration, 'filter_plugin_pages' ], 10, 3 );
        } );

        add_action( 'awpcp_settings_renderers', [ $this, 'register_settings_renderers' ] );
    }

    public function admin_notices() {
        foreach (awpcp_get_property($this, 'errors', array()) as $error) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_error($error);
        }

        if ( ! function_exists( 'imagecreatefrompng' ) ) {
            $this->missing_gd_library_notice();
        }
    }

    /**
     * @since 4.0.0
     */
    public function wp_loaded() {
        $this->setup_runtime_options();
        $this->router->configure_routes();
        $this->setup_javascript_data();

        if ( (int) $this->settings->get_option( 'awpcppagefilterswitch' ) === 1 ) {
            add_filter( 'wp_list_pages_excludes', 'exclude_awpcp_child_pages' );
        }
    }

    private function missing_gd_library_notice() {
        $message = __( "AWPCP requires the graphics processing library GD and it is not installed. Contact your web host to fix this.", 'another-wordpress-classifieds-plugin' );
        $message = sprintf( '<strong>%s</strong> %s', __( 'Warning', 'another-wordpress-classifieds-plugin' ), $message );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_print_error( $message );
    }

    /**
     * Returns information about available and installed
     * premium modules.
     *
     * @since  3.0
     */
    public function get_premium_modules_information() {
        static $modules = null;

        if ( is_null( $modules ) ) {
            $modules = array(
                'attachments' => array(
                    'name' => __( 'Attachments', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/attachments-module/?ref=panel',
                    'installed' => defined( 'AWPCP_ATTACHMENTS_MODULE' ),
                    'version' => 'AWPCP_ATTACHMENTS_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'authorize.net' => array(
                    'name' => __(  'Authorize.Net', 'another-wordpress-classifieds-plugin'  ),
                    'url' => 'https://awpcp.com/downloads/authorizenet-module/?ref=user-panel',
                    'installed' => defined( 'AWPCP_AUTHORIZE_NET_MODULE' ),
                    'version' => 'AWPCP_AUTHORIZE_NET_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'buddypress-listings' => array(
                    'name' => __( 'BuddyPress Listings', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/buddypress-module/?ref=panel',
                    'installed' => defined( 'AWPCP_BUDDYPRESS_LISTINGS_MODULE_DB_VERSION' ),
                    'version' => 'AWPCP_BUDDYPRESS_LISTINGS_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'campaign-manager' => array(
                    'name' => __( 'Campaign Manager', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/campaign-manager-module/?ref=panel',
                    'installed' => defined( 'AWPCP_CAMPAIGN_MANAGER_MODULE' ),
                    'version' => 'AWPCP_CAMPAIGN_MANAGER_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'category-icons' => array(
                    'name' => __( 'Category Icons', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/category-icons-module/?ref=panel',
                    'installed' => defined( 'AWPCP_CATEGORY_ICONS_MODULE_DB_VERSION' ),
                    'version' => 'AWPCP_CATEGORY_ICONS_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'comments' => array(
                    'name' => __(  'Comments & Ratings', 'another-wordpress-classifieds-plugin'  ),
                    'url' => 'https://awpcp.com/downloads/comments-ratings-module/?ref=panel',
                    'installed' => defined( 'AWPCP_COMMENTS_MODULE' ),
                    'version' => 'AWPCP_COMMENTS_MODULE_VERSION',
                    'required' => '4.0.1',
                ),
                'coupons' => array(
                    'name' => __( 'Coupons/Discount', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/coupons-module/?ref=panel',
                    'installed' => defined( 'AWPCP_COUPONS_MODULE' ),
                    'version' => 'AWPCP_COUPONS_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'extra-fields' => array(
                    'name' => __( 'Extra Fields', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/extra-fields-module/?ref=panel',
                    'installed' => defined( 'AWPCP_EXTRA_FIELDS_MODULE' ),
                    'version' => 'AWPCP_EXTRA_FIELDS_MODULE_DB_VERSION',
                    'required' => '4.0.1',
                ),
                'featured-ads' => array(
                    'name' => __( 'Featured Ads', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/featured-ads-module/?ref=panel',
                    'installed' => defined( 'AWPCP_FEATURED_ADS_MODULE' ),
                    'version' => 'AWPCP_FEATURED_ADS_MODULE_DB_VERSION',
                    'required' => '4.0.1',
                ),
                'fee-per-category' => array(
                    'name' => __( 'Fee per Category', 'another-wordpress-classifieds-plugin' ),
                    'url' =>'https://awpcp.com/downloads/fee-category-module/?ref=panel',
                    'installed' => defined( 'AWPCP_FPC_MODULE_DB_VERSION' ),
                    'version' => 'AWPCP_FPC_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'mark-as-sold' => array(
                    'name' => __( 'Mark as Sold', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/mark-as-sold-module/?ref=panel',
                    'installed' => defined( 'AWPCP_MARK_AS_SOLD_MODULE' ),
                    'version' => 'AWPCP_MARK_AS_SOLD_MODULE_DB_VERSION',
                    'required' => '4.0.1',
                ),
                'paypal-pro' => array(
                    'name' => __(  'PayPal Pro', 'another-wordpress-classifieds-plugin'  ),
                    'url' => 'https://awpcp.com/downloads/paypal-pro-module/?ref=user-panel',
                    'installed' => defined( 'AWPCP_PAYPAL_PRO_MODULE' ),
                    'version' => 'AWPCP_PAYPAL_PRO_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'region-control' => array(
                    'name' => __( 'Regions Control', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/regions-module/?ref=panel',
                    'installed' => defined( 'AWPCP_REGION_CONTROL_MODULE' ),
                    'version' => 'AWPCP_REGION_CONTROL_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'rss' => array(
                    'name' => __( 'RSS', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/rss-feeds-module/?ref=panel',
                    'installed' => defined( 'AWPCP_RSS_MODULE' ),
                    'version' => 'AWPCP_RSS_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'stripe' => array(
                    'name' => __( 'Stripe', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/stripe-module/',
                    'installed' => defined( 'AWPCP_STRIPE_MODULE_DB_VERSION' ),
                    'version' => 'AWPCP_STRIPE_MODULE_DB_VERSION',
                    'required' => '4.0.0',
                ),
                'subscriptions' => array(
                    'name' => __( 'Membership to Post', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/downloads/subscriptions-module/?ref=panel',
                    'installed' => defined( 'AWPCP_SUBSCRIPTIONS_MODULE' ),
                    'version' => 'AWPCP_SUBSCRIPTIONS_MODULE_DB_VERSION',
                    'required'  => '4.0.1',
                ),
                'videos' => array(
                    'name' => __( 'Videos', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/premium-modules/',
                    'installed' => defined( 'AWPCP_VIDEOS_MODULE' ),
                    'version' => 'AWPCP_VIDEOS_MODULE_DB_VERSION',
                    'required' => '4.0.0-RC1',
                    'private' => true,
                ),
                'xml-sitemap' => array(
                    'name' => __( 'XML Sitemap', 'another-wordpress-classifieds-plugin'  ),
                    'url' => 'https://awpcp.com/premium-modules/',
                    'installed' => function_exists( 'awpcp_generate_ad_entries' ),
                    'version' => 'AWPCP_XML_SITEMAP_MODULE_DB_VERSION',
                    'required'  => '4.0.0',
                    'removed'   => true,
                ),
                'zip-code-search' => array(
                    'name' => __( 'ZIP Code Search Module', 'another-wordpress-classifieds-plugin' ),
                    'url' => 'https://awpcp.com/premium-modules/',
                    'installed' => defined( 'AWPCP_ZIP_CODE_SEARCH_MODULE_DB_VERSION' ),
                    'version' => 'AWPCP_ZIP_CODE_SEARCH_MODULE_DB_VERSION',
                    'required' => '4.0.1',
                ),
            );
        }

        return $modules;
    }

    /**
     * @since 3.0.2
     */
    public function is_compatible_with( $module, $version ) {
        $modules = $this->get_premium_modules_information();

        if ( ! isset( $modules[ $module ] ) ) {
            return false;
        }

        if ( version_compare( $version, $modules[ $module ]['required'], '<' ) ) {
            return false;
        }

        return true;
    }

    private function store_browse_categories_page_information() {
        $page_info = get_option( 'awpcp-browse-categories-page-information' );

        if ( isset( $page_info['page_id'] ) ) {
            delete_option( 'awpcp-store-browse-categories-page-information' );
            return;
        }

        $page_id = awpcp_get_page_id_by_ref( 'browse-categories-page-name' );

        if ( 0 === (int) $page_id ) {
            delete_option( 'awpcp-store-browse-categories-page-information' );
            return;
        }

        $page = get_post( $page_id );

        if ( $page && $page->post_status === 'trash' ) {
            $desired_post_slug = get_post_meta( $page_id, '_wp_desired_post_slug', true );
            $page_uri = get_page_uri( $page_id );

            if ( $desired_post_slug ) {
                $page_uri = str_replace( $page->post_name, $desired_post_slug, $page_uri );
            } else {
                $page_uri = str_replace( '__trashed', '', $page_uri );
            }
        } elseif ( $page ) {
            $page_uri = get_page_uri( $page_id );
        } else {
            $page_uri = '';
        }

        $page_info = array( 'page_id' => $page_id, 'page_uri' => $page_uri );

        update_option( 'awpcp-browse-categories-page-information', $page_info, false );
        update_option( 'awpcp-show-delete-browse-categories-page-notice' , true, false );

        delete_option( 'awpcp-store-browse-categories-page-information' );
    }

    private function maybe_fix_browse_categories_page_information() {
        $page_info = get_option( 'awpcp-browse-categories-page-information' );

        if ( empty( $page_info['page_uri'] ) ) {
            delete_option( 'awpcp-maybe-fix-browse-categories-page-information' );
            return;
        }

        if ( ! string_ends_with( $page_info['page_uri'], '__trashed' ) ) {
            delete_option( 'awpcp-maybe-fix-browse-categories-page-information' );
            return;
        }

        $page = get_post( $page_info['page_id'] );

        if ( $page && $page->post_status === 'trash' ) {
            $desired_post_slug = get_post_meta( $page->ID, '_wp_desired_post_slug', true );
        } else {
            $desired_post_slug = '';
        }

        if ( $desired_post_slug ) {
            $page_info['page_uri'] = str_replace( $page->post_name, $desired_post_slug, $page_info['page_uri'] );
        } else {
            $page_info['page_uri'] = str_replace( '__trashed', '', $page_info['page_uri'] );
        }

        update_option( 'awpcp-browse-categories-page-information', $page_info, false );

        delete_option( 'awpcp-maybe-fix-browse-categories-page-information' );
    }

    /**
     * A good place to register all AWPCP standard scripts that can be
     * used from other sections.
     */
    public function register_scripts() {
        global $wp_styles;
        global $wp_scripts;

        global $awpcp_db_version;

        $js      = AWPCP_URL . '/resources/js';
        $css     = AWPCP_URL . '/resources/css';
        $vendors = AWPCP_URL . '/resources/vendors';

        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        /* vendors */

        if (isset($wp_scripts->registered['jquery-ui-core'])) {
            $ui_version = $wp_scripts->registered['jquery-ui-core']->ver;
        } else {
            $ui_version = '1.9.2';
        }

        wp_register_style(
            'awpcp-jquery-ui',
            "$vendors/jquery-ui.css",
            array(),
            $ui_version
        );

        wp_register_script('awpcp-jquery-validate', "{$js}/jquery-validate/all.js", array('jquery'), '1.10.0', true);
        wp_register_script(
            'awpcp-knockout',
            "$vendors/knockout-min.js",
            array(),
            '3.5.0',
            true
        );

        wp_register_script(
            'awpcp-lightgallery',
            "$vendors/lightgallery/js/lightgallery.min.js",
            array( 'jquery' ),
            '1.2.22',
            true
        );

        wp_register_style(
            'awpcp-lightgallery',
            "$vendors/lightgallery/css/lightgallery.min.css",
            array(),
            '1.2.22'
        );

        wp_register_style(
            'awpcp-font-awesome',
            "$vendors/fontawesome/css/all.min.css",
            array(),
            '5.2.0'
        );

        $this->maybe_register_script(
            'breakpoints.js',
            $vendors . '/breakpoints.js/breakpoints' . $min . '.js',
            array( 'jquery' ),
            '0.0.10',
            true
        );

        wp_register_script(
            'awpcp-jquery-usableform',
            $js . '/jquery-usableform/jquery-usableform' . $min . '.js',
            array( 'jquery' ),
            $awpcp_db_version,
            true
        );

        wp_register_script(
            'awpcp-knockout-progress',
            $js . '/knockout-progress/knockout-progress' . $min . '.js',
            array( 'awpcp' ),
            $awpcp_db_version,
            true
        );

        /**
         * If WooCommerce 3.2.0 or newer is active, we use their fork of select2
         * to avoid conflicts.
         *
         * See https://github.com/woocommerce/woocommerce/pull/15792
         */
        if ( awpcp_should_register_select2_script() ) {
            wp_register_style(
                'select2',
                "{$vendors}/selectWoo/css/select2.min.css",
                array(),
                '4.0.5'
            );

            wp_register_script(
                'select2',
                "{$vendors}/selectWoo/js/select2.full.min.js",
                array('jquery'),
                '4.0.5',
                true
            );
        }

        wp_register_script(
            'daterangepicker',
            "$vendors/daterangepicker/daterangepicker.min.js",
            [ 'jquery', 'moment' ],
            '3.0.3',
            true
        );

        wp_register_style(
            'daterangepicker',
            "$vendors/daterangepicker.min.css",
            [],
            '3.0.3'
        );

        /* helpers */
        $src = ! $min && file_exists( AWPCP_DIR . '/resources/js/awpcp.src.js' ) ? '.src' : '.min';
        wp_register_script(
            'awpcp',
            $js . '/awpcp' . $src . '.js',
            [
                'jquery',
                'backbone',
                'underscore',
                'awpcp-knockout',
                awpcp_get_select2_script_handle(),
                'breakpoints.js',
            ],
            $awpcp_db_version,
            true
        );

        wp_register_script( 'awpcp-admin', "{$js}/awpcp-admin.min.js", array( 'awpcp', 'awpcp-jquery-validate' ), $awpcp_db_version, true );

        wp_register_script(
            'awpcp-admin-edit-post',
            $js . '/admin/edit-post' . $min . '.js',
            array(
                'awpcp',
                'awpcp-jquery-validate',
                'jquery-ui-datepicker',
            ),
            $awpcp_db_version,
            true
        );

        wp_register_script( 'awpcp-billing-form', "{$js}/awpcp-billing-form.js", array( 'awpcp' ), $awpcp_db_version, true );
        wp_register_script( 'awpcp-multiple-region-selector', "{$js}/awpcp-multiple-region-selector.js", array( 'awpcp', 'awpcp-jquery-validate' ), $awpcp_db_version, true );

        wp_register_script('awpcp-admin-wp-table-ajax', "{$js}/admin-wp-table-ajax.js", array('jquery-form'), $awpcp_db_version, true);

        // register again with old name too (awpcp-table-ajax-admin), for backwards compatibility
        wp_register_script('awpcp-table-ajax-admin', "{$js}/admin-wp-table-ajax.js", array('jquery-form'), $awpcp_db_version, true);

        wp_register_script('awpcp-toggle-checkboxes', "{$js}/checkuncheckboxes.js", array('jquery'), $awpcp_db_version, true);

        /* admin */

        wp_register_style(
            'awpcp-admin-menu',
            "{$css}/awpcp-admin-menu.css",
            awpcp_maybe_add_asset_dependencies( [ 'awpcp-font-awesome' => 'enqueue-font-awesome-style' ] ),
            $awpcp_db_version
        );

        wp_register_style('awpcp-admin-style', "{$css}/awpcp-admin.css", array(), $awpcp_db_version);

        wp_register_script('awpcp-admin-general', "{$js}/admin-general.js", array('awpcp'), $awpcp_db_version, true);
        wp_register_script('awpcp-admin-settings', "{$js}/admin-settings.js", array('awpcp-admin'), $awpcp_db_version, true);
        wp_register_script('awpcp-admin-fees', "{$js}/admin-fees.js", array('awpcp-admin-wp-table-ajax'), $awpcp_db_version, true);

        wp_register_script(
            'awpcp-admin-fee-details',
            $js . '/admin-fee-details' . $min . '.js',
            array( 'awpcp', 'awpcp-jquery-usableform' ),
            $awpcp_db_version,
            true
        );

        wp_register_script('awpcp-admin-credit-plans', "{$js}/admin-credit-plans.js", array('awpcp-admin-wp-table-ajax'), $awpcp_db_version, true);
        wp_register_script( 'awpcp-admin-listings', "{$js}/admin-listings.js", array( 'awpcp', 'awpcp-admin-wp-table-ajax', 'plupload-all' ), $awpcp_db_version, true );
        wp_register_script('awpcp-admin-users', "{$js}/admin-users.js", array('awpcp-admin-wp-table-ajax'), $awpcp_db_version, true);
        wp_register_script( 'awpcp-admin-attachments', "{$js}/admin-attachments.js", array( 'awpcp' ), $awpcp_db_version, true );

        wp_register_script(
            'awpcp-admin-import',
            "{$js}/admin-import.js",
            array(
                'awpcp',
                'awpcp-jquery-usableform',
                'awpcp-knockout-progress',
                'jquery-ui-datepicker',
                'jquery-ui-autocomplete',
                awpcp_get_select2_script_handle(),
            ),
            $awpcp_db_version,
            true
        );

        wp_register_script(
            'awpcp-admin-export',
            "{$js}/admin-export.js",
            array( 'awpcp' ),
            $awpcp_db_version,
            true
        );

        wp_register_style(
            'awpcp-admin-export-style',
            "{$css}/awpcp-admin-export.css",
            array(),
            $awpcp_db_version
        );

        wp_register_script(
            'awpcp-admin-listings-table',
            $js . '/admin/listings-table' . $min . '.js',
            [ 'awpcp', 'daterangepicker' ],
            $awpcp_db_version,
            true
        );

        wp_register_script( 'awpcp-admin-form-fields', "{$js}/admin-form-fields.js", array( 'awpcp', 'jquery-ui-sortable', 'jquery-effects-highlight', 'jquery-effects-core' ), $awpcp_db_version, true );

        wp_register_script(
            'awpcp-admin-manual-upgrade',
            "{$js}/admin-manual-upgrade.js",
            array(
                'awpcp',
                'awpcp-knockout-progress',
                'moment',
            ),
            $awpcp_db_version,
            true
        );

        wp_register_script(
            'awpcp-admin-pointers',
            $js . '/admin-pointers.min.js',
            array( 'awpcp', 'wp-pointer' ),
            $awpcp_db_version,
            true
        );

        wp_register_script(
            'awpcp-admin-debug',
            $js . '/admin/debug-admin-page' . $min . '.js',
            array(),
            $awpcp_db_version,
            true
        );

        /* frontend */

        wp_register_style(
            'awpcp-frontend-style',
            "{$css}/awpcpstyle.css",
            awpcp_maybe_add_asset_dependencies( [ 'awpcp-font-awesome' => 'enqueue-font-awesome-style' ] ),
            $awpcp_db_version
        );

        wp_register_script(
            'awpcp-page-place-ad',
            "{$js}/page-place-ad.js",
            array(
                'awpcp',
                'awpcp-multiple-region-selector',
                'awpcp-jquery-validate',
                awpcp_get_select2_script_handle(),
                'jquery-ui-datepicker',
                'jquery-ui-autocomplete',
                'plupload-all',
                'backbone',
            ),
            $awpcp_db_version,
            true
        );

        $dependencies = [
            'awpcp',
            'awpcp-jquery-validate',
        ];

        wp_register_script(
            'awpcp-submit-listing-page',
            $js . '/frontend/submit-listing-page.min.js',
            apply_filters( 'awpcp_submit_listing_page_script_dependencies', $dependencies ),
            $awpcp_db_version,
            true
        );

        $dependencies = array('awpcp', 'awpcp-multiple-region-selector', 'awpcp-jquery-validate', 'jquery-ui-datepicker');
        wp_register_script('awpcp-page-search-listings', "{$js}/page-search-listings.js", $dependencies, $awpcp_db_version, true);

        wp_register_script('awpcp-page-reply-to-ad', "{$js}/page-reply-to-ad.js", array('awpcp', 'awpcp-jquery-validate'), $awpcp_db_version, true);

        wp_register_script(
            'awpcp-page-show-ad',
            "{$js}/page-show-ad.js",
            array(
                'awpcp',
            ),
            $awpcp_db_version,
            true
        );

        wp_register_script(
            'awpcp-ad-counter',
            "{$js}/ad-counter.js",
            array(
                'jquery',
            ),
            $awpcp_db_version,
            true
        );
    }

    /**
     * Register a script ocassionally replacing a previously registered script
     * with the same handle if our version is more recent.
     */
    private function maybe_register_script( $handle, $src, $deps, $ver, $in_footer = false ) {
        $scripts = wp_scripts();

        if ( isset( $scripts->registered[ $handle ] ) ) {
            $registered_script = $scripts->registered[ $handle ];
        } else {
            $registered_script = null;
        }

        if ( $registered_script && version_compare( $registered_script->ver, $ver, '>=' ) ) {
            return;
        }

        if ( $registered_script ) {
            wp_deregister_script( $handle );
        }

        wp_register_script( $handle, $src, $deps, $ver, $in_footer );
    }

    /**
     * Finds and register a custom stylsheet to be included
     * right after the plugin's main stylesheet.
     */
    public function register_custom_style() {
        global $awpcp_db_version;

        $location_alternatives = array(
            get_stylesheet_directory() => get_stylesheet_directory_uri(),
            get_template_directory() => get_template_directory_uri(),
            WP_PLUGIN_DIR => plugins_url(),
        );

        $stylesheet_url = null;

        foreach ( $location_alternatives as $directory_path => $directory_url ) {
            if ( file_exists( $directory_path . '/awpcp-custom.css' ) ) {
                $stylesheet_url = $directory_url . '/awpcp-custom.css';
                break;
            }

            if ( file_exists( $directory_path . '/awpcp_custom_stylesheet.css' ) ) {
                $stylesheet_url = $directory_url . '/awpcp_custom_stylesheet.css';
                break;
            }
        }

        if ( ! $stylesheet_url ) {
            return;
        }

        wp_register_style(
            'awpcp-custom-css',
            $stylesheet_url,
            array( 'awpcp-frontend-style' ),
            $awpcp_db_version,
            'all'
        );
    }

    public function enqueue_scripts() {
        if ( is_admin() ) {
            wp_enqueue_style( 'awpcp-admin-menu' );
        }

        if ( is_awpcp_admin_page() ) {
            wp_enqueue_style( 'awpcp-admin-style' );
            wp_enqueue_script('awpcp-admin-general');
            wp_enqueue_script('awpcp-toggle-checkboxes');

            $options = array(
                'ajaxurl' => awpcp_ajaxurl(),
                'nonce'   => wp_create_nonce( 'awpcp_ajax' ),
            );
            wp_localize_script('awpcp-admin-general', 'AWPCPAjaxOptions', $options);
        } elseif ( ! is_admin() ) {
            $query = awpcp_query();

            if ( $query->is_post_listings_page() || $query->is_edit_listing_page() || $query->is_single_listing_page() ) {
                awpcp_maybe_include_lightbox_style();
            }

            if ( $query->is_single_listing_page() ) {
                wp_enqueue_script('awpcp-ad-counter');
            }

            wp_enqueue_style('awpcp-frontend-style');
            wp_enqueue_style('awpcp-custom-css');
        }
    }

    public function localize_scripts() {
        $scripts = awpcp_wordpress_scripts();

        /*localize jQuery Validate messages.*/
        $this->js->set( 'default-validation-messages', array(
            'required' => __( 'This field is required.', 'another-wordpress-classifieds-plugin' ),
            'email' => __( 'Please enter a valid email address.', 'another-wordpress-classifieds-plugin' ),
            'url' => __( 'Please enter a valid URL.', 'another-wordpress-classifieds-plugin' ),
            'classifiedsurl' => __( 'Please enter a valid URL.', 'another-wordpress-classifieds-plugin' ),
            'number' => __( 'Please enter a valid number.', 'another-wordpress-classifieds-plugin' ),
            'money' => __( 'Please enter a valid amount.', 'another-wordpress-classifieds-plugin' ),
            'maxCategories'  => __( 'You have reached the maximum allowed categories for the selected fee plan.', 'another-wordpress-classifieds-plugin' ),
        ) );

        global $wp_locale;

        $this->js->localize( 'datepicker', array(

            'prevText' => _x( '&#x3c;Prev', '[UI Datepicker] Display text for previous month link', 'another-wordpress-classifieds-plugin' ),
            'nextText' => _x( 'Next&#x3e;', '[UI Datepicker] Display text for next month link', 'another-wordpress-classifieds-plugin' ),
            'monthNames' => array_values( $wp_locale->month ), // Names of months for drop-down and formatting
            'monthNamesShort' => array_values( $wp_locale->month_abbrev ), // For formatting
            'dayNames' => array_values( $wp_locale->weekday ),
            'dayNamesShort' => array_values( $wp_locale->weekday_abbrev ), // For formatting
            'dayNamesMin' => array_values( $wp_locale->weekday_initial ), // Column headings for days starting at Sunday
            'firstDay' => intval( _x( '0', '[UI Datepicker] The first day of the week, Sun = 0, Mon = 1, ...', 'another-wordpress-classifieds-plugin' ) ),
            'isRTL' => $wp_locale->text_direction === 'ltr' ? false : true, // True if right-to-left language, false if left-to-right
        ) );

        $this->js->localize( 'media-uploader-beforeunload', array(
            'files-are-being-uploaded' => __( 'There are files currently being uploaded.', 'another-wordpress-classifieds-plugin' ),
            'files-pending-to-be-uploaded' => __( 'There are files pending to be uploaded.', 'another-wordpress-classifieds-plugin' ),
            'no-files-were-uploaded' => __( "You haven't uploaded any images or files.", 'another-wordpress-classifieds-plugin' ),
        ) );

        if ( $scripts->script_will_be_printed( 'awpcp' ) ) {
            $this->js->set( 'ajaxurl', awpcp_ajaxurl() );
            $this->js->set( 'decimal-separator', get_awpcp_option( 'decimal-separator' ) );
            $this->js->set( 'thousands-separator', get_awpcp_option( 'thousands-separator' ) );

            $this->js->print_data();
        }
    }

    public function register_content_placeholders( $placeholders ) {
        $handler = awpcp_edit_listing_url_placeholder();
        $placeholders['edit_listing_url'] = array( 'callback' => array( $handler, 'do_placeholder' ) );

        $handler = awpcp_edit_listing_link_placeholder();
        $placeholders['edit_listing_link'] = array( 'callback' => array( $handler, 'do_placeholder' ) );

        return $placeholders;
    }

    /**
     * Register other AWPCP settings, normally for private use.
     */
    public function register_settings() {
        $this->settings_manager->add_setting( [
            'id'      => 'show-quick-start-guide-notice',
            'type'    => 'checkbox',
            'default' => false,
            'section' => 'private-settings',
        ] );
    }

    /**
     * @since 2.2.2
     */
    public function register_payment_term_types($payments) {
        $payments->register_payment_term_type(new AWPCP_FeeType());
    }

    /**
     * @since  2.2.2
     */
    public function register_payment_methods($payments) {
        if (get_awpcp_option('activatepaypal')) {
            $payments->register_payment_method( awpcp_paypal_standard_payment_gateway() );
        }

        if (get_awpcp_option('activate2checkout')) {
            $payments->register_payment_method(new AWPCP_2CheckoutPaymentGateway());
        }
    }

    /**
     * @since 3.0-beta
     */
    public function register_widgets() {
        register_widget("AWPCP_LatestAdsWidget");
        register_widget('AWPCP_RandomAdWidget');
        register_widget( 'AWPCP_Search_Widget' );
        register_widget( 'AWPCP_CategoriesWidget' );
    }

    /**
     * @since 3.8.6
     */
    public function register_personal_data_exporters( $exporters ) {
        $exporters['another-wordpres-classifieds-plugin-user'] = array(
            'exporter_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'               => array(
                new AWPCP_PersonalDataExporter( $this->get_user_personal_data_provider() ),
                'export_personal_data',
            ),
        );

        $exporters['another-wordpres-classifieds-plugin-listings'] = array(
            'exporter_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'               => array(
                new AWPCP_PersonalDataExporter( $this->get_listings_personal_data_provider() ),
                'export_personal_data',
            ),
        );

        $exporters['another-wordpres-classifieds-plugin-payment'] = array(
            'exporter_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'               => array(
                new AWPCP_PersonalDataExporter( $this->get_payment_personal_data_provider() ),
                'export_personal_data',
            ),
        );

        return $exporters;
    }

    /**
     * @since 3.8.6
     */
    private function get_user_personal_data_provider() {
        static $instance;

        if ( is_null( $instance ) ) {
            $instance = new AWPCP_UserPersonalDataProvider(
                $this->get_data_formatter()
            );
        }

        return $instance;
    }

    /**
     * @since 3.8.6
     */
    public function get_data_formatter() {
        static $instance;

        if ( is_null( $instance ) ) {
            $instance = new AWPCP_DataFormatter();
        }

        return $instance;
    }

    /**
     * @since 3.8.6
     */
    private function get_listings_personal_data_provider() {
        static $instance;

        if ( is_null( $instance ) ) {
            $instance = new AWPCP_ListingsPersonalDataProvider(
                $this->container['ListingsCollection'],
                $this->container['ListingRenderer'],
                $this->container['ListingsLogic'],
                $this->container['AttachmentsCollection'],
                $this->get_data_formatter()
            );
        }

        return $instance;
    }

    /**
     * @since 3.8.6
     */
    private function get_payment_personal_data_provider() {
        static $instance;

        if ( is_null( $instance ) ) {
            $instance = new AWPCP_PaymentPersonalDataProvider( $this->get_data_formatter() );
        }

        return $instance;
    }

    /**
     * @since 3.8.6
     */
    public function register_personal_data_erasers( $erasers ) {
        $erasers['another-wordpress-classifieds-plugin-user'] = array(
            'eraser_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'             => array(
                new AWPCP_PersonalDataEraser( $this->get_user_personal_data_provider() ),
                'erase_personal_data',
            ),
        );

        $erasers['another-wordpres-classifieds-plugin-listings'] = array(
            'eraser_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'               => array(
                new AWPCP_PersonalDataEraser( $this->get_listings_personal_data_provider() ),
                'erase_personal_data',
            ),
        );

        $erasers['another-wordpres-classifieds-plugin-payment'] = array(
            'eraser_friendly_name' => __( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
            'callback'               => array(
                new AWPCP_PersonalDataEraser( $this->get_payment_personal_data_provider() ),
                'erase_personal_data',
            ),
        );

        return $erasers;
    }

    public function register_notification_handlers() {
        $media_uploaded_notification = awpcp_media_uploaded_notification();
        add_action( 'awpcp-media-uploaded', array( $media_uploaded_notification, 'maybe_schedule_notification' ), 10, 2 );
        add_action( 'awpcp-media-uploaded-notification', array( $media_uploaded_notification, 'send_notification' ) );
        if (get_awpcp_option('send-ad-enabled-email')) {
            add_action('awpcp_approve_ad', 'awpcp_ad_enabled_email');
        }
    }

    public function register_file_handlers( $file_handlers ) {
        $file_handlers['image'] = array(
            'mime_types' => $this->settings->get_runtime_option( 'image-mime-types' ),
            'constructor' => 'ImageFileHandler',
        );

        return $file_handlers;
    }

    public function get_container_configurations() {
        $configurations[] = new AWPCP_ContainerConfiguration();
        $configurations[] = new AWPCP_WordPressContainerConfiguration();
        $configurations[] = new AWPCP_SettingsContainerConfiguration();
        $configurations[] = new AWPCP_ListingsContainerConfiguration();
        $configurations[] = new AWPCP_MediaContainerConfiguration();
        $configurations[] = new AWPCP_FrontendContainerConfiguration();
        $configurations[] = new AWPCP_AdminContainerConfiguration();
        $configurations[] = new AWPCP_UpgradeContainerConfiguration();

        return apply_filters( 'awpcp_container_configurations', $configurations );
    }

    /**------------------------------------------------------------------------
     * Payment Transaction Integration
     */

    /**
     * Set payment status to Not Required in requiredtransactions made by
     * admin users.
     *
     * @since  2.2.2
     */
    public function process_transaction_update_payment_status($transaction) {
        switch ($transaction->get_status()) {
            case AWPCP_Payment_Transaction::STATUS_OPEN:
                if (awpcp_current_user_is_admin())
                    $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED;
                break;
        }
    }

    /**
     * WP Affiliate Platform integration.
     *
     * Notifies WP Affiliate Platform plugin when a transaction
     * that involves money exchange has been completed.
     *
     * @since 3.0.2
     */
    public function process_transaction_notify_wp_affiliate_platform($transaction) {
        if ( ! ( $transaction->is_payment_completed() || $transaction->is_completed() ) ) {
            return;
        }

        if ( $transaction->payment_is_not_required() ) {
            return;
        }

        if ( ! $transaction->was_payment_successful() ) {
            return;
        }

        $allowed_context = array( 'add-credit', 'place-ad', 'renew-ad', 'buy-subscription' );
        $context = $transaction->get('context');

        if ( ! in_array( $context, $allowed_context , true) ) {
            return;
        }

        $amount = $transaction->get_total_amount();

        if ( $amount <= 0 ) {
            return;
        }

        $unique_transaction_id = $transaction->id;
        $referrer = isset( $_COOKIE['ap_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ap_id'] ) ) : null;
        $email = '';

        if ( $transaction->get( 'ad_id' ) ) {
            $email = awpcp_wordpress()->get_post_meta( $transaction->get( 'ad_id' ), '_awpcp_contact_email', true );
        } elseif ( $transaction->user_id ) {
            $user = get_userdata( $transaction->user_id );
            $email = $user->user_email;
        }

        $data = array(
            'sale_amt' => $amount,
            'txn_id'=> $unique_transaction_id,
            'referrer' => $referrer,
            'buyer_email' => $email,
        );

        do_action( 'wp_affiliate_process_cart_commission', $data );
    }

    /**
     * Handler for AJAX request from the Multiple Region Selector to get new options
     * for a given field.
     *
     * @since 3.0.2
     */
    public function get_regions_options() {
        $type        = awpcp_get_var( array( 'param' => 'type' ),'get' );
        $parent_type = awpcp_get_var( array( 'param' => 'parent_type' ),'get' );
        $parent      = awpcp_get_var( array( 'param' => 'parent' ),'get' );
        $context     = awpcp_get_var( array( 'param' => 'context' ),'get' );

        $options = apply_filters( 'awpcp-get-regions-options', false, $type, $parent_type, $parent, $context );

        $response = array( 'status' => 'ok', 'options' => $options );

        header( "Content-Type: application/json" );
        echo wp_json_encode($response);
        die();
    }

    public function register_listing_actions( $actions, $listing ) {
        $this->maybe_add_listing_action( $actions, $listing, new AWPCP_DeleteListingAction() );
        $this->maybe_add_listing_action( $actions, $listing, new AWPCP_RenewListingAction() );
        return $actions;
    }

    private function maybe_add_listing_action( &$actions, $listing, $action ) {
        if ( $action->is_enabled_for_listing( $listing ) ) {
            $actions[ $action->get_slug() ] = $action;
        }
    }
}
