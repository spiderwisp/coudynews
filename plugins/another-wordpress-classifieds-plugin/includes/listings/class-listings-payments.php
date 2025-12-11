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
class AWPCP_ListingsPayments {

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings_logic, $listing_renderer, $payments ) {
        $this->listings_logic   = $listings_logic;
        $this->listing_renderer = $listing_renderer;
        $this->payments         = $payments;
    }

    /**
     * @since 4.0.0
     */
    public function update_listing_payment_term( $listing, $new_payment_term ) {
        $previous_payment_term = $this->listing_renderer->get_payment_term( $listing );
        $payment_type          = 'money';

        if ( $this->payments->payment_terms_are_equals( $new_payment_term, $previous_payment_term ) ) {
            return;
        }

        $transaction = $this->create_transaction( $listing, $new_payment_term, $payment_type );

        $this->listings_logic->update_listing_payment_term( $listing, $new_payment_term );

        $this->payments->set_transaction_item_from_payment_term( $transaction, $new_payment_term, $payment_type );

        $errors = array();
        $this->payments->set_transaction_status_to_completed( $transaction, $errors );

        if ( $errors ) {
            $this->listings_logic->update_listing_payment_term( $listing, $previous_payment_term );
            $transaction->delete();
            return;
        }

        // Reload payment term objects so that changes made when the transaction
        // was being processed are available to handlers of `awpcp_listing_payment_term_changed`.
        if ( $previous_payment_term ) {
            $previous_payment_term = $this->payments->get_payment_term( $previous_payment_term->id, $previous_payment_term->type );
        }

        $new_payment_term = $this->listing_renderer->get_payment_term( $listing );

        do_action( 'awpcp_listing_payment_term_changed', $listing, $previous_payment_term, $new_payment_term );
    }

    /**
     * @since 4.0.0
     */
    private function create_transaction( $listing, $payment_term, $payment_type ) {
        $transaction = $this->payments->create_transaction();

        // TODO: Merge with code from Create Emtpy Listing and Save Listing Information ajax handlers. I think the transaction logic can be extracted.
        $transaction->user_id = $listing->post_author;
        $transaction->set( 'context', 'place-ad' );
        $transaction->set( 'ad-id', $listing->ID );
        $transaction->set( 'category', $this->listing_renderer->get_categories_ids( $listing ) );
        $transaction->set( 'payment-term-type', $payment_term->type );
        $transaction->set( 'payment-term-id', $payment_term->id );
        $transaction->set( 'payment-term-payment-type', $payment_type );
        $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED;

        return $transaction;
    }
}
