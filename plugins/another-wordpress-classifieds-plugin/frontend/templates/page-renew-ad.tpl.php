<div id="classiwrapper">

    <h2><?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 esc_html_e( 'Renew Ad', 'another-wordpress-classifieds-plugin' ); ?></h2>

<?php if ( $step == 'checkout' ): ?>

    <?php foreach ($header as $part): ?>
    <p><?php echo wp_kses_post( $part ); ?></p>
    <?php endforeach ?>

    <p><?php
    printf(
        // translators: %s is the amount
        esc_html__( 'Please click the payment button below to proceed with Payment for your Ad renewal. You will be asked to pay %s.', 'another-wordpress-classifieds-plugin'),
        wp_kses_post( $amount )
    );
    ?></p>
<?php endif ?>
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
?>
</div>
