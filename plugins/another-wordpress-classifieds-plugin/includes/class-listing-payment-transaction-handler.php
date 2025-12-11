<?php
/**
 * @package AWPCP\Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_listing_payment_transaction_handler() {
    return new AWPCP_ListingPaymentTransactionHandler(
        awpcp_listing_renderer(),
        awpcp_listings_collection(),
        awpcp_listings_api(),
        awpcp()->container['Settings'],
        awpcp_wordpress()
    );
}

class AWPCP_ListingPaymentTransactionHandler {

    private $listing_renderer;
    private $listings;
    private $listings_logic;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    private $wordpress;

    public function __construct( $listing_renderer, $listings, $listings_logic, $settings, $wordpress ) {
        $this->listing_renderer = $listing_renderer;
        $this->listings = $listings;
        $this->listings_logic = $listings_logic;
        $this->settings         = $settings;
        $this->wordpress = $wordpress;
    }

    public function transaction_status_updated( $transaction ) {
        $this->process_payment_transaction( $transaction );
    }

    public function process_payment_transaction( $transaction ) {
        $transaction_is_completed = $transaction->is_completed();

        if ( ! $transaction->is_payment_completed() && ! $transaction_is_completed ) {
            return;
        }

        if ( strcmp( $transaction->get( 'context' ), 'place-ad' ) !== 0 ) {
            return;
        }

        if ( ! $transaction->get( 'ad-id' ) ) {
            return;
        }

        try {
            $listing = $this->listings->get( $transaction->get( 'ad-id' ) );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        $this->update_listing_payment_information( $listing, $transaction );

        // We process the transaction as soon as the payment is completed when users
        // pay for their ads after entering all the required information only. That
        // way ads will become available even if the user doesn't return to the website
        // from the payment gateway.
        //
        // We wait until the transaction is complete for ads that were paid before the
        // deatils were provided.
        if ( ! $transaction_is_completed && $this->settings->get_option( 'pay-before-place-ad' ) ) {
            return;
        }

        $this->consolidate_transaction( $listing, $transaction );
    }

    private function update_listing_payment_information( $listing, $transaction ) {
        if ( ! $transaction->get( 'previous-ad-payment-status' ) ) {
            $transaction->set( 'previous-ad-payment-status', $this->listing_renderer->get_payment_status( $listing ) );

            $this->wordpress->update_post_meta( $listing->ID, '_awpcp_payment_status', $transaction->payment_status );
            $this->wordpress->update_post_meta( $listing->ID, '_awpcp_payment_gateway', $transaction->payment_gateway );
            $this->wordpress->update_post_meta( $listing->ID, '_awpcp_payer_email', $transaction->payer_email );
        }
    }

    private function consolidate_transaction( $listing, $transaction ) {
        $previous_listing_payment_status     = $transaction->get( 'previous-ad-payment-status' );
        $listing_had_accepted_payment_status = $this->is_accepted_payment_status( $previous_listing_payment_status );
        $is_transaction_consolidated = (bool) $transaction->get( 'ad-consolidated-at' );
        $should_trigger_actions = $is_transaction_consolidated;

        if ( $transaction->was_payment_successful() ) {
            if ( ! $listing_had_accepted_payment_status ) {
                $this->listings_logic->update_listing_verified_status( $listing, $transaction );
                $this->listings_logic->set_new_listing_post_status( $listing, $transaction->payment_status, $should_trigger_actions );
            }

            if ( ! $is_transaction_consolidated ) {
                $this->listings_logic->consolidate_new_ad( $listing, $transaction );
            }
        } elseif ( $transaction->did_payment_failed() && $listing_had_accepted_payment_status ) {
            if ( $is_transaction_consolidated ) {
                $this->listings_logic->disable_listing( $should_trigger_actions );
            } else {
                $this->listings_logic->disable_listing_without_triggering_actions( $should_trigger_actions );
            }
        }
    }

    private function is_accepted_payment_status( $payment_status ) {
        if ( $payment_status === AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING ) {
            return true;
        } elseif ( $payment_status === AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED ) {
            return true;
        } elseif ( $payment_status === AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED ) {
            return true;
        }
        return false;
    }
}
