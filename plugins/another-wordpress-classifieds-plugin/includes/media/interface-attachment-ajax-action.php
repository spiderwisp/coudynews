<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



interface AWPCP_Attachment_Ajax_Action {

    public function do_action( $ajax_handler, $attachment, $listing );
}
