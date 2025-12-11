<?php
/**
 * @package AWPCP\Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Renew_Listing_Payment_Transaction_Handler.
 */
function awpcp_renew_listing_payment_transaction_handler() {
    $container = awpcp()->container;

    return new AWPCP_Renew_Listing_Payment_Transaction_Handler(
        $container['ListingRenderer'],
        $container['ListingsCollection'],
        $container['ListingsLogic']
    );
}

/**
 * Hadnler for Renew Listing transactions.
 */
class AWPCP_Renew_Listing_Payment_Transaction_Handler {

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * Constructor.
     */
    public function __construct( $listing_renderer, $listings, $listings_logic ) {
        $this->listing_renderer = $listing_renderer;
        $this->listings         = $listings;
        $this->listings_logic   = $listings_logic;
    }

    /**
     * Checks transactions as they are being processed by the plugin to act on those
     * that are already completed.
     */
    public function process_payment_transaction( $transaction ) {
        if ( $transaction->is_payment_completed() ) {
            $this->process_transaction( $transaction );
        }
    }

    /**
     * Process a transaction that has been completed.
     */
    private function process_transaction( $transaction ) {
        if ( strcmp( $transaction->get( 'context' ), 'renew-ad' ) !== 0 ) {
            return;
        }

        $listing_id = $transaction->get( 'ad-id' );

        if ( ! $listing_id ) {
            return;
        }

        if ( ! $transaction->was_payment_successful() ) {
            return;
        }

        if ( $transaction->get( 'listing-renewed-on' ) ) {
            return;
        }

        try {
            $listing = $this->listings->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        if ( AWPCP_FeeType::TYPE !== $payment_term->type ) {
            return;
        }

        $this->listings_logic->renew_listing( $listing );

        $transaction->set( 'listing-renewed-on', current_time( 'mysql' ) );
        $transaction->save();

        awpcp_send_ad_renewed_email( $listing );

        // TODO: MOVE inside Ad::renew() ?
        do_action( 'awpcp-renew-ad', $listing->ad_id, $transaction );
    }
}
