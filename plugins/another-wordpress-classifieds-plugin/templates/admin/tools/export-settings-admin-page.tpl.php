<?php
/**
 * @package AWPCP\Admin\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><h2 class="nav-tab-wrapper">
    <?php $import_settings_url = add_query_arg( 'awpcp-view', 'import-settings' ); ?>
    <a href="<?php echo esc_url( $import_settings_url ); ?>" class="nav-tab">Import Settings</a>
    <?php $export_settings_url = add_query_arg( 'awpcp-view', 'export-settings' ); ?>
    <a href="<?php echo esc_url( $export_settings_url ); ?>" class="nav-tab nav-tab-active">Export Settings</a>
</h2>

<div class="awpcp-export-settings-form">
    <p><?php echo esc_html__( 'Download a JSON file with the values for all settings.', 'another-wordpress-classifieds-plugin' ); ?></p>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( $nonce_action ); ?>
        <input type="submit" name="export-settings-submit" id="export-settings-submit" class="button button-primary" value="<?php echo esc_html__( 'Download Settings', 'another-wordpress-classifieds-plugin' ); ?>">
        <a class="button" href="<?php echo esc_url( $tools_url ); ?>"><?php echo esc_html__( 'Return to Tools', 'another-wordpress-classifieds-plugin' ); ?></a>
    </form>
</div>
