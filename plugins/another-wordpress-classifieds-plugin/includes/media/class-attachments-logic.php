<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides methods to moderate, set as featured, delete or otherwise manage
 * attachments as required by the core plugin and premium modules.
 */
class AWPCP_Attachments_Logic {

    private $file_types;
    private $attachments;
    private $wordpress;

    public function __construct( $file_types, $attachments, $wordpress ) {
        $this->file_types  = $file_types;
        $this->attachments = $attachments;
        $this->wordpress   = $wordpress;
    }

    public function approve_attachment( $attachment ) {
        return $this->wordpress->update_post_meta(
            $attachment->ID,
            '_awpcp_allowed_status',
            AWPCP_Attachment_Status::STATUS_APPROVED
        );
    }

    public function reject_attachment( $attachment ) {
        return $this->wordpress->update_post_meta(
            $attachment->ID,
            '_awpcp_allowed_status',
            AWPCP_Attachment_Status::STATUS_REJECTED
        );
    }

    public function enable_attachment( $attachment ) {
        return $this->wordpress->update_post_meta( $attachment->ID, '_awpcp_enabled', true );
    }

    public function disable_attachment( $attachment ) {
        return $this->wordpress->delete_post_meta( $attachment->ID, '_awpcp_enabled' );
    }

    public function set_attachment_as_featured( $attachment ) {
        $attachment_type = $this->get_type_of_attachment( $attachment );

        $attachments = $this->attachments->find_attachments_of_type(
            $attachment_type,
            [ 'post_parent' => $attachment->post_parent ]
        );

        foreach ( $attachments as $an_attachment ) {
            $this->wordpress->delete_post_meta( $an_attachment->ID, '_awpcp_featured' );
        }

        $this->wordpress->set_post_thumbnail( $attachment->post_parent, $attachment->ID );

        return $this->wordpress->update_post_meta( $attachment->ID, '_awpcp_featured', true );
    }

    private function get_type_of_attachment( $attachment ) {
        $file_types     = $this->file_types->get_file_types();
        $file_extension = awpcp_get_file_extension( $attachment->post_title );

        foreach ( $file_types as $type => $subtypes ) {
            foreach ( $subtypes as $subtype_properties ) {
                if ( in_array( $attachment->post_mime_type, $subtype_properties['mime_types'], true ) ) {
                    return $type;
                }

                if ( in_array( $file_extension, $subtype_properties['extensions'], true ) ) {
                    return $type;
                }
            }
        }

        return null;
    }

    public function delete_attachment( $attachment ) {
        return $this->wordpress->delete_attachment( $attachment->ID, true ) !== false;
    }
}
