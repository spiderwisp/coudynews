<?php
/**
 * Onboarding Wizard - Never miss an important update step.
 *
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

?>
<section id="awpcp-onboarding-consent-tracking-step" class="awpcp-onboarding-step awpcp-card-box awpcp-current" data-step-name="<?php echo esc_attr( $step ); ?>">
    <div class="awpcp-card-box-header"><?php awpcp_inline_svg( 'logo.svg' ); ?></div>

    <div class="awpcp-card-box-content">
        <h2 class="awpcp-card-box-title"><?php esc_html_e( 'Never miss an important update', 'another-wordpress-classifieds-plugin' ); ?></h2>

        <p class="awpcp-card-box-text">
            <?php esc_html_e( 'Get key updates, tips, and occasional offers to enhance your WordPress experience. Opt in and help us improve compatibility with your site!', 'another-wordpress-classifieds-plugin' ); ?>
        </p>
    </div>

    <div class="awpcp-card-box-footer">
        <a href="#" class="button button-secondary awpcp-onboarding-skip-step">
            <?php esc_html_e( 'Skip', 'another-wordpress-classifieds-plugin' ); ?>
        </a>

        <a href="#" id="awpcp-onboarding-consent-tracking" class="button button-primary">
            <?php
            esc_html_e( 'Allow & Continue', 'another-wordpress-classifieds-plugin' );
            awpcp_inline_svg( 'arrow-right-icon.svg' );
            ?>
        </a>
    </div>

    <div class="awpcp-card-box-permission">
        <span class="awpcp-collapsible">
            <?php
            esc_html_e( 'Allow AWP Classifieds Plugin to', 'another-wordpress-classifieds-plugin' );
            awpcp_inline_svg( 'arrow-bottom-icon.svg' );
            ?>
        </span>

        <div class="awpcp-collapsible-content hidden">
            <div class="awpcp-card-box-permission-item">
                <span><?php awpcp_inline_svg( 'user-icon.svg' ); ?></span>

                <div class="awpcp-card-box-permission-item-content">
                    <h4><?php esc_html_e( 'View Basic Profile Info', 'another-wordpress-classifieds-plugin' ); ?></h4>
                    <span><?php esc_html_e( 'Your WordPress user’s: first & last name and email address', 'another-wordpress-classifieds-plugin' ); ?></span>
                </div>
            </div>

            <div class="awpcp-card-box-permission-item">
                <span><?php awpcp_inline_svg( 'layout-icon.svg' ); ?></span>

                <div class="awpcp-card-box-permission-item-content">
                    <h4><?php esc_html_e( 'View Basic Website Info', 'another-wordpress-classifieds-plugin' ); ?></h4>
                    <span><?php esc_html_e( 'Homepage URL & title, WP & PHP versions, site language', 'another-wordpress-classifieds-plugin' ); ?></span>
                </div>
            </div>

            <div class="awpcp-card-box-permission-item">
                <span><?php awpcp_inline_svg( 'puzzle-icon.svg' ); ?></span>

                <div class="awpcp-card-box-permission-item-content">
                    <h4><?php esc_html_e( 'View Basic Plugin Info', 'another-wordpress-classifieds-plugin' ); ?></h4>
                    <span><?php esc_html_e( 'Current plugin & SDK versions, and if active or uninstalled', 'another-wordpress-classifieds-plugin' ); ?></span>
                </div>
            </div>

            <div class="awpcp-card-box-permission-item">
                <span><?php awpcp_inline_svg( 'field-colors-style-icon.svg' ); ?></span>

                <div class="awpcp-card-box-permission-item-content">
                    <h4><?php esc_html_e( 'View Plugins & Themes List', 'another-wordpress-classifieds-plugin' ); ?></h4>
                    <span><?php esc_html_e( 'Names, slugs, versions, and if active or not', 'another-wordpress-classifieds-plugin' ); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
