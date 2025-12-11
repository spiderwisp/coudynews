<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>

<tr class="inline-edit-row quick-edit-row alternate inline-editor delete" id="delete-1">
    <td class="colspanchange" colspan="<?php echo esc_attr( $columns ); ?>">
        <form action="" method="post">
        <fieldset class="inline-edit-col-left"><div class="inline-edit-col">
                <label>
                    <span class="title delete-title" style="width: 100%">
                        <?php esc_html_e( 'Are you sure you want to delete this item?', 'another-wordpress-classifieds-plugin' ); ?>
                    </span>
                </label>
        </fieldset>

        <p class="submit inline-edit-save">
            <?php
            $url = $this->page_url(
                array(
                    'action' => 'delete',
                    'id'     => awpcp_get_var(
                        array( 'param' => 'id', 'sanitize' => 'absint' ),
                        'post'
                    ),
                )
            );
            $cancel = __( 'Cancel', 'another-wordpress-classifieds-plugin');
            $delete = __( 'Delete', 'another-wordpress-classifieds-plugin');
            ?>
            <a class="button-secondary cancel alignleft" title="<?php echo esc_attr( $cancel ); ?>" href="#inline-edit" accesskey="c"><?php echo esc_html( $cancel ); ?></a>
            <a class="button-primary alignright" title="<?php echo esc_attr( $delete ); ?>" href="<?php echo esc_url( $url ); ?>" accesskey="s"><?php echo esc_html( $delete ); ?></a>
            <br class="clear">
        </p>
        </form>
    </td>
</tr>
