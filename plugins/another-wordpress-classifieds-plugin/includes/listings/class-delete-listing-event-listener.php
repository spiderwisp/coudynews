<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This class integrates with the events fired inside {@see wp_trash_post()}
 * and {@see wp_delete_post()} to allow the plugin and modules to react when
 * a listing is sent to Trash or permanently deleted.
 *
 * This class attemtps to follow some of the principles of a service based plugin
 * implementation, as in https://github.com/mwpd/basic-scaffold/tree/4e2d4cf
 *
 * @since 4.0.0
 */
class AWPCP_DeleteListingEventListener {

    /**
     * @var string
     */
    private $listing_post_type;

    public function __construct( $listing_post_type ) {
        $this->listing_post_type = $listing_post_type;
    }

    /**
     * Add handlers for the actions and filters of our interest.
     *
     * @since 4.0.0
     */
    public function register() {
        add_action( 'wp_trash_post', [ $this, 'before_trash_post' ] );
        add_action( 'trashed_post', [ $this, 'after_trash_post' ] );

        add_action( 'untrash_post', [ $this, 'before_untrash_post' ] );
        add_action( 'untrashed_post', [ $this, 'after_untrash_post' ] );

        add_action( 'before_delete_post', [ $this, 'before_delete_post' ] );
        add_action( 'after_delete_post', [ $this, 'after_delete_post' ] );
    }

    /**
     * @since 4.0.0
     */
    public function before_trash_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_before_trash_ad', $post_id );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_do_action( $action, $post_id ) {
        $post = get_post( $post_id );

        if ( ! isset( $post->post_type ) ) {
            return;
        }

        if ( $this->listing_post_type !== $post->post_type ) {
            return;
        }

        do_action( $action, $post );
    }

    /**
     * @since 4.0.0
     */
    public function after_trash_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_after_trash_ad', $post_id );
    }

    /**
     * @since 4.0.0
     */
    public function before_untrash_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_before_untrash_ad', $post_id );
    }

    /**
     * @since 4.0.0
     */
    public function after_untrash_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_after_untrash_ad', $post_id );
    }

    /**
     * @since 4.0.0
     */
    public function before_delete_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_before_delete_ad', $post_id );
    }

    /**
     * @since 4.0.0
     */
    public function after_delete_post( $post_id ) {
        $this->maybe_do_action( 'awpcp_delete_ad', $post_id );
    }
}
