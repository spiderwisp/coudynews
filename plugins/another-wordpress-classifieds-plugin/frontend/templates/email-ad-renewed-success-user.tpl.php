<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 // emails are sent in plain text, blank lines in templates are required ?>
<?php echo wp_kses_post( $introduction ); ?>


<?php esc_html_e( 'Listing Title', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $listing_title ); ?>

<?php esc_html_e( 'Listing URL', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_url_raw( get_permalink( $ad->ID ) ); ?>

<?php esc_html_e( 'Listing ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $ad->ID ); ?>

<?php esc_html_e( 'Listing Edit Email', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $contact_email ); ?>

<?php if ( get_awpcp_option( 'include-ad-access-key' ) ): ?>
<?php esc_html_e( 'Listing Edit Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $access_key ); ?>
<?php endif; ?>

<?php esc_html_e( 'Listing End Date', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $end_date ); ?>

<?php
    // translators: %s is the admin email
    $text = __( 'If you have questions about your listing, please contact %s.', 'another-wordpress-classifieds-plugin' );
    echo esc_html( sprintf( $text, awpcp_admin_recipient_email_address() ) );
?>


<?php esc_html_e( 'Thank you for your business', 'another-wordpress-classifieds-plugin' ); ?>


<?php echo esc_url_raw( home_url() ); ?>
