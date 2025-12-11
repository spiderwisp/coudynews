<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Listings Media Uploader Component.
 */
function awpcp_listings_media_uploader_component() {
    return new AWPCP_Listings_Media_Uploader_Component(
        awpcp_media_uploader_component(),
        awpcp_file_validation_errors(),
        awpcp()->js
    );
}

/**
 * UI component used to upload media to listings.
 */
class AWPCP_Listings_Media_Uploader_Component {

    /**
     * @var AWPCP_MediaUploaderComponent
     */
    private $media_uploader_component;

    /**
     * @var AWPCP_FileValidationErrors
     */
    private $validation_errors;

    /**
     * @var AWPCP_JavaScript
     */
    private $javascript;

    /**
     * @var bool
     */
    private $echo = false;

    /**
     * Constructor.
     */
    public function __construct( $media_uploader_component, $validation_errors, $javascript ) {
        $this->media_uploader_component = $media_uploader_component;
        $this->validation_errors        = $validation_errors;
        $this->javascript               = $javascript;
    }

    /**
     * @param array $configuration  An array of configuration options.
     */
    public function render( $configuration ) {
        $strings = [
            'upload-restrictions-images-others-videos' => __( 'You can upload <images-left> images of up to <images-max-file-size> each, <videos-left> videos of up to <videos-max-file-size> each and <others-left> other files of up to <others-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-images-others'        => __( 'You can upload <images-left> images of up to <images-max-file-size> each and <others-left> other files (no videos) of up to <others-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-images-videos'        => __( 'You can upload <images-left> images of up to <images-max-file-size> each and <videos-left> videos of up to <videos-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-others-videos'        => __( 'You can upload <videos-left> videos of up to <videos-max-file-size> each and <others-left> other files (no images) of up to <others-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-images'               => __( 'You can upload <images-left> images of up to <images-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-others'               => __( 'You can upload <others-left> files (no videos or images) of up to <others-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'upload-restrictions-videos'               => __( 'You can upload <videos-left> videos of up to <videos-max-file-size> each.', 'another-wordpress-classifieds-plugin' ),
            'cannot-add-more-files'                    => $this->validation_errors->get_cannot_add_more_files_of_type_error_message(),
            'file-is-too-large'                        => $this->validation_errors->get_file_is_too_large_error_message(),
        ];

        $this->javascript->localize( 'media-uploader-strings', $strings );

        $configuration['l10n'] = $strings;

        if ( $this->echo ) {
            $this->media_uploader_component->show( $configuration );
            return;
        }

        return $this->media_uploader_component->render( $configuration );
    }

    /**
     * @since 4.3.3
     *
     * @param array $configuration  An array of configuration options.
     *
     * @return void
     */
    public function show( $configuration ) {
        $this->echo = true;
        $this->render( $configuration );
        $this->echo = false;
    }
}
