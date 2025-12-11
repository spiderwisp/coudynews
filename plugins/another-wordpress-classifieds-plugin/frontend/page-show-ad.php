<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the content for the individual page of an ad.
 */
class AWPCP_Show_Ad_Page {

    /**
     * @var AWPCP_ListingsContentRenderer
     */
    private $listings_content_renderer;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings_collection;

    /**
     * @var AWPCP_Request
     */
    private $request;

    public function __construct( $listings_content_renderer, $listings_collection, $request ) {
        $this->listings_content_renderer = $listings_content_renderer;
        $this->listings_collection       = $listings_collection;
        $this->request                   = $request;

        add_filter( 'awpcp-ad-details', array( $this, 'oembed' ) );
    }

    /**
     * Acts on awpcp-ad-details filter to add oEmbed support
     */
    public function oembed( $content ) {
        if ( get_awpcp_option( 'allowhtmlinadtext' ) ) {
            global $wp_embed;
            $usecache           = $wp_embed->usecache;
            $wp_embed->usecache = false;
            $content            = $wp_embed->run_shortcode( $content );
            $content            = $wp_embed->autoembed( $content );
            $wp_embed->usecache = $usecache;
        }
        return $content;
    }

    public function dispatch() {
        $listing_id = $this->request->get_current_listing_id();

        if ( ! $listing_id ) {
            $browse_listings_url = awpcp_get_page_url( 'browse-ads-page-name' );

            $message = __( 'No ad ID was specified. Return to {browse_listings_link}browse all ads{/browse_listings_link}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{browse_listings_link}', '<a href="' . esc_url( $browse_listings_url ) . '">', $message );
            $message = str_replace( '{/browse_listings_link}', '</a>', $message );

            return awpcp_print_error( $message );
        }

        try {
            $post = $this->listings_collection->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            $browse_listings_url = awpcp_get_page_url( 'browse-ads-page-name' );

            $message = __( 'No ad was found with ID equal to {listing_id}. Return to {browse_listings_link}browse all ads{/browse_listings_link}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{listing_id}', $listing_id, $message );
            $message = str_replace( '{browse_listings_link}', '<a href="' . esc_url( $browse_listings_url ) . '">', $message );
            $message = str_replace( '{/browse_listings_link}', '</a>', $message );

            return awpcp_print_error( $message );
        }

        return $this->listings_content_renderer->render(
            $post->post_content,
            $post
        );
    }
}
