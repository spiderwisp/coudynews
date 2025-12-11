<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>

<tr style="" class="inline-edit-row quick-edit-row alternate inline-editor" id="edit-1">
    <td class="colspanchange" colspan="<?php echo esc_attr( $columns ); ?>">
        <form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">

        <?php $label = $action == 'debit' ? __( 'Remove Credit', 'another-wordpress-classifieds-plugin') : __( 'Add Credit', 'another-wordpress-classifieds-plugin'); ?>

        <fieldset class="inline-edit-col-wide">
            <div class="inline-edit-col">
                <h4><?php echo esc_html( $label ); ?></h4>

                <label>
                    <span class="title"><?php esc_html_e( 'Amount', 'another-wordpress-classifieds-plugin' ); ?></span>
                    <span class="input-text-wrap formatted-field"><input type="text" value="" name="amount"></span>
                </label>
            </div>
        </fieldset>

        <p class="submit inline-edit-save">
            <?php $cancel = __( 'Cancel', 'another-wordpress-classifieds-plugin'); ?>
            <a class="button-secondary cancel alignleft" title="<?php echo esc_attr( $cancel ); ?>" href="#inline-edit" accesskey="c"><?php echo esc_html( $cancel ); ?></a>
            <a class="button-primary save alignleft" style="margin-left: 5px;" title="<?php echo esc_attr( $label ); ?>" href="#inline-edit" accesskey="s"><?php echo esc_html( $label ); ?></a>
            <img alt="" src="<?php echo esc_url( admin_url( '/images/wpspin_light.gif' ) ); ?>" style="display: none;" class="waiting">
            <input type="hidden" value="<?php echo esc_attr( $user->ID ); ?>" name="user">
            <input type="hidden" value="<?php echo esc_attr( awpcp_get_var( array( 'param' => 'action' ), 'post' ) ); ?>" name="action">
            <br class="clear">
        </p>

        </form>
    </td>
</tr>
