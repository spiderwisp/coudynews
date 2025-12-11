<?php
/**
 * Onboarding Wizard - Success (You're All Set!) Step.
 *
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

?>
<section id="awpcp-onboarding-success-step" class="awpcp-onboarding-step awpcp-card-box hidden" data-step-name="<?php echo esc_attr( $step ); ?>">
    <div class="awpcp-card-box-header"><?php awpcp_inline_svg( 'logo.svg' ); ?></div>

    <div class="awpcp-card-box-content">
        <h2 class="awpcp-card-box-title"><?php esc_html_e( 'You\'re All Set!', 'another-wordpress-classifieds-plugin' ); ?></h2>

        <p class="awpcp-card-box-text">
            <?php esc_html_e( 'Congratulations on completing the onboarding process! We hope you enjoy using AWP Classifieds Plugin.', 'another-wordpress-classifieds-plugin' ); ?>
        </p>
    </div>

    <div class="awpcp-card-box-footer">
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=awpcp_listing' ) ); ?>" class="button button-secondary">
            <?php esc_html_e( 'Create a Classified Ad', 'another-wordpress-classifieds-plugin' ); ?>
        </a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=awpcp.php' ) ); ?>" class="button button-primary">
            <?php esc_html_e( 'Go to Dashboard', 'another-wordpress-classifieds-plugin' ); ?>
        </a>
    </div>
</section>
