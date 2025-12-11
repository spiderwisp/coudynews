<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_credit_plans_notices() {
    return new AWPCP_CreditPlansNotices( awpcp()->settings, awpcp_payments_api() );
}

class AWPCP_CreditPlansNotices {

    private $settings;
    private $payments;

    public function __construct( $settings, $payments ) {
        $this->settings = $settings;
        $this->payments = $payments;
    }

    public function dispatch() {
        if ( $this->settings->get_option( 'enable-credit-system' ) && $this->no_credit_plans_defined() ) {
            return $this->render_notice();
        }
    }

    private function no_credit_plans_defined() {
        return count( $this->payments->get_credit_plans() ) === 0;
    }

    private function render_notice() {
        $message = sprintf(
            /* translators: %1$s open link html, %2$s close link html */
            __( 'You enabled the Credit System, but there are no credit plans defined. Please %1$sadd credit plans or disable the Credit System%2$s.', 'another-wordpress-classifieds-plugin' ),
            '<a href="' . esc_url( awpcp_get_admin_credit_plans_url() ) . '">',
            '</a>'
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_print_error( $message );
    }
}
