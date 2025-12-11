<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-actions-submit-listing-section awpcp-submit-listing-section">
    <h2 class="awpcp-submit-listing-section-title js-handler"><?php echo esc_html_x( 'Actions', 'actions submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span></span></h2>

    <div class="awpcp-submit-listing-section-content" data-collapsible awpcp-keep-open>
        <div class="awpcp-actions-submit-listing-section__edit_mode">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $listing_actions;
            ?>
        </div>
    </div>
</div>
