<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_add_credit_plan_ajax_handler() {
    return new AWPCP_TableEntryActionAjaxHandler(
        new AWPCP_Add_Credit_Plan_Action_Handler(
            awpcp_add_edit_table_entry_rendering_helper( awpcp_credit_plans_admin_page() ),
            awpcp_request()
        ),
        awpcp_ajax_response()
    );
}

class AWPCP_Add_Credit_Plan_Action_Handler implements AWPCP_Table_Entry_Action_Handler {

    private $rendering_helper;
    private $request;

    public function __construct( $rendering_helper, $request ) {
        $this->rendering_helper = $rendering_helper;
        $this->request = $request;
    }

    public function process_entry_action( $ajax_handler ) {
        // The nonce was verified before this method is called.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
        $posted = $_POST;
        awpcp_sanitize_value( 'sanitize_textarea_field', $posted );
        $plan   = new AWPCP_CreditPlan( $posted );

        if ( $this->request->post( 'save' ) ) {
            $this->save_new_credit_plan( $plan, $ajax_handler );
        } else {
            $template = AWPCP_DIR . '/admin/templates/admin-panel-credit-plans-entry-form.tpl.php';
            $ajax_handler->success( array( 'html' => $this->rendering_helper->render_entry_form( $template, $plan ) ) );
        }
    }

    private function save_new_credit_plan( $plan, $ajax_handler ) {
        $errors = array();

        if ( $plan->save( $errors ) === false ) {
            return $ajax_handler->error( array(
                'message' => __( 'The form has errors', 'another-wordpress-classifieds-plugin' ),
                'errors'  => $errors,
            ) );
        } else {
            return $ajax_handler->success( array( 'html' => $this->rendering_helper->render_entry_row( $plan ) ) );
        }
    }
}
