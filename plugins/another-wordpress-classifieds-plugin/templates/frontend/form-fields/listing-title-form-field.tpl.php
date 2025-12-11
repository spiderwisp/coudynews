<?php
/**
 * @package AWPCP\Templates\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><p class="awpcp-form-field awpcp-clearfix   awpcp-form-spacer awpcp-form-spacer-title">
    <label class="awpcp-form-field__label" for="<?php echo esc_attr( $html['id'] ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?></label>
    <input class="awpcp-textfield awpcp-has-value inputbox" id="<?php echo esc_attr( $html['id'] ); ?>" type="text" size="50" name="<?php echo esc_attr( $html['name'] ); ?>" value="<?php echo awpcp_esc_attr( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" required data-max-characters="<?php echo esc_attr( $characters_allowed ); ?>" data-remaining-characters="<?php echo esc_attr( $remaining_characters ); ?>"/>
    <label for="<?php echo esc_attr( $html['id'] ); ?>" class="characters-left"><span class="characters-left-placeholder"><?php echo esc_html( $remaining_characters_text ); ?></span><?php echo esc_html( $characters_allowed_text ); ?></label>
    <?php awpcp_show_form_error( $html['name'], $errors ); ?>
</p>
