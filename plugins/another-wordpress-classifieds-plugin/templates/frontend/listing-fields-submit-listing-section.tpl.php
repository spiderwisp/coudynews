<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-listing-fields-submit-listing-section awpcp-submit-listing-section">
    <h2 class="awpcp-submit-listing-section-title js-handler"><?php echo esc_html_x( 'Fields', 'listing fields submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span></span></h2>

    <div class="awpcp-submit-listing-section-content" data-collapsible awpcp-keep-open>
        <div class="awpcp-listing-fields-submit-listing-section__loading_mode">
            <?php echo esc_html_x( 'Loading...', 'listing fields submit listing section', 'another-wordpress-classifieds-plugin' ); ?>
        </div>
        <div class="awpcp-listing-fields-submit-listing-section__edit_mode">
            <form>
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $form_fields;
                ?>
            </form>
        </div>

    </div>

    <script type="text/javascript">
    /* <![CDATA[ */
        window.awpcp = window.awpcp || {};
        window.awpcp.options = window.awpcp.options || [];
        window.awpcp.options.push( ['save_listing_information_nonce', <?php echo wp_json_encode( $nonces['save_listing_information'] ); ?> ] );
        window.awpcp.options.push( ['clear_listing_information_nonce', <?php echo wp_json_encode( $nonces['clear_listing_information'] ); ?> ] );
    /* ]]> */
    </script>
</div>
