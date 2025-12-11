<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Verify data received from PayPal IPN notifications and returns PayPal's
 * response.
 *
 * Request errors, if any, are returned by reference.
 *
 * @since 2.0.7
 *
 * @return string VERIFIED, INVALID or ERROR
 */
function awpcp_paypal_verify_received_data($data=array(), &$errors=array()) {
    $content = 'cmd=_notify-validate';
    foreach ($data as $key => $value) {
        $value    = rawurlencode(stripslashes($value));
        $content .= "&$key=$value";
    }

    // Use WordPress HTTP API for all verification requests
    return awpcp_paypal_verify_received_data_with_wp_http( $content, $errors );
}

/**
 * Validate the data received from PayFast.
 *
 * @since 3.7.8
 */
function awpcp_payfast_verify_received_data( $data = array() ) {
    $content = '';

    foreach ( $data as $key => $value ) {
        if ( $key === 'signature' ) {
            continue;
        }

        $content .= $key . '=' . rawurlencode( stripslashes( $value ) ) . '&';
    }

    $content = rtrim( $content, '&' );
    return awpcp_payfast_verify_received_data_with_wp_http( $content );
}

/**
 * This function was added to replace the legacy functions awpcp_payfast_verify_received_data_with_curl() and awpcp_payfast_verify_received_data_with_fsockopen().
 *
 * @since 4.4
 *
 * @return string 'VALID', 'INVALID' or 'ERROR'
 */
function awpcp_payfast_verify_received_data_with_wp_http( $content ) {
    if ( get_awpcp_option( 'paylivetestmode' ) ) {
        $host = 'sandbox.payfast.co.za';
    } else {
        $host = 'www.payfast.co.za';
    }

    $url = 'https://' . $host . '/eng/query/validate';

    $args = array(
        'method'      => 'POST',
        'timeout'     => 30,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent'   => 'Another WordPress Classifieds Plugin',
        ),
        'body'        => $content,
        'cookies'     => array(),
        'sslverify'   => true,
    );

    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        return 'ERROR';
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    if ( 200 !== $response_code ) {
        return 'ERROR';
    }

    $response_body = wp_remote_retrieve_body( $response );
    $response_body = trim( $response_body );

    if ( in_array( $response_body, array( 'VALID', 'INVALID' ), true ) ) {
        return $response_body;
    } else {
        return 'ERROR';
    }
}

/**
 * email the administrator and the user to notify that the payment process was failed
 * @since  2.1.4
 */
function awpcp_payment_failed_email($transaction, $message='') {
    // $message parameter is kept for backward compatibility but not currently used
    $user = get_userdata($transaction->user_id);

    // user email

    $mail           = new AWPCP_Email();
    $mail->to[]     = awpcp_format_recipient_address( $user->user_email, $user->display_name );
    $mail->subject  = get_awpcp_option('paymentabortedsubjectline');

    $template = AWPCP_DIR . '/frontend/templates/email-abort-payment-user.tpl.php';
    $mail->prepare($template, compact('message', 'user', 'transaction'));

    $mail->send();

    // admin email

    $mail           = new AWPCP_Email();
    $mail->to[]     = awpcp_admin_email_to();
    $mail->subject  = __( 'Customer attempt to pay has failed', 'another-wordpress-classifieds-plugin');

    $template = AWPCP_DIR . '/frontend/templates/email-abort-payment-admin.tpl.php';
    $mail->prepare($template, compact('message', 'user', 'transaction'));

    $mail->send();
}

function awpcp_paypal_supported_currencies() {
    return array(
        'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR',
        'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD',
        'THB', 'TRY', 'USD',
    );
}

function awpcp_paypal_supports_currency( $currency_code ) {
    $currency_codes = awpcp_paypal_supported_currencies();

    if ( ! in_array( $currency_code, $currency_codes, true ) ) {
        return false;
    }

    return true;
}
