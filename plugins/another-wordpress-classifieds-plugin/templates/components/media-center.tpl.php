<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-media-center">
    <?php $media_uploader = awpcp_listings_media_uploader_component(); ?>
    <?php $media_uploader->show( $media_uploader_configuration ); ?>

    <?php $messages = awpcp_messages_component(); ?>
    <?php $messages->show( array( 'media-uploader', 'media-manager', 'thumbnails-generator' ) ); ?>

    <?php if ( $show_background_color_explanation ) : ?>
    <p><?php echo esc_html( __( 'The images or files with pale red background have been rejected by an administrator user. Likewise, files with a pale yellow background are awaiting approval. Files that are awaiting approval and rejected files, cannot be shown in the frontend.', 'another-wordpress-classifieds-plugin' ) ); ?></p>
    <?php endif; ?>

    <?php $media_manager = awpcp_media_manager_component(); ?>
    <?php $media_manager->show( $files, $media_manager_configuration ); ?>

    <div class="awpcp-thumbnails-generator" data-nonce="<?php echo esc_attr( wp_create_nonce( 'awpcp-upload-generated-thumbnail-for-listing-' . $listing->ID ) ); ?>">
        <video preload="none" muted="muted" width="0" height="0"></video>
        <canvas></canvas>
    </div>
</div>
