<?php
/**
 * Onboarding Wizard Page.
 *
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

?>
<div id="awpcp-onboarding-wizard-page" class="awpcp-hide-js" data-current-step="consent-tracking">
    <div id="awpcp-onboarding-container">
        <ul id="awpcp-onboarding-rootline" class="awpcp-onboarding-rootline">
            <li class="awpcp-onboarding-rootline-item" data-step="consent-tracking">
                <?php awpcp_inline_svg( 'icon-check.svg' ); ?>
            </li>
            <li class="awpcp-onboarding-rootline-item" data-step="success">
                <?php awpcp_inline_svg( 'icon-check.svg' ); ?>
            </li>
        </ul>

        <?php
        foreach ( $step_parts as $step => $file ) {
            require AWPCP_DIR . '/templates/admin/onboarding-wizard/' . $file;
        }
        ?>

        <a id="awpcp-onboarding-return-dashboard" href="<?php echo esc_url( admin_url( 'admin.php?page=awpcp.php' ) ); ?>">
            <?php esc_html_e( 'Exit Onboarding', 'another-wordpress-classifieds-plugin' ); ?>
        </a>
    </div>
</div>
