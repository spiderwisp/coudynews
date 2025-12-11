<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin uninstaller.
 */
class AWPCP_Uninstaller {

    /**
     * @var string
     */
    private $plugin_basename;

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var object
     */
    private $listings_logic;

    /**
     * @var object
     */
    private $listings_collection;

    /**
     * @var object
     */
    private $categories_logic;

    /**
     * @var object
     */
    private $categories_collection;

    /**
     * @var object
     */
    private $roles_and_capabilities;

    /**
     * @var object
     */
    private $settings;

    /**
     * @var object
     */
    private $db;

    /**
     * @param string $plugin_basename           The plugin's basename.
     * @param string $listing_post_type         The identifier for the Listing post type.
     * @param object $listings_logic            An instance of Listings Logic.
     * @param object $listings_collection       An instance of Listings Collection.
     * @param object $categories_logic          An instance of Categories Logic.
     * @param object $categories_collection     An instance of Categories Collection.
     * @param object $roles_and_capabilities    An instance of Roles And Capabilities.
     * @param object $settings                  An instance of Settings.
     * @param object $db                        An instance of wpdb.
     * @since 4.0.0
     */
    public function __construct( $plugin_basename, $listing_post_type, $listings_logic, $listings_collection, $categories_logic, $categories_collection, $roles_and_capabilities, $settings, $db ) {
        $this->plugin_basename        = $plugin_basename;
        $this->listing_post_type      = $listing_post_type;
        $this->listings_logic         = $listings_logic;
        $this->listings_collection    = $listings_collection;
        $this->categories_logic       = $categories_logic;
        $this->categories_collection  = $categories_collection;
        $this->roles_and_capabilities = $roles_and_capabilities;
        $this->settings               = $settings;
        $this->db                     = $db;
    }

    /**
     * @since 4.0.0     Migrated for Installer class.
     */
    public function uninstall() {
        $this->delete_classifieds_pages();
        $this->delete_categories_and_associated_listings();
        $this->delete_listings();
        $this->remove_uploads_directory();
        $this->drop_custom_tables();
        $this->delete_options();
        $this->delete_metadata();
        $this->remove_widgets();
        $this->remove_roles_and_capabilities();
        $this->clear_scheduled_events();
        $this->deactivate_plugin();
    }

    /**
     * @since 4.0.0
     */
    private function delete_classifieds_pages() {
        foreach ( awpcp_get_plugin_pages_ids() as $page_id ) {
            wp_delete_post( $page_id, true );
        }
    }

    /**
     * @since 4.0.0     Migrated from Installer.
     */
    private function delete_categories_and_associated_listings() {
        foreach ( $this->categories_collection->find_categories() as $category ) {
            try {
                $this->categories_logic->delete_category_and_associated_listings( $category );
            } catch ( AWPCP_Exception $e ) {
                continue;
            }
        }
    }

    /**
     * @since 4.0.0     Migrated from Installer.
     */
    private function delete_listings() {
        foreach ( $this->listings_collection->find_listings() as $listing ) {
            $this->listings_logic->delete_listing( $listing );
        }

        $this->db->query( $this->db->prepare( "DELETE FROM {$this->db->posts} WHERE post_type LIKE %s", $this->listing_post_type ) );
    }

    /**
     * @since 4.0.0
     */
    private function remove_uploads_directory() {
        list( $uploads_dir ) = awpcp_setup_uploads_dir();

        if ( file_exists( $uploads_dir ) ) {
            require_once AWPCP_DIR . '/includes/class-fileop.php';

            $fileop = new fileop();
            $fileop->delete( $uploads_dir );
        }
    }

    /**
     * @since 4.0.0
     */
    private function drop_custom_tables() {
        $tables = array(
            $this->db->prefix . 'awpcp_adfees',
            $this->db->prefix . 'awpcp_ads',
            $this->db->prefix . 'awpcp_ad_regions',
            $this->db->prefix . 'awpcp_admeta',
            $this->db->prefix . 'awpcp_media',
            $this->db->prefix . 'awpcp_categories',
            $this->db->prefix . 'awpcp_payments',
            $this->db->prefix . 'awpcp_credit_plans',
            $this->db->prefix . 'awpcp_pages',
            $this->db->prefix . 'awpcp_tasks',
            $this->db->prefix . 'awpcp_adsettings',
            $this->db->prefix . 'awpcp_adphotos',
            $this->db->prefix . 'awpcp_pagename',
            $this->db->prefix . 'awpcp_comments',

            $this->db->prefix . 'awpcp_user_ratings',
            $this->db->prefix . 'awpcp_extra_fields',
            $this->db->prefix . 'awpcp_subscriptions',
            $this->db->prefix . 'awpcp_subscription_plans',
            $this->db->prefix . 'awpcp_subscription_ads',
            $this->db->prefix . 'awpcp_advertisement_positions',
            $this->db->prefix . 'awpcp_campaigns',
            $this->db->prefix . 'awpcp_campaign_positions',
            $this->db->prefix . 'awpcp_campaign_sections',
            $this->db->prefix . 'awpcp_campaign_section_positions',
            $this->db->prefix . 'awpcp_categories_prices',
            $this->db->prefix . 'awpcp_coupons',
            $this->db->prefix . 'awpcp_listing_zip_codes',
            $this->db->prefix . 'awpcp_zip_codes',
            $this->db->prefix . 'awpcp_regions',
        );

        foreach ( $tables as $table ) {
            $this->db->query( 'DROP TABLE IF EXISTS ' . esc_sql( $table ) );
        }
    }

    /**
     * @since 4.0.0
     */
    private function delete_options() {
        $options_to_delete = array(
            $this->settings->setting_name,
            'awpcp-activated',
            'awpcp-flush-rewrite-rules',
            'awpcp-form-fields-order',
            'awpcp-legacy-categories',
            'awpcp-pending-manual-upgrade',
            'awpcp-plugin-pages',
            'awpcp-debug',
            'awpcp-regions-children',
            'awpcp-regions-type-hierarchy',
            'awpcp_db_version',
            'awpcp_installationcomplete',
            'awpcp_listing_category_children',
            'awpcp_pagename_warning',
            'widget_awpcplatestads',
        );

        $option_names = $this->db->get_col( "SELECT option_name FROM {$this->db->options} WHERE option_name LIKE '%awpcp%'" );

        foreach ( $option_names as $option_name ) {
            if ( preg_match( '/^awpcp-category-icon/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/^awpcp-fee-categories-prices/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/^awpcp.*(installed|db)[_-]version$/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/^awpcp-.*-id$/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/^awpcp-messages/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/^awpcp-payment-transaction-/', $option_name ) ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/awpcp-categories-list-cache/', $option_name ) !== false ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/awpcp-license-status-check/', $option_name ) !== false ) {
                $options_to_delete[] = $option_name;
            } elseif ( preg_match( '/awpcp-region-control-duplicated-regions/', $option_name ) !== false ) {
                $options_to_delete[] = $option_name;
            }
        }

        array_map( 'delete_option', $options_to_delete );
    }

    /**
     * @since 4.0.0
     */
    private function delete_metadata() {
        $blog_prefix = $this->db->get_blog_prefix();

        $this->db->query( "DELETE FROM {$this->db->postmeta} WHERE meta_key LIKE '_awpcp_%'" );
        $this->db->query( "DELETE FROM {$this->db->postmeta} WHERE meta_key LIKE '__awpcp_%'" );

        $this->db->query( "DELETE FROM {$this->db->usermeta} WHERE meta_key LIKE '{$blog_prefix}awpcp-%'" );
    }

    /**
     * @since 4.0.0
     */
    private function remove_widgets() {
        awpcp_unregister_widget_if_exists( 'AWPCP_LatestAdsWidget' );
        awpcp_unregister_widget_if_exists( 'AWPCP_RandomAdWidget' );
        awpcp_unregister_widget_if_exists( 'AWPCP_Search_Widget' );
    }

    /**
     * @since 4.0.0
     */
    private function remove_roles_and_capabilities() {
        array_map(
            array( $this->roles_and_capabilities, 'remove_administrator_capabilities_from_role' ),
            $this->roles_and_capabilities->get_administrator_roles_names()
        );

        array_map(
            array( $this->roles_and_capabilities, 'remove_subscriber_capabilities_from_role' ),
            $this->roles_and_capabilities->get_subscriber_roles_names()
        );

        $this->roles_and_capabilities->remove_moderator_role();
    }

    /**
     * @since 4.0.0
     */
    private function clear_scheduled_events() {
        wp_clear_scheduled_hook( 'doadexpirations_hook' );
        wp_clear_scheduled_hook( 'doadcleanup_hook' );
        wp_clear_scheduled_hook( 'awpcp_ad_renewal_email_hook' );
        wp_clear_scheduled_hook( 'awpcp-clean-up-payment-transactions' );
    }

    /**
     * @since 4.0.0
     */
    private function deactivate_plugin() {
        deactivate_plugins( $this->plugin_basename );
    }
}
