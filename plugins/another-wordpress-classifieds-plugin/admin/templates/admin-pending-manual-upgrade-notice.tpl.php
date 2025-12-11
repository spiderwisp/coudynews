<?php
/**
 * Template used to build the content of the admin notice shown when the plugin
 * has pending manual upgrade tasks.
 *
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="update-nag awpcp-update-nag clearfix">
    <div>
        <span class="awpcp-update-nag-title">
            <?php
            echo wp_kses_post(
                awpcp_admin_page_title( esc_html__( 'Manual Upgrade Required', 'another-wordpress-classifieds-plugin' ) )
            );
            ?>
        </span>

        <?php echo wp_kses_post( $message ); ?>

        <p>
            <?php
            printf(
                '<a class="button button-primary" href="%s">%s</a>',
                esc_url( awpcp_get_admin_upgrade_url() ),
                esc_html__( 'Upgrade', 'another-wordpress-classifieds-plugin' )
            );
            ?>
        </p>
    </div>
</div>
