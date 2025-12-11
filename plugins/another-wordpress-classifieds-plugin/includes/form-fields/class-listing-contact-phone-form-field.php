<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Listing Contact Phone Form Field class.
 *
 * @param string $slug      An identifier for this field.
 */
function awpcp_listing_contact_phone_form_field( $slug ) {
    return new AWPCP_ListingContactPhoneFormField( $slug, awpcp()->settings );
}

class AWPCP_ListingContactPhoneFormField extends AWPCP_FormField {

    /**
     * @var object
     */
    protected $settings;

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
        return _x( 'Contact Phone Number', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @return bool     Whether this field is required or not.
     */
    protected function is_required() {
        return $this->settings->get_option( 'displayphonefieldreqop' );
    }

    /**
     * @param mixed $context   The context in which this field is being rendered.
     */
    public function is_allowed_in_context( $context ) {
        if ( ! $this->settings->get_option( 'displayphonefield' ) ) {
            return false;
        }

        return parent::is_allowed_in_context( $context );
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data['metadata']['_awpcp_contact_phone'] ) ) {
            return null;
        }

        return $data['metadata']['_awpcp_contact_phone'];
    }

    /**
     * @param mixed  $value     The current value for thi field.
     * @param array  $errors    An array of form errors.
     * @param object $listing   An instance of WP_Post.
     * @param mixed  $context   The context in which this field is being rendered.
     */
    public function render( $value, $errors, $listing, $context ) {
        $validators = '';

        if ( $this->is_required() ) {
            $validators = 'required';
        }

        $params = array(
            'required'   => $this->is_required(),
            'value'      => $value,
            'errors'     => $errors,

            'label'      => $this->get_label(),
            'help_text'  => '',
            'validators' => $validators,

            'html'       => array(
                'id'       => str_replace( '_', '-', $this->get_slug() ),
                'name'     => $this->get_slug(),
                'readonly' => false,
            ),
        );

        return awpcp_render_template( 'frontend/form-fields/listing-contact-phone-form-field.tpl.php', $params );
    }
}
