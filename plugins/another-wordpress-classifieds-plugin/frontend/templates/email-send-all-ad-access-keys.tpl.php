<?php
/**
 * emails are sent in plain text, all blank lines in templates are required
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo wp_kses_post( $introduction );
?>:

<?php esc_html_e( 'Total ads found sharing your email address', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( count( $ads ) ); ?>

<?php foreach ( $ads as $ad ): ?>
<?php echo esc_html( $listing_renderer->get_listing_title( $ad ) ); ?>
<?php esc_html_e( 'Access Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $listing_renderer->get_access_key( $ad ) ); ?>
<?php esc_html_e( 'Edit Link:', 'another-wordpress-classifieds-plugin' ); ?> <?php echo esc_url_raw( awpcp_get_edit_listing_url_with_access_key( $ad ) ); ?>

<?php endforeach; ?>

<?php echo esc_html_x( 'The edit link will expire after 24 hours. If you use the link after it has expired, a new one will be delivered to your email address automatically.', 'edit link email', 'another-wordpress-classifieds-plugin' ); ?>

<?php echo esc_html( awpcp_get_blog_name() ); ?>
<?php echo esc_url_raw( home_url() ); ?>
