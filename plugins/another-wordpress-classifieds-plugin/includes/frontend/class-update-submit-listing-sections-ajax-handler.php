<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajax handler for the action that retrieves up to date versions of the specified
 * submit listing sections.
 */
class AWPCP_UpdateSubmitListingSectionsAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_SubmitLisitngSectionsGenerator
     */
    private $sections_generator;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @since 4.0.0
     */
    public function __construct( $sections_generator, $listings, $payments, $response ) {
        parent::__construct( $response );

        $this->sections_generator = $sections_generator;
        $this->listings           = $listings;
        $this->payments           = $payments;
    }

    /**
     * @since 4.0.0
     */
    public function ajax() {
        $transaction  = $this->payments->get_transaction();
        $listing_id   = awpcp_get_var( array( 'param' => 'listing' ) );
        $sections_ids = awpcp_get_var( array( 'param' => 'sections' ), 'post' );
        $mode         = awpcp_get_var( array( 'param' => 'mode' ) );

        if ( 'edit' !== $mode ) {
            $mode = 'create';
        }

        try {
            $listing = $this->listings->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            return $this->error_response( $e->getMessage() );
        }

        $response = [
            'sections' => $this->sections_generator->get_sections( $sections_ids, $mode, $listing, $transaction ),
        ];

        return $this->success( $response );
    }
}
