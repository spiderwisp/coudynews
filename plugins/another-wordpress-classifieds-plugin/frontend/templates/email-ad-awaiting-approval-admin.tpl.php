<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 foreach ($messages as $message): ?>
<?php echo wp_kses_post( $message ); ?>

<?php endforeach; ?>

<?php echo esc_html( awpcp_get_blog_name() ); ?>
<?php
echo esc_url_raw( home_url() );
