<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_files_settings() {
    return new AWPCP_FilesSettings();
}

class AWPCP_FilesSettings {

    private $file_types;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    public function __construct() {
        $this->file_types = awpcp_file_types();
        $this->settings   = awpcp()->container['Settings'];
    }

    public function register_settings( $settings_manager ) {
        $settings_manager->add_settings_group( [
            'id'       =>'attachments-settings',
            'name'     => __( 'Media', 'another-wordpress-classifieds-plugin' ),
            'priority' => 50,
        ] );

        $this->register_general_media_settings( $settings_manager );
        $this->register_moderation_media_settings( $settings_manager );
        $this->register_images_media_settings( $settings_manager );
    }

    private function register_general_media_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'general-media-settings',
            'name'     => __( 'General', 'another-wordpress-classifieds-plugin' ),
            'priority' => 10,
            'parent'   => 'attachments-settings',
        ] );

        $group = 'general-media-settings';
        $key   = 'uploads-directory';

        $settings_manager->add_section( $group, __( 'Uploads Directory', 'another-wordpress-classifieds-plugin' ), 'uploads-directory', 10, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting( $key, 'uploadfoldername', __( 'Uploads folder name', 'another-wordpress-classifieds-plugin' ), 'textfield', 'uploads', __( 'Upload folder name. (Folder must exist and be located in your wp-content directory)', 'another-wordpress-classifieds-plugin' ) );

        $permissions = array(
            '0755' => '0755',
            '0775' => '0775',
            '0777' => '0777',
        );

        $settings_manager->add_setting(
            $key,
            'upload-directory-permissions',
            __( 'File permissions for uploads directory', 'another-wordpress-classifieds-plugin' ),
            'radio',
            '0755',
            __( 'File permissions applied to the uploads directory and sub-directories so that the plugin is allowed to write to those directories.', 'another-wordpress-classifieds-plugin' ),
            array( 'options' => $permissions )
        );
    }

    public function primary_image_excerpt_section_header() {
        esc_html_e( 'Configure the dimensions of the image displayed as the thumbnail for each ad in the page with the list of ads.', 'another-wordpress-classifieds-plugin' );
    }

    public function primary_image_section_header() {
        esc_html_e( 'Configure the dimensions of the image displayed as the main thumbnail on the page that shows the ad details.', 'another-wordpress-classifieds-plugin' );
    }

    public function thumbnails_section_header() {
        esc_html_e( 'These are the remaining images that are not primary ones, if you have more than one image allowed per listing.', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    private function register_moderation_media_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'moderation-media-settings',
            'name'     => __( 'Dimensions', 'another-wordpress-classifieds-plugin' ),
            'priority' => 20,
            'parent'   => 'attachments-settings',
        ] );

        $this->register_images_file_size_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    public function register_images_moderation_settings( $settings_manager ) {
        $key = 'images-moderation-settings';

        $settings_manager->add_section( 'listings-moderation', __( 'Images Moderation', 'another-wordpress-classifieds-plugin' ), $key, 20, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting( $key, 'imagesapprove', __( 'Hide images until admin approves them', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, '');

        $settings_manager->add_setting(
            $key,
            'show-popup-if-user-did-not-upload-files',
            __( "Show popup if user didn't upload files", 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            false,
            __( 'If checked, a popup warning the user about leaving the page without uploading a file, will be shown when users try to navigate away from the Upload Files step without uploading at least one image or attachment.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'imagesallowedfree', __( 'Number of images allowed if payments are disabled (Free Mode)', 'another-wordpress-classifieds-plugin' ), 'textfield', 4, __( 'If images are allowed and payments are disabled, users will be allowed upload this amount of images.', 'another-wordpress-classifieds-plugin' ) );

        $default_image_extenstions   = array();
        $image_extensions            = $this->file_types->get_file_extensions_in_group( 'image' );

        if ( $this->settings->get_option( 'imagesallowdisallow', true ) ) {
            $default_image_extenstions = $image_extensions;
        }

        awpcp_register_allowed_extensions_setting(
            $settings_manager,
            $key,
            array(
                'name' => 'allowed-image-extensions',
                'label' => __( 'Allowed image extensions', 'another-wordpress-classifieds-plugin' ),
                'choices' => $image_extensions,
                'default' => $default_image_extenstions,
            )
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_images_file_size_settings( $settings_manager ) {
        $group = 'moderation-media-settings';
        $key   = 'image-file-size';

        $settings_manager->add_section( $group, __( 'Images File Size', 'another-wordpress-classifieds-plugin' ), 'image-file-size', 30, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting( $key, 'maximagesize', __( 'Maximum file size per image', 'another-wordpress-classifieds-plugin' ), 'textfield', '1000000', __( 'Maximum file size, in bytes, for files user can upload to system. 1 MB = 1000000 bytes. You can google "x MB to bytes" to get an accurate conversion.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'minimagesize', __( 'Minimum file size per image', 'another-wordpress-classifieds-plugin' ), 'textfield', '300', __( 'Minimum file size, in bytes, for files user can upload to system. 1 MB = 1000000 bytes. You can google "x MB to bytes" to get an accurate conversion.', 'another-wordpress-classifieds-plugin' ) );
    }

    /**
     * @since 4.0.0
     */
    private function register_images_dimensions_settings( $settings_manager ) {
        $group = 'moderation-media-settings';
        $key   = 'images-dimensions-settings';

        $settings_manager->add_section( $group, __( 'Images Dimensions', 'another-wordpress-classifieds-plugin' ), 'images-dimensions-settings', 30, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting( $key, 'imgminwidth', __( 'Minimum image width', 'another-wordpress-classifieds-plugin' ), 'textfield', '640', __( 'Minimum width for images.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'imgminheight', __( 'Minimum image height', 'another-wordpress-classifieds-plugin' ), 'textfield', '480', __( 'Minimum height for images.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'imgmaxwidth', __( 'Maximum image width', 'another-wordpress-classifieds-plugin' ), 'textfield', '640', __( 'Maximum width for images. Images wider than settings are automatically resized upon upload.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'imgmaxheight', __( 'Maximum image height', 'another-wordpress-classifieds-plugin' ), 'textfield', '480', __( 'Maximum height for images. Images taller than settings are automatically resized upon upload.', 'another-wordpress-classifieds-plugin' ) );
    }

    private function register_images_media_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'presentation-media-settings',
            'name'     => __( 'Presentation', 'another-wordpress-classifieds-plugin' ),
            'priority' => 30,
            'parent'   => 'attachments-settings',
        ] );

        $this->register_images_presentation_settings( $settings_manager );
        $this->register_images_dimensions_settings( $settings_manager );
        $this->register_primary_image_settings( $settings_manager );
        $this->register_thumbnails_image_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    private function register_images_presentation_settings( $settings_manager ) {
        global $awpcp_imagesurl;

        $key = 'images-presentation-settings';

        $settings_manager->add_settings_section( [
            'subgroup' => 'presentation-media-settings',
            'name'     => __( 'Images Presentation', 'another-wordpress-classifieds-plugin' ),
            'id'       => 'images-presentation-settings',
            'priority' => 20,
        ] );

        $options = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4);

        $settings_manager->add_setting( [
            'id'          => 'display-thumbnails-in-columns',
            'name'        => __( 'Number of columns of thumbnails to show in Show Ad page', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'select',
            'default'     => 0,
            'description' => __( 'Zero means there will be as many thumbnails as possible per row.', 'another-wordpress-classifieds-plugin' ),
            'options'     => $options,
            'section'     => $key,
        ] );

        $settings_manager->add_setting( $key, 'awpcp_thickbox_disabled', __( "Disable AWPCP's Lightbox feature", 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Do not include the lightbox jQuery plugin used by AWPCP. Use this option to fix conflicts with themes or plugins that also offers a lightbox feature.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'show-click-to-enlarge-link', __( 'Show click to enlarge link?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, '' );

        $settings_manager->add_setting( $key, 'hide-noimage-placeholder', __( 'Hide No Image placeholder', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, '' );

        $settings_manager->add_setting( [
            'id' => 'override-noimage-placeholder',
            'name' => __( 'Override the No Image placeholder image with my own', 'another-wordpress-classifieds-plugin' ),
            'type' => 'checkbox',
            'default' => 0,
            'behavior' => [
                'shownUnless' => 'hide-noimage-placeholder',
            ],
            'section' => $key,
        ] );

        $settings_manager->add_setting( [
            'id'          => 'noimage-placeholder-url',
            'name'        => __( 'No Image Placeholder URL', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => sprintf( '%s/adhasnoimage.png', $awpcp_imagesurl ),
            'description' => __( 'Put the URL of an existing image on your site to use.  The size of this image should match the thumbnail size settings on this tab', 'another-wordpress-classifieds-plugin' ),
            'behavior'   => [
                'shownUnless' => 'hide-noimage-placeholder',
                'enabledIf'   => 'override-noimage-placeholder',
            ],
            'section'     => $key,
        ] );
    }

    /**
     * @since 4.0.0
     */
    private function register_primary_image_settings( $settings_manager ) {
        $settings_manager->add_settings_section(
            [
                'id'       => 'featured-image-on-lists',
                'name'     => __( 'Primary Image (List of ads)', 'another-wordpress-classifieds-plugin'),
                'subgroup' => 'moderation-media-settings',
                'callback' => [ $this, 'primary_image_excerpt_section_header' ],
                'priority' => 35,
            ]
        );

        $settings_manager->add_setting(
            [
                'id'          => 'displayadthumbwidth',
                'name'        => __( 'Thumbnail width', 'another-wordpress-classifieds-plugin' ),
                'type'        => 'textfield',
                'default'     => '80',
                'description' => __( 'Width of the thumbnail for the primary image shown in the list of ads.', 'another-wordpress-classifieds-plugin' ),
                'section'     => 'featured-image-on-lists',
            ]
        );

        $settings_manager->add_setting(
            [
                'id'          => 'featured-image-height-on-lists',
                'name'        => __( 'Thumbnail height', 'another-wordpress-classifieds-plugin' ),
                'type'        => 'textfield',
                'default'     => $this->settings->get_option( 'displayadthumbwidth', '' ),
                'description' => __( 'Height of the thumbnail for the primary image shown in the list of ads.', 'another-wordpress-classifieds-plugin' ),
                'section'     => 'featured-image-on-lists',
            ]
        );

        $settings_manager->add_setting(
            [
                'id'          => 'crop-featured-image-on-lists',
                'name'        => 'Crop thumbnail',
                'type'        => 'checkbox',
                'default'     => true,
                'description' => __( 'If you decide to crop thumbnails, images will match exactly the dimensions in the settings above but part of the image may be cropped out. If you decide to resize, image thumbnails will be resized to match the specified width and their height will be adjusted proportionally; depending on the uploaded images, thumbnails may have different heights.', 'another-wordpress-classifieds-plugin' ),
                'section'     => 'featured-image-on-lists',
            ]
        );

        $key = 'primary-image';

        $settings_manager->add_settings_section(
            [
                'id' => 'primary-image',
                'name'     => __( 'Primary Image (Single ad page)', 'another-wordpress-classifieds-plugin'),
                'subgroup' => 'moderation-media-settings',
                'callback' => [ $this, 'primary_image_section_header' ],
                'priority' => 40,
            ]
        );

        $settings_manager->add_setting( $key, 'primary-image-thumbnail-width', __( 'Thumbnail width (Primary Image)', 'another-wordpress-classifieds-plugin' ), 'textfield', '200', __( 'Width of the thumbnail for the primary image shown in Single Ad view.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'primary-image-thumbnail-height', __( 'Thumbnail height (Primary Image)', 'another-wordpress-classifieds-plugin' ), 'textfield', '200', __( 'Height of the thumbnail for the primary image shown in Single Ad view.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'crop-primary-image-thumbnails', __( 'Crop primary image thumbnails?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, _x('If you decide to crop thumbnails, images will match exactly the dimensions in the settings above but part of the image may be cropped out. If you decide to resize, image thumbnails will be resized to match the specified width and their height will be adjusted proportionally; depending on the uploaded images, thumbnails may have different heights.', 'settings', 'another-wordpress-classifieds-plugin' ) );
    }

    /**
     * @since 4.0.0
     */
    private function register_thumbnails_image_settings( $settings_manager ) {
        $group = 'moderation-media-settings';
        $key = 'thumbnails';

        $settings_manager->add_section( $group, __( 'Thumbnails', 'another-wordpress-classifieds-plugin' ), 'thumbnails', 50, array( $this, 'thumbnails_section_header' ) );

        $settings_manager->add_setting( $key, 'imgthumbwidth', __( 'Thumbnail width', 'another-wordpress-classifieds-plugin' ), 'textfield', '125', __( 'Width of the thumbnail images.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'imgthumbheight', __( 'Thumbnail height', 'another-wordpress-classifieds-plugin' ), 'textfield', '125', __( 'Height of the thumbnail images.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'crop-thumbnails', __( 'Crop thumbnail images?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, _x( 'If you decide to crop thumbnails, images will match exactly the dimensions in the settings above but part of the image may be cropped out. If you decide to resize, image thumbnails will be resized to match the specified width and their height will be adjusted proportionally; depending on the uploaded images, thumbnails may have different heights.', 'settings', 'another-wordpress-classifieds-plugin' ) );
    }
}
