<?php
/**
 * @package AWPCP\Templates\FormFields;
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-price-form-field awpcp-form-field awpcp-clearfix awpcp-form-spacer">
    <label class="awpcp-form-field__label" for="<?php echo esc_attr( $html['id'] ); ?>">
        <?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?>
        <?php if ( ! empty( $help_text ) ) : ?>
        &nbsp;<span class="helptext"><?php echo esc_html( $help_text ); ?></span>
        <?php endif; ?>
    </label>
    <?php if ( $show_currency_symbol_on_right ) : ?>
    <label class="awpcp-price-form-field__currency-symbol-container --currency-symbol-on-right"><input class="awpcp-textfield awpcp-price-textfield awpcp-has-value inputbox <?php echo esc_attr( $validators ); ?>" id="<?php echo esc_attr( $html['id'] ); ?>" <?php echo $html['readonly'] ? 'readonly="readonly"' : ''; ?> type="text" size="50" name="<?php echo esc_attr( $html['name'] ); ?>" value="<?php echo awpcp_esc_attr( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" /><span class="awpcp-price-form-field-curency-symbol awpcp-price-form-field-curency-symbol-on-right"><?php echo esc_html( $currency_symbol ); ?></span></label>
    <?php else : ?>
    <label class="awpcp-price-form-field__currency-symbol-container --currency-symbol-on-left"><span class="awpcp-price-form-field-curency-symbol awpcp-price-form-field-curency-symbol-on-left"><?php echo esc_html( $currency_symbol ); ?></span><input class="awpcp-textfield awpcp-price-textfield awpcp-has-value inputbox <?php echo esc_attr( $validators ); ?>" id="<?php echo esc_attr( $html['id'] ); ?>" <?php echo $html['readonly'] ? 'readonly="readonly"' : ''; ?> type="text" size="50" name="<?php echo esc_attr( $html['name'] ); ?>" value="<?php echo awpcp_esc_attr( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" /></label>
    <?php endif; ?>
    <?php awpcp_show_form_error( $html['name'], $errors ); ?>
</div>
