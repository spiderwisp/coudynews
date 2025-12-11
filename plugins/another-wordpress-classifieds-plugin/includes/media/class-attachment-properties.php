<?php
/**
 * @package AWPCP\Attachments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper class used to get properties from Attachments objects (WP_Post).
 */
class AWPCP_Attachment_Properties {

    /**
     * @var AWPCP_WordPress
     */
    private $wordpress;

    /**
     * Constructor.
     */
    public function __construct( $wordpress ) {
        $this->wordpress = $wordpress;
    }

    /**
     * @since unknown
     */
    public function is_enabled( $attachment ) {
        return $this->wordpress->get_post_meta( $attachment->ID, '_awpcp_enabled', true );
    }

    /**
     * @since unknown
     */
    public function is_featured( $attachment ) {
        return $this->wordpress->get_post_meta( $attachment->ID, '_awpcp_featured', true );
    }

    /**
     * @since unknown
     */
    public function get_allowed_status( $attachment ) {
        return $this->wordpress->get_post_meta( $attachment->ID, '_awpcp_allowed_status', true );
    }

    /**
     * @since unknown
     */
    public function is_awaiting_approval( $attachment ) {
        return $this->get_allowed_status( $attachment ) === AWPCP_Attachment_Status::STATUS_AWAITING_APPROVAL;
    }

    /**
     * @since unknown
     */
    public function is_image( $attachment ) {
        return in_array( $attachment->post_mime_type, awpcp_get_image_mime_types(), true );
    }

    /**
     * @since 4.0.0
     */
    public function get_url( $attachment ) {
        return $this->wordpress->get_attachment_url( $attachment->ID );
    }

    /**
     * @since unknown
     */
    public function get_image_url( $attachment, $size ) {
        return $this->wordpress->get_attachment_image_url( $attachment->ID, "awpcp-$size" );
    }

    /**
     * @since unknown
     */
    public function get_icon_url( $attachment ) {
        $src = $this->wordpress->get_attachment_image_src( $attachment->ID, 'awpcp-thumbnail', true );
        return is_array( $src ) ? $src[0] : null;
    }

    /**
     * @since unknown
     */
    public function get_image( $attachment, $size, $ah, $attributes ) {
        return $this->wordpress->get_attachment_image( $attachment->ID, "awpcp-$size", $ah, $attributes );
    }
}
