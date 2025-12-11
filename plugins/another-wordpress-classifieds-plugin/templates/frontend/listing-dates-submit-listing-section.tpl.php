<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-listing-dates-submit-listing-section awpcp-submit-listing-section">
    <h2 class="awpcp-submit-listing-section-title js-handler"><?php echo esc_html_x( 'Dates', 'listing dates submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span></span></h2>
    <div class="awpcp-submit-listing-section-content" data-collapsible awpcp-keep-open>
        <div class="awpcp-listing-dates-submit-listing-section__loading_mode">
            <?php echo esc_html_x( 'Loading...', 'listing dates submit listing section', 'another-wordpress-classifieds-plugin' ); ?>
        </div>
        <div class="awpcp-listing-dates-submit-listing-section__edit_mode">
            <form>
                <p><?php echo esc_html( $description ); ?></p>
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $form_fields;
                ?>
            </form>
        </div>

    </div>
</div>
