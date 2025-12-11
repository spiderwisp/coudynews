<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_update_attachment_allowed_status_ajax_handler() {
    $attachment_action = new AWPCP_Update_Attachment_Allowed_Status_Ajax_Action(
        awpcp_attachments_logic(),
        awpcp_attachments_collection(),
        awpcp_listings_api()
    );

    return awpcp_attachment_action_ajax_handler( $attachment_action );
}

class AWPCP_Update_Attachment_Allowed_Status_Ajax_Action implements AWPCP_Attachment_Ajax_Action {

    private $attachments_logic;

    protected $attachments;

    protected $listings_logic;

    public function __construct( $attachments_logic, $attachments, $listings_logic ) {
        $this->attachments_logic = $attachments_logic;
        $this->attachments       = $attachments;
        $this->listings_logic    = $listings_logic;
    }

    /**
     * @since 4.0.0     Attemtps to remove _awpcp_has_images_awaiting_approval if
     *                  there are no other attachments awaiting approval.
     */
    public function do_action( $ajax_handler, $attachment, $listing ) {
        $current_action = awpcp_get_var( array( 'param' => 'action' ) );
        $status_updated = false;

        if ( 'awpcp-approve-file' === $current_action ) {
            $status_updated = $this->attachments_logic->approve_attachment( $attachment );
        }

        if ( 'awpcp-reject-file' === $current_action ) {
            $status_updated = $this->attachments_logic->reject_attachment( $attachment );
        }

        if ( $status_updated ) {
            $this->maybe_clear_having_images_awaiting_approval_mark( $listing );
        }

        return $status_updated;
    }

    /**
     * @since 4.0.0
     */
    private function maybe_clear_having_images_awaiting_approval_mark( $listing ) {
        $query_vars = [
            'post_parent' => $listing->ID,
        ];

        $attachments = $this->attachments->find_attachments_awaiting_approval( $query_vars );

        if ( ! empty( $attachments ) ) {
            return;
        }

        $this->listings_logic->remove_having_images_awaiting_approval_mark( $listing );
    }
}
