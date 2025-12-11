<?php
/**
 * For a long time instances of classes were created using constructor functions
 * such as awpcp_attachments_collection(). That was the first attempt to
 * keep information about how to initialize a class in one place instead of using
 * the new operator everywhere an instance was necessary.
 *
 * On 4.0 we introduced a custom implementation of a Dependency Injection
 * Container (See class-container.php) and started using Container Configuration
 * objects (See interface-container-configuration.php) to register constructors
 * for the different objects used in the plugin.
 *
 * The Dependency Injection Container (DIC) added support to reuse instances of
 * classes that behave like services and is the preferred method to register and access
 * new classes. However, most of the code is still using the old constructor
 * functions.
 *
 * This file contains all the constructor functions for classes that have been
 * added to the container but that are still being instantiated using a constructor
 * function in other parts of the code.
 *
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_RolesAndCapabilities class.
 */
function awpcp_roles_and_capabilities() {
    return new AWPCP_RolesAndCapabilities( awpcp()->settings );
}

function awpcp_listing_authorization() {
    return awpcp()->container['ListingAuthorization'];
}

function awpcp_listing_upload_limits() {
    if ( ! isset( $GLOBALS['awpcp-listing-upload-limits'] ) ) {
        $GLOBALS['awpcp-listing-upload-limits'] = new AWPCP_ListingUploadLimits(
            awpcp_attachments_collection(),
            awpcp_file_types(),
            awpcp_listing_renderer(),
            awpcp()->settings
        );
    }

    return $GLOBALS['awpcp-listing-upload-limits'];
}

function awpcp_file_types() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_FileTypes( awpcp()->settings );
    }

    return $instance;
}

function awpcp_payments_api() {
    return awpcp()->container['Payments'];
}

/**
 * @since 3.0.2
 */
function awpcp_listings_api() {
    if ( ! isset( $GLOBALS['awpcp-listings-api'] ) ) {
        $GLOBALS['awpcp-listings-api'] = new AWPCP_ListingsAPI(
            awpcp_attachments_logic(),
            awpcp_attachments_collection(),
            awpcp_listing_renderer(),
            awpcp_listings_collection(),
            awpcp_roles_and_capabilities(),
            awpcp()->settings,
            awpcp_wordpress()
        );
    }

    return $GLOBALS['awpcp-listings-api'];
}

/**
 * @since 4.0.0     Extracted from class file.
 */
function awpcp_import_listings_admin_page() {
    return awpcp()->container['ImportListingsAdminPage'];
}

/**
 * @since 4.0.0     Extracted from class file.
 */
function awpcp_render_listing_form_steps( $selected_step, $transaction = null ) {
    return awpcp_listing_form_steps_componponent()->render( $selected_step, compact( 'transaction' ) );
}

/**
 * @since 4.0.0     Extracted from class file.
 */
function awpcp_listing_form_steps_componponent() {
    return new AWPCP_FormStepsComponent(
        new AWPCP_SubmitListingFormSteps(
            awpcp_payments_api(),
            awpcp()->settings
        )
    );
}

/**
 * @since 4.0.0     Extracted from class file.
 */
function awpcp_uploads_manager() {
    return new AWPCP_UploadsManager( awpcp()->settings );
}

/**
 * @since 3.8.6
 */
function awpcp_facebook_integration() {
    return new AWPCP_FacebookIntegration(
        awpcp()->container['ListingRenderer'],
        awpcp()->settings,
        awpcp_wordpress()
    );
}

/**
 * @since 3.0.2
 */
function awpcp_request() {
    return new AWPCP_Request();
}

/**
 * @since 4.0.0 Extracted from class file.
 */
function awpcp_database_tables() {
    return new AWPCP_Database_Tables( awpcp_database_helper() );
}

/**
 * @since 4.0.0 Extracted from class file.
 */
function awpcp_attachments_collection() {
    return new AWPCP_Attachments_Collection( awpcp_file_types(), awpcp_wordpress() );
}

/**
 * @since 4.0.0 Extracted from class-attachments-logic.php.
 */
function awpcp_attachments_logic() {
    $container = awpcp()->container;

    return new AWPCP_Attachments_Logic(
        awpcp_file_types(),
        $container['AttachmentsCollection'],
        $container['WordPress']
    );
}

/**
 * @since 4.0.0 Extracted from class-facebook-cache-helper.php.
 */
function awpcp_facebook_cache_helper() {
    $container = awpcp()->container;

    return new AWPCP_FacebookCacheHelper(
        awpcp_facebook_integration(),
        $container['ListingRenderer'],
        $container['ListingsCollection'],
        $container['Settings']
    );
}

/**
 * Constructor function for Attachment Properties.
 *
 * @since 4.0.1 Extracted from class-attachment-properties.php.
 */
function awpcp_attachment_properties() {
    return new AWPCP_Attachment_Properties( awpcp_wordpress() );
}

/**
 * Constructor function for Terms Of Service Form Field.
 *
 * @since 4.0.2
 */
function awpcp_terms_of_service_form_field( $slug ) {
    $container = awpcp()->container;

    return new AWPCP_TermsOfServiceFormField(
        $slug,
        $container['RolesAndCapabilities'],
        $container['Settings'],
        $container['TemplateRenderer']
    );
}

/**
 * Constructor function for Categories Registry class.
 *
 * @since 4.0.3 Extracted from class-categories-registry.php.
 */
function awpcp_categories_registry() {
    return new AWPCP_Categories_Registry(
        awpcp()->container['ArrayOptions']
    );
}

/**
 * @since 3.6
 * @since 4.0.4 Extracted from class-query.php and modified to load an instance
 *              from the container.
 */
function awpcp_query() {
    return awpcp()->container['Query'];
}

/**
 * Constructor function for Indeed Membership Pro Plugin Integration class.
 *
 * @since 4.0.4
 */
function awpcp_indeed_membership_pro_plugin_integration() {
    return new AWPCP_IndeedMembershipProPluginIntegration(
        awpcp()->container['Query']
    );
}
