<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Container Configuration.
 */
class AWPCP_ContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * Modifies the given dependency injection container.
     *
     * @param AWPCP_Container $container    The Dependency Injection Container.
     */
    public function modify( $container ) {
        $container['wpdb'] = function( $container ) {
            return $GLOBALS['wpdb'];
        };

        $container['Uninstaller'] = $container->service( function( $container ) {
            return new AWPCP_Uninstaller(
                $container['plugin_basename'],
                $container['listing_post_type'],
                $container['ListingsLogic'],
                $container['ListingsCollection'],
                $container['CategoriesLogic'],
                $container['CategoriesCollection'],
                $container['RolesAndCapabilities'],
                $container['Settings'],
                $container['wpdb']
            );
        } );

        $container['Request'] = $container->service( function( $container ) {
            return new AWPCP_Request();
        } );

        $container['Payments'] = $container->service( function( $container ) {
            return new AWPCP_PaymentsAPI(
                $container['Request']
            );
        } );

        $container['RolesAndCapabilities'] = $container->service( function( $container ) {
            return new AWPCP_RolesAndCapabilities(
                $container['Settings']
            );
        } );

        $container['UsersCollection'] = $container->service( function( $container ) {
            return new AWPCP_UsersCollection(
                $container['Payments'],
                $container['Settings'],
                $container['wpdb']
            );
        } );

        $container['EmailFactory'] = $container->service( function( $container ) {
            return new AWPCP_EmailFactory();
        } );

        $container['AkismetWrapperFactory'] = $container->service( function( $container ) {
            return new AWPCP_AkismetWrapperFactory();
        } );

        $container['ListingAkismetDataSource'] = $container->service( function( $container ) {
            return new AWPCP_ListingAkismetDataSource(
                $container['ListingRenderer']
            );
        } );

        $container['SPAMSubmitter'] = $container->service( function( $container ) {
            return new AWPCP_SpamSubmitter(
                $container['AkismetWrapperFactory'],
                $container['ListingAkismetDataSource']
            );
        } );

        $container['TemplateRenderer'] = $container->service( function( $container ) {
            return new AWPCP_Template_Renderer();
        } );

        $container['SendListingToFacebookHelper'] = $container->service( function( $container ) {
            return new AWPCP_SendToFacebookHelper(
                AWPCP_Facebook::instance(),
                awpcp_facebook_integration(),
                $container['ListingRenderer'],
                $container['ListingsCollection'],
                $container['Settings'],
                $container['WordPress']
            );
        } );

        $container['FormFieldsData'] = $container->service( function( $container ) {
            return new AWPCP_FormFieldsData(
                $container['ListingAuthorization'],
                $container['ListingRenderer']
            );
        } );

        $container['FormFieldsValidator'] = $container->service( function( $container ) {
            return new AWPCP_FormFieldsValidator(
                $container['ListingAuthorization'],
                $container['RolesAndCapabilities'],
                $container['Settings']
            );
        } );

        $container['ListingDetailsFormFieldsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_FormFieldsRenderer(
                'awpcp_listing_details_form_fields'
            );
        } );

        $container['ListingDateFormFieldsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_FormFieldsRenderer(
                'awpcp_listing_date_form_fields'
            );
        } );

        $container['HTMLRenderer'] = $container->service( function( $container ) {
            return new AWPCP_HTML_Renderer();
        } );

        // Media.
        $container['FileTypes'] = $container->service( function( $container ) {
            return new AWPCP_FileTypes( $container['Settings'] );
        } );

        // Components.
        $container['UserSelector'] = $container->service( function( $container ) {
            return new AWPCP_UserSelector(
                $container['UsersCollection'],
                $container['TemplateRenderer']
            );
        } );

        $container['MediaCenterComponent'] = $container->service( function ( $container ) {
            return new AWPCP_MediaCenterComponent(
                $container['ListingUploadLimits'],
                $container['AttachmentsCollection'],
                $container['TemplateRenderer'],
                $container['Settings']
            );
        } );

        $container['EmailHelper'] = $container->service( function( $container ) {
            return new AWPCP_EmailHelper(
                $container['Settings']
            );
        } );

        $this->register_media_objects( $container );
        $this->register_categories_ui_objects( $container );
        $this->register_upgrade_task_handlers( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_media_objects( $container ) {
        $container['ImageRenderer'] = $container->service(
            function( $container ) {
                return new AWPCP_ImageRenderer(
                    $container['Settings']
                );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_categories_ui_objects( $container ) {
        $container['CategoriesListCache'] = $container->service(
            function( $container ) {
                return new AWPCP_CategoriesListCache(
                    $container['listing_category_taxonomy'],
                    awpcp_categories_collection()
                );
            }
        );
    }

    /**
     * Register constructors for Upgrade Task Handlers.
     *
     * @since 4.0.0
     */
    private function register_upgrade_task_handlers( $container ) {
        $this->register_upgrade_task_handlers_for_4_0_0( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_upgrade_task_handlers_for_4_0_0( $container ) {
        $container['FixIDCollisionForListingCategoriesUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_FixIDCollisionForListingCategoriesUpgradeTaskHandler(
                    $container['listing_category_taxonomy'],
                    awpcp_categories_registry(),
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['StoreCategoriesOrderAsTermMetaTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_StoreCategoriesOrderAsTermMetaTaskHandler(
                    awpcp_categories_collection(),
                    awpcp_categories_registry(),
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['ListingsRegistry'] = $container->service(
            function( $container ) {
                return new AWPCP_ListingsRegistry(
                    $container['ArrayOptions']
                );
            }
        );

        $container['MaybeForcePostIDUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_MaybeForcePostIDUpgradeTaskHandler(
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['FixIDCollisionForListingsUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_FixIDCollisionForListingsUpgradeTaskHandler(
                    $container['ListingsRegistry'],
                    $container['ListingsCollection'],
                    $container['WordPress'],
                    $container['wpdb']
                );
            }
        );

        $container['GenerateThumbnailsForMigratedMediaTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_GenerateThumbnailsForMigratedMediaTaskHandler(
                    $container['WordPress']
                );
            }
        );

        $container['AddMissingIsPaidMetaUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_AddMissingIsPaidMetaUpgradeTaskHandler(
                    $container['ListingRenderer'],
                    $container['ListingsCollection']
                );
            }
        );

        $container['AddMissingViewsMetaUpgradeTaskHandler'] = $container->service(
            function( $container ) {
                return new AWPCP_AddMissingViewsMetaUpgradeTaskHandler(
                    $container['ListingsCollection']
                );
            }
        );

        $container['UpdateMostRecentDate'] = $container->service(
            function( $container ) {
                return new AWPCP_UpdateMostRecentDate(
                    $container['wpdb']
                );
            }
        );

        $container['AddMissingCategoriesOrder'] = $container->service(
            function( $container ) {
                return new AWPCP_AddMissingCategoriesOrder(
                    $container['listing_category_taxonomy'],
                    awpcp_wordpress()
                );
            }
        );

        $container['AddMissingPhoneDigits'] = $container->service(
            function( $container ) {
                return new AWPCP_AddMissingPhoneDigits(
                    awpcp_wordpress()
                );
            }
        );
    }
}
