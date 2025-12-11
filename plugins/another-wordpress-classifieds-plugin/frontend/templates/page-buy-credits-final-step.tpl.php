<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 foreach ( $messages as $message ): ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<p><?php $payments->show_account_balance(); ?></p>

<p><?php esc_html_e( 'The credit in your account can be used to pay for posting your Ads. You can add more credit when posting a new Ad or using the "Add Credit" button below.', 'another-wordpress-classifieds-plugin' ); ?></p>

<form method="get" action="<?php echo esc_attr( $action_url ); ?>">
    <p class="awpcp-form-submit">
        <input id="submit" class="button" type="submit" value="<?php echo esc_attr( __( 'Add Credit', 'another-wordpress-classifieds-plugin' ) ); ?>">
    </p>
</form>
