<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// emails are sent in plain text, trailing whitespace are required for proper formatting
// translators: %s is the contact name
printf( esc_html__( 'Hello %s,', 'another-wordpress-classifieds-plugin'), esc_html( $contact_name ) );
?>

<?php
printf(
    // translators: %1$s is the listing title, %2$s is the listing URL
    esc_html__( 'Your Ad "%1$s" was recently approved by the admin. You should be able to see the Ad published here: %2$s.', 'another-wordpress-classifieds-plugin' ),
    esc_html( $listing_title ),
    esc_url_raw( get_permalink( $listing->ID ) )
);
?>

<?php echo esc_html( awpcp_get_blog_name() ); ?>
<?php
echo esc_url_raw( home_url() );
