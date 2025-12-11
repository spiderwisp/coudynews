<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


    foreach ($messages as $message) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo awpcp_print_message($message);
    }
?>

<p>
    <?php
    printf(
        // translators: %s is the ad link
        esc_html__( 'You are responding to Ad: %s.', 'another-wordpress-classifieds-plugin'),
        wp_kses_post( $ad_link )
    );
    ?>
</p>

<form class="awpcp-reply-to-ad-form" method="post" name="myform">
    <?php foreach($hidden as $name => $value): ?>
    <input type="hidden" name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value) ?>" />
    <?php endforeach ?>

    <?php $disabled = $ui['disable-sender-fields'] ? 'disabled' : ''; ?>

    <p class="awpcp-form-spacer">
        <label for="awpcp-contact-sender-name"><?php esc_html_e( 'Your name', 'another-wordpress-classifieds-plugin' ); ?></label>
        <input id="awpcp-contact-sender-name" class="awpcp-textfield inputbox required" type="text" name="awpcp_sender_name" value="<?php echo esc_attr( $form['awpcp_sender_name'] ); ?>" <?php echo esc_attr( $disabled ); ?> />
        <?php awpcp_show_form_error( 'awpcp_sender_name', $errors ); ?>
    </p>

    <p class="awpcp-form-spacer">
        <label for="awpcp-contact-sender-email"><?php esc_html_e( 'Your email address', 'another-wordpress-classifieds-plugin' ); ?></label>
        <input id="awpcp-contact-sender-email" class="awpcp-textfield inputbox required email" type="text" name="awpcp_sender_email" value="<?php echo esc_attr( $form['awpcp_sender_email'] ); ?>" <?php echo esc_attr( $disabled ); ?> />
        <?php awpcp_show_form_error( 'awpcp_sender_email', $errors ); ?>
    </p>

    <p class="awpcp-form-spacer">
        <label for="awpcp-contact-message"><?php esc_html_e( 'Your message', 'another-wordpress-classifieds-plugin' ); ?></label>
        <textarea id="awpcp-contact-message" class="awpcp-textarea required" name="awpcp_contact_message" rows="5" cols="90%"><?php echo esc_textarea( $form['awpcp_contact_message'] ); ?></textarea>
        <?php awpcp_show_form_error( 'awpcp_contact_message', $errors ); ?>
    </p>

    <?php if ($ui['captcha']): ?>
    <p class='awpcp-form-spacer'>
        <?php awpcp_create_captcha( get_awpcp_option( 'captcha-provider' ) )->show(); ?>
        <?php awpcp_show_form_error( 'captcha', $errors ); ?>
    </p>
    <?php endif ?>

    <input type="submit" class="button" value="<?php esc_attr_e( 'Continue','another-wordpress-classifieds-plugin' ); ?>" />
</form>
