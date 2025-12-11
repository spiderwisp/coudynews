<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_SetTransactionStatusToCompletedStepDecorator extends AWPCP_StepDecorator {

    private $payments;
    private $transaction;

    public function __construct( $decorated, $payments ) {
        parent::__construct( $decorated );
        $this->payments = $payments;
    }

    public function before_get( $controller ) {
        $this->set_transaction_status_to_completed_if_necessary( $controller );
    }

    public function before_post( $controller ) {
        $this->set_transaction_status_to_completed_if_necessary( $controller );
    }

    private function set_transaction_status_to_completed_if_necessary( $controller ) {
        $this->transaction = $controller->get_transaction();

        if ( ! $this->transaction->is_completed() ) {
            $this->set_transaction_status_to_completed();
        }
    }

    private function set_transaction_status_to_completed() {
        $errors = array();
        $this->payments->set_transaction_status_to_completed( $this->transaction, $errors );

        if ( ! $this->transaction->is_completed() ) {
            throw new AWPCP_Exception( esc_html( implode( ' ', $errors ) ) );
        }
    }
}
