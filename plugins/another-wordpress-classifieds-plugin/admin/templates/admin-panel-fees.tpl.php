<?php
/**
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( awpcp_get_option( 'freepay' ) !== '1' ) :
        printf(
            // translators: %1$s is a line break, %2$s is the payment settings page URL
            esc_html__( 'Currently your classifieds are in Free mode. Fee plans are not available or used during free mode.%1$s To change this, visit the %2$s and enable Charge Listing Fee setting.', 'another-wordpress-classifieds-plugin' ),
            '<br/><br/>',
            '<strong><a href="' . esc_url( admin_url( 'admin.php?page=awpcp-admin-settings&g=payment-settings' ) ) . '">' .
                esc_html__( 'Payment Settings page', 'another-wordpress-classifieds-plugin' ) .
            '</a></strong>'
        );
else :
    ?>
<form method="get" action="<?php echo esc_attr( $page->url( array( 'action' => false ) ) ); ?>">
    <?php foreach ( $page->params as $name => $value ) : ?>
    <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
    <?php endforeach ?>

    <?php $url   = $page->url( array( 'awpcp-action' => 'add-fee' ) ); ?>
    <?php $label = __( 'Add Fee Plan', 'another-wordpress-classifieds-plugin' ); ?>
    <a class="button-primary" title="<?php echo esc_attr( $label ); ?>" href="<?php echo esc_attr( $url ); ?>" accesskey="s"><?php echo esc_html( $label ); ?></a>
    <p><?php
        printf(
            // translators: %s is the fee plan sort order and sort direction settings URL
            esc_html__( 'If you wish to change the sorting of your fee plans, you can change the %s.', 'another-wordpress-classifieds-plugin' ),
            sprintf(
                '<a href="%s">%s</a>',
                esc_url( awpcp_get_admin_settings_url( 'payment-settings' ) ),
                esc_html__( 'Fee Plan sort order and sort direction Settings', 'another-wordpress-classifieds-plugin' )
            )
        );
    ?></p>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->display();
    ?>
</form>
<?php endif; ?>
