<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Media Manager Component.
 */
function awpcp_media_manager_component() {
    return new AWPCP_MediaManagerComponent(
        awpcp_attachment_properties(),
        awpcp()->js,
        awpcp()->settings
    );
}

/**
 * UI component to manage listing attachments.
 */
class AWPCP_MediaManagerComponent {

    /**
     * @var AWPCP_Attachment_Properties
     */
    private $attachment_properties;

    /**
     * @var AWPCP_JavaScript
     */
    private $javascript;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var bool
     */
    private $echo = false;

    /**
     * Constructor.
     */
    public function __construct( $attachment_properties, $javascript, $settings ) {
        $this->attachment_properties = $attachment_properties;
        $this->javascript            = $javascript;
        $this->settings              = $settings;
    }

    /**
     * @param array $files      An array of listing attachments.
     * @param array $options    An array of options.
     */
    public function render( $files = array(), $options = array() ) {
        $options['files'] = $this->prepare_files( $files );

        $this->javascript->set( 'media-manager-data', $options );

        return $this->render_component( $options );
    }

    public function show( $files = array(), $options = array() ) {
        $this->echo = true;
        $this->render( $files, $options );
        $this->echo = false;
    }

    /**
     * @param array $files  An array of listing attachments.
     */
    private function prepare_files( $files ) {
        $files_info = array();

        foreach ( $files as $file ) {
            $files_info[] = array(
                'id'           => $file->ID,
                'name'         => $file->post_title,
                'listingId'    => $file->post_parent,
                'enabled'      => $this->attachment_properties->is_enabled( $file ),
                'status'       => $this->attachment_properties->get_allowed_status( $file ),
                'mimeType'     => $file->post_mime_type,
                'isImage'      => $this->attachment_properties->is_image( $file ),
                'isPrimary'    => $this->attachment_properties->is_featured( $file ),
                'thumbnailUrl' => $this->attachment_properties->get_image_url( $file, 'thumbnail' ),
                'iconUrl'      => $this->attachment_properties->get_icon_url( $file ),
                'url'          => $this->attachment_properties->get_image_url( $file, 'large' ),
            );
        }

        return $files_info;
    }

    /**
     * @param array $options    An array of options.
     *
     * @return string
     */
    private function render_component( $options ) {
        $thumbnails_width = $this->settings->get_option( 'imgthumbwidth' );
        $file             = AWPCP_DIR . '/templates/components/media-manager.tpl.php';
        if ( $this->echo ) {
            include $file;
            return '';
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
