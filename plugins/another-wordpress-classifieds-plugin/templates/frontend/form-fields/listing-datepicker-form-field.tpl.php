<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><p class="awpcp-form-field awpcp-form-spacer awpcp-clearfix">
    <label class="awpcp-form-field__label" for="<?php echo esc_attr( $html['id'] ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?></label>
    <input class="awpcp-textfield inputbox" id="<?php echo esc_attr( $html['id'] ); ?>" type="text" size="50" datepicker-placeholder value="<?php echo esc_attr( $formatted_value ); ?>" autocomplete="off"/>
    <input type="hidden" name="<?php echo esc_attr( $html['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />
    <?php awpcp_show_form_error( $html['name'], $errors ); ?>
</p>
