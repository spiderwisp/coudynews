<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Automatically removes attachments when ads are permanently deleted.
 */
class AWPCP_RemoveListingAttachmentsService {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var AWPCP_Attachments_Collection
     */
    private $attachments;

    /**
     * @var AWPCP_WordPress
     */
    private $wordpress;

    /**
     * @var array List of attachments that should be removed, organized
     *            by parent ID.
     */
    private $queue = [];

    public function __construct( $listing_post_type, $attachments, $wordpress ) {
        $this->listing_post_type = $listing_post_type;
        $this->attachments       = $attachments;
        $this->wordpress         = $wordpress;
    }

    public function enqueue_attachments_to_be_removed( $post_id ) {
        $post = $this->wordpress->get_post( $post_id );

        if ( $this->listing_post_type !== $post->post_type ) {
            return;
        }

        $query_vars  = [ 'post_parent' => $post->ID ];
        $attachments = $this->attachments->find_uploaded_attachments( $query_vars );

        if ( ! $attachments ) {
            return;
        }

        $this->queue[ $post_id ] = $attachments;
    }

    public function remove_attachments( $post_id ) {
        if ( ! isset( $this->queue[ $post_id ] ) || ! is_array( $this->queue[ $post_id ] ) ) {
            return;
        }

        foreach ( $this->queue[ $post_id ] as $attachment ) {
            $this->wordpress->delete_attachment( $attachment->ID );
        }

        unset( $this->queue[ $post_id ] );
    }
}
