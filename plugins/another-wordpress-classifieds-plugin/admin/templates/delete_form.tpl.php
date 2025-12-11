<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><tr class="inline-edit-row quick-edit-row alternate inline-editor delete" id="delete-1">
    <td class="colspanchange" colspan="<?php echo esc_attr( $columns ); ?>">
        <form class="awpcp-delete-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        <fieldset><div class="inline-edit-col">
                <label>
                    <span class="title delete-title" style="width: 100%"><?php esc_html_e( 'Are you sure you want to delete this item?', 'another-wordpress-classifieds-plugin' ); ?></span>
                </label>
        </fieldset>

        <p class="submit inline-edit-save">
            <a class="button-secondary cancel alignleft" title="<?php echo esc_attr( __( 'Cancel', 'another-wordpress-classifieds-plugin' ) ); ?>" href="#inline-edit" accesskey="c"><?php esc_html_e( 'Cancel', 'another-wordpress-classifieds-plugin' ); ?></a>
            <a class="button-primary delete alignright" title="<?php echo esc_attr( __( 'Delete', 'another-wordpress-classifieds-plugin' ) ); ?>" href="#inline-edit" accesskey="s"><?php esc_html_e( 'Delete', 'another-wordpress-classifieds-plugin' ); ?></a>
            <img alt="" src="<?php echo esc_url( admin_url( '/images/wpspin_light.gif' ) ); ?>" style="display: none;" class="waiting">
            <input type="hidden" value="<?php echo esc_attr( awpcp_get_var( array( 'param' => 'id' ), 'post' ) ); ?>" name="id">
            <input type="hidden" value="<?php echo esc_attr( awpcp_get_var( array( 'param' => 'action' ), 'post' ) ); ?>" name="action">
            <br class="clear">
        </p>
        </form>
    </td>
</tr>
