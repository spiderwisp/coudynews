<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<p><?php echo esc_html_x( 'You are about to pay for the following items.', 'checkout-payment page', 'another-wordpress-classifieds-plugin' ); ?></p>

<h3><?php echo esc_html_x( 'Payment Terms', 'checkout-payment page', 'another-wordpress-classifieds-plugin' ); ?></h3>

<?php echo $this->render_account_balance_for_transaction( $transaction ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php $this->show_transaction_items( $transaction ); ?>

<?php echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
