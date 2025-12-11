<?php
/**
 * @package AWPCP/Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Manual Upgrade Tasks class.
 */
function awpcp_manual_upgrade_tasks() {
    return awpcp()->container['ManualUpgradeTasks'];
}

/**
 * Registers the plugin manual upgrade routines that are enabled from other
 * routines on Installer during upgrade.
 *
 * Manual Upgrade Routines are routines that require the user to initiate them
 * from the admin dashboard and keep the browser tab open until all the steps
 * have been completed.
 *
 * If a manual upgrade routine is defined with blocking = true, then all other
 * plugin features are disabled until that upgrade routine is completed.
 */
class AWPCP_Manual_Upgrade_Tasks {

    /**
     * @var AWPCP_Upgrade_Tasks_Manager
     */
    private $upgrade_tasks;

    /**
     * Constructor.
     */
    public function __construct( $upgrade_tasks ) {
        $this->upgrade_tasks = $upgrade_tasks;
    }

    /**
     * Register manual upgrade rotuines.
     */
    public function register_upgrade_tasks() {
        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'    => 'awpcp-import-payment-transactions',
                'name'    => __( 'Import Payment Transactions', 'another-wordpress-classifieds-plugin' ),
                'handler' => 'ImportPaymentTransactionsTaskHandler',
                'context' => 'plugin',
            ]
        );

        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-migrate-regions-information',
            'name' => __( 'Migrate Regions Information', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'MigrateRegionsInformationTaskHandler',
            'context' => 'plugin',
        ) );

        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-migrate-media-information',
            'name' => __( 'Migrate Media Information', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'MigrateMediaInformationTaskHandler',
            'context' => 'plugin',
        ) );

        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-update-media-status',
            'name' => __( 'Update Image/Attachments Status', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'UpdateMediaStatusTaskHandler',
            'context' => 'plugin',
        ) );

        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-store-phone-number-digits',
            'name' => __( 'Store phone number digits', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'StorePhoneNumberDigitsUpgradeTaskHandler',
            'context' => 'plugin',
        ) );

        $this->register_upgrade_tasks_for_4_0_0();
    }

    /**
     * @since 4.0.0
     */
    private function register_upgrade_tasks_for_4_0_0() {
        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-store-listing-categories-as-custom-taxonomies',
            'name' => __( 'Store Listing Categories as Custom Taxonomies', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'StoreListingCategoriesAsCustomTaxonomiesUpgradeTaskHandler',
            'context' => 'plugin',
        ) );

        $this->upgrade_tasks->register_upgrade_task( array(
            'slug' => 'awpcp-store-listings-as-custom-post-types',
            'name' => __( 'Store Listings as Custom Post Types', 'another-wordpress-classifieds-plugin' ),
            'handler' => 'StoreListingsAsCustomPostTypesUpgradeTaskHandler',
            'context' => 'plugin',
        ) );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-fix-id-collision-for-listing-categories',
                'name'     => __( 'Fix ID Collision for Listing Categories', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'FixIDCollisionForListingCategoriesUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => true,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-store-categories-order-as-term-meta',
                'name'     => __( 'Store Categories Order as Term Meta', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'StoreCategoriesOrderAsTermMetaTaskHandler',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-maybe-force-post-id',
                'name'     => __( 'Maybe force the value for the next Post ID', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'MaybeForcePostIDUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => true,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-fix-id-collision-for-listings',
                'name'     => __( 'Fix ID Collision for Listings', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'FixIDCollisionForListingsUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => true,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-update-categories-term-count',
                'name'     => __( 'Update Categories Term Count', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'UpdateCategoriesTermCountUpgradeTaskRunner',
                'context'  => 'plugin',
                'blocking' => true,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-store-media-as-attachments-upgrade-task-handler',
                'name'     => __( 'Store Media as Attachments', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'StoreMediaAsAttachmentsUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-generate-thumbnails-for-migrated-media',
                'name'     => __( 'Generate Thumbnails for Migrated Media', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'GenerateThumbnailsForMigratedMediaTaskHandler',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-add-missing-is-paid-meta',
                'name'     => __( 'Add Missing _awpcp_is_paid Meta', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'AddMissingIsPaidMetaUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-add-missing-views-meta',
                'name'     => __( 'Add Missing _awpcp_views Meta', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'AddMissingViewsMetaUpgradeTaskHandler',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-update-most-recent-date',
                'name'     => __( 'Update _awpcp_most_recent_start_date', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'UpdateMostRecentDate',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );

        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-add-missing-categories-order',
                'name'     => __( 'Add missing _awpcp_order', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'AddMissingCategoriesOrder',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );
        $this->upgrade_tasks->register_upgrade_task(
            [
                'slug'     => 'awpcp-add-contact-phone-number-digits',
                'name'     => __( 'Add missing awpcp-add-contact-phone-number-digits', 'another-wordpress-classifieds-plugin' ),
                'handler'  => 'AddMissingPhoneDigits',
                'context'  => 'plugin',
                'blocking' => false,
            ]
        );
    }
}
