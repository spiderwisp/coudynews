<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ListingsPaymentTransactions {

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @since 4.0.0
     */
    public function __construct( $payments ) {
        $this->payments = $payments;
    }

    /**
     * Attempts to load the transaction with ID equal to the
     * value of a transaction_id $_REQUEST parameter.
     *
     * @since 4.0.0
     */
    public function get_current_transaction() {
        return $this->payments->get_transaction();
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  When an error occurs trying to change the transaction
     *                          status to Checkout.
     */
    public function prepare_transaction_for_checkout( $transaction, $data ) {
        $categories   = $data['categories'];
        $payment_term = $data['payment_term'];
        $payment_type = $data['payment_type'];
        $user_id      = $data['user_id'];

        $number_of_categories_allowed = apply_filters( 'awpcp-number-of-categories-allowed-in-post-listing-order-step', 1, $payment_term );

        $transaction->user_id = $user_id;
        $transaction->set( 'category', array_slice( $categories, 0, $number_of_categories_allowed ) );
        $transaction->set( 'payment-term-type', $payment_term->type );
        $transaction->set( 'payment-term-id', $payment_term->id );
        $transaction->set( 'payment-term-payment-type', $payment_type );

        $transaction->remove_all_items();
        $transaction->reset_payment_status();

        $this->payments->set_transaction_item_from_payment_term( $transaction, $payment_term, $payment_type );

        // Process transaction to grab Credit Plan information.
        $this->payments->set_transaction_credit_plan( $transaction );

        // Let other parts of the plugin know a transaction is being processed.
        $this->payments->process_transaction( $transaction );

        $transaction_errors = array();
        $this->payments->set_transaction_status_to_ready_to_checkout( $transaction, $transaction_errors );

        if ( $transaction_errors ) {
            throw new AWPCP_Exception( esc_html( array_shift( $transaction_errors ) ) );
        }
    }
}
