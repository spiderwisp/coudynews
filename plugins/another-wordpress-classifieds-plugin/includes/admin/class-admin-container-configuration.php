<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Container configuration for common classes used on the Admin Dashboard.
 */
class AWPCP_AdminContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @param AWPCP_Container $container     An instance of Container.
     */
    public function modify( $container ) {
        $container['Admin'] = $container->service( function( $container ) {
            return new AWPCP_Admin(
                $container['listing_post_type'],
                $container,
                $container['ListingsTableViewsHandler'],
                $container['ListingsTableActionsHandler'],
                $container['ListingsTableNavHandler'],
                $container['ListingsTableSearchHandler'],
                $container['ListingsTableColumnsHandler'],
                $container['ListTableRestrictions']
            );
        } );

        $container['ListTableRestrictions'] = $container->service( function( $container ) {
            return new AWPCP_ListTableRestrictions(
                $container['listing_post_type'],
                $container['RolesAndCapabilities'],
                $container['Request']
            );
        } );

        /* Listings Container */

        $container['ListingsTableActionsHandler'] = $container->service( function( $container ) {
            return new AWPCP_ListTableActionsHandler(
                $container['ListingsTableActions'],
                awpcp_listings_collection()
            );
        } );

        $container['ListingsTableActions'] = $container->service( function( $container ) {
            return new AWPCP_FilteredArray( 'awpcp_list_table_actions_listings' );
        } );

        $container['ListingsTableViewsHandler'] = $container->service( function( $container ) {
            return new AWPCP_ListTableViewsHandler(
                $container['ListingsTableViews']
            );
        } );

        $container['OnboardingWizard'] = $container->service( function( $container ) {
            return new AWPCP_OnboardingWizard();
        } );

        $container['ListingsTableViews'] = $container->service( function( $container ) {
            return new AWPCP_FilteredArray( 'awpcp_list_table_views_listings' );
        } );

        $container['NewListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_NewListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['ExpiredListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_ExpiredListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['AwaitingApprovalListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_AwaitingApprovalListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['ImagesAwaitingApprovalListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_ImagesAwaitingApprovalListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['FlaggedListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_FlaggedListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['IncompleteListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_IncompleteListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['UnverifiedListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_UnverifiedListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['CompleteListingTableView'] = $container->service( function( $container ) {
            return new AWPCP_CompleteListingTableView(
                $container['ListingsCollection']
            );
        } );

        $container['EnableListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_EnableListingTableAction(
                awpcp_listings_api(),
                $container['ListingRenderer'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['DisableListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_DisableListingTableAction(
                awpcp_listings_api(),
                $container['ListingRenderer'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['ApproveImagesTableAction'] = $container->service( function( $container ) {
            return new AWPCP_ApproveImagesTableAction(
                awpcp_listings_api(),
                $container['RolesAndCapabilities'],
                $container['AttachmentsLogic']
            );
        } );

        $container['SendAccessKeyListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_SendAccessKeyListingTableAction(
                $container['EmailFactory'],
                $container['ListingRenderer']
            );
        } );

        $container['MarkAsSPAMListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MarkAsSPAMListingTableAction(
                $container['SPAMSubmitter'],
                $container['ListingsLogic'],
                $container['RolesAndCapabilities'],
                $container['WordPress']
            );
        } );

        $container['UnflagListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_UnflagListingTableAction(
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['ModeratorRenewListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_ModeratorRenewListingTableAction(
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['ListingRenewedEmailNotifications'],
                $container['RolesAndCapabilities'],
                $container['Settings']
            );
        } );

        $container['SubscriberRenewListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_SubscriberRenewListingTableAction(
                $container['ListingRenderer'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['SubscriberRenewListingAdminPage'] = $container->service( function( $container ) {
            return new AWPCP_RenewAdPage(
                'awpcp-admin-renew-listing',
                awpcp_admin_page_title( __( 'Renew Ad', 'another-wordpress-classifieds-plugin' ) ),
                $container['AttachmentsCollection'],
                $container['ListingUploadLimits'],
                $container['ListingAuthorization'],
                $container['ListingRenderer'],
                $container['ListingsLogic'],
                $container['ListingsCollection'],
                $container['Payments'],
                $container['TemplateRenderer'],
                $container['WordPress']
            );
        } );

        $container['MakeFeaturedListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MakeFeaturedListingTableAction(
                $container['RolesAndCapabilities'],
                $container['WordPress']
            );
        } );

        $container['MakeStandardListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MakeStandardListingTableAction(
                $container['RolesAndCapabilities'],
                $container['WordPress']
            );
        } );

        $container['MarkReviewedListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MarkReviewedListingTableAction(
                $container['RolesAndCapabilities'],
                $container['WordPress']
            );
        } );

        $container['MarkPaidListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MarkPaidListingTableAction(
                $container['ListingsLogic'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['MarkVerifiedListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_MarkVerifiedListingTableAction(
                $container['ListingsLogic'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['SendVerificationEmailTableAction'] = $container->service( function( $container ) {
            return new AWPCP_SendVerificationEmailTableAction(
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['SendToFacebookPageListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_SendToFacebookPageListingTableAction(
                $container['SendListingToFacebookHelper'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['SendToFacebookGroupListingTableAction'] = $container->service( function( $container ) {
            return new AWPCP_SendToFacebookGroupListingTableAction(
                $container['SendListingToFacebookHelper'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['ListingsTableNavHandler'] = $container->service( function( $container ) {
            return new AWPCP_ListingsTableNavHandler(
                $container['HTMLRenderer']
            );
        } );

        $container['ListingsTableSearchHandler'] = $container->service( function( $container ) {
            return new AWPCP_ListTableSearchHandler(
                $container['ListingsTableSearchModes'],
                $container['HTMLRenderer']
            );
        } );

        $container['ListingsTableSearchModes'] = $container->service( function( $container ) {
            return new AWPCP_FilteredArray( 'awpcp_list_table_search_listings' );
        } );

        $container['IDListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_IDListingsTableSearchMode();
        } );

        $container['KeywordListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_KeywordListingsTableSearchMode();
        } );

        $container['TitleListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_TitleListingsTableSearchMode();
        } );

        $container['UserListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_UserListingsTableSearchMode();
        } );

        $container['ContactNameListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_ContactNameListingsTableSearchMode();
        } );

        $container['ContactPhoneListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_ContactPhoneListingsTableSearchMode();
        } );

        $container['ContactEmailListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_ContactEmailListingsTableSearchMode();
        } );

        $container['PayerEmailListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_PayerEmailListingsTableSearchMode();
        } );

        $container['LocationListingsTableSearchMode'] = $container->service( function( $container ) {
            return new AWPCP_LocationListingsTableSearchMode();
        } );

        $container['ListingInformationMetabox'] = $container->service( function( $container ) {
            return new AWPCP_ListingInfromationMetabox(
                $container['ListingsPayments'],
                $container['ListingRenderer'],
                $container['Payments'],
                $container['TemplateRenderer'],
                $container['Request']
            );
        } );

        $container['ListingOwnerMetabox'] = $container->service( function( $container ) {
            return new AWPCP_ListingOwnerMetabox(
                $container['UserSelector'],
                $container['UsersCollection'],
                $container['RolesAndCapabilities']
            );
        } );

        $container['ListingFieldsMetabox'] = $container->service( function( $container ) {
            return new AWPCP_ListingFieldsMetabox(
                $container['ListingsLogic'],
                $container['FormFieldsData'],
                $container['FormFieldsValidator'],
                $container['ListingDetailsFormFieldsRenderer'],
                $container['ListingDateFormFieldsRenderer'],
                $container['MediaCenterComponent'],
                $container['TemplateRenderer'],
                $container['WordPress'],
                $container['ListingAuthorization']
            );
        } );

        $container['ListingsTableColumnsHandler'] = $container->service( function( $container ) {
            return new AWPCP_ListingsTableColumnsHandler(
                $container['listing_post_type'],
                $container['listing_category_taxonomy'],
                $container['ListingRenderer'],
                $container['ListingsCollection']
            );
        } );

        $container['TestSSLClientAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_TestSSLClientAjaxHandler();
        } );

        $container['UpdatePaymentTerm'] = $container->service(
            function( $container ) {
                return new AWPCP_PaymentTermAjaxHandler(
                    $container['Request'],
                    $container['ListingInformationMetabox'],
                    awpcp_ajax_response(),
                    awpcp_listings_collection()
                );
            }
        );

        $this->register_importer_objects( $container );
        $container['ExportListingsAdminPage'] = $container->service( function( $container ) {
            return new AWPCP_ExportListingsAdminPage();
        } );
        $this->register_tools_objects( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_importer_objects( $container ) {
        $container['ImporterDelegateFactory'] = $container->service( function( $container ) {
            return new AWPCP_CSV_Importer_Delegate_Factory( $container );
        } );

        $container['CSVImporterColumns'] = $container->service( function( $container ) {
            return new AWPCP_CSVImporterColumns();
        } );

        $container['ImporterFormStepsComponent'] = $container->service( function( $container ) {
            return new AWPCP_FormStepsComponent( new AWPCP_ImporterFormSteps() );
        } );

        $container['ImportListingsAdminPage'] = $container->service( function( $container ) {
            return new AWPCP_ImportListingsAdminPage(
                $container['ImporterFormStepsComponent'],
                $container['Settings']
            );
        } );

        $container['SupportedCSVHeadersAdminPage'] = $container->service( function( $container ) {
            return new AWPCP_SupportedCSVHeadersAdminPage(
                $container['CSVImporterColumns'],
                $container['TemplateRenderer']
            );
        } );

        $container['ExampleCSVFileAdminPage'] = $container->service( function( $container ) {
            return new AWPCP_ExampleCSVFileAdminPage(
                $container['CSVImporterColumns'],
                $container['TemplateRenderer']
            );
        } );
    }

    /**
     * @since 4.0.0
     */
    private function register_tools_objects( $container ) {
        $container['ToolsAdminPage'] = $container->service(
            function( $container ) {
                return new AWPCP_ToolsAdminPage(
                    $container['TemplateRenderer']
                );
            }
        );
    }
}
