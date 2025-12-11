<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajax handler for listing actions available through the Edit Listing page.
 */
class AWPCP_ExecuteListingActionAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings, $response, $request ) {
        parent::__construct( $response );

        $this->listings = $listings;
        $this->request  = $request;
    }
    /**
     * @since 4.0.0
     */
    public function ajax() {
        try {
            return $this->execute_action();
        } catch ( AWPCP_Exception $e ) {
            return $this->error_response( $e->getMessage() );
        }
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If no listing ID is provided, current user is not
     *                          authorized or the action is not defined.
     */
    public function execute_action() {
        $listing_id = $this->request->post( 'listing_id' );

        if ( empty( $listing_id ) ) {
            throw new AWPCP_Exception( esc_html__( 'No listing ID was provided.', 'another-wordpress-classifieds-plugin' ) );
        }

        $listing = $this->listings->get( $listing_id );
        $action  = $this->request->post( 'listing_action' );
        $nonce   = $this->request->post( 'nonce' );

        if ( ! wp_verify_nonce( $nonce, "awpcp-listing-action-{$listing->ID}-{$action}" ) ) {
            throw new AWPCP_Exception( esc_html__( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( ! has_filter( "awpcp-custom-listing-action-$action" ) ) {
            throw new AWPCP_Exception( esc_html( str_replace( '{action}', $action, __( 'Unknown action: {action}', 'another-wordpress-classifieds-plugin' ) ) ) );
        }

        $response = apply_filters( "awpcp-custom-listing-action-$action", [], $listing );

        if ( isset( $response['error'] ) ) {
            return $this->error_response( $response['error'] );
        }

        return $this->success( $response );
    }
}
