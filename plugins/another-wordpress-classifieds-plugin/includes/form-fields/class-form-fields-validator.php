<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class used to validate listing Form Fields data.
 */
class AWPCP_FormFieldsValidator {

    /**
     * @var object
     */
    private $authorization;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @var object
     */
    private $settings;

    /**
     * @since 4.0.0
     *
     * @param object $authorization     An instance of Listing Authorization.
     * @param object $roles             An instance of Roles and Capabilities.
     * @param object $settings          An instance of Settings API.
     */
    public function __construct( $authorization, $roles, $settings ) {
        $this->authorization = $authorization;
        $this->roles         = $roles;
        $this->settings      = $settings;
    }

    /**
     * @since 4.0.0
     *
     * @param array  $data  Array of data to validate.
     * @param object $post  An instance of WP_Post.
     */
    public function get_validation_errors( $data, $post ) {
        $errors = array();

        $allowed_to_edit_start_date = $this->authorization->is_current_user_allowed_to_edit_listing_start_date( $post );
        $allowed_to_edit_end_date   = $this->authorization->is_current_user_allowed_to_edit_listing_end_date( $post );

        $start_date = null;
        $end_date   = null;

        if ( isset( $data['metadata']['_awpcp_start_date'] ) ) {
            $start_date = strtotime( $data['metadata']['_awpcp_start_date'] );
        }

        if ( isset( $data['metadata']['_awpcp_end_date'] ) ) {
            $end_date = strtotime( $data['metadata']['_awpcp_end_date'] );
        }

        if ( $allowed_to_edit_start_date && empty( $start_date ) ) {
            $errors['start_date'] = __( 'Please enter a start date.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $allowed_to_edit_end_date && empty( $end_date ) ) {
            $errors['end_date'] = __( 'Please enter an end date.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $start_date && $end_date && $start_date > $end_date ) {
            $errors['start_date'] = __( 'The start date must occur before the end date.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['post_fields']['post_title'] ) ) {
            $errors['ad_title'] = __( 'Please enter a title for your ad.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['post_fields']['post_content'] ) ) {
            $errors['ad_details'] = __( 'Please provide a description for your classified ad.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $this->settings->get_option( 'displaywebsitefield' ) && $this->settings->get_option( 'displaywebsitefieldreqop' ) ) {
            if ( empty( $data['metadata']['_awpcp_website_url'] ) ) {
                $errors['websiteurl'] = __( 'Please enter a website address.', 'another-wordpress-classifieds-plugin' );
            }
        }

        if ( ! empty( $data['metadata']['_awpcp_website_url'] ) && ! isValidURL( $data['metadata']['_awpcp_website_url'] ) ) {
            $errors['websiteurl'] = __( 'Please enter a valid website address.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['metadata']['_awpcp_contact_name'] ) ) {
            $errors['ad_contact_name'] = __( 'Please enter a contact name.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['metadata']['_awpcp_contact_email'] ) ) {
            $errors['ad_contact_email'] = __( 'Please enter a contact email address.', 'another-wordpress-classifieds-plugin' );
        }

        if ( ! empty( $data['metadata']['_awpcp_contact_email'] ) && ! awpcp_is_valid_email_address( $data['metadata']['_awpcp_contact_email'] ) ) {
            $errors['ad_contact_email'] = __( 'Please enter a valid email address.', 'another-wordpress-classifieds-plugin' );
        }

        if ( ! empty( $data['metadata']['_awpcp_contact_email'] ) && ! awpcp_is_email_address_allowed( $data['metadata']['_awpcp_contact_email'] ) ) {
            $allowed_domains = explode( "\n", $this->settings->get_option( 'ad_poster_email_address_whitelist' ) );
            $domains_list    = '<strong>' . implode( '</strong><strong>', $allowed_domains ) . '</strong>';

            // translators: %s is a comma separated list of domain names.
            $message = __( 'The email address you entered is not allowed on this website. Please use an email address from one of the following domains: %s.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '%s', $domains_list, $message );

            $errors['ad_contact_email'] = $message;
        }

        if ( $this->settings->get_option( 'displayphonefield' ) && $this->settings->get_option( 'displayphonefieldreqop' ) ) {
            if ( empty( $data['metadata']['_awpcp_contact_phone'] ) ) {
                $errors['ad_contact_phone'] = __( 'Please enter a contact phone number.', 'another-wordpress-classifieds-plugin' );
            }
        }

        $errors = array_merge( $errors, $this->get_validation_errors_for_regions( $data, $post ) );

        if ( $this->settings->get_option( 'displaypricefield' ) && $this->settings->get_option( 'displaypricefieldreqop' ) ) {
            if ( 0 === strlen( $data['metadata']['_awpcp_price'] ) || false === $data['metadata']['_awpcp_price'] ) {
                $errors['ad_item_price'] = __( 'Please enter a price for the ad.', 'another-wordpress-classifieds-plugin' );
            }
        }

        if ( $this->settings->get_option( 'displaypricefield' ) && ! empty( $data['metadata']['_awpcp_price'] ) ) {
            if ( ! is_numeric( $data['metadata']['_awpcp_price'] ) ) {
                $errors['ad_item_price'] = __( 'Please enter a valid price. Make sure to use numbers only and don\'t include a currency symbol.', 'another-wordpress-classifieds-plugin' );
            }
        }

        if ( $this->settings->get_option( 'requiredtos' ) && ! $this->roles->current_user_is_moderator() ) {
            if ( $data['terms_of_service'] !== 'accepted' ) {
                $errors['terms_of_service'] = __( 'Please read and accept the Terms of Service.', 'another-wordpress-classifieds-plugin' );
            }
        }

        // TODO: Replace null with a payment term object, if that still makes sense here.
        return apply_filters( 'awpcp-validate-post-listing-details', $errors, $data, null );
    }

    /**
     * @param array  $data  Array of data to validate.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_validation_errors_for_regions( $data, $post ) {
        return array();
    }
}
