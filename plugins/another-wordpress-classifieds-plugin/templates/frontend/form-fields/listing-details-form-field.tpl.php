<?php
/**
 * @package AWPCP\Templates\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><p class="awpcp-form-field awpcp-clearfix awpcp-form-spacer">
    <label class="awpcp-form-field__label" for="<?php echo esc_attr( $html['id'] ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?></label>
    <?php awpcp_show_form_error( $html['name'], $errors ); ?>
    <?php if ( ! empty( $help_text ) ) : ?>
    <label for="<?php echo esc_attr( $html['id'] ); ?>" class="helptext"><?php echo wp_kses_post( $help_text ); ?></label>
    <?php endif; ?>
    <textarea id="<?php echo esc_attr( $html['id'] ); ?>" class="awpcp-textarea awpcp-has-value" <?php
        echo $html['readonly'] ? 'readonly="readonly"' : ''; ?> name="<?php echo esc_attr( $html['name'] ); ?>" rows="10" cols="50" required data-max-characters="<?php echo esc_attr( $characters_allowed ); ?>" data-remaining-characters="<?php echo esc_attr( $remaining_characters ); ?>"><?php
        /* Content alerady escaped if necessary. Do not escape again here! */
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $value;
        ?></textarea>
    <label for="<?php echo esc_attr( $html['id'] ); ?>" class="characters-left"><span class="characters-left-placeholder"><?php echo esc_html( $remaining_characters_text ); ?></span><?php echo esc_html( $characters_allowed_text ); ?></label>
</p>
