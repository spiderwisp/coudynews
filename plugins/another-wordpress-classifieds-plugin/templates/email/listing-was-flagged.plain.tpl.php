<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 esc_html_e( 'Hello', 'another-wordpress-classifieds-plugin' ); ?>,

<?php
$text = __( 'You have pending flagged listings to review at <site-name>. Please check them out here <flagged-listings-url>.', 'another-wordpress-classifieds-plugin' );
$text = str_replace( '<site-name>', $site_name, $text );
$text = str_replace( '<flagged-listings-url>', $flagged_listings_url, $text );

echo esc_html( $text );
