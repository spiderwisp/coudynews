<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 echo wp_kses_post( $introduction ); ?>

<?php esc_html_e( 'Listing Details are below:', 'another-wordpress-classifieds-plugin' ); ?>

<?php esc_html_e( 'Title', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $listing_title ); ?>
<?php esc_html_e( 'Posted on', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $start_date ); ?>
<?php esc_html_e( 'Expires on', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $end_date ); ?>

<?php
printf(
    /* translators: %s: URL to renew the ad */
    esc_html__( 'You can renew your Ad visiting this link: %s', 'another-wordpress-classifieds-plugin' ),
    esc_url_raw( $renew_url )
);
?>
