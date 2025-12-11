<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_set_attachment_as_featured_ajax_handler() {
    $attachment_action = new AWPCP_Set_Attachment_As_Featured_Ajax_Action(
        awpcp_attachment_properties(),
        awpcp_attachments_logic()
    );

    return awpcp_attachment_action_ajax_handler( $attachment_action );
}

class AWPCP_Set_Attachment_As_Featured_Ajax_Action implements AWPCP_Attachment_Ajax_Action {

    private $attachments_properties;
    private $attachments_logic;

    public function __construct( $attachments_properties, $attachments_logic ) {
        $this->attachments_properties = $attachments_properties;
        $this->attachments_logic = $attachments_logic;
    }

    public function do_action( $ajax_handler, $attachment, $listing ) {
        if ( $this->attachments_properties->is_image( $attachment ) ) {
            return $this->attachments_logic->set_attachment_as_featured( $attachment );
        } else {
            // TODO: is this filter necessary? set_attachment_as_fetured should take care
            //        of havnig only a featured attachment per type of attachment (image, video, others).
            return apply_filters( 'awpcp-set-file-as-primary', false, $attachment, $listing );
        }
    }
}
