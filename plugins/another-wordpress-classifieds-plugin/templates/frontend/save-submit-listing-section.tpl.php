<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="awpcp-submit-listing-section-container">
    <?php if ( $show_preview_section ) : ?>
    <div class="awpcp-save-submit-listing-section awpcp-submit-listing-section">
        <h2 class="awpcp-submit-listing-section-title"><?php echo esc_html( $section_label ); ?></h2>
        <div class="awpcp-submit-listing-section-content">
            <div class="awpcp-save-submit-listing-section__edit_mode">
                <div class="awpcp-preview-listing-button-container">
                    <input class="awpcp-preview-listing-button button" type="submit" value="<?php echo esc_attr( $preview_button_label ); ?>" name="preview" data-refresh-label="<?php echo esc_attr( $refresh_button_label ); ?>" />
                </div>
                <div class="awpcp-listing-preview-container"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <p class="form-submit">
        <input class="button" type="reset" value="<?php echo esc_attr( _x( 'Clear form', 'save submit listing section', 'another-wordpress-classifieds-plugin' ) ); ?>"/>
        <input class="awpcp-submit-listing-button button button-primary" type="submit" value="<?php echo esc_attr( $submit_button_label ); ?>" name="submit"/>
    </p>
</div>
