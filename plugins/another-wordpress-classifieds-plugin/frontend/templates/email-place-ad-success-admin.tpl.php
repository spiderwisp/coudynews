<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 // translators: %s is the URL to the classified ads section ?>
<?php $message = __( 'A new ad has been submitted. A copy of the details sent to the customer can be found below. You can follow this link %s to go to the Classified Ads section to approve/reject/spam and see the full version of the ad.', 'another-wordpress-classifieds-plugin') ?>
<?php printf( esc_html( $message ), esc_url_raw( $url ) ); ?>

<?php
echo wp_kses_post( $content );
