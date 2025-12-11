<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updates the payment term of a listing.
 */
class AWPCP_PaymentTermAjaxHandler extends AWPCP_AjaxHandler {

    private $request;
    private $metabox;
    private $listings;

    public function __construct( $request, $metabox, $response, $listings ) {
        parent::__construct( $response );

        $this->request  = $request;
        $this->metabox  = $metabox;
        $this->listings = $listings;
    }

    public function ajax() {
        $listing = $this->listings->get( $this->request->post( 'listing' ) );

        if ( ! wp_verify_nonce( $this->request->post( 'nonce' ), 'awpcp-upload-media-for-listing-' . $listing->ID ) ) {
            return false;
        }

        $this->metabox->save( $listing->ID, $listing );
    }

    /**
     * @since 4.0.13
     */
    public function maybe_prevent_ad_approval( $prevent_approval ) {
        if ( $prevent_approval ) {
            return $prevent_approval;
        }

        $context = $this->request->post( 'context' );

        if( $context && 'admin-place-ad' === $context ) {
            return true;
        }

        return $prevent_approval;
    }
}
