<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingActionAdminPage {

    protected $listings;

    public function __construct( $listings ) {
        $this->listings = $listings;
    }

    protected function get_selected_listings() {
        $listing_id   = awpcp_get_var( array( 'param' => 'id' ) );
        $listings_ids = (array) awpcp_get_var( array( 'param' => 'selected', 'default' => $listing_id ) );
        $listings_ids = array_filter( array_map( 'intval', $listings_ids ) );

        return $this->listings->find_all_by_id( $listings_ids );
    }

    protected function show_bulk_operation_result_message( $successful_count, $failed_count, $success_message, $error_message ) {
        if ( $successful_count > 0 && $failed_count > 0) {
            /* translators: %1$s the success message, %2$s the error message */
            $message = _x( '%1$s and %2$s.', 'Listing bulk operations: <message-ok> and <message-error>.', 'another-wordpress-classifieds-plugin' );
            awpcp_flash( sprintf( $message, $success_message, $error_message ), 'error' );
        } elseif ( $successful_count > 0 ) {
            awpcp_flash( $success_message . '.' );
        } elseif ( $failed_count > 0 ) {
            awpcp_flash( ucfirst( $error_message . '.' ), 'error' );
        }
    }
}
