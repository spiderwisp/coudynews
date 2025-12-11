<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utility methods used to find attachments.
 */
class AWPCP_Attachments_Collection {

    private $file_types;
    private $wordpress;

    public function __construct( $file_types, $wordpress ) {
        $this->file_types = $file_types;
        $this->wordpress  = $wordpress;
    }

    public function get( $attachment_id ) {
        return $this->wordpress->get_post( $attachment_id );
    }

    /**
     * Returns the attachment that the user selected as featured or the first
     * attachment of the requested type that is available.
     */
    public function get_featured_attachment_of_type( $type, $query = array() ) {
        $query['posts_per_page'] = 1;

        $attachments_of_type_query = $this->make_attachments_of_type_query( $type, $query );
        $featured_attachment_query = $this->make_featured_attachment_query( $attachments_of_type_query );

        $attachments = $this->find_visible_attachments( $featured_attachment_query );

        if ( empty( $attachments ) ) {
            $attachments = $this->find_visible_attachments( $attachments_of_type_query );
        }

        return array_shift( $attachments );
    }

    private function make_featured_attachment_query( $query ) {
        $query['meta_query'][] = array(
            'key'        => '_awpcp_featured',
            'value'      => true,
            'comparator' => '=',
            'type'       => 'UNSIGNED',
        );

        return $query;
    }

    /**
     * @since 4.0.0
     */
    public function get_featured_image( $post_parent_id ) {
        return $this->get_featured_attachment_of_type(
            'image',
            [
                'post_parent' => $post_parent_id,
            ]
        );
    }

    public function find_attachments( $query = array() ) {
        $attachments = $this->wordpress->create_posts_query( $this->prepare_attachments_query( $query ) );
        return $attachments->posts;
    }

    private function prepare_attachments_query( $query ) {
        $query['post_type'] = 'attachment';

        if ( ! isset( $query['post_status'] ) ) {
            $query['post_status'] = 'any';
        }

        if ( ! isset( $query['posts_per_page'] ) ) {
            $query['posts_per_page'] = -1;
        }

        if ( ! isset( $query['orderby'] ) ) {
            $query['orderby'] = 'name';
        }

        if ( ! isset( $query['order'] ) ) {
            $query['order'] = 'ASC';
        }

        return $query;
    }

    public function count_attachments( $query = array() ) {
        $attachments = new WP_Query( $this->prepare_count_attachments_query( $query ) );
        return $attachments->found_posts;
    }

    private function prepare_count_attachments_query( $query ) {
        $query = $this->prepare_attachments_query( $query );

        $query['fields'] = 'ids';

        return $query;
    }

    public function count_attachments_of_type( $type, $query = array() ) {
        return $this->count_attachments( $this->make_attachments_of_type_query( $type, $query ) );
    }

    private function make_attachments_of_type_query( $types, $query ) {
        $allowed_mime_types = array();

        foreach ( (array) $types as $type ) {
            $mime_types_in_group = $this->file_types->get_allowed_file_mime_types_in_group( $type );
            $allowed_mime_types  = array_merge( $allowed_mime_types, $mime_types_in_group );
        }

        if ( ! empty( $allowed_mime_types ) ) {
            $query['post_mime_type'] = $allowed_mime_types;
        }

        return $query;
    }

    public function find_visible_attachments( $query = array() ) {
        return $this->find_attachments( $this->make_visible_attachments_query( $query ) );
    }

    private function make_visible_attachments_query( $query ) {
        $query['meta_query'][] = array(
            'key'     => '_awpcp_enabled',
            'value'   => true,
            'compare' => '=',
            'type'    => 'UNSIGNED',
        );

        $query['meta_query'][] = array(
            'key'     => '_awpcp_allowed_status',
            'value'   => AWPCP_Attachment_Status::STATUS_APPROVED,
            'compare' => '=',
            'type'    => 'CHAR',
        );

        return $query;
    }

    public function find_attachments_awaiting_approval( $query = array() ) {
        return $this->find_attachments( $this->make_attachments_awaiting_approval_query( $query ) );
    }

    /**
     * Returns attachments that were uploaded using any of the plugin's upload
     * screens (instead of being uploaded to the Media Library directly, for exmaple).
     *
     * @since 4.0.0
     */
    public function find_uploaded_attachments( $query = [] ) {
        return $this->find_attachments( $this->make_uploaded_attachments_query( $query ) );
    }

    private function make_uploaded_attachments_query( $query ) {
        $query['meta_query'][] = [
            'key'     => '_awpcp_allowed_status',
            'compare' => 'EXISTS',
        ];

        return $query;
    }

    private function make_attachments_awaiting_approval_query( $query ) {
        $query['meta_query'][] = array(
            'key'   => '_awpcp_allowed_status',
            'value' => AWPCP_Attachment_Status::STATUS_AWAITING_APPROVAL,
        );

        return $query;
    }

    public function find_attachments_of_type( $type, $query = array() ) {
        return $this->find_attachments( $this->make_attachments_of_type_query( $type, $query ) );
    }

    public function find_attachments_of_type_awaiting_approval( $type, $query = array() ) {
        return $this->find_attachments_awaiting_approval( $this->make_attachments_of_type_query( $type, $query ) );
    }

    public function find_visible_attachments_of_type( $types, $query = array() ) {
        return $this->find_visible_attachments( $this->make_attachments_of_type_query( $types, $query ) );
    }
}
