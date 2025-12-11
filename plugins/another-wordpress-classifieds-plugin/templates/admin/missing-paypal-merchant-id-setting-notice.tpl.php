<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="missing-paypal-merchant-id-setting-notice" class="notice notice-warning is-dismissible awpcp-notice">
    <?php
    awpcp_html_admin_second_level_heading(
        array(
            'content' => esc_html__( "What's your PayPal Merchant ID?", 'another-wordpress-classifieds-plugin' ),
            'echo'    => true,
        )
    );
    ?>
    <p><?php esc_html_e( 'In order to verify payments made through PayPal, AWP Classifieds Plugin needs to know your PayPal Merchant ID.', 'another-wordpress-classifieds-plugin' ); ?></p>
    <p><?php
        printf(
            // translators: %1$s is the PayPal settings URL, %2$s is the opening link tag for payment settings, %3$s is the closing link tag
            esc_html__( 'Go to %1$s to obtain the Merchant ID and then go to the %2$sPayment Settings%3$s page to enter the value.', 'another-wordpress-classifieds-plugin' ),
            '<a href="https://www.paypal.com/myaccount/settings/" target="_blank">https://www.paypal.com/myaccount/settings/</a>',
            '<a href="' . esc_url( awpcp_get_admin_settings_url( 'payment-settings' ) ) . '">',
            '</a>'
        )
    ?></p>
</div>
