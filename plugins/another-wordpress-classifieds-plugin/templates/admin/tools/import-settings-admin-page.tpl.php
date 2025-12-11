<?php
/**
 * @package AWPCP\Admin\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><h2 class="nav-tab-wrapper">
    <?php $import_settings_url = add_query_arg( 'awpcp-view', 'import-settings' ); ?>
    <a href="<?php echo esc_url( $import_settings_url ); ?>" class="nav-tab nav-tab-active">Import Settings</a>
    <?php $export_settings_url = add_query_arg( 'awpcp-view', 'export-settings' ); ?>
    <a href="<?php echo esc_url( $export_settings_url ); ?>" class="nav-tab">Export Settings</a>
</h2>

<div class="awpcp-import-settings-form">
    <p><?php echo esc_html__( 'If you have a file with plugin settings in a JSON format, you may import those settings uploading the file here.', 'another-wordpress-classifieds-plugin' ); ?></p>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $action_url ); ?>">
        <?php wp_nonce_field( $nonce_action ); ?>
        <label class="screen-reader-text" for="settings_file"><?php echo esc_html__( 'Settings JSON file', 'another-wordpress-classifieds-plugin' ); ?></label>
        <input type="file" id="settings_file" name="settings_file">
        <input type="submit" name="import-settings-submit" id="import-settings-submit" class="button button-primary" value="<?php echo esc_html__( 'Import Now', 'another-wordpress-classifieds-plugin' ); ?>">
        <a class="button" href="<?php echo esc_url( $tools_url ); ?>"><?php echo esc_html__( 'Return to Tools', 'another-wordpress-classifieds-plugin' ); ?></a>
    </form>
</div>
