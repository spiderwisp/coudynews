<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php if ( 'confirm' === $action ) : ?>

<p>
    <?php esc_html_e( 'Thank you for using AWPCP. You have arrived at this page by clicking the Uninstall link. If you are certain you wish to uninstall the plugin, please click the button at the bottom to proceed.', 'another-wordpress-classifieds-plugin' ); ?>
</p>
<p><strong><?php esc_html_e( 'PLEASE NOTE:  When you click the button below, ALL your data related to the plugin including your classifieds, images and everything else created by the plugin will be permanently deleted.', 'another-wordpress-classifieds-plugin' ); ?>&nbsp;<em><u><?php echo esc_html_e( 'We cannot recover the data after you click this.', 'another-wordpress-classifieds-plugin' ); ?></u></em></strong>
</p>

<h3><?php echo esc_html( __( 'BEFORE YOU CLICK THE BUTTON BELOW &mdash; read carefully in case you want to extract your data first!', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

<ol>
    <li><?php esc_html_e( 'If you plan to use the data created by the plugin please export the data from your mysql database before clicking the uninstall link.', 'another-wordpress-classifieds-plugin' ); ?></li>
    <li><?php
    printf(
        // translators: %s is the directory name
        esc_html__( 'If you want to keep your user uploaded images, please download %s to your local drive for later use or rename the folder to something else so the uninstaller can bypass it.', 'another-wordpress-classifieds-plugin' ),
        '<code>' . esc_html( $dirname ) . '</code>'
    );
    ?></li>
</ol>

<p>
    <a class="button button-primary" href="<?php echo esc_attr( wp_nonce_url( add_query_arg( array( 'action' => 'uninstall' ), $url ), 'awpcp-uninstall' ) ); ?>">
        <?php esc_html_e( 'Proceed with Uninstalling AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ); ?>
    </a>
</p>

<?php elseif ( 'uninstall' === $action ) : ?>

<h3><?php esc_html_e( 'Almost done... one more step!', 'another-wordpress-classifieds-plugin' ); ?></h3>

<p>
    <a class="button button-primary" href="<?php echo esc_attr( admin_url( 'plugins.php' ) ); ?>">
        <?php esc_html_e( 'Please click here to deactivate plugins.', 'another-wordpress-classifieds-plugin' ); ?>
    </a>
</p>

<?php endif ?>
