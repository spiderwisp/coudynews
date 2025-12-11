<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 esc_html_e( 'Your Ad has been successfully updated. Ad information is shown below.', 'another-wordpress-classifieds-plugin') ?>

<?php if (!empty($message)): ?>
<?php echo wp_kses_post( $message ); ?>
<?php endif ?>

<?php esc_html_e( 'Ad Information', 'another-wordpress-classifieds-plugin' ); ?>

<?php esc_html_e( 'Listing Title', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $listing_title ); ?>
<?php esc_html_e( 'Listing URL', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_url_raw( get_permalink( $ad->ID ) ); ?>
<?php esc_html_e( 'Listing ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $ad->ID ); ?>
<?php esc_html_e( 'Listing Edit Email', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $contact_email ); ?>
<?php if ( get_awpcp_option( 'include-ad-access-key' ) ): ?>
<?php esc_html_e( 'Listing Edit Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $access_key ); ?>
<?php endif; ?>


<?php
printf(
    // translators: %s is the admin email
    esc_html__( 'If you have questions about your listing contact %s. Thank you for your business.', 'another-wordpress-classifieds-plugin' ),
    esc_html( $admin_email )
);
?>

<?php echo esc_html( awpcp_get_blog_name() ); ?>

<?php echo esc_url_raw( home_url() ); ?>
