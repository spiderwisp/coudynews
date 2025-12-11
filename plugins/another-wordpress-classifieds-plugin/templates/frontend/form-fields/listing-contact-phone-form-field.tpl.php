<?php
/**
 * @package AWPCP\Templates\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><p class="awpcp-form-field awpcp-clearfix   awpcp-form-spacer">
    <label class="awpcp-form-field__label" for="<?php echo esc_attr( $html['id'] ); ?>">
        <?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?>
        <?php if ( ! empty( $help_text ) ) : ?>
        &nbsp;<span class="helptext"><?php echo esc_html( $help_text ); ?></span>
        <?php endif; ?>
    </label>
    <input class="awpcp-textfield awpcp-has-value inputbox <?php echo esc_attr( $validators ); ?>" id="<?php echo esc_attr( $html['id'] ); ?>" <?php echo $html['readonly'] ? 'readonly="readonly"' : ''; ?> type="text" size="50" name="<?php echo esc_attr( $html['name'] ); ?>" value="<?php echo awpcp_esc_attr( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
    <?php awpcp_show_form_error( $html['name'], $errors ); ?>
</p>
