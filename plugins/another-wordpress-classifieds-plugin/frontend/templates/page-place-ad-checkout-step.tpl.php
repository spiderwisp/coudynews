<h2><?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 esc_html_e( 'Complete Payment', 'another-wordpress-classifieds-plugin' ); ?></h2>

<?php
    if ( isset( $transaction ) && get_awpcp_option( 'show-create-listing-form-steps' ) ) {
        if ( $transaction->is_doing_checkout() ) {
            awpcp_listing_form_steps_componponent()->show( 'checkout', compact( 'transaction' ) );
        } elseif ( $transaction->is_processing_payment() ) {
            awpcp_listing_form_steps_componponent()->show( 'payment', compact( 'transaction' ) );
        }
    }
?>

<?php foreach ($messages as $message): ?>
    <?php echo awpcp_print_message($message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach ?>

<?php $payments->show_checkout_page( $transaction, $hidden ); ?>
