<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UI to upload and manage listing images.
 */
class AWPCP_MediaCenterComponent {

    /**
     * @var string
     */
    private $template = AWPCP_DIR . '/templates/components/media-center.tpl.php';

    /**
     * @var object
     */
    private $upload_limits;

    /**
     * @var object
     */
    private $attachments;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @var object
     */
    private $settings;

    /**
     * @param object $upload_limits         An instance of Listing Upload Limits.
     * @param object $attachments           An instance of Attachments Collection.
     * @param object $template_renderer     An instance of Template Renderer.
     * @param object $settings              An instance of Settings.
     * @since 4.0.0
     */
    public function __construct( $upload_limits, $attachments, $template_renderer, $settings ) {
        $this->upload_limits     = $upload_limits;
        $this->attachments       = $attachments;
        $this->template_renderer = $template_renderer;
        $this->settings          = $settings;
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function render( $post ) {
        $allowed_files = $this->upload_limits->get_listing_upload_limits( $post );
        $attachments   = $this->attachments->find_attachments( array( 'post_parent' => $post->ID ) );

        $params = array(
            'listing'                           => $post,
            'files'                             => $attachments,
            'show_background_color_explanation' => $this->settings->get_option( 'imagesapprove' ),
            'media_manager_configuration'       => array(
                'nonce'              => wp_create_nonce( 'awpcp-manage-listing-media-' . $post->ID ),
                'allowed_files'      => $allowed_files,
                'show_admin_actions' => awpcp_current_user_is_moderator(),
            ),
            'media_uploader_configuration'      => array(
                'listing_id'    => $post->ID,
                'context'       => 'manage-media',
                'nonce'         => wp_create_nonce( 'awpcp-upload-media-for-listing-' . $post->ID ),
                'allowed_files' => $allowed_files,
            ),
        );

        return $this->template_renderer->render_template( $this->template, $params );
    }
}
