<?php
/**
 * @package AWPCP\Admin\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Import_Settings_Admin_Page class
 */
function awpcp_import_settings_admin_page() {
    return new AWPCP_Import_Settings_Admin_Page();
}

/**
 * Admin page that allows users to import settings from a JSON file.
 */
class AWPCP_Import_Settings_Admin_Page {

    /**
     * @var string
     */
    private $nonce_action = 'awpcp-import-settings';

    /**
     * @var AWPCP_Settings_JSON_Writer
     */
    private $settings_writer;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->settings_writer   = awpcp_settings_json_writer();
        $this->template_renderer = awpcp_template_renderer();
        $this->request           = awpcp_request();
    }

    /**
     * Executed during admin_init when this page is visited.
     */
    public function on_admin_init() {
        $nonce = awpcp_get_var( array( 'param' => '_wpnonce' ), 'post' );
        if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) || ! isset( $_FILES['settings_file'] ) ) {
            return;
        }

        try {
            $this->try_to_import_settings( 'settings_file' );
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( $e->getMessage(), array( 'notice', 'notice-error' ) );
        }

        $params = array(
            'page'         => 'awpcp-admin-settings',
            'awpcp-action' => 'import-settings',
        );

        $redirect_url = add_query_arg( $params, admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect_url );
        exit();
    }

    /**
     * Verifies that the request is valid, that a file was uploaded
     * and uses the Settings Writer to update the settings.
     *
     * @param string $file_id The name inside the $_FILES array.
     *
     * @throws AWPCP_Exception When input parameters are invalid or there
     *                          is an error trying to write from the JSON
     *                          file.
     */
    private function try_to_import_settings( $file_id ) {
        $nonce = $this->request->post( '_wpnonce' );

        if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
            $message = _x( 'Are you sure you want to do this?', 'import settings', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( isset( $_FILES[ $file_id ]['error'] ) && UPLOAD_ERR_OK !== absint( $_FILES[ $file_id ]['error'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            $error = awpcp_uploaded_file_error( $_FILES[ $file_id ] );
            throw new AWPCP_Exception( esc_html( $error[1] ) );
        }

        $settings_file = sanitize_text_field( isset( $_FILES[ $file_id ]['tmp_name'] ) ? $_FILES[ $file_id ]['tmp_name'] : '' );

        if ( ! is_uploaded_file( $settings_file ) ) {
            $message = _x( "There was a problem trying to read the settings file; it appears the file wasn't uploaded correctly. Please try again.", 'import settings', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $this->settings_writer->write( $settings_file );

        awpcp_flash( esc_html__( 'Your settings have been successfully imported.', 'another-wordpress-classifieds-plugin' ), array( 'notice', 'notice-info' ) );
    }

    /**
     * Shows the import settings form.
     */
    public function dispatch() {
        return $this->render_import_settings_form();
    }

    /**
     * Renders the import settings form.
     */
    private function render_import_settings_form() {
        $template = AWPCP_DIR . '/templates/admin/tools/import-settings-admin-page.tpl.php';

        $params = array(
            'action_url'   => '',
            'nonce_action' => $this->nonce_action,
            'tools_url'    => remove_query_arg( 'awpcp-view' ),
        );

        return $this->template_renderer->render_template( $template, $params );
    }
}
