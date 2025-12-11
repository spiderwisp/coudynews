<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 if ( awpcp_get_var( array( 'param' => 'register' ) ) ): ?>
    <?php echo awpcp_print_message( __( 'Please check your email for the password and then return to log in.', 'another-wordpress-classifieds-plugin' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php elseif ( awpcp_get_var( array( 'param' => 'reset' ) ) ): ?>
    <?php echo awpcp_print_message( __( 'Please check your email to reset your password.', 'another-wordpress-classifieds-plugin' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php elseif ( $message ): ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>

<div class="awpcp-login-form">
    <?php wp_login_form( array( 'redirect' => $redirect ) ); ?>

    <p id="nav" class="nav">
    <?php if ( isset($_GET['checkemail']) && in_array( $_GET['checkemail'], array('confirm', 'newpass'), true ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
    <!-- nothing here -->
    <?php elseif ( $show_register_link ) : ?>
    <a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Register', 'another-wordpress-classifieds-plugin' ); ?></a> |
    <a href="<?php echo esc_url( $lost_password_url ); ?>" title="<?php esc_attr_e( 'Password Lost and Found', 'another-wordpress-classifieds-plugin' ); ?>"><?php echo esc_html( __( 'Lost your password?', 'another-wordpress-classifieds-plugin' ) ); ?></a>
    <?php else : ?>
    <a href="<?php echo esc_url( $lost_password_url ); ?>" title="<?php esc_attr_e( 'Password Lost and Found', 'another-wordpress-classifieds-plugin' ); ?>"><?php echo esc_html( __( 'Lost your password?', 'another-wordpress-classifieds-plugin' ) ); ?></a>
    <?php endif; ?>
    </p>
</div>
