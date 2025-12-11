<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Listing Form Fields.
 */
function awpcp_listing_form_fields() {
    return new AWPCP_ListingFormFields(
        awpcp()->container['ListingAuthorization']
    );
}

/**
 * Listing Form Fields defines the set of fields available for listings on the
 * Edit and Submit Listing forms.
 */
class AWPCP_ListingFormFields {

    /**
     * @var AWPCP_ListingAuthorization
     */
    private $authorization;

    /**
     * @since 4.0.0
     */
    public function __construct( $authorization ) {
        $this->authorization = $authorization;
    }

    /**
     * @param array $fields     An array of Form Fields definitions.
     * @since 4.0.0
     */
    public function register_listing_details_form_fields( $fields ) {
        foreach ( $this->get_listing_form_fields( [] ) as $field_slug => $field_constructor ) {
            if ( is_callable( $field_constructor ) ) {
                $fields[ $field_slug ] = call_user_func( $field_constructor, $field_slug );
            }
        }

        $sorted_fields = array();

        foreach ( $this->get_fields_order() as $field_slug ) {
            if ( isset( $fields[ $field_slug ] ) ) {
                $sorted_fields[ $field_slug ] = $fields[ $field_slug ];
                unset( $fields[ $field_slug ] );
            }
        }

        // Show Terms of Service field last, unless explicitly configured to
        // appear in a different position.
        if ( isset( $fields['terms_of_service'] ) ) {
            $terms_of_service_field = $fields['terms_of_service'];
            unset( $fields['terms_of_service'] );
            $fields['terms_of_service'] = $terms_of_service_field;
        }

        return array_merge( $sorted_fields, $fields );
    }

    /**
     * @param array $fields     An array of Form Fields definitions.
     */
    public function get_listing_form_fields( $fields ) {
        $fields['ad_title']         = 'awpcp_listing_title_form_field';
        $fields['websiteurl']       = 'awpcp_listing_website_form_field';
        $fields['ad_contact_name']  = 'awpcp_listing_contact_name_form_field';
        $fields['ad_contact_email'] = 'awpcp_listing_contact_email_form_field';
        $fields['ad_contact_phone'] = 'awpcp_listing_contact_phone_form_field';
        $fields['ad_item_price']    = 'awpcp_listing_price_form_field';
        $fields['ad_details']       = 'awpcp_listing_details_form_field';
        $fields['terms_of_service'] = 'awpcp_terms_of_service_form_field';

        if ( is_admin() && isset( $GLOBALS['typenow'] ) && 'awpcp_listing' === $GLOBALS['typenow'] ) {
            unset( $fields['ad_title'] );
            unset( $fields['ad_details'] );
        }

        return apply_filters( 'awpcp-form-fields', $fields );
    }

    /**
     * Return the order in which form fields should be shown.
     */
    public function get_fields_order() {
        return get_option( 'awpcp-form-fields-order', array() );
    }

    /**
     * Updates the order in which form fields should be shown.
     */
    public function update_fields_order( $order ) {
        return update_option( 'awpcp-form-fields-order', $order );
    }

    /**
     * @since 4.0.0
     */
    public function get_listing_details_form_fields() {
        return $this->register_listing_details_form_fields( [] );
    }

    /**
     * @since 4.0.0 Copied from old Form Fields class.
     */
    public function get_field( $slug ) {
        $form_fields = $this->get_listing_details_form_fields();

        if ( ! isset( $form_fields[ $slug ] ) ) {
            return null;
        }

        return $form_fields[ $slug ];
    }

    /**
     * @param array $fields     An array of form fields.
     * @since 4.0.0
     */
    public function register_listing_date_form_fields( $fields, $listing ) {
        $template_renderer = awpcp()->container['TemplateRenderer'];

        if ( $this->authorization->is_current_user_allowed_to_edit_listing_start_date( $listing ) ) {
            $fields['start_date'] = new AWPCP_ListingDatePickerFormField( 'start_date', __( 'Start Date', 'another-wordpress-classifieds-plugin' ), $template_renderer );
        }

        if ( $this->authorization->is_current_user_allowed_to_edit_listing_end_date( $listing ) ) {
            $fields['end_date'] = new AWPCP_ListingDatePickerFormField( 'end_date', __( 'End Date', 'another-wordpress-classifieds-plugin' ), $template_renderer );
        }

        return $fields;
    }
}
