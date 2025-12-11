<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 foreach ( $messages as $message ): ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<?php foreach ( $errors as $error ): ?>
    <?php echo awpcp_print_error( $error ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<?php $payments->show_account_balance(); ?>

<h3><?php echo esc_html( __( 'Select a Credit Plan', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

<form method="post">

    <?php $payments->show_credit_plans_table( $transaction, true ); ?>

    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php echo esc_attr( __( 'Continue', 'another-wordpress-classifieds-plugin' ) ); ?>" id="submit" name="submit">
        <?php if ( ! is_null( $transaction ) ): ?>
        <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
        <?php endif; ?>
        <input type="hidden" value="select-credit-plan" name="step">
    </p>
</form>
