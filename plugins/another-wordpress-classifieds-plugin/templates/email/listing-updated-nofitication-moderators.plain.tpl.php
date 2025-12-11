<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


printf(
    // translators: %1$s is the listing title, %2$s is the manage listing URL
    esc_html__( 'The ad "%1$s" was modified. A copy of the details sent to the customer can be found below. You can follow this link %2$s to go to the Manage Ad Listing section to approve/reject/spam and see the full version of the Ad.', 'another-wordpress-classifieds-plugin' ),
    esc_html( $listing_title ),
    esc_url_raw( $manage_listing_url )
);
?>

<?php
echo wp_kses_post( $content );
