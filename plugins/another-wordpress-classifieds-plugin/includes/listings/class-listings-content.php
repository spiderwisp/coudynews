<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integrates with WordPress to render full listings.
 */
class AWPCP_ListingsContent {

    /**
     * @var string A post type identifier.
     */
    private $post_type;

    /**
     * @var object  An instance of Listing Content Renderer.
     */
    private $content_renderer;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @param string $post_type         The identifier of the Listing post type.
     * @param object $content_renderer  An instance of Listing Content Renderer.
     * @param object $wordpress         An instance of WordPress.
     * @since 4.0.0
     */
    public function __construct( $post_type, $content_renderer, $wordpress ) {
        $this->post_type        = $post_type;
        $this->content_renderer = $content_renderer;
        $this->wordpress        = $wordpress;
    }

    /**
     * Handle for the `the_content` filter.
     *
     * @param string $content   The content of the current post.
     * @since 4.0.0
     */
    public function filter_content( $content ) {
        $post = $this->wordpress->get_post();

        if ( ! $this->is_on_single_listing_page( $post ) ) {
            return $content;
        }

        return $this->content_renderer->render( $content, $post );
    }

    /**
     * Remove shortcodes from listings content.
     *
     * @since 4.3.6
     *
     * @param string $content The content of the current post.
     *
     * @return string
     */
    public function filter_content_with_shortcodes( $content ) {
        $post = $this->wordpress->get_post();

        if ( ! $this->is_on_single_listing_page( $post ) ) {
            return $content;
        }

        return strip_shortcodes($content);
    }

    /**
     * Check if the current post is a single listing page.
     *
     * @since 4.3.6
     *
     * @param WP_Post|boolean $post An instance of WP_Post or false.
     *
     * @return boolean
     */
    private function is_on_single_listing_page( $post ) {
        if ( ! $post ) {
            return false;
        }

        if ( $this->post_type !== $post->post_type ) {
            return false;
        }

        if ( ! is_singular( $this->post_type ) ) {
            return false;
        }

        return true;
    }
}
