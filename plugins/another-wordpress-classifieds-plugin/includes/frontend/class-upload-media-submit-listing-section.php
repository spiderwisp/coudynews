<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upload Media section for the Submit Listing page.
 */
class AWPCP_UploadMediaSubmitListingSection {

    /**
     * @var string
     */
    private $template = 'frontend/upload-media-submit-listing-section.tpl.php';

    /**
     * @var AWPCP_Attachments_Collection
     */
    private $attachments;

    /**
     * @var AWPCP_ListingUploadLimits
     */
    private $listing_upload_limits;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $attachments, $listing_upload_limits, $roles, $template_renderer, $settings ) {
        $this->attachments           = $attachments;
        $this->listing_upload_limits = $listing_upload_limits;
        $this->roles                 = $roles;
        $this->template_renderer     = $template_renderer;
        $this->settings              = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function get_id() {
        return 'upload-media';
    }

    /**
     * @since 4.0.0
     */
    public function get_position() {
        return 20;
    }

    /**
     * @since 4.0.0
     */
    public function get_state( $listing = null ) {
        if ( is_null( $listing ) ) {
            return 'disabled';
        }

        if ( ! $this->listing_upload_limits->are_uploads_allowed_for_listing( $listing ) ) {
            return 'disabled';
        }

        return 'edit';
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'plupload-all' );
    }

    /**
     * @since 4.0.0
     */
    public function render( $listing = null ) {
        if ( is_null( $listing ) ) {
            return $this->render_empty_section();
        }

        return $this->render_media_manager( $listing );
    }

    /**
     * @since 4.0.0
     */
    private function render_empty_section() {
        $params = array(
            'listing' => null,
        );

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function render_media_manager( $listing ) {
        $upload_limits = $this->listing_upload_limits->get_listing_upload_limits( $listing );

        $images_allowed  = 0;
        $images_uploaded = 0;
        $messages        = [];

        if ( isset( $upload_limits['images']['allowed_file_count'] ) ) {
            $images_allowed = $upload_limits['images']['allowed_file_count'];
        }

        if ( isset( $upload_limits['images']['uploaded_file_count'] ) ) {
            $images_uploaded = $upload_limits['images']['uploaded_file_count'];
        }

        if ( $this->settings->get_option( 'imagesapprove' ) === 1 ) {
            $messages[] = __( 'Image approval is in effect so any new images you upload will not be visible to viewers until an admin approves them.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $images_uploaded > 0 ) {
            $messages[] = _x( 'Thumbnails of already uploaded images are shown below.', 'images upload step', 'another-wordpress-classifieds-plugin' );
        }

        $params = [
            'show_background_color_explanation' => $this->settings->get_option( 'imagesapprove' ),
            'media_manager_configuration'       => [
                'nonce'              => wp_create_nonce( 'awpcp-manage-listing-media-' . $listing->ID ),
                'allowed_files'      => $upload_limits,
                'show_admin_actions' => $this->roles->current_user_is_moderator(),
            ],
            'media_uploader_configuration'      => [
                'listing_id'    => $listing->ID,
                'context'       => 'post-listing',
                'nonce'         => wp_create_nonce( 'awpcp-upload-media-for-listing-' . $listing->ID ),
                'allowed_files' => $upload_limits,
            ],
            'listing'                           => $listing,
            'files'                             => $this->attachments->find_attachments(
                [
                    'post_parent' => $listing->ID,
                ]
            ),
            'images_allowed'                    => $images_allowed,
            'images_uploaded'                   => $images_uploaded,
            'messages'                          => $messages,
        ];

        return $this->template_renderer->render_template( $this->template, $params );
    }
}
