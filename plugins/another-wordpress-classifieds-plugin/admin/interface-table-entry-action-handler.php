<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



interface AWPCP_Table_Entry_Action_Handler {

    public function process_entry_action( $ajax_handler );
}
