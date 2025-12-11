<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Listing Contact Email Form Field class.
 *
 * @param string $slug      An identifier for this field.
 */
function awpcp_listing_contact_email_form_field( $slug ) {
    return new AWPCP_ListingContactEmailFormField( $slug, awpcp()->settings );
}

class AWPCP_ListingContactEmailFormField extends AWPCP_FormField {

    /**
     * @var object
     */
    private $settings;

    /**
     * @param string $slug      An identifier for this field.
     * @param object $settings  An instance of Settings API.
     */
    public function __construct( $slug, $settings ) {
        parent::__construct( $slug );
        $this->settings = $settings;
    }

    /**
     * @return string   The label for this field.
     */
    public function get_name() {
        return _x( 'Contact Email', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @return string   Auxiliary text explained the purpose of the field or how
     *                  to fill it.
     */
    public function get_help_text() {
        return _x( 'The access codes needed to edit your listing will be sent to this email address.', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @return bool     Whether this field is required or not.
     */
    protected function is_required() {
        return true;
    }

    /**
     * @param mixed $value     The current value for thi field.
     */
    public function is_readonly( $value ) {
        $make_contact_fields_writable = $this->settings->get_option( 'make-contact-fields-writable-for-logged-in-users' );

        if ( is_user_logged_in() && $make_contact_fields_writable ) {
            return false;
        }

        if ( ! is_user_logged_in() || awpcp_current_user_is_moderator() || empty( $value ) ) {
            return false;
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data['metadata']['_awpcp_contact_email'] ) ) {
            return null;
        }

        return $data['metadata']['_awpcp_contact_email'];
    }

    /**
     * @param mixed  $value     The current value for thi field.
     * @param array  $errors    An array of form errors.
     * @param object $listing   An instance of WP_Post.
     * @param mixed  $context   The context in which this field is being rendered.
     */
    public function render( $value, $errors, $listing, $context ) {
        $params = array(
            'required'  => $this->is_required(),
            'value'     => $value,
            'errors'    => $errors,

            'label'     => $this->get_label(),
            'help_text' => $this->get_help_text(),

            'html'      => array(
                'id'       => str_replace( '_', '-', $this->get_slug() ),
                'name'     => $this->get_slug(),
                'readonly' => $this->is_readonly( $value ),
            ),
        );

        return awpcp_render_template( 'frontend/form-fields/listing-contact-email-form-field.tpl.php', $params );
    }
}
