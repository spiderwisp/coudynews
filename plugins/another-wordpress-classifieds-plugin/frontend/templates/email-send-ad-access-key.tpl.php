<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


printf(
    // translators: %s is the contact name
    esc_html__( 'Hello %s,', 'another-wordpress-classifieds-plugin' ),
    esc_html( $contact_name )
);
?>

<?php
printf(
    // translators: %1$s is the listing title, %2$s is the contact email
    esc_html__( 'Below you will find the access key for your Ad "%1$s" associated with the email address %2$s.', 'another-wordpress-classifieds-plugin' ),
    esc_html( $listing_title ),
    esc_html( $contact_email )
);
?>

<?php esc_html_e( 'Access Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $access_key ); ?>
<?php esc_html_e( 'Edit Link:', 'another-wordpress-classifieds-plugin' ); ?> <?php echo esc_url_raw( $edit_link ); ?>

<?php echo esc_html_x( 'The edit link will expire after 24 hours. If you use the link after it has expired, a new one will be delivered to your email address automatically.', 'edit link email', 'another-wordpress-classifieds-plugin' ); ?>

<?php echo esc_html( awpcp_get_blog_name() ); ?>
<?php echo esc_url_raw( home_url() ); ?>
