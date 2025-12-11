<?php
/**
 * @package AWPCP\Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns instance AWPCP_PayPalStandardPaymentGateway.
 */
function awpcp_paypal_standard_payment_gateway() {
    return new AWPCP_PayPalStandardPaymentGateway( awpcp_request() );
}

/**
 * Class AWPCP_PayPalStandardPaymentGateway
 */
class AWPCP_PayPalStandardPaymentGateway extends AWPCP_PaymentGateway {

    const PAYPAL_URL  = 'https://www.paypal.com/cgi-bin/webscr';
    const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    public $request;

    public function __construct( $request ) {
        parent::__construct(
            'paypal',
            _x( 'PayPal', 'payment gateways', 'another-wordpress-classifieds-plugin' ),
            '',
            AWPCP_URL . '/resources/images/payments-paypal.jpg'
        );

        $this->request = $request;
    }

    public function get_integration_type() {
        return self::INTEGRATION_BUTTON;
    }

    public function verify_transaction( $transaction ) {
        $errors = array();

        /*
        PayPal can redirect users using a GET request and issuing
        a POST request in the background. If the transaction was
        already verified during the POST request the result
        should be stored in the transaction's verified attribute.
        */
        $response = null;
        $verified = $transaction->get( 'verified', false );
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! empty( $_POST ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $response = awpcp_paypal_verify_received_data( $_POST, $errors );
            $verified = strcasecmp( $response, 'VERIFIED' ) === 0;
        }

        if ( ! $verified ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $variables = count( $_POST );
            $url       = awpcp_current_url();

            if ( $variables <= 0 ) {
                /* translators: %s link url. */
                $message  = __( "We haven't received your payment information from PayPal yet and we are unable to verify your transaction. Please reload this page or visit <a href=\"%1\$s\">%2\$s</a> in 30 seconds to continue placing your Ad.", 'another-wordpress-classifieds-plugin' );
                $errors[] = sprintf( $message, $url, $url );
            } else {
                /* translators: %s status %d variables count. */
                $message  = __( 'PayPal returned the following status from your payment: %1$s. %2$d payment variables were posted.', 'another-wordpress-classifieds-plugin' );
                $errors[] = sprintf( $message, $response, $variables );
                $errors[] = __( 'If this status is not COMPLETED or VERIFIED, then you may need to wait a bit before your payment is approved, or contact PayPal directly as to the reason the payment is having a problem.', 'another-wordpress-classifieds-plugin' );
            }

            $errors[] = __( 'If you have any further questions, please contact this site administrator.', 'another-wordpress-classifieds-plugin' );

            if ( $variables <= 0 ) {
                $transaction->errors['verification-get'] = $errors;
            } else {
                $transaction->errors['verification-post'] = $errors;
            }
        } else {
            // clean up previous errors.
            unset( $transaction->errors['verification-get'] );
            unset( $transaction->errors['verification-post'] );
        }

        $txn_id = awpcp_get_var( array( 'param' => 'txn_id' ), 'post' );
        $transaction->set( 'txn-id', $txn_id );
        $transaction->set( 'verified', $verified );

        return $response;
    }

    private function validate_transaction( $transaction ) {
        $errors = $transaction->errors;

        // PayPal can redirect users using a GET request and issuing
        // a POST request in the background. If the transaction was
        // already verified during the POST transaction the result
        // should be stored in the transaction's validated attribute.
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( empty( $_POST ) ) {
            return $transaction->get( 'validated', false );
        }

        $mc_gross      = awpcp_get_var( array( 'param' => 'mc_gross', 'sanitize' => 'floatval' ), 'post' );
        $payment_gross = awpcp_get_var( array( 'param' => 'payment_gross', 'sanitize' => 'floatval' ), 'post' );
        $tax           = awpcp_get_var( array( 'param' => 'tax', 'sanitize' => 'floatval' ), 'post' );
        $txn_id        = awpcp_get_var( array( 'param' => 'txn_id' ), 'post' );
        $txn_type      = awpcp_get_var( array( 'param' => 'txn_type' ), 'post' );
        $custom        = awpcp_get_var( array( 'param' => 'custom' ), 'post' );
        $payer_email   = awpcp_get_var( array( 'param' => 'payer_email' ), 'post' );

        // this variables are not used for verification purposes.
        $item_name            = awpcp_get_var( array( 'param' => 'item_name' ), 'post' );
        $item_number          = awpcp_get_var( array( 'param' => 'item_number' ), 'post' );
        $quantity             = awpcp_get_var( array( 'param' => 'quantity' ), 'post' );
        $mc_fee               = awpcp_get_var( array( 'param' => 'mc_fee' ), 'post' );
        $payment_currency     = awpcp_get_var( array( 'param' => 'mc_currency' ), 'post' );
        $exchange_rate        = awpcp_get_var( array( 'param' => 'exchange_rate' ), 'post' );
        $payment_status       = awpcp_get_var( array( 'param' => 'payment_status' ), 'post' );
        $payment_type         = awpcp_get_var( array( 'param' => 'payment_type' ), 'post' );
        $payment_date         = awpcp_get_var( array( 'param' => 'payment_date' ), 'post' );
        $first_name           = awpcp_get_var( array( 'param' => 'first_name' ), 'post' );
        $last_name            = awpcp_get_var( array( 'param' => 'last_name' ), 'post' );
        $address_street       = awpcp_get_var( array( 'param' => 'address_street' ), 'post' );
        $address_zip          = awpcp_get_var( array( 'param' => 'address_zip' ), 'post' );
        $address_city         = awpcp_get_var( array( 'param' => 'address_city' ), 'post' );
        $address_state        = awpcp_get_var( array( 'param' => 'address_state' ), 'post' );
        $address_country      = awpcp_get_var( array( 'param' => 'address_country' ), 'post' );
        $address_country_code = awpcp_get_var( array( 'param' => 'address_country_code' ), 'post' );
        $residence_country    = awpcp_get_var( array( 'param' => 'residence_country' ), 'post' );

        // TODO: Add support for recurring payments and subscriptions?
        if ( ! in_array( $txn_type, array( 'web_accept', 'cart' ), true ) ) {
            // we do not support other forms of payment right now.
            return;
        }

        $totals = $transaction->get_totals();

        $amount                  = number_format( $totals['money'], 2 );
        $amount_before_tax       = number_format( $mc_gross - $tax, 2 );
        $mc_gross_formatted      = number_format( $mc_gross, 2 );
        $payment_gross_formatted = number_format( $payment_gross, 2 );

        if ( $amount !== $mc_gross_formatted && $amount !== $payment_gross_formatted && $amount !== $amount_before_tax ) {
            $message                           = __( 'The amount you have paid does not match the required amount for this transaction. Please contact us to clarify the problem.', 'another-wordpress-classifieds-plugin' );
            $transaction->errors['validation'] = $message;
            $transaction->payment_status       = AWPCP_Payment_Transaction::PAYMENT_STATUS_INVALID;
            awpcp_payment_failed_email( $transaction, $message );
            return false;
        }

        if ( ! $this->funds_were_sent_to_correct_receiver() ) {
            $message                           = __( 'There was an error processing your transaction. If funds have been deducted from your account, they have not been processed to our account. You will need to contact PayPal about the matter.', 'another-wordpress-classifieds-plugin' );
            $transaction->errors['validation'] = $message;
            $transaction->payment_status       = AWPCP_Payment_Transaction::PAYMENT_STATUS_INVALID;
            awpcp_payment_failed_email( $transaction, $message );
            return false;
        }

        // TODO: handle this filter for Ads and Subscriptions.
        $duplicated = apply_filters( 'awpcp-payments-is-duplicated-transaction', false, $txn_id );
        if ( $duplicated ) {
            $message                           = __( 'It appears this transaction has already been processed. If you do not see your ad in the system please contact the site adminstrator for assistance.', 'another-wordpress-classifieds-plugin' );
            $transaction->errors['validation'] = $message;
            $transaction->payment_status       = AWPCP_Payment_Transaction::PAYMENT_STATUS_INVALID;
            awpcp_payment_failed_email( $transaction, $message );
            return false;
        }

        if ( strcasecmp( $payment_status, 'Completed' ) === 0 ) {
            $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED;

        } elseif ( strcasecmp( $payment_status, 'Pending' ) === 0 ) {
            $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;
        } elseif (
            strcasecmp( $payment_status, 'Refunded' ) === 0
            || strcasecmp( $payment_status, 'Reversed' ) === 0
            || strcasecmp( $payment_status, 'Partially-Refunded' ) === 0
            || strcasecmp( $payment_status, 'Canceled_Reversal' ) === 0
            || strcasecmp( $payment_status, 'Denied' ) === 0
            || strcasecmp( $payment_status, 'Expired' ) === 0
            || strcasecmp( $payment_status, 'Failed' ) === 0
            || strcasecmp( $payment_status, 'Voided' ) === 0
        ) {
            $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;

        } else {
            $message                           = __( "We couldn't determine the payment status for your transaction. Please contact customer service if you are viewing this message after having made a payment. If you have not tried to make a payment and you are viewing this message, it means this message is being shown in error and can be disregarded.", 'another-wordpress-classifieds-plugin' );
            $transaction->errors['validation'] = $message;
            $transaction->payment_status       = AWPCP_Payment_Transaction::PAYMENT_STATUS_UNKNOWN;

            return false;
        }

        // at this point the validation was successful, any previously stored
        // errors are irrelevant.
        unset( $transaction->errors['validation'] );

        $transaction->set( 'validated', true );
        $transaction->payment_gateway = $this->slug;
        $transaction->payer_email     = $payer_email;

        return true;
    }

    public function funds_were_sent_to_correct_receiver() {
        $receiver = awpcp_get_var( array( 'param' => 'receiver_email' ), 'post' );
        $business = awpcp_get_var( array( 'param' => 'business' ), 'post' );

        $email_addresses = array( $receiver, $business );
        $email_addresses = array_filter( $email_addresses, 'awpcp_is_valid_email_address' );

        $paypal_email = get_awpcp_option( 'paypalemail' );

        foreach ( $email_addresses as $email_address ) {
            if ( strcasecmp( $paypal_email, $email_address ) === 0 ) {
                return true;
            }
        }

        $received_id  = awpcp_get_var( array( 'param' => 'received_id' ), 'post' );
        $merchant_ids = array( $received_id, $business );
        $merchant_ids = array_filter( $merchant_ids, 'strlen' );

        $paypal_merchant_id = get_awpcp_option( 'paypal_merchant_id' );

        foreach ( $merchant_ids as $merchant_id ) {
            if ( strcasecmp( $paypal_merchant_id, $merchant_id ) === 0 ) {
                return true;
            }
        }

        return false;
    }

    public function process_payment( $transaction ) {
        return $this->render_payment_button( $transaction );
    }

    private function render_payment_button( $transaction ) {
        global $awpcp_imagesurl;

        // no current support for multiple items.
        $item = $transaction->get_item( 0 );

        $is_recurring         = get_awpcp_option( 'paypalpaymentsrecurring' );
        $is_test_mode_enabled = intval( get_awpcp_option( 'paylivetestmode' ) ) === 1;

        $currency = get_awpcp_option( 'paypalcurrencycode' );
        $custom   = $transaction->id;

        $totals = $transaction->get_totals();
        $amount = $totals['money'];

        $payments   = awpcp_payments_api();
        $return_url = $payments->get_return_url( $transaction );
        $notify_url = $payments->get_notify_url( $transaction );
        $cancel_url = $payments->get_cancel_url( $transaction );

        $paypal_url = $is_test_mode_enabled ? self::SANDBOX_URL : self::PAYPAL_URL;

        ob_start();
            include AWPCP_DIR . '/frontend/templates/payments-paypal-payment-button.tpl.php';
            $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function process_payment_completed( $transaction ) {
        if ( $transaction->get( 'verified', false ) ) {
            return;
        }

        if ( ! $this->request->post( 'verify_sign' ) ) {
            $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_VERIFIED;
            return;
        }

        $response                    = $this->verify_transaction( $transaction );
        $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_UNKNOWN;
        if ( 'VERIFIED' === $response ) {
            $this->validate_transaction( $transaction );
        } elseif ( 'INVALID' === $response ) {
            $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_INVALID;
        }
    }

    public function process_payment_notification( $transaction ) {
        $this->process_payment_completed( $transaction );
    }

    public function process_payment_canceled( $transaction ) {
        $transaction->errors[]       = __( 'The payment transaction was canceled by the user.', 'another-wordpress-classifieds-plugin' );
        $transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_CANCELED;
    }
}
