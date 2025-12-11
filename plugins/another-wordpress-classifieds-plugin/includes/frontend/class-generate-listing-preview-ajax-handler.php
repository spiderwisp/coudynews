<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajax handler for the action that saves information for the new and existing listings.
 */
class AWPCP_GenerateListingPreviewAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_ListingsContentRenderer
     */
    private $listings_content_renderer;

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
    public function __construct( $listings_content_renderer, $listings, $response, $request ) {
        parent::__construct( $response );

        $this->listings_content_renderer = $listings_content_renderer;
        $this->listings                  = $listings;
        $this->request                   = $request;
    }

    /**
     * TODO: Is apply_filters( 'the_content' ) going to cause compatibility issues?
     * TODO: Do we really need to run that filter here?
     *
     * @since 4.0.0
     */
    public function ajax() {
        $listing_id = $this->request->post( 'ad_id' );
        $listing    = $this->listings->get( $listing_id );
        $content    = apply_filters( 'the_content', $listing->post_content );
        $preview    = $this->listings_content_renderer->render_content_without_notices( $content, $listing );

        return $this->success( [ 'preview' => $preview ] );
    }
}
