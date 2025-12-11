<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_FormFieldsTableFactory {

    public function create_table() {
        _deprecated_function( __METHOD__, '4.3.4', 'AWPCP_FormFieldsTable' );
        return new AWPCP_FormFieldsTable();
    }
}
