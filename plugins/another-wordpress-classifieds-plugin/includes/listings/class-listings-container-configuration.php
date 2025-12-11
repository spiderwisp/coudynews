<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register constructors for classes necessary to provide the Listing custom
 * post type.
 */
class AWPCP_ListingsContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @param object $container     An instance of Container.
     * @since 4.0.0
     */
    public function modify( $container ) {
        $container['listing_post_type']         = 'awpcp_listing';
        $container['listing_category_taxonomy'] = 'awpcp_listing_category';

        $this->register_listings_presentation_values( $container );
        $this->register_listings_logic_values( $container );
        $this->register_listings_categories_values( $container );
        $this->register_listings_payments_values( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_listings_presentation_values( $container ) {
        $container['ListingsPermalinks'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsPermalinks(
                    $container['listing_post_type'],
                    $container['listing_category_taxonomy'],
                    $container['ListingRenderer'],
                    awpcp_rewrite_rules_helper(),
                    $container['Settings']
                );
            }
        );

        $container['LoopIntegration'] = $container->service(
            function( $container ) {
                return new AWPCP_Loop_Integration(
                    $container['ListingRenderer'],
                    $container['ListingsCollection'],
                    $container['WordPress'],
                    $container['Request']
                );
            }
        );

        $container['ListingsContent'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsContent(
                    $container['listing_post_type'],
                    $container['ListingsContentRenderer'],
                    $container['WordPress']
                );
            }
        );

        $container['ListingsContentRenderer'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsContentRenderer(
                    $container['ListingRenderer']
                );
            }
        );

        $container['ListingRenderer'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingRenderer(
                    awpcp_categories_collection(),
                    '',
                    awpcp_payments_api(),
                    $container['WordPress']
                );
            }
        );

        $container['QueryIntegration'] = $container->service(
            function( $container ) {
                return new AWPCP_QueryIntegration(
                    $container['listing_post_type'],
                    $container['listing_category_taxonomy'],
                    $container['Settings'],
                    $GLOBALS['wpdb']
                );
            }
        );

        $container['TermQueryIntegration'] = $container->service(
            function( $container ) {
                return new AWPCP_TermQueryIntegration(
                    $container['listing_category_taxonomy']
                );
            }
        );

        $container['ListingsCollection'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsCollection(
                    $container['listing_post_type'],
                    $container['WordPress'],
                    $container['RolesAndCapabilities']
                );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_listings_logic_values( $container ) {
        $container['ListingsLogic'] = $container->service( function( $container ) {
            return new AWPCP_ListingsAPI(
                $container['AttachmentsLogic'],
                $container['AttachmentsCollection'],
                $container['ListingRenderer'],
                $container['ListingsCollection'],
                $container['RolesAndCapabilities'],
                $container['Settings'],
                $container['WordPress']
            );
        } );

        $container['ListingAuthorization'] = $container->service( function( $container ) {
            return new AWPCP_ListingAuthorization(
                $container['ListingRenderer'],
                awpcp_roles_and_capabilities(),
                $container['Settings'],
                $container['Request']
            );
        } );

        $container['ListingUploadLimits'] = $container->service( function( $container ) {
            return new AWPCP_ListingUploadLimits(
                awpcp_attachments_collection(),
                awpcp_file_types(),
                $container['ListingRenderer'],
                $container['Settings']
            );
        } );

        $container['ListingRenewedEmailNotifications'] = $container->service( function( $container ) {
            return new AWPCP_ListingRenewedEmailNotifications(
                $container['ListingRenderer'],
                $container['TemplateRenderer'],
                $container['Settings']
            );
        } );

        $container['DeleteListingEventListener'] = $container->service(
            function( $container ) {
                return new AWPCP_DeleteListingEventListener(
                    $container['listing_post_type']
                );
            }
        );

        $container['RemoveListingAttachmentsService'] = $container->service(
            function( $container ) {
                return new AWPCP_RemoveListingAttachmentsService(
                    $container['listing_post_type'],
                    $container['AttachmentsCollection'],
                    $container['WordPress']
                );
            }
        );

        $container['RemoveListingRegionsService'] = $container->service(
            function( $container ) {
                return new AWPCP_RemoveListingRegionsService(
                    $container['wpdb']
                );
            }
        );

        $container['ListingsViewCounter'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsViewCounter(
                    awpcp_ajax_response(),
                    $container['Request'],
                    $container['ListingsLogic']
                );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_listings_categories_values( $container ) {
        $container['ListingsCategoriesPermalinks'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsCategoriesPermalinks(
                    $container['listing_category_taxonomy']
                );
            }
        );

        $container['CategoriesLogic'] = $container->service( function( $container ) {
            return new AWPCP_Categories_Logic(
                $container['listing_category_taxonomy'],
                $container['ListingsLogic'],
                $container['ListingsCollection'],
                $container['WordPress']
            );
        } );

        $container['CategoriesCollection'] = $container->service( function( $container ) {
            return new AWPCP_Categories_Collection(
                $container['listing_category_taxonomy'],
                awpcp_categories_registry(),
                $container['WordPress']
            );
        } );

        $container['CategoryPresenter'] = $container->service(
            function( $container ) {
                return new AWPCP_CategoryPresenter(
                    $container['CategoriesCollection']
                );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_listings_payments_values( $container ) {
        $container['ListingsPayments'] = $container->service( function( $container ) {
            return new AWPCP_ListingsPayments(
                $container['ListingsLogic'],
                $container['ListingRenderer'],
                $container['Payments']
            );
        } );

        $container['ListingsPaymentTransactions'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsPaymentTransactions(
                    $container['Payments']
                );
            }
        );

        $container['PaymentInformationValidator'] = $container->service( function( $container ) {
            return new AWPCP_PaymentInformationValidator(
                $container['listing_category_taxonomy'],
                $container['CategoriesCollection'],
                $container['Payments'],
                $container['RolesAndCapabilities']
            );
        } );
    }
}
