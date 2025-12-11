<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Image Attachment Creator.
 */
function awpcp_image_attachment_creator() {
    $container = awpcp()->container;

    return new AWPCP_Image_Attachment_Creator(
        awpcp_listing_attachment_creator(),
        $container['AttachmentsLogic'],
        $container['AttachmentsCollection'],
        awpcp_listings_api(),
        awpcp()->settings
    );
}

/**
 * Creates listing image attachments.
 */
class AWPCP_Image_Attachment_Creator {

    /**
     * @var object
     */
    private $attachment_creator;

    /**
     * @var object AttachmentsLogic
     */
    private $attachments_logic;

    /**
     * @var AWPCP_Attachments_Collection
     */
    private $attachments_collection;

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var object Settings
     */
    private $settings;

    public function __construct( $attachment_creator, $attachments_logic, $attachments_collection, $listings_logic, $settings ) {
        $this->attachment_creator     = $attachment_creator;
        $this->attachments_logic      = $attachments_logic;
        $this->attachments_collection = $attachments_collection;
        $this->listings_logic         = $listings_logic;
        $this->settings               = $settings;
    }

    /**
     * @param object $listing       An instance of WP_Post.
     * @param object $file_logic    An instance of File Logic.
     */
    public function create_attachment( $listing, $file_logic ) {
        $allowed_status             = AWPCP_Attachment_Status::STATUS_APPROVED;
        $image_is_awaiting_approval = false;

        // If imagesapprove setting is enabled and the current user is a regular
        // user then the image must be approved by the admin before we can show
        // it in the frontend.
        if ( ! awpcp_current_user_is_moderator() && $this->settings->get_option( 'imagesapprove' ) ) {
            $allowed_status             = AWPCP_Attachment_Status::STATUS_AWAITING_APPROVAL;
            $image_is_awaiting_approval = true;
        }

        // Try to find the featured image for the listing before the attachment is
        // created. get_featured_image() will return any attached image as the
        // featured one, even if it is not actually set as the featured image
        // for that post object.
        $featured_image = $this->attachments_collection->get_featured_image( $listing->ID );

        $image_attachment = $this->attachment_creator->create_attachment( $listing, $file_logic, $allowed_status );

        if ( ! $image_attachment ) {
            return null;
        }

        if ( $image_is_awaiting_approval ) {
            $this->listings_logic->mark_as_having_images_awaiting_approval( $listing );
        }

        if ( ! $featured_image ) {
            $this->attachments_logic->set_attachment_as_featured( $image_attachment );
        }

        return $image_attachment;
    }
}
