<?php
/**
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="metabox-holder">
    <div class="awpcp-credit-system-settings-postbox postbox">
        <?php
        awpcp_html_postbox_handle(
            array(
                'content' => __( 'Credit System Settings', 'another-wordpress-classifieds-plugin' ),
                'echo'    => true,
            )
        );
        ?>
        <div class="inside">
        <form action="<?php echo esc_attr( admin_url( 'options.php' ) ); ?>" method="post">
            <table class="form-table">
            <?php do_settings_fields( 'credit-system-settings', 'credit-system-settings' ); ?>
            </table>
            <?php settings_fields( $option ); ?>
            <input type="hidden" name="group" value="<?php echo 'payment-settings'; ?>" />
            <input type="hidden" name="subgroup" value="<?php echo 'credit-system-settings'; ?>" />
            <p class="submit">
                <input type="submit" value="<?php echo esc_attr( __( 'Save Changes', 'another-wordpress-classifieds-plugin' ) ); ?>" class="button-primary" id="submit" name="submit">
            </p>
        </form>
        </div>
    </div>
</div>

<form method="get" action="<?php echo esc_attr( $page->url( array( 'action' => false ) ) ); ?>">
    <?php foreach ( $page->params as $name => $value ) : ?>
    <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
    <?php endforeach; ?>

    <?php $url = $page->url( array( 'action' => 'add-credit-plan' ) ); ?>
    <?php $label = __( 'Add Credit Plan', 'another-wordpress-classifieds-plugin' ); ?>
    <a class="add button-primary" title="<?php echo esc_attr( $label ); ?>" href="<?php echo esc_attr( $url ); ?>" accesskey="s">
        <?php echo esc_html( $label ); ?>
    </a>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->display();
    ?>
</form>
