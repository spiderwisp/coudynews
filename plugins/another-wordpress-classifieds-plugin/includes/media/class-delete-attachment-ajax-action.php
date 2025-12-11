<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_delete_attachment_ajax_handler() {
    $attachment_action = new AWPCP_Delete_Attachment_Ajax_Action(
        awpcp_attachments_logic()
    );

    return awpcp_attachment_action_ajax_handler( $attachment_action );
}

class AWPCP_Delete_Attachment_Ajax_Action implements AWPCP_Attachment_Ajax_Action {

    private $attachment_logic;

    public function __construct( $attachment_logic ) {
        $this->attachment_logic = $attachment_logic;
    }

    public function do_action( $ajax_handler, $attachment, $listing ) {
        return $this->attachment_logic->delete_attachment( $attachment );
    }
}
