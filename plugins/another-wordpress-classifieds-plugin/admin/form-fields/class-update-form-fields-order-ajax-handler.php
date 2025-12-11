<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function.
 */
function awpcp_update_form_fields_order_ajax_handler() {
    return new AWPCP_UpdateFormFieldsOrderAjaxHandler(
        awpcp_listing_form_fields(),
        awpcp_request(),
        awpcp_ajax_response()
    );
}

/**
 * Handler for the ajax action that updates the order of listing's form fields.
 */
class AWPCP_UpdateFormFieldsOrderAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_ListingFormFields
     */
    private $form_fields;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * Constructor.
     */
    public function __construct( $form_fields, $request, $response ) {
        parent::__construct( $response );

        $this->form_fields = $form_fields;
        $this->request     = $request;
    }

    /**
     * Handles ajax request.
     */
    public function ajax() {
        awpcp_check_admin_ajax();

        $fields       = $this->form_fields->get_listing_details_form_fields();
        $fields_order = array();

        foreach ( $this->request->post( 'awpcp-form-fields-order' ) as $element_id ) {
            $field_slug = str_replace( 'field-', '', $element_id );

            if ( ! isset( $fields[ $field_slug ] ) ) {
                continue;
            }

            $fields_order[] = $field_slug;
        }

        if ( $this->form_fields->update_fields_order( $fields_order ) ) {
            return $this->success( array( 'selected' => $this->request->post( 'selected' ) ) );
        }
    }
}
