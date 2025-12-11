<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Import_Listings_Ajax_Handler extends AWPCP_AjaxHandler {

    private $import_sessions_manager;

    protected $csv_importer_factory;

    public function __construct() {
        parent::__construct( awpcp_ajax_response() );

        $this->import_sessions_manager = new AWPCP_CSV_Import_Sessions_Manager();
        $this->csv_importer_factory    = new AWPCP_CSV_Importer_Factory();
    }

    public function ajax() {
        awpcp_check_admin_ajax();

        $import_session = $this->import_sessions_manager->get_current_import_session();

        $csv_importer = $this->csv_importer_factory->create_importer( $import_session );
        $csv_importer->import_rows();

        $this->import_sessions_manager->update_current_import_session( $import_session );

        return $this->success( array(
            'rowsCount' => $import_session->get_number_of_rows(),
            'rowsImported' => $import_session->get_number_of_rows_imported(),
            'rowsRejected' => $import_session->get_number_of_rows_rejected(),
            'errors' => array_merge(
                $import_session->get_last_errors(),
                $import_session->get_last_messages()
            ),
        ) );
    }
}
