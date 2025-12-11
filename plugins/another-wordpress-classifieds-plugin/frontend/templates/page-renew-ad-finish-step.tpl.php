<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 if ( ! empty( $title ) ): ?>
<h2><?php echo esc_html( $title ); ?></h2>
<?php else: ?>
<h2><?php esc_html_e( 'Your Ad has been renewed', 'another-wordpress-classifieds-plugin' ); ?></h2>
<?php endif; ?>

<?php echo wp_kses_post( $response ); ?>
