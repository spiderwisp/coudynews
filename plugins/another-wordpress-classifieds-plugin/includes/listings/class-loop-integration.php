<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loop Integration adds filter handlers to overwrite
 * some of the attributes of the pages used by the plugin
 * to display classifieds content.
 */
class AWPCP_Loop_Integration {

    /**
     * @var object.
     */
    private $listing_renderer;

    /**
     * @var object.
     */
    private $listings;

    /**
     * @var object.
     */
    private $wordpress;

    /**
     * @var object.
     */
    private $request;

    /**
     * Constructor.
     *
     * @param object $listing_renderer An instance of Listing Renderer.
     * @param object $listings         An instance of Listings Collection.
     * @param object $wordpress        An instance of WordPress.
     * @param object $request          An instnace of Request.
     */
    public function __construct( $listing_renderer, $listings, $wordpress, $request ) {
        $this->listing_renderer = $listing_renderer;
        $this->listings         = $listings;
        $this->wordpress        = $wordpress;
        $this->request          = $request;
    }

    /**
     * Setup filters and actions handlers.
     */
    public function setup() {
        if ( is_page( $this->get_show_listing_page_id() ) && $this->request->get_current_listing_id() ) {
            add_filter( 'the_title', array( $this, 'maybe_overwrite_page_title' ), 10, 2 );
        }
    }

    /**
     * Helper function used to retrie the ID of the Show Listing page.
     */
    private function get_show_listing_page_id() {
        return awpcp_get_page_id_by_ref( 'show-ads-page-name' );
    }

    /**
     * Attempts to replace the title of the Show Listing page
     * with the title of the listing being displayed.
     *
     * @param string $title     The title of the post.
     * @param int    $post_id   The ID of the post that is being processed.
     */
    public function maybe_overwrite_page_title( $title, $post_id = null ) {
        if ( is_null( $post_id ) ) {
            $post    = $this->wordpress->get_post();
            $post_id = isset( $post->ID ) ? $post->ID : null;
        }

        if ( $this->get_show_listing_page_id() !== $post_id ) {
            return $title;
        }

        $listing_id = $this->request->get_current_listing_id();

        try {
            $listing = $this->listings->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            return $title;
        }

        return $this->listing_renderer->get_listing_title( $listing );
    }
}
