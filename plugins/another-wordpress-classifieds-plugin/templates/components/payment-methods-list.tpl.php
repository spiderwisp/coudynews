<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<ul class="awpcp-payment-methods-list" data-breakpoints-class-prefix="awpcp-payment-methods-list" data-breakpoints='{"480": [0,480]}'>
<?php if ( empty( $payment_methods ) ): ?>
    <li><?php esc_html_e( 'No payment methods available.', 'another-wordpress-classifieds-plugin') ?></li>
<?php endif ?>
<?php foreach ( $payment_methods as $payment_method ): ?>
    <li class="awpcp-payment-methods-list-payment-method">
        <label>
            <input class="" type="radio" value="<?php echo esc_attr( $payment_method->slug ); ?>" name="payment_method" <?php checked( $payment_method->slug, $selected_payment_method ); ?>>
            <?php if ($payment_method->icon): ?>
            <img alt="<?php echo esc_attr( $payment_method->name ); ?>" src="<?php echo esc_attr( $payment_method->icon ); ?>">
            <?php else: ?>
            <span><?php echo esc_html( $payment_method->name ); ?></span>
            <?php endif ?>
        </label>
    </li>
<?php endforeach; ?>
</ul>
