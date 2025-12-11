<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_FormFieldsAdminPage.
 */
function awpcp_form_fields_admin_page() {
    return new AWPCP_FormFieldsAdminPage();
}

/**
 * Admin page to manage order of listing's form fields.
 */
class AWPCP_FormFieldsAdminPage extends AWPCP_AdminPageWithTable {

    /**
     * @var AWPCP_ListingFormFields
     */
    private $form_fields;

    /**
     * Constructor.
     */
    public function __construct() {
        $page  = 'awpcp-form-fields';
        $title = awpcp_admin_page_title( __( 'Form Fields', 'another-wordpress-classifieds-plugin' ) );
        parent::__construct( $page, $title, _x( 'Form Fields', 'sub menu title', 'another-wordpress-classifieds-plugin' ) );

        $this->form_fields = awpcp_listing_form_fields();
    }

    /**
     * Creates an instance of table used to render form fields rows.
     */
    public function get_table() {
        if ( empty( $this->table ) ) {
            $this->table = new AWPCP_FormFieldsTable();
        }

        return $this->table;
    }

    /**
     * Enqueue required scripts.
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-form-fields' );
    }

    /**
     * Renders the page.
     */
    public function dispatch() {
        $form_fields = $this->form_fields->get_listing_details_form_fields();

        $table = $this->get_table();
        $table->prepare( $form_fields, count( $form_fields ) );

        $params = array(
            'page'  => $this,
            'table' => $table,
        );

        $template = AWPCP_DIR . '/templates/admin/form-fields-admin-page.tpl.php';

        return awpcp_render_template( $template, $params );
    }
}
