<?php
/**
 * @package AWPCP\Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AWPCP_PaymentGateway
 */
abstract class AWPCP_PaymentGateway {

    const INTEGRATION_BUTTON      = 'button';
    const INTEGRATION_CUSTOM_FORM = 'custom-form';
    const INTEGRATION_REDIRECT    = 'redirect';

    /**
     * @var string
     */
    public $slug;

    public $name;

    public $description;

    public $icon;

    public function __construct( $slug, $name, $description, $icon ) {
        $this->slug        = $slug;
        $this->name        = $name;
        $this->description = $description;
        $this->icon        = $icon;
    }

    protected function sanitize_billing_information( $data ) {
        if ( strlen( $data['exp_year'] ) === 2 ) {
            $data['exp_year'] = "20{$data['exp_year']}";
        }

        return $data;
    }

    protected function get_posted_billing_information() {
        $data['country']            = awpcp_get_var( array( 'param' => 'country' ), 'post' );
        $data['credit_card_number'] = awpcp_get_var( array( 'param' => 'credit_card_number' ), 'post' );
        $data['credit_card_type']   = awpcp_get_var( array( 'param' => 'credit_card_type' ), 'post' );
        $data['exp_month']          = awpcp_get_var( array( 'param' => 'exp_month' ), 'post' );
        $data['exp_year']           = awpcp_get_var( array( 'param' => 'exp_year' ), 'post' );
        $data['csc']                = awpcp_get_var( array( 'param' => 'csc' ), 'post' );

        $data['first_name']  = awpcp_get_var( array( 'param' => 'first_name' ), 'post' );
        $data['last_name']   = awpcp_get_var( array( 'param' => 'last_name' ), 'post' );
        $data['address_1']   = awpcp_get_var( array( 'param' => 'address_1' ), 'post' );
        $data['address_2']   = awpcp_get_var( array( 'param' => 'address_2' ), 'post' );
        $data['city']        = awpcp_get_var( array( 'param' => 'city' ), 'post' );
        $data['state']       = awpcp_get_var( array( 'param' => 'state' ), 'post' );
        $data['postal_code'] = awpcp_get_var( array( 'param' => 'postal_code' ), 'post' );
        $data['email']       = awpcp_get_var( array( 'param' => 'email' ), 'post' );

        $data['direct-payment-step'] = awpcp_get_var( array( 'param' => 'direct-payment-step' ), 'post' );
        $data['transaction_id']      = awpcp_get_var( array( 'param' => 'transaction_id' ), 'post' );
        $data['step']                = awpcp_get_var( array( 'param' => 'step' ), 'post' );

        return $this->sanitize_billing_information( $data );
    }

    /**
     * @param array $data billing details.
     * @param array $errors errors.
     *
     * @return bool
     */
    protected function validate_posted_billing_information( $data, &$errors = array() ) {
        if ( empty( $data['country'] ) ) {
            $errors['country'] = __( 'The Country is required', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['credit_card_number'] ) ) {
            $errors['credit_card_number'] = __( 'The Credit Card Number is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['exp_month'] ) ) {
            $errors['exp_month'] = __( 'The Expiration Month is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['exp_year'] ) ) {
            $errors['exp_year'] = __( 'The Expiration Year is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['csc'] ) ) {
            $errors['csc'] = __( 'The Card Security Code is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['first_name'] ) ) {
            $errors['first_name'] = __( 'The First Name is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['last_name'] ) ) {
            $errors['last_name'] = __( 'The Last Name is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['address_1'] ) ) {
            $errors['address_1'] = __( 'The Address Line 1 is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['city'] ) ) {
            $errors['city'] = __( 'The City is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( in_array( $data['country'], array( 'US', 'CA', 'AU' ), true ) && empty( $data['state'] ) ) {
            $errors['state'] = __( 'The State is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['postal_code'] ) ) {
            $errors['postal_code'] = __( 'The Postal Code is required.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $data['email'] ) ) {
            $errors['email'] = __( 'The Email is required.', 'another-wordpress-classifieds-plugin' );
        }

        return empty( $errors );
    }

    protected function get_user_info( $user_id = false ) {
        $fields = array( 'first_name', 'last_name', 'user_email', 'awpcp-profile' );
        $data   = awpcp_users_collection()->find_by_id( $user_id, $fields );

        $translations = array(
            'first_name' => 'first_name',
            'last_name'  => 'last_name',
            'email'      => 'user_email',
            'city'       => 'city',
            'address_1'  => 'address',
        );

        foreach ( $translations as $field => $key ) {
            $info[ $field ] = awpcp_get_property( $data, $key );
        }

        return $info;
    }

    protected function render_billing_form( $transaction, $data = array(), $hidden = array(), $errors = array() ) {
        wp_enqueue_script( 'awpcp-billing-form' );
        $listing_id = awpcp_get_var( array( 'param' => 'listing_id' ) );
        if ( empty( $data['email'] ) && $listing_id ) {
            $data['email'] = get_post_meta( $listing_id, '_awpcp_contact_email', true );
        }
        if ( $transaction->user_id && empty( $data ) && is_user_logged_in() ) {
            $data = $this->get_user_info( $transaction->user_id );
        }

        ob_start();
            include AWPCP_DIR . '/frontend/templates/payments-billing-form.tpl.php';
            $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    abstract public function get_integration_type();
    abstract public function process_payment( $transaction);
    abstract public function process_payment_notification( $transaction);
    abstract public function process_payment_completed( $transaction);
    abstract public function process_payment_canceled( $transaction);
}
