<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><h2><?php esc_html_e( 'Select Payment Term', 'another-wordpress-classifieds-plugin' ); ?></h2>

<?php foreach ($messages as $message): ?>
    <?php echo awpcp_print_message($message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach ?>

<?php foreach ($transaction_errors as $error): ?>
    <?php echo awpcp_print_message($error, array('error')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach ?>

<?php if (!awpcp_current_user_is_admin()): ?>
<?php $payments->show_account_balance(); ?>
<?php endif ?>

<form class="awpcp-order-form" method="post">
    <?php awpcp_show_form_error( 'payment-term', $form_errors ); ?>
    <?php $payment_terms_list->show( null, $payment_terms_list_options ); ?>

    <?php $payments->show_credit_plans_table( $transaction ); ?>

    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php echo esc_attr( __( 'Continue', 'another-wordpress-classifieds-plugin' ) ); ?>" id="submit" name="submit">
        <?php if (!is_null($transaction)): ?>
        <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
        <?php endif; ?>
        <input type="hidden" value="order" name="step">
    </p>
</form>
