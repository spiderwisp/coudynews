<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="postbox">
    <div class="inside">
        <ul class="awpcp-admin-manage-links">
            <li class="label"><?php esc_html_e( 'Manage Links', 'another-wordpress-classifieds-plugin' ); ?>:</li>
            <li><a href="<?php echo esc_attr( $urls['view-listing'] ); ?>"><?php esc_html_e( 'View Listing', 'another-wordpress-classifieds-plugin' ); ?></a></li>
            <li><a href="<?php echo esc_attr( $urls['listings'] ); ?>"><?php esc_html_e( 'Return to Listings', 'another-wordpress-classifieds-plugin' ); ?></a></li>
        </ul>
    </div>
</div>

<div class="postbox">
    <div class="inside">

    <?php include( AWPCP_DIR . '/templates/components/media-center.tpl.php' ); ?>

    </div>
</div>
