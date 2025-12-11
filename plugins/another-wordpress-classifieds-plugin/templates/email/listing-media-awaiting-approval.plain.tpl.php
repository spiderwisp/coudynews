<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 esc_html_e( 'Hello', 'another-wordpress-classifieds-plugin' ); ?>,

<?php echo wp_kses_post( $message ); ?>
