<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * TODO: improve the API to make the fields able to return the value that was posted.
 * TODO: define a get_posted_value, similiar to public abstract function get_posted_value( $request );
 */
abstract class AWPCP_FormField {

    private $slug;

    public function __construct( $slug ) {
        $this->slug = $slug;
    }

    public function get_slug() {
        return $this->slug;
    }

    public function get_label() {
        return $this->get_name();
    }

    abstract public function get_name();

    protected function is_required() {
        return false;
    }

    public function is_readonly( $value ) {
        return false;
    }

    public function is_allowed_in_context( $context ) {
        if ( $context['action'] === 'search' ) {
            return false;
        }

        return true;
    }

    /**
     * Extract the value for this field from a data array returned by
     * FormFieldsData::get_stored_data() or FormFieldsData::get_posted_data().
     *
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        _doing_it_wrong( __FUNCTION__, 'Overwrite this method in a subclass of FormField.', 'AWP Classifieds Plugin 4.0.0' );
        return null;
    }

    protected function format_value( $value ) {
        return $value;
    }

    abstract public function render( $value, $errors, $listing, $context );
}
