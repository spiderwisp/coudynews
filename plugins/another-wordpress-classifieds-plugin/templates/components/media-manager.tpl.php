<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><!-- Using template binding as workaround for conflict with imagesLoaded plugin
from Paul Irish. See https://github.com/drodenbaugh/awpcp/issues/979. -->
<div class="awpcp-media-manager" data-bind="{ template: 'awpcp-media-manager-template' }"></div>

<<?php echo 'script'; // Vim struggles to parse the PHP/HTML code inside a SCRIPT tag for some reason. ?> type="text/html" id="awpcp-media-manager-template">
    <div class="awpcp-uploaded-files-group awpcp-uploaded-images" data-bind="if: haveImages">
        <h3 class="awpcp-uploaded-files-group-title"><?php echo esc_html( __( 'Images', 'another-wordpress-classifieds-plugin' ) ); ?></h3>
        <ul class="awpcp-uploaded-files-list" data-bind="foreach: { data: images, as: 'image' }">
            <li data-bind="css: $root.getFileCSSClasses( image ), attr: { id: $root.getFileId( image ) }">
                <div class="awpcp-uploaded-file-thumbnail-container">
                    <img data-bind="attr: { src: thumbnailUrl }">
                    <div class="awpcp-progress-bar-container" data-bind="if: shouldShowProgressBar">
                        <div class="awpcp-progress-bar" data-bind="style: { width: progress() + '%' }"></div>
                    </div>
                </div>
                <ul class="awpcp-uploaded-file-actions">
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-change-status-action">
                        <a href="#enable-image" title="<?php echo esc_attr( __( 'Image currently enabled &mdash; click to disable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: enabled(), click: $root.disableFile">
                            <span class="fa fa-times"></span>
                        </a>
                        <a href="#disable-image" title="<?php echo esc_attr( __( 'Image currently disabled &mdash; click to enable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: ! enabled(), click: $root.enableFile">
                            <span class="fa fa-check"></span>
                        </a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-set-as-primary-action" data-bind="visible: !isPrimary()">
                        <a href="#set-image-as-primary" title="<?php echo esc_attr( __( 'Set as Primary Image', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: !isPrimary(), click: $root.setFileAsPrimary">
                            <span class="fa fa-star"></span>
                        </a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-delete-action"><a title="<?php echo esc_attr( __( 'Click to delete this image', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.deleteFile"><span class="fa fa-trash-alt fa-trash"></span></a></li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-reject-action" data-bind="visible: $root.showAdminActions() && isApproved()">
                        <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'Image currently approved &mdash; click to reject it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.rejectFile, css: { 'awpcp-toggle-on': !isApproved(), 'awpcp-toggle-off': isApproved() }"><span class="fa fa-thumbs-down"></span></a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-approve-action" data-bind="visible: $root.showAdminActions() && !isApproved()">
                            <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'Image currently rejected &mdash; click to approve it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.approveFile, css: { 'awpcp-toggle-on': isApproved(), 'awpcp-toggle-off': !isApproved() }"><span class="fa fa-thumbs-up"></span></a>
                    </li>
                </ul>
                <span class="awpcp-spinner awpcp-spinner-visible awpcp-uploaded-file-spinner" data-bind="visible: isBeingModified"></span>
                <div class="awpcp-uploaded-file-label awpcp-uploaded-file-primary-label" data-bind="visible: isPrimary"><?php echo esc_html( __( 'Primary Image', 'another-wordpress-classifieds-plugin' ) ); ?></div>
                <div class="awpcp-uploaded-file-label awpcp-uploaded-file-rejected-label" data-bind="visible: isRejected"><?php echo esc_html( __( 'Rejected', 'another-wordpress-classifieds-plugin' ) ); ?></div>
            </li>
        </ul>
    </div>
    <div class="awpcp-uploaded-files-group awpcp-uploaded-videos" data-bind="if: haveVideos">
        <h3 class="awpcp-uploaded-files-group-title"><?php echo esc_html( __( 'Videos', 'another-wordpress-classifieds-plugin' ) ); ?></h3>
        <ul class="awpcp-uploaded-files-list clearfix" data-bind="foreach: { data: videos, as: 'video' }">
            <li data-bind="css: $root.getFileCSSClasses( video ), attr: { id: $root.getFileId( video ) }">
                <div class="awpcp-uploaded-file-thumbnail-container">
                    <img data-bind="attr: { src: thumbnailUrl }" width="<?php echo esc_attr( $thumbnails_width ); ?>px">
                    <div class="awpcp-progress-bar-container" data-bind="if: shouldShowProgressBar">
                        <div class="awpcp-progress-bar" data-bind="style: { width: progress() + '%' }"></div>
                    </div>
                </div>
                <ul class="awpcp-uploaded-file-actions clearfix">
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-change-status-action">
                        <a href="#" title="<?php echo esc_attr( __( 'Video currently enabled &mdash; click to disable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: enabled(), click: $root.disableFile">
                            <span class="fa fa-times"></span>
                        </a>
                        <a href="#" title="<?php echo esc_attr( __( 'Video currently disabled &mdash; click to enable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: ! enabled(), click: $root.enableFile">
                            <span class="fa fa-check"></span>
                        </a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-set-as-primary-action" data-bind="visible: !isPrimary()">
                        <a href="#" title="<?php echo esc_attr( __( 'Set as Primary Video', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: !isPrimary(), click: $root.setFileAsPrimary">
                            <span class="fa fa-star"></span>
                        </a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-delete-action"><a title="<?php echo esc_attr( __( 'Click to delete this video', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.deleteFile"><span class="fa fa-trash-alt"></span></a></li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-reject-action" data-bind="visible: isApproved()">
                        <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'Video currently approved &mdash; click to reject it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.rejectFile, css: { 'awpcp-toggle-on': !isApproved(), 'awpcp-toggle-off': isApproved() }"><span class="fa fa-thumbs-down"></span></a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-approve-action" data-bind="visible: !isApproved()">
                            <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'Video currently rejected &mdash; click to approve it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.approveFile, css: { 'awpcp-toggle-on': isApproved(), 'awpcp-toggle-off': !isApproved() }"><span class="fa fa-thumbs-up"></span></a>
                    </li>
                </ul>
                <span class="awpcp-spinner awpcp-spinner-visible awpcp-uploaded-file-spinner" data-bind="visible: isBeingModified"></span>
                <div class="awpcp-uploaded-file-label awpcp-uploaded-file-primary-label" data-bind="visible: isPrimary"><?php echo esc_html( __( 'Primary Video', 'another-wordpress-classifieds-plugin' ) ); ?></div>
                <div class="awpcp-uploaded-file-label awpcp-uploaded-file-rejected-label" data-bind="visible: isRejected"><?php echo esc_html( __( 'Rejected', 'another-wordpress-classifieds-plugin' ) ); ?></div>
            </li>
        </ul>
    </div>

    <div class="awpcp-uploaded-files-group awpcp-uploaded-files" data-bind="if: haveOtherFiles">
        <h3 class="awpcp-uploaded-files-group-title"><?php echo esc_html( __( 'Other Files', 'another-wordpress-classifieds-plugin' ) ); ?></h3>
        <ul class="awpcp-uploaded-files-list" data-bind="foreach: { data: others, as: 'file' }">
            <li data-bind="css: $root.getFileCSSClasses( file ), attr: { id: $root.getFileId( file ) }">
                <div class="awpcp-uploaded-file-thumbnail-container">
                    <a target="_blank">
                        <img data-bind="attr: { src: iconUrl }" />
                        <span data-bind="text: name"></span>
                    </a>
                    <div class="awpcp-progress-bar-container" data-bind="if: shouldShowProgressBar">
                        <div class="awpcp-progress-bar" data-bind="style: { width: progress() + '%' }"></div>
                    </div>
                </div>
                <ul class="awpcp-uploaded-file-actions">
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-change-status-action">
                        <a href="#" title="<?php echo esc_attr( __( 'File currently enabled &mdash; click to disable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: enabled(), click: $root.disableFile">
                            <span class="fa fa-times"></span>
                        </a>
                        <a href="#" title="<?php echo esc_attr( __( 'File currently disabled &mdash; click to enable it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="visible: ! enabled(), click: $root.enableFile">
                            <span class="fa fa-check"></span>
                        </a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-delete-action"><a title="<?php echo esc_attr( __( 'Click to delete this file', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.deleteFile"><span class="fa fa-trash-alt"></span></a></li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-reject-action" data-bind="visible: $root.showAdminActions() && isApproved()">
                        <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'File currently approved &mdash; click to reject it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.rejectFile, css: { 'awpcp-toggle-on': !isApproved(), 'awpcp-toggle-off': isApproved() }"><span class="fa fa-thumbs-down"></span></a>
                    </li>
                    <li class="awpcp-uploaded-file-action awpcp-uploaded-file-approve-action" data-bind="visible: $root.showAdminActions() && !isApproved()">
                            <a class="awpcp-toggle-button" title="<?php echo esc_attr( __( 'File currently rejected &mdash; click to approve it', 'another-wordpress-classifieds-plugin' ) ); ?>" data-bind="click: $root.approveFile, css: { 'awpcp-toggle-on': isApproved(), 'awpcp-toggle-off': !isApproved() }"><span class="fa fa-thumbs-up"></span></a>
                    </li>
                </ul>
                <span class="awpcp-spinner awpcp-spinner-visible awpcp-uploaded-file-spinner" data-bind="visible: isBeingModified"></span>
                <div class="awpcp-uploaded-file-label awpcp-uploaded-file-rejected-label" data-bind="visible: isRejected"><?php echo esc_html( __( 'Rejected', 'another-wordpress-classifieds-plugin' ) ); ?></div>
            </li>
        </ul>
    </div>
</script>

<script type="text/javascript">
/* <![CDATA[ */
    window.awpcp = window.awpcp || {};
    window.awpcp.options = window.awpcp.options || [];
    window.awpcp.options.push( ['media-manager-data', <?php echo wp_json_encode( $options ); ?> ] );
/* ]]> */
</script>
