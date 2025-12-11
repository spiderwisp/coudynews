<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Container configuration for objects used on the frontend.
 */
class AWPCP_FrontendContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @param AWPCP_Container $container    The plugin's container.
     * @since 4.0.0
     */
    public function modify( $container ) {
        $container['Query'] = $container->service(
            function( $container ) {
                return new AWPCP_Query(
                    $container['listing_post_type']
                );
            }
        );

        $container['Meta'] = $container->service(
            function( $container ) {
                return new AWPCP_Meta(
                    $container['ListingsCollection'],
                    $container['CategoriesCollection'],
                    $container['TitleBuilder'],
                    awpcp_meta_tags_generator(),
                    awpcp_query(),
                    $container['Request']
                );
            }
        );

        $container['TitleBuilder'] = $container->service(
            function( $container ) {
                return new AWPCP_PageTitleBuilder(
                    $container['ListingRenderer'],
                    $container['CategoriesCollection']
                );
            }
        );

        $container['SubmitListingPage'] = $container->service( function( $container ) {
            return new AWPCP_SubmitListingPage(
                $container['SubmitListingSectionsGenerator'],
                $container['ListingRenderer'],
                $container['ListingsLogic'],
                $container['ListingsCollection'],
                $container['ListingAuthorization'],
                $container['Payments'],
                $container['Settings']
            );
        } );

        $container['EditListingPage'] = $container->service( function( $container ) {
            return new AWPCP_EditListingPage(
                $container['SubmitListingSectionsGenerator'],
                $container['ListingRenderer'],
                $container['ListingsLogic'],
                $container['ListingsCollection'],
                $container['ListingAuthorization'],
                $container['Settings'],
                $container['Request']
            );
        } );

        $container['SubmitListingSectionsGenerator'] = $container->service( function( $container ) {
            return new AWPCP_SubmitLisitngSectionsGenerator();
        } );

        $container['CreateEmptyListingAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_CreateEmptyListingAjaxHandler(
                $container['ListingsLogic'],
                $container['ListingsPaymentTransactions'],
                $container['PaymentInformationValidator'],
                $container['Payments'],
                $container['RolesAndCapabilities'],
                $container['CAPTCHA'],
                awpcp_ajax_response(),
                $container['Settings'],
                $container['ListingOrderPostedData'],
                $container['Request']
            );
        } );

        $container['ListingOrderPostedData'] = $container->service( function( $container ) {
            return new AWPCP_ListingOrderPostedData(
                $container['listing_category_taxonomy'],
                $container['Payments'],
                $container['RolesAndCapabilities'],
                $container['Request']
            );
        } );

        $container['ListingPostedData'] = $container->service( function( $container ) {
            return new AWPCP_ListingPostedData(
                $container['listing_category_taxonomy'],
                $container['FormFieldsData'],
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['Payments'],
                $container['ListingAuthorization'],
                $container['RolesAndCapabilities'],
                $container['Request']
            );
        } );

        $container['CAPTCHA'] = $container->service( function( $container ) {
            return new AWPCP_CAPTCHA(
                $container['CAPTCHAProviderFactory']->get_captcha_provider(),
                $container['RolesAndCapabilities'],
                $container['Settings']
            );
        } );

        $container['CAPTCHAProviderFactory'] = $container->service( function( $container ) {
            return new AWPCP_CAPTCHAProviderFactory(
                $container['Settings'],
                $container['Request']
            );
        } );

        $this->register_pages( $container );
        $this->register_ajax_handlers( $container );
        $this->register_listing_actions_handlers( $container );
    }

    /**
     * @since 4.0.0
     */
    public function register_pages( $container ) {
        $container['ShowListingPage'] = $container->service(
            function( $container ) {
                return new AWPCP_Show_Ad_Page(
                    $container['ListingsContentRenderer'],
                    $container['ListingsCollection'],
                    $container['Request']
                );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    public function register_ajax_handlers( $container ) {
        $container['UpdateListingOrderAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_UpdateListingOrderAjaxHandler(
                $container['ListingsLogic'],
                $container['PaymentInformationValidator'],
                $container['ListingsCollection'],
                $container['ListingsPaymentTransactions'],
                $container['ListingOrderPostedData'],
                awpcp_ajax_response()
            );
        } );

        $container['UpdateSubmitListingSectionsAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_UpdateSubmitListingSectionsAjaxHandler(
                $container['SubmitListingSectionsGenerator'],
                $container['ListingsCollection'],
                $container['Payments'],
                awpcp_ajax_response()
            );
        } );

        $container['SaveListingInformationAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_SaveListingInformationAjaxHandler(
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['ListingsCollection'],
                $container['ListingsPaymentTransactions'],
                $container['FormFieldsValidator'],
                $container['PaymentInformationValidator'],
                $container['ListingPostedData'],
                $container['RolesAndCapabilities'],
                $container['Settings'],
                awpcp_ajax_response()
            );
        } );

        $container['GenerateListingPreviewAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_GenerateListingPreviewAjaxHandler(
                $container['ListingsContentRenderer'],
                $container['ListingsCollection'],
                awpcp_ajax_response(),
                $container['Request']
            );
        } );

        $container['ExecuteListingActionAjaxHandler'] = $container->service( function( $container ) {
            return new AWPCP_ExecuteListingActionAjaxHandler(
                $container['ListingsCollection'],
                awpcp_ajax_response(),
                $container['Request']
            );
        } );
    }

    /**
     * @since 4.0.0
     */
    public function register_listing_actions_handlers( $container ) {
        $container['DeleteListingActionHandler'] = $container->service( function( $container ) {
            return new AWPCP_DeleteListingActionHandler(
                $container['ListingsLogic']
            );
        } );
    }
}
