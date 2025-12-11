<?php
/**
 * @package AWPCP\Templates\Admin\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-email-template-setting">
    <p>
        <label for="<?php echo esc_attr( $setting_id ); ?>-subject"><?php esc_html_e( 'Subject', 'another-wordpress-classifieds-plugin' ); ?></label>
        <input id="<?php echo esc_attr( $setting_id ); ?>-subject" class="regular-text" type="text" name="<?php echo esc_attr( $subject_field_name ); ?>" value="<?php echo esc_attr( $subject ); ?>"/>
    </p>

    <p>
        <label for="<?php echo esc_attr( $setting_id ); ?>-body"><?php esc_html_e( 'Body', 'another-wordpress-classifieds-plugin' ); ?></label>
        <textarea id="<?php echo esc_attr( $setting_id ); ?>-body" rows="20" name="<?php echo esc_attr( $body_field_name ); ?>"><?php echo esc_html( $body ); ?></textarea>
    </p>

    <input type="hidden" name="<?php echo esc_attr( $version_field_name ); ?>" value="<?php echo esc_attr( $version ); ?>" />

    <?php if ( ! empty( $placeholders ) ) : ?>
    <p><?php esc_html_e( 'The following is a list of placeholders you can use to personalise the message:', 'another-wordpress-classifieds-plugin' ); ?></p>

    <dl>
        <?php foreach ( $placeholders as $name => $description ) : ?>
        <dt><code>{<?php echo esc_html( $name ); ?>}</code></dt>
        <dd><?php echo esc_html( $description ); ?></dd>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>
</div>
