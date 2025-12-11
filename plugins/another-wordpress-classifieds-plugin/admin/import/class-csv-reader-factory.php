<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_CSV_Reader_Factory {

    public function create_reader( $csv_file_path ) {
        return new AWPCP_CSV_Reader( $csv_file_path );
    }
}
