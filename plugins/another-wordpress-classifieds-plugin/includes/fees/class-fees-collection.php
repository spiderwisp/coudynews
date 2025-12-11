<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_fees_collection() {
    return new AWPCP_Fees_Collection();
}

class AWPCP_Fees_Collection {

    public function get( $fee_id ) {
        $fee = AWPCP_Fee::find_by_id( $fee_id );

        if ( is_null( $fee ) ) {
            $message = __( 'No Fee Plan was found with ID = <fee-id>.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<fee-id>', $fee_id, $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return $fee;
    }

    public function all() {
        return AWPCP_Fee::query();
    }
}
