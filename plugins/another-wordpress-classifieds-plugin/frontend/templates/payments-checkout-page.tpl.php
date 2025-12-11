<?php
/**
 * @package AWPCP/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $attempts > 0 ) {
    foreach ( $transaction_errors as $error_message ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_print_message( $error_message, array( 'error' ) );
    }
}

?><p><?php echo esc_html_x( 'You are about to pay for the following items. Please review the order and choose a payment method.', 'checkout step', 'another-wordpress-classifieds-plugin' ); ?></p>

<form class="awpcp-checkout-form" method="post">

    <h3><?php echo esc_html_x( 'Payment Terms', 'checkout step', 'another-wordpress-classifieds-plugin' ); ?></h3>

    <?php echo $this->render_account_balance_for_transaction( $transaction ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <?php $this->show_transaction_items( $transaction ); ?>

    <h3><?php echo esc_html_x( 'Payment Method', 'checkout step', 'another-wordpress-classifieds-plugin' ); ?></h3>

    <?php echo $this->render_payment_methods( $transaction ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php esc_html_e( 'Continue', 'another-wordpress-classifieds-plugin' ); ?>" id="submit" name="submit">
        <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
        <input type="hidden" value="<?php echo esc_attr( $attempts + 1 ); ?>" name="attempts">
        <?php foreach ( $hidden as $name => $value ) : ?>
        <input type="hidden" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>">
        <?php endforeach ?>
    </p>

</form>
