<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_UpdateListingOrderAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_PaymentInformationValidator
     */
    private $payment_information_validator;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_ListingsPaymentTransactions
     */
    private $listings_transactions;

    /**
     * @var AWPCP_ListingOrderPostedData
     */
    private $posted_data;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings_logic, $payment_information_validator, $listings, $listings_transactions, $posted_data, $response ) {
        parent::__construct( $response );

        $this->listings_logic                = $listings_logic;
        $this->payment_information_validator = $payment_information_validator;
        $this->listings                      = $listings;
        $this->listings_transactions         = $listings_transactions;
        $this->posted_data                   = $posted_data;
    }

    /**
     * @since 4.0.0
     */
    public function ajax() {
        try {
            return $this->process_request();
        } catch ( AWPCP_Exception $e ) {
            return $this->multiple_errors_response( $e->getMessage() );
        }
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception When user is not authorized to update the ad's order
     *                         data or the information submitted is invalid.
     */
    private function process_request() {
        $nonce = awpcp_get_var( array( 'param' => 'nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'awpcp-update-listing-order' ) ) {
            throw new AWPCP_Exception( esc_html__( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        $listing_id = awpcp_get_var( array( 'param' => 'listing_id' ) );
        $listing    = $this->listings->get( $listing_id );

        if ( ! $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            throw new AWPCP_Exception( esc_html__( 'The payment information for the specified ad cannot be modified at this time.', 'another-wordpress-classifieds-plugin' ) );
        }

        $transaction = $this->listings_transactions->get_current_transaction();

        if ( is_null( $transaction ) ) {
            throw new AWPCP_Exception( esc_html__( "The specified transaction doesn't exist.", 'another-wordpress-classifieds-plugin' ) );
        }

        $posted_data = $this->posted_data->get_posted_data();
        $post_data   = $posted_data['post_data'];

        $errors = $this->payment_information_validator->get_validation_errors( $post_data );

        if ( $errors ) {
            throw new AWPCP_Exception( wp_kses_post( array_shift( $errors ) ) );
        }

        $this->listings_logic->update_listing( $listing, $post_data );
        $this->listings_transactions->prepare_transaction_for_checkout( $transaction, $posted_data );
        $this->listings_logic->update_listing_payment_term( $listing, $posted_data['payment_term'] );

        $response = [
            'transaction' => $transaction->id,
            'listing'     => [
                'ID' => $listing->ID,
            ],
        ];

        return $this->success( $response );
    }
}
