<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 echo wp_kses_post( get_awpcp_option( 'listingaddedbody' ) ); ?>

<?php esc_html_e( 'Listing Title', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $listing_title ); ?>

<?php esc_html_e( 'Listing URL', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_url_raw( get_permalink( $ad->ID ) ); ?>

<?php esc_html_e( 'Listing ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $ad->ID ); ?>

<?php esc_html_e( 'Listing Edit Email', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $contact_email ); ?>

<?php if ( $include_listing_access_key ): ?>
<?php esc_html_e( 'Listing Edit Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $access_key ); ?>

<?php endif; ?>
<?php if ($transaction): ?>
<?php // translators: %s is the blog name ?>
<?php printf( esc_html__( '%s Transaction', 'another-wordpress-classifieds-plugin' ), esc_html( $blog_name ) ); ?>: <?php echo esc_html( $transaction->id ); ?>

<?php   if ($transaction->get('txn-id')): ?>
<?php esc_html_e( 'Payment Transaction', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $transaction->get( 'txn-id' ) ); ?>

<?php   endif ?>
<?php   if ( $show_total_amount ): ?>
<?php echo esc_html( __( 'Order Total', 'another-wordpress-classifieds-plugin' ) ); ?> (<?php echo esc_html( $currency_code ); ?>): <?php echo esc_html( awpcp_format_money( $total_amount ) ); ?>

<?php   endif; ?>
<?php   if ( $show_total_credits ): ?>
<?php echo esc_html( __( 'Order Total (credits)', 'another-wordpress-classifieds-plugin' ) ); ?>: <?php echo esc_html( $total_credits ); ?>

<?php   endif; ?>

<?php endif ?>
<?php if ( $include_edit_listing_url ): ?>

<?php esc_html_e( 'The next link will take you to a page where you can edit the listing:', 'another-wordpress-classifieds-plugin' ); ?>

<?php echo esc_url_raw( awpcp_get_edit_listing_url( $ad, 'email' ) ); ?>

<?php endif; ?>
<?php if (!empty($message)): ?>
<?php esc_html_e( 'Additional Details', 'another-wordpress-classifieds-plugin' ); ?>

<?php echo wp_kses_post( $message ); ?>

<?php endif ?>
<?php
printf(
    // translators: %s is the admin email
    esc_html__( 'If you have questions about your listing contact %s. Thank you for your business.', 'another-wordpress-classifieds-plugin' ),
    esc_html( $admin_email )
);
?>

<?php echo esc_html( $blog_name ); ?>

<?php echo esc_url_raw( home_url() ); ?>
