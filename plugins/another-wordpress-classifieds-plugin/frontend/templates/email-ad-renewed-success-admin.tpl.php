<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// emails are sent in plain text, blank lines in templates are required
esc_html_e( 'An ad has been renewed. A copy of the details sent to the customer can be found below:', 'another-wordpress-classifieds-plugin' );
?>

<?php
echo wp_kses_post( $body );
