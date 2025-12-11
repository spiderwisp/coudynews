<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Defines the list of form steps displayed on the Submit Listing page.
 */
class AWPCP_SubmitListingFormSteps implements AWPCP_FormSteps {

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $payments, $settings ) {
        $this->payments = $payments;
        $this->settings = $settings;
    }

    /**
     * @since 4.0.0     Extracted from Form Steps Component.
     */
    public function get_steps( $params = [] ) {
        $transaction = null;
        $steps       = array();

        if ( isset( $params['transaction'] ) ) {
            $transaction = $params['transaction'];
        }

        if ( $this->should_show_login_step( $transaction ) ) {
            $steps['login'] = __( 'Login/Registration', 'another-wordpress-classifieds-plugin' );
        }

        $should_show_payment_steps = $this->payments->payments_enabled();
        $should_pay_before         = $this->settings->get_option( 'pay-before-place-ad' );

        if ( $should_show_payment_steps && $should_pay_before ) {
            $steps['listing-category'] = __( 'Select a Category', 'another-wordpress-classifieds-plugin' );
            $steps['checkout']         = __( 'Checkout', 'another-wordpress-classifieds-plugin' );
            $steps['payment']          = __( 'Payment', 'another-wordpress-classifieds-plugin' );
        }

        $steps['listing-information'] = __( 'Enter Ad Information', 'another-wordpress-classifieds-plugin' );

        if ( $should_show_payment_steps && ! $should_pay_before ) {
            $steps['checkout'] = __( 'Checkout', 'another-wordpress-classifieds-plugin' );
            $steps['payment']  = __( 'Payment', 'another-wordpress-classifieds-plugin' );
        }

        $steps['finish'] = __( 'Finish', 'another-wordpress-classifieds-plugin' );

        return $steps;
    }

    private function should_show_login_step( $transaction ) {
        if ( ! is_user_logged_in() && ! $this->settings->get_option( 'requireuserregistration' ) ) {
            return false;
        }

        if ( ! is_user_logged_in() ) {
            return true;
        }

        if ( ! is_null( $transaction ) ) {
            return $transaction->get( 'user-just-logged-in', false );
        }

        return awpcp_get_var( array( 'param' => 'loggedin', 'default' => false ) );
    }
}
