<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 echo awpcp_print_message($message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<p><?php esc_html_e( 'You are about to pay for the following items.', 'another-wordpress-classifieds-plugin' ); ?></p>

<h3><?php esc_html_e( 'Payment Terms', 'another-wordpress-classifieds-plugin' ); ?></h3>

<?php $this->show_account_balance(); ?>

<?php $this->show_transaction_items( $transaction ); ?>

<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $output;
?>
