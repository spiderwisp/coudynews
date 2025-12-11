<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-upload-media-submit-listing-section awpcp-submit-listing-section">
    <h2 class="awpcp-submit-listing-section-title js-handler"><?php echo esc_html_x( 'Images and attachments', 'upload media submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span></span></h2>

    <div class="awpcp-submit-listing-section-content" data-collapsible awpcp-keep-open>
        <div class="awpcp-upload-media-listing-section__loading_mode">
            <?php echo esc_html_x( 'Loading...', 'upload media submit listing section', 'another-wordpress-classifieds-plugin' ); ?>
        </div>
        <div class="awpcp-upload-media-listing-section__edit_mode">
            <?php if ( is_null( $listing ) ) : ?>
            <?php else : ?>

                <?php foreach ( $messages as $message ) : ?>
                    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>

                <?php include AWPCP_DIR . '/templates/components/media-center.tpl.php'; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
