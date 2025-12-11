<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-listing-fields-metabox awpcp-master-detail-tabs">
    <input type="hidden" name="awpcp_listing_fields_nonce" value="<?php echo esc_attr( $nonce ); ?>" />

    <ul class="awpcp-tabs">
        <?php $label = _x( 'Form Fields', 'listing fields metabox', 'another-wordpress-classifieds-plugin' ); ?>
        <li class="awpcp-tab awpcp-tab-active"><a href="#awpcp-listing-fields--form-fields"><span class="screen-reader-text"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-feedback"></span><span class="awpcp-tab-name"><?php echo esc_html( $label ); ?></span></a></li>
        <?php if ( $date_form_fields ) : ?>
            <?php $label = _x( 'Start/End Date', 'listing fields metabox', 'another-wordpress-classifieds-plugin' ); ?>
        <li class="awpcp-tab"><a href="#awpcp-listing-fields--start-end-date"><span class="screen-reader-text"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-calendar-alt"></span><span class="awpcp-tab-name"><?php echo esc_html( $label ); ?></span></a></li>
        <?php endif; ?>
        <?php $label = _x( 'Images', 'listing fields metabox', 'another-wordpress-classifieds-plugin' ); ?>
        <li class="awpcp-tab"><a href="#awpcp-listing-fields--media-manager"><span class="screen-reader-text"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-format-gallery"></span><span class="awpcp-tab-name"><?php echo esc_html( $label ); ?></span></a></li>
    </ul>
    <div id="awpcp-listing-fields--form-fields" class="awpcp-tab-panel awpcp-tab-panel-active">
        <?php foreach ( $errors as $index => $error_message ) : ?>
            <?php if ( is_numeric( $index ) ) : ?>
                <?php echo awpcp_render_error_message( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php echo $details_form_fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <div id="awpcp-listing-fields--start-end-date" class="awpcp-tab-panel"><?php echo $date_form_fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <div id="awpcp-listing-fields--media-manager" class="awpcp-listing-fields--media-manager-tab-panel awpcp-tab-panel"><?php echo $media_manager; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
</div>
