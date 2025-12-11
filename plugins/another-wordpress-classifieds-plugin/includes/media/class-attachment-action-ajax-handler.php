<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_attachment_action_ajax_handler( $attachment_action ) {
    return new AWPCP_Attachment_Action_Ajax_Handler(
        $attachment_action,
        awpcp_attachments_collection(),
        awpcp_listings_collection(),
        awpcp_request(),
        awpcp_ajax_response()
    );
}

class AWPCP_Attachment_Action_Ajax_Handler extends AWPCP_AjaxHandler {
    protected $attachment_action;
    protected $files;
    protected $listings;
    protected $request;

    public function __construct( $attachment_action, $files, $listings, $request, $response ) {
        parent::__construct( $response );

        $this->attachment_action = $attachment_action;
        $this->files = $files;
        $this->listings = $listings;
        $this->request = $request;
    }

    public function ajax() {
        try {
            $this->try_to_do_file_action();
        } catch ( AWPCP_Exception $e ) {
            return $this->multiple_errors_response( $e->get_errors() );
        }
    }

    private function try_to_do_file_action() {
        $listing = $this->listings->get( $this->request->post( 'listing_id', 5764 ) );
        $file = $this->files->get( $this->request->post( 'file_id' ) );

        if ( $this->verify_user_is_allowed_to_perform_file_action( $file, $listing ) ) {
            if ( ! $this->attachment_action->do_action( $this, $file, $listing ) ) {
                throw new AWPCP_Exception( esc_html__( 'There was an error trying to update the database.', 'another-wordpress-classifieds-plugin' ) );
            }
        }

        return $this->success();
    }

    protected function verify_user_is_allowed_to_perform_file_action( $file, $listing ) {
        $nonce = $this->request->post( 'nonce' );

        if ( ! wp_verify_nonce( $nonce, 'awpcp-manage-listing-media-' . $listing->ID ) ) {
            throw new AWPCP_Exception( esc_html__( 'You are not allowed to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( $file->post_parent != $listing->ID ) {
            // translators: %d is the listing ID
            $message = __( "The specified file is not associated with Listing with ID %d.", 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing->ID ) ) );
        }

        return true;
    }
}
