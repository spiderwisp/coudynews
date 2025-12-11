<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_delete_fee_ajax_handler() {
    return new AWPCP_TableEntryActionAjaxHandler(
        new AWPCP_Delete_Fee_Action_Handler(),
        awpcp_ajax_response()
    );
}

class AWPCP_Delete_Fee_Action_Handler implements AWPCP_Table_Entry_Action_Handler {

    private $page;
    private $request;

    public function __construct() {
        $this->page    = awpcp_fees_admin_page();
        $this->request = awpcp_request();
    }

    public function process_entry_action( $ajax_handler ) {
        $fee = AWPCP_Fee::find_by_id( $this->request->post( 'id' ) );

        if ( is_null( $fee ) ) {
            $message = __( "The specified Fee doesn't exists.", 'another-wordpress-classifieds-plugin' );
            return $ajax_handler->error( array( 'message' => $message ) );
        }

        $errors = array();

        if ( $this->request->post( 'remove' ) ) {
            if ( AWPCP_Fee::delete( $fee->id, $errors ) ) {
                return $ajax_handler->success();
            } else {
                return $ajax_handler->error( array( 'message' => join( '<br/>', $errors ) ) );
            }
        } else {
            $params = array( 'columns' => count( $this->page->get_table()->get_columns() ) );
            $template = AWPCP_DIR . '/admin/templates/delete_form.tpl.php';
            return $ajax_handler->success( array( 'html' => awpcp_render_template( $template, $params ) ) );
        }
    }
}
