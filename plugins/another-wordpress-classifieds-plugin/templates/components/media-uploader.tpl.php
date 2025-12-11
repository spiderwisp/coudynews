<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div id="awpcp-media-uploader" class="awpcp-media-uploader">
    <div class="awpcp-hide-if-js">
        <?php echo esc_html( __( "Your browser doesn't have Flash, Silverlight or HTML5 support.", 'another-wordpress-classifieds-plugin' ) ); ?>
    </div>
    <div class="awpcp-media-uploader-dropzone">
        <div class="awpcp-media-uploader-dropzone-inner">
            <div class="awpcp-media-uploader-instructions">
                <span class="awpcp-media-uploader-instructions-title"><?php echo esc_html__( 'Drop files here to upload', 'another-wordpress-classifieds-plugin' ); ?></span>
                <span><?php echo esc_html_x( 'or', "The 'or' after 'Drop files here to upload' in Media Uploader", 'another-wordpress-classifieds-plugin' ); ?></span>
                <a href="#" class="awpcp-media-uploader-browser-button awpcp-button"><?php echo esc_html__( 'Select Files', 'another-wordpress-classifieds-plugin' ); ?></a>
            </div>
            <div class="awpcp-media-uploader-restrictions"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
/* <![CDATA[ */
    window.awpcp = window.awpcp || {};
    window.awpcp.localization = window.awpcp.localization || [];
    window.awpcp.localization.push( ['media-uploader-strings', <?php echo wp_json_encode( $configuration['l10n'] ); ?> ] );
    window.awpcp.options = window.awpcp.options || [];
    window.awpcp.options.push( ['media-uploader-data', <?php echo wp_json_encode( $configuration ); ?> ] );
/* ]]> */
</script>
