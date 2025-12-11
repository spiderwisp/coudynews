<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common methods for Submit Listing sections.
 */
trait AWPCP_SubmitListingSectionTrait {

    /**
     * @since 4.0.0
     */
    protected function can_payment_information_be_modified_during_submit( $listing ) {
        if ( is_null( $listing ) ) {
            return true;
        }

        if ( $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            return true;
        }

        return false;
    }
}
