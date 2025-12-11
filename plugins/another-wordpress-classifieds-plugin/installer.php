<?php
/**
 * Installation and Upgrade functions
 *
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

define('AWPCP_TABLE_ADFEES', $wpdb->prefix . "awpcp_adfees");
define('AWPCP_TABLE_ADS', $wpdb->prefix . "awpcp_ads");
define('AWPCP_TABLE_AD_REGIONS', $wpdb->prefix . "awpcp_ad_regions");
define('AWPCP_TABLE_AD_META', $wpdb->prefix . 'awpcp_admeta');
define('AWPCP_TABLE_MEDIA', $wpdb->prefix . "awpcp_media");
define('AWPCP_TABLE_CATEGORIES', $wpdb->prefix . "awpcp_categories");
define('AWPCP_TABLE_PAYMENTS', $wpdb->prefix . 'awpcp_payments');
define('AWPCP_TABLE_CREDIT_PLANS', $wpdb->prefix . 'awpcp_credit_plans');
define('AWPCP_TABLE_PAGES', $wpdb->prefix . "awpcp_pages");
define('AWPCP_TABLE_TASKS', $wpdb->prefix . "awpcp_tasks");

// TODO: Remove references to AWPCP_TABLE_ADPHOTOS constant when the routines
// to upgrade to 2.x are removed from the codebase.
define( 'AWPCP_TABLE_ADPHOTOS', $wpdb->prefix . 'awpcp_adphotos' );

// TODO: remove these constants after another major release (Added in 3.5.3)
define( 'AWPCP_TABLE_PAGENAME', $wpdb->prefix . 'awpcp_pagename' );

function awpcp_installer() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_Installer();
    }

    return $instance;
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Installer {

    private $upgrade_tasks;
    private $plugin_tables;
    private $settings;

    public function __construct() {
        $this->upgrade_tasks = awpcp_upgrade_tasks_manager();
        $this->plugin_tables = awpcp_database_tables();
        $this->settings      = awpcp_settings_api();
    }

    public function activate() {
        $this->install_or_upgrade();

        update_option( 'awpcp-activated', true );

        if ( get_transient( AWPCP_OnboardingWizard::TRANSIENT_NAME ) !== 'no' ) {
            set_transient(
                AWPCP_OnboardingWizard::TRANSIENT_NAME,
                AWPCP_OnboardingWizard::TRANSIENT_VALUE,
                60
            );
        }
    }

    public function install_or_upgrade() {
        global $awpcp_db_version;

        $installed_version = get_option( 'awpcp_db_version' );

        if ( ! $this->is_version_number( $awpcp_db_version ) ) {
            // Something is wrong. The version extracted from the plugin's headers
            // is not a valid version number.

            // We create a log entry for debug purposes, but abort the operation.
            $this->log_upgrade( $installed_version, $awpcp_db_version );

            return;
        }

        if ( $installed_version !== false && ! $this->is_version_number( $installed_version ) ) {
            // Something is wrong. The installed version should always be false
            // or a valid version number.

            // We create a log entry for debug purposes, but abort the operation.
            $this->log_upgrade( $installed_version, $awpcp_db_version );

            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // if table exists, this is an upgrade
        if ( $installed_version !== false && awpcp_table_exists( AWPCP_TABLE_PAYMENTS ) ) {
            $this->upgrade( $installed_version, $awpcp_db_version );
        } else {
            $this->install( $awpcp_db_version );
        }

        update_option( 'awpcp-installed-or-upgraded', true );
    }

    /**
     * @since 3.8.4
     */
    private function is_version_number( $version_string ) {
        return preg_match( '/^\d[\d.]*/', $version_string );
    }

    private function log_upgrade( $oldversion, $newversion ) {
        $upgrade_log = get_option( 'awpcp-upgrade-log', array() );

        $upgrade_log[] = array(
            'oldversion'    => $oldversion,
            'newversion'    => $newversion,
            'PHP_SELF'      => awpcp_get_server_value( 'PHP_SELF' ),
            'DOCUMENT_ROOT' => awpcp_get_server_value( 'DOCUMENT_ROOT' ),
            'SERVER_NAME'   => awpcp_get_server_value( 'SERVER_NAME' ),
            'REQUEST_URI'   => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
            'QUERY_STRING'  => isset( $_SERVER['QUERY_STRING'] ) ? esc_url_raw( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '',
            'date'          => current_time( 'mysql' ),
        );

        // Keep latest 100 entries. This should prevent filling the database with
        // log entries if `awpcp_db_version` is set to something invalid permanently.
        $upgrade_log = array_slice( $upgrade_log, 0, 100 );

        update_option( 'awpcp-upgrade-log', $upgrade_log );
    }

    /**
     * Creates AWPCP tables.
     */
    public function install( $version ) {
        global $awpcp, $wpdb;

        dbDelta( $this->plugin_tables->get_listing_meta_table_definition() );
        dbDelta( $this->plugin_tables->get_listing_regions_table_definition() );
        dbDelta( $this->plugin_tables->get_fees_table_definition() );
        dbDelta( $this->plugin_tables->get_payments_table_definition() );
        dbDelta( $this->plugin_tables->get_credit_plans_table_definition() );
        dbDelta( $this->plugin_tables->get_tasks_table_definition() );

        // insert default Fee
        $fee = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE adterm_id = 1',
                AWPCP_TABLE_ADFEES
            )
        );
        if ( empty( $fee ) ) {
            $data = array(
                'adterm_id' => 1,
                'adterm_name' => __( '30 Day Listing', 'another-wordpress-classifieds-plugin' ),
                'amount' => 9.99,
                'recurring' => 1,
                'rec_period' => 30,
                'rec_increment' => 'D',
                'buys' => 0,
                'imagesallowed' => 6,
            );

            $wpdb->insert(AWPCP_TABLE_ADFEES, $data);
        }

        $result = update_option( 'awpcp_db_version', $version );

        $awpcp->settings->update_option('show-quick-start-guide-notice', true, true);
        $awpcp->settings->update_option( 'show-drip-autoresponder', true, true );

        update_option( 'awpcp-installed', true );
        do_action('awpcp_install');

        return $result;
    }

    // TODO: remove settings table after another major release
    // TODO: remove pages table after another major release (Added in 3.5.3)
    public function upgrade($oldversion, $newversion) {
        foreach ( $this->get_upgrade_routines() as $version => $routines ) {
            if ( version_compare( $oldversion, $version ) >= 0 ) {
                continue;
            }

            foreach ( (array) $routines as $routine ) {
                if ( method_exists( $this, $routine ) ) {
                    $this->{$routine}( $oldversion );
                }
            }
        }

        do_action( 'awpcp_upgrade', $oldversion, $newversion );

        $this->log_upgrade( $oldversion, $newversion );

        return update_option( "awpcp_db_version", $newversion );
    }

    /**
     * TODO: Update upgrade system to ensure that tasks are executed in the
     * order they are defined here.
     *
     * In 4.0.0beta10 routines were still executed in the order they are registered
     * in Manual Upgrade Tasks.
     */
    private function get_upgrade_routines() {
        // You have to use at least major.minor.patch version numbers.
        return array(
            '4.0.0beta1' => array(
                'create_old_listing_id_column_in_listing_regions_table',
                'migrate_wordpress_page_settings',
                'migrate_reply_to_ad_email_template',
                'migrate_verify_email_message_email_template',
                'keep_legacy_url_structure',
                'remove_old_capabilities',
                'enable_upgrade_routine_to_migrate_listing_categories',
                'enable_upgrade_routine_to_migrate_listings',
            ),
            '4.0.0beta2' => array(
                'enable_routine_to_fix_id_collision_for_listing_categories',
            ),
            '4.0.0beta4' => [
                'enable_routine_to_store_categories_order_as_term_meta',
            ],
            '4.0.0beta5' => [
                'rename_translation_files_using_outdated_textdomain',
            ],
            '4.0.0beta6' => [
                'enable_routine_to_fix_id_collision_for_listings',
            ],
            '4.0.0beta8' => [
                'enable_routine_to_force_post_id',
            ],
            '4.0.0beta13' => [
                'fix_old_listing_id_metadata',
                'maybe_enable_routine_to_update_categories_term_count',
                'maybe_enable_upgrade_routines_to_migrate_media',
            ],
            '4.0.0' => [
                'enable_routine_to_add_missing_is_paid_meta',
                'enable_routine_to_add_missing_views_meta',
                'delete_settings_table',
            ],
            '4.0.2' => [
                'remove_invalid_admin_editor_metadata',
            ],
            '4.0.5' => [
                'enable_routine_to_update_most_recent_date',
            ],
            '4.0.6' => [
                'enable_routine_to_add_default_awpcp_order',
            ],
            '4.0.7' => [
                'enable_routine_to_add_awpcp_contact_phone_number_digits',
            ],
            '4.0.14' => [
                'increase_adfee_table_amount_field_max_value',
            ],
        );
    }

    private function create_old_listing_id_column_in_listing_regions_table() {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_AD_REGIONS, 'old_listing_id' ) ) {
            $wpdb->query(
                $wpdb->prepare(
                    'ALTER TABLE %i ADD `old_listing_id` INT(10) NOT NULL AFTER `ad_id`',
                    AWPCP_TABLE_AD_REGIONS
                )
            );
        }

        $wpdb->update(
            AWPCP_TABLE_AD_REGIONS,
            array( 'old_listing_id' => 'ad_id' ),
            array( 'old_listing_id' => 0 )
        );
    }

    private function migrate_wordpress_page_settings() {
        $pages = get_option( 'awpcp-plugin-pages' );

        if ( empty( $pages ) ) {
            return;
        }

        foreach ( $pages as $page_ref => $page_info ) {
            if ( empty( $page_info['page_id'] ) ) {
                continue;
            }

            awpcp_update_plugin_page_id( $page_ref, $page_info['page_id'] );
        }
    }

    /**
     * @since 4.0.0
     */
    private function migrate_reply_to_ad_email_template() {
        $previous_subject = $this->settings->get_option( 'contactformsubjectline', __( 'Response to your AWPCP Demo Ad', 'another-wordpress-classifieds-plugin' ) );
        $previous_body    = $this->settings->get_option( 'contactformbodymessage', __( 'Someone has responded to your AWPCP Demo Ad', 'another-wordpress-classifieds-plugin' ) );

        $template = $this->settings->get_option( 'contact-form-user-notification-email-template' );

        if ( ! empty( $template ) ) {
            // We already migrated the settings or someone provided a new value first. Abort.
            return;
        }

        $template = [
            'subject' => _x( "{__previous_subject__} regarding: {listing_title}", 'reply to ad email', 'another-wordpress-classifieds-plugin' ),
            'body'    => _x( "{__previous_body__}\n\nContact name: {sender_name}\nContact email: {sender_email}\n\nContacting about {listing_title}\n{listing_url}\n\nMessage:\n\n{message}\n\n{website_title}\n{website_url}", 'reply to ad email', 'another-wordpress-classifieds-plugin' ),
            'version' => '4.0.0',
        ];

        $template['subject'] = str_replace( '{__previous_subject__}', $previous_subject, $template['subject'] );
        $template['body']    = str_replace( '{__previous_body__}', $previous_body, $template['body'] );

        $this->settings->set_or_update_option( 'contact-form-user-notification-email-template', $template );
    }

    /**
     * @since 4.0.0
     */
    private function migrate_verify_email_message_email_template() {
        $previous_subject = $this->settings->get_option( 'verifyemailsubjectline', __( 'Verify the email address used for Ad $title', 'another-wordpress-classifieds-plugin' ) );
        $previous_body    = $this->settings->get_option( 'verifyemailbodymessage', __( "Hello \$author_name \n\nYou recently posted the Ad \$title to \$website_name. \n\nIn order to complete the posting process you have to verify your email address. Please click the link below to complete the verification process. You will be redirected to the website where you can see your Ad. \n\n\$verification_link \n\nAfter you verify your email address, the administrator will be notified about the new Ad. If moderation is enabled, your Ad will remain in a disabled status until the administrator approves it.\n\n\$website_name\n\n\$website_url", 'another-wordpress-classifieds-plugin' ) );

        $template = $this->settings->get_option( 'verify-email-message-email-template' );

        if ( ! empty( $template ) ) {
            // We already migrated the settings or someone provided a new value first. Abort.
            return;
        }

        $template = [
            'subject'  => $previous_subject,
            'body'     => $previous_body,
            'version'  => '4.0.0',
        ];

        $template['subject'] = str_replace( '$title', '{listing_title}', $template['subject'] );

        $template['body'] = str_replace( '$title', '{listing_title}', $template['body'] );
        $template['body'] = str_replace( '$author_name', '{author_name}', $template['body'] );
        $template['body'] = str_replace( '$verification_link', '{verification_link}', $template['body'] );
        $template['body'] = str_replace( '$website_name', '{website_title}', $template['body'] );
        $template['body'] = str_replace( '$website_url', '{website_url}', $template['body'] );

        $this->settings->set_or_update_option( 'verify-email-message-email-template', $template );
    }

    /**
     * @since 4.0.0
     */
    private function keep_legacy_url_structure() {
        $this->settings->set_or_update_option( 'display-listings-as-single-posts', false );

        $main_plugin_page = awpcp_get_page_by_ref( 'main-page-name' );
        $show_listing_page = awpcp_get_page_by_ref( 'show-ads-page-name' );

        if ( $main_plugin_page && $show_listing_page && $show_listing_page->post_parent == $main_plugin_page->ID ) {
            $this->settings->set_or_update_option( 'listings-slug', $show_listing_page->post_name );
            $this->settings->set_or_update_option( 'include-main-page-slug-in-listing-url', true );
        } elseif ( $show_listing_page ) {
            $this->settings->set_or_update_option( 'listings-slug', get_page_uri( $show_listing_page ) );
            $this->settings->set_or_update_option( 'include-main-page-slug-in-listing-url', false );
        }
    }

    /**
     * @since 4.0.0
     */
    private function remove_old_capabilities() {
        $roles_and_capabilities = awpcp()->container['RolesAndCapabilities'];
        $capabilities           = [
            'manage_classifieds',
            'manage_classifieds_listings',
        ];

        $roles_and_capabilities->remove_capabilities( $capabilities );
    }

    private function enable_upgrade_routine_to_migrate_listing_categories() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-listing-categories-as-custom-taxonomies' );
    }

    private function enable_upgrade_routine_to_migrate_listings() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-listings-as-custom-post-types' );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_enable_upgrade_routines_to_migrate_media( $oldversion ) {
        // These upgrade routines were first introduced in 4.0.0beta1 and modified
        // for 4.0.0beta11 (See https://github.com/drodenbaugh/awpcp/issues/2201)
        // and 4.0.0beta13 (See https://github.com/drodenbaugh/awpcp/issues/2370).
        //
        // The routines continued to be included in other beta releases and enqueued
        // last, so that all blocking routines were always executed first, even those
        // introduced after 4.0.0beta1, and media migration (when necessary) was
        // always performed as a non-blocking routine.
        //
        // However, if the website is already using 4.0.0beta1 or superior, then there
        // is no need to enable the routine again.
        if ( version_compare( $oldversion, '4.0.0beta1', '>=' ) ) {
            return;
        }

        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-media-as-attachments-upgrade-task-handler' );
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-generate-thumbnails-for-migrated-media' );
    }

    /**
     * Version 4.0.0beta1 can create listing_category terms having an ID equal to
     * the ID of one of the categories stored in the awpcp_categories table.
     *
     * When that happens, that listing_cateogry becomes inaccessible because
     * the plugin will automatically redirect to the listing_category assocaited
     * with the category from the awpcp_categories table that has the same ID.
     *
     * This upgrade routine fixes that problem by replacing affected terms with
     * identical ones that have a different, non-conflicting ID.
     *
     * @since 4.0.0
     */
    private function enable_routine_to_fix_id_collision_for_listing_categories() {
        // TODO: We need to make each upgrade routine its own class so that we
        // can inject dependencies through the constructor.
        $collisions = awpcp_categories_registry()->get_id_collisions();

        if ( $collisions ) {
            $this->upgrade_tasks->enable_upgrade_task( 'awpcp-fix-id-collision-for-listing-categories' );
            delete_option( 'awpcp-ficflc-last-listing-id' );
        }
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_store_categories_order_as_term_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-categories-order-as-term-meta' );
    }

    /**
     * Many versions of the plugin used AWPCP as the textdomain for translations,
     * the filename prefix for PO and MO files, both official and custom.
     *
     * As we move towards providing translations using Language Packs exclusively,
     * we want to stop loading translation files using the old textdomain in their
     * names. This upgrade routine attempts to rename those files using the new
     * textdomain and to move them to the  wp-languages/another-wordpress-classifieds-plugin
     * directory.
     *
     * @since 4.0.0
     */
    private function rename_translation_files_using_outdated_textdomain() {
        if ( ! function_exists( 'glob' ) ) {
            return;
        }

        $filesystem = awpcp_get_wp_filesystem();

        // Ensure the target directory exists
        $target_dir = WP_LANG_DIR . '/another-wordpress-classifieds-plugin';
        if ( ! $filesystem->is_dir( $target_dir ) ) {
            $filesystem->mkdir( $target_dir, awpcp_get_dir_chmod() );
        }

        $basename = dirname( plugin_basename( AWPCP_FILE ) );

        // Historically we have loaded custom and official translation files from these directories.
        $directories = [
            WP_PLUGIN_DIR . "/$basename",
            WP_PLUGIN_DIR . "/$basename/languages",
            WP_LANG_DIR . '/another-wordpress-classifieds-plugin',
            WP_LANG_DIR . '/plugins',
        ];

        $files_to_move   = [];
        $files_not_moved = [];

        foreach ( $directories as $directory ) {
            $files_found   = glob( "$directory/AWPCP-*.{po,mo}", GLOB_BRACE );
            $files_to_move = array_merge( $files_to_move, $files_found );
        }

        foreach ( $files_to_move as $file ) {
            $filename = basename( $file );
            $filename = str_replace( 'AWPCP', 'another-wordpress-classifieds-plugin', $filename );

            $path = WP_LANG_DIR . "/another-wordpress-classifieds-plugin/$filename";

            if ( $filesystem->exists( $path ) ) {
                $files_not_moved[] = $file;
                continue;
            }

            $result = $filesystem->move( $file, $path, true );
            if ( ! $result ) {
                $files_not_moved[] = $file;
                continue;
            }
        }

        if ( $files_not_moved ) {
            update_option( 'awpcp_translation_files_with_outdated_textdomain', $files_not_moved, false );
        }
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_fix_id_collision_for_listings() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-fix-id-collision-for-listings' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_force_post_id() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-maybe-force-post-id' );

        update_option( 'awpcp_mfpi_maybe_force_post_id', true );
    }

    /**
     * Fix the name of the post meta that holds the old ID of the listing.
     *
     * Version 4.0.0beta1-4.0.0beta12 used an _awpcp_old_id meta to store the
     * ID that the listing was using in the custom tables. To improve the
     * performance of SQL queries trying to find listings by their old ID,
     * 4.0.0beta13 started including the old ID in the meta_key (_awpcp_old_id_1234).
     * That way queries no longer need to find a meta whose value matches the
     * old ID but can check whether a specifc meta_key exists instead.
     *
     * @since 4.0.0
     */
    private function fix_old_listing_id_metadata() {
        global $wpdb;
        $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = CONCAT('_awpcp_old_id_', meta_value) WHERE meta_key = '_awpcp_old_id'" );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_enable_routine_to_update_categories_term_count( $oldversion ) {
        // We started using wp_defer_term_counting() in 4.0.0beta13, but anyone
        // who is running 4.0.0beta1 or older already migrated the ads and
        // categories using the old version of the routine, so there is no need
        // to update term counts again (it was done automatically by WordPress
        // every time a categories were associated with ads).
        if ( version_compare( $oldversion, '4.0.0beta1', '>=' ) ) {
            return;
        }

        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-update-categories-term-count' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_add_missing_is_paid_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-is-paid-meta' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_add_missing_views_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-views-meta' );
    }

    /**
     * @since 4.0.0
     */
    private function delete_settings_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}awpcp_adsettings" );
    }

    /**
     * Remove pending data and validation errors stored by mistake.
     *
     * Versions 4.0.0 and 4.0.1 stored invalid pending data and validation errors
     * for the admin editor every time the post was saved during an admin request.
     * The method responsible for saving the information entered in the Listing
     * Fields metabox was being executed without checking whether the metabox was
     * actually being saved or not.
     *
     * This routine removes the invalid data.
     *
     * @since 4.0.2
     *
     * @see https://github.com/drodenbaugh/awpcp/issues/2557
     */
    private function remove_invalid_admin_editor_metadata() {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '__awpcp_admin_editor_pending_data'" );

        foreach ( $results as $postmeta ) {
            $meta_value = maybe_unserialize( $postmeta->meta_value );

            /*
             * If the metadata inclues values for at least one of the following
             * fields then it was created when the metabox was really being
             * saved and we don't need to remove it.
             */
            if ( ! empty( $meta_value['metadata']['_awpcp_contact_name'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_contact_email'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_contact_phone'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_website_url'] ) ) {
                continue;
            }

            delete_post_meta( $postmeta->post_id, '__awpcp_admin_editor_pending_data' );
            delete_post_meta( $postmeta->post_id, '__awpcp_admin_editor_validation_errors' );
        }
    }

    /**
     * @since 4.0.5
     */
    private function enable_routine_to_update_most_recent_date() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-update-most-recent-date' );
    }

    /**
     * @since 4.0.6
     */
    private function enable_routine_to_add_default_awpcp_order() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-categories-order' );
    }

    /**
     * @since 4.0.7
     */
    private function enable_routine_to_add_awpcp_contact_phone_number_digits() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-contact-phone-number-digits' );
    }

    /**
     * Increase pending data and validation errors stored by mistake.
     *
     * @since 4.0.14
     *
     * @see https://github.com/drodenbaugh/awpcp/issues/2970
     */
    private function increase_adfee_table_amount_field_max_value() {
        global $wpdb;

        if ( awpcp_column_exists( AWPCP_TABLE_ADFEES, 'amount' ) ) {
            $wpdb->query(
                $wpdb->prepare(
                    'ALTER TABLE %i MODIFY `amount` FLOAT(10,2) UNSIGNED NOT NULL DEFAULT 0.00',
                    AWPCP_TABLE_ADFEES
                )
            );
        }
    }
}

/**
 * Set tables charset to utf8 and text-based columns collate to utf8_general_ci.
 */
function awpcp_fix_table_charset_and_collate($tables) {
    global $wpdb;

    $tables = is_array($tables) ? $tables : array($tables);

    $types = array('varchar', 'char', 'text', 'enum', 'set');

    foreach ($tables as $table) {
        $wpdb->query(
            $wpdb->prepare( "ALTER TABLE %i CHARACTER SET utf8 COLLATE utf8_general_ci", $table )
        );

        $columns = $wpdb->get_results(
            $wpdb->prepare( "SHOW COLUMNS FROM %i", $table ),
            ARRAY_N
        );

        $parts      = array();
        $query_vars = array( $table );
        foreach ($columns as $col) {
            foreach ($types as $type) {
                if (strpos($col[1], $type) !== false) {
                    $definition = 'CHANGE %i %i %i ';
                    $query_vars[] = $col[0];
                    $query_vars[] = $col[0];
                    $query_vars[] = $col[1];
                    $definition.= "CHARACTER SET utf8 COLLATE utf8_general_ci ";
                    $definition.= strcasecmp($col[2], 'NO') === 0 ? 'NOT NULL ' : '';

                    // TEXT columns can't have a default value in Strict mode.
                    if ( $type !== 'text' ) {
                        if ( strcasecmp( $col[4], 'NULL' ) === 0 ) {
                            $definition .= 'DEFAULT NULL';
                        } else {
                            $definition .= 'DEFAULT %s';
                            $query_vars[] = $col[4];
                        }
                    }
                    $parts[] = $definition;
                    break;
                }
            }
        }

        $wpdb->query(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "ALTER TABLE %i " . join( ', ', $parts ),
                $query_vars
            )
        );
    }
}
