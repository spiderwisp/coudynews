<?php
/**
 * @package AWPCP\Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This function can be improved to use a filter to allow Extra Fields and other
 * modules to define custom messages for the form fields they provide.
 *
 * See https://jqueryvalidation.org/validate/#messages to understand what can be
 * included as messages.
 *
 * @since 4.0.0
 */
function awpcp_listing_form_fields_validation_messages() {
    return array(
        'ad_title'          => __( 'Please type in a title for your ad.', 'another-wordpress-classifieds-plugin' ),
        'websiteurl'        => __( 'Please type in a valid URL.', 'another-wordpress-classifieds-plugin' ),
        'ad_contact_name'   => __( 'Please type in the name of the person to contact.', 'another-wordpress-classifieds-plugin' ),
        'ad_contact_email'  => __( 'Please type in the email address of the person to contact.', 'another-wordpress-classifieds-plugin' ),
        'ad_contact_phone'  => __( 'Please type in the phone number of the person to contact.', 'another-wordpress-classifieds-plugin' ),
        'ad_country'        => __( 'The country is a required field.', 'another-wordpress-classifieds-plugin' ),
        'ad_county_village' => __( 'The county is a required field.', 'another-wordpress-classifieds-plugin' ),
        'ad_state'          => __( 'The state is a required field.', 'another-wordpress-classifieds-plugin' ),
        'ad_city'           => __( 'The city is a required field.', 'another-wordpress-classifieds-plugin' ),
        'ad_item_price'     => __( 'Please type in a price for your ad.', 'another-wordpress-classifieds-plugin' ),
        'ad_details'        => __( 'Please type in the details of your ad.', 'another-wordpress-classifieds-plugin' ),
        'captcha'           => __( 'Please type in the result of the operation.', 'another-wordpress-classifieds-plugin' ),
        'terms_of_service'  => __( 'Please read and accept the Terms of Service.', 'another-wordpress-classifieds-plugin' ),
    );
}
