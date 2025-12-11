<?php
/**
 * TODO: This doesn't seem to be needed anymore.
 *
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_delete_listing_ajax_handler() {
    return new AWPCP_TableEntryActionAjaxHandler(
        new AWPCP_DeleteListingAjaxHandler(
            awpcp_listings_api(),
            awpcp_listings_collection(),
            awpcp_listing_authorization(),
            awpcp_request()
        ),
        awpcp_ajax_response()
    );
}

class AWPCP_DeleteListingAjaxHandler implements AWPCP_Table_Entry_Action_Handler {

    private $listings_logic;
    private $listings;
    private $authorization;
    private $request;

    public function __construct( $listings_logic, $listings, $authorization, $request ) {
        $this->listings_logic = $listings_logic;
        $this->listings = $listings;
        $this->authorization = $authorization;
        $this->request = $request;
    }

    public function process_entry_action( $ajax_handler ) {
        $listing_id = $this->request->post( 'id', 0 );

        try {
            $listing = $this->listings->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            $message = _x( "The specified Ad doesn't exists.", 'ajax delete ad', 'another-wordpress-classifieds-plugin' );
            return $ajax_handler->error( array( 'message' => $message ) );
        }

        if ( ! $this->authorization->is_current_user_allowed_to_edit_listing( $listing ) ) {
            $message = _x( 'You are not authorized to edit this listing.', 'ajax delete ad', 'another-wordpress-classifieds-plugin' );
            return $ajax_handler->error( array( 'message' => $message ) );
        }

        if ( $this->request->post( 'remove' ) ) {
            $this->delete_listing( $listing, $ajax_handler );
        } else {
            // $params = array( 'columns' => count( $this->page->get_table()->get_columns() ) );
            $params = array( 'columns' => 0 );
            $template = AWPCP_DIR . '/admin/templates/delete_form.tpl.php';
            return $ajax_handler->success( array( 'html' => awpcp_render_template( $template, $params ) ) );
        }
    }

    private function delete_listing( $listing, $ajax_handler ) {
        if ( $this->listings_logic->delete_listing( $listing ) ) {
            return $ajax_handler->success();
        } else {
            $message = __( 'That Ad failed to delete.', 'another-wordpress-classifieds-plugin' );
            return $ajax_handler->error( array( 'message' => $message ) );
        }
    }
}
