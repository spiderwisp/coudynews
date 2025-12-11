<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_PaymentTransactionHelper {

    private $transaction_attributes;
    private $transaction;

    private $transaction_context;

    public function __construct( $transaction_attributes ) {
        $this->transaction_attributes = $transaction_attributes;

        // Added for phpstan, but this class doesn't look like it's being used.
        $this->transaction_context = 'todo';
    }

    public function get_transaction_context() {
        return $this->transaction_context;
    }

    public function get_transaction() {
        return $this->get_transaction_with_finder_function( array( 'AWPCP_Payment_Transaction', 'find_by_id' ) );
    }

    private function get_transaction_with_finder_function( $finder ) {
        if ( ! isset( $this->transaction ) ) {
            $id = awpcp_get_var( array( 'param' => 'transaction_id' ) );
            $this->transaction = call_user_func_array( $finder, array( $id ) );
            $this->transaction = $this->set_transaction_attributes_if_transaction_is_new( $this->transaction );
        }

        return $this->transaction;
    }

    private function set_transaction_attributes_if_transaction_is_new( $transaction ) {
        if ( ! is_null( $transaction ) && $transaction->is_new() ) {
            $transaction->user_id = wp_get_current_user()->ID;

            foreach( $this->transaction_attributes as $name => $value ) {
                $transaction->set( $name, $value );
            }
        }

        return $transaction;
    }

    public function get_or_create_transaction() {
        return $this->get_transaction_with_finder_function( array( 'AWPCP_Payment_Transaction', 'find_or_create' ) );
    }
}

class AWPCP_PaymentTransactionHelperBuilder {

    public function build( $transaction_attributes ) {
        return new AWPCP_PaymentTransactionHelper( $transaction_attributes );
    }
}
