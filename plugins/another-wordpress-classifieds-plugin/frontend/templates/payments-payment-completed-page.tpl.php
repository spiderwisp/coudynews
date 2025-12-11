<form id="awpcp-payment-completed-form" method="post" action=<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 echo esc_attr($action) ?>>
    <h3><?php esc_html_e( 'Transaction Details', 'another-wordpress-classifieds-plugin' ); ?></h3>

    <?php $this->show_transaction_items( $transaction ); ?>
    <h3><?php echo esc_html( $title ); ?></h3>

    <p><?php echo esc_html( $text ); ?></p>

    <?php $this->show_transaction_errors( $transaction ); ?>

    <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
    <?php foreach ($hidden as $name => $value): ?>
    <input type="hidden" value="<?php echo esc_attr($value) ?>" name="<?php echo esc_attr($name) ?>">
    <?php endforeach ?>

    <?php if ($success): ?>
    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php esc_attr_e( 'Continue', 'another-wordpress-classifieds-plugin' ); ?>" id="submit" name="submit">
    </p>
    <?php endif ?>
</form>
