<h3><?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 $payments->show_payment_completed_page_title( $transaction ); ?></h3>

<?php foreach ($messages as $message): ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<?php $payments->show_payment_completed_page( $transaction, $url, $hidden ); ?>
