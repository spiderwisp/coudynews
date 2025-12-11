<?php
/**
 * Upgrade Container Configuration class.
 *
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Container configuration object responsible for registering classes used
 * in manual upgrade routines.
 */
class AWPCP_UpgradeContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @since 4.0.0
     *
     * @see AWPCP_ContainerConfigurationInterface::modify()
     */
    public function modify( $container ) {
        $container['UpgradeTasksManager'] = $container->service(
            function ( $container ) {
                return new AWPCP_Upgrade_Tasks_Manager();
            }
        );

        $container['ManualUpgradeTasks'] = $container->service(
            function( $container ) {
                return new AWPCP_Manual_Upgrade_Tasks(
                    $container['UpgradeTasksManager']
                );
            }
        );

        $container['UpgradeSessions'] = $container->service(
            function( $container ) {
                return new AWPCP_Upgrade_Sessions(
                    awpcp_upgrade_tasks_manager(),
                    $container['WordPress']
                );
            }
        );

        $container['UpgradeTaskHandlerFactory'] = $container->service(
            function( $container ) {
                return new AWPCP_Upgrade_Task_Handler_Factory( $container );
            }
        );

        $container['UpgradeTaskController'] = $container->service(
            function( $container ) {
                return new AWPCP_UpgradeTaskController(
                    awpcp_upgrade_tasks_manager(),
                    $container['UpgradeTaskHandlerFactory']
                );
            }
        );

        $container['UpgradeTaskAjaxHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Upgrade_Task_Ajax_Handler(
                    $container['UpgradeTaskController'],
                    null,
                    awpcp_ajax_response()
                );
            }
        );

        $this->register_3_x_x_objects( $container );
        $this->register_4_0_0_objects( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_3_x_x_objects( $container ) {
        $container['ImportPaymentTransactionsTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Import_Payment_Transactions_Task_Handler();
            }
        );

        $container['MigrateMediaInformationTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Migrate_Media_Information_Task_Handler(
                    $container['Settings'],
                    $container['wpdb']
                );
            }
        );

        $container['MigrateRegionsInformationTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Migrate_Regions_Information_Task_Handler();
            }
        );

        $container['UpdateMediaStatusTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Update_Media_Status_Task_Handler();
            }
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_4_0_0_objects( $container ) {
        $container['StoreListingCategoriesAsCustomTaxonomiesUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Store_Listing_Categories_As_Custom_Taxonomies_Upgrade_Task_Handler(
                    awpcp_categories_registry(),
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['StoreListingsAsCustomPostTypesUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Store_Listings_As_Custom_Post_Types_Upgrade_Task_Handler(
                    awpcp_categories_registry(),
                    awpcp_legacy_listings_metadata(),
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['StoreMediaAsAttachmentsUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Store_Media_As_Attachments_Upgrade_Task_Handler(
                    $container['Settings'],
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['StorePhoneNumberDigitsUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_Store_Phone_Number_Digits_Upgrade_Task_Handler(
                    $container['wpdb']
                );
            }
        );

        $container['UpdateCategoriesTermCountUpgradeTaskRunner'] = $container->service(
            function( $container ) {
                return new AWPCP_Update_Categories_Term_Count_Upgrade_Task_Runner(
                    $container['listing_category_taxonomy'],
                    $container['wpdb']
                );
            }
        );
    }
}
