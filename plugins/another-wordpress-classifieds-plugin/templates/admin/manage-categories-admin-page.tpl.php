<?php
/**
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-manage-categories-category-form-container postbox-container">
    <div class="metabox-holder">
        <div class="metabox-sortables">
            <div class="postbox">
                <?php
                    awpcp_html_admin_third_level_heading(
                        array(
                            'content'    => esc_html( $form_title ),
                            'attributes' => array( 'class' => 'hndle' ),
                            'echo'       => true,
                        )
                    );
                ?>
                <div class="inside">
                    <div class="form-wrap">
                    <form id="awpcp_launch" class="awpcp-manage-categories-category-form" method="post">
                        <input type="hidden" name="awpcp-action" value="<?php echo esc_attr( $form_values['action'] ); ?>" />
                        <input type="hidden" name="category_id" value="<?php echo esc_attr( $form_values['category_id'] ); ?>" />
                        <input type="hidden" name="aeaction" value="<?php echo esc_attr( $form_values['action'] ); ?>" />
                        <input type="hidden" name="awpcp-cat-form-nonce" value="<?php echo esc_attr( $form_values['nonce'] ); ?>" />
                        <input type="hidden" name="offset" value="<?php echo esc_attr( $offset ); ?>" />
                        <input type="hidden" name="results" value="<?php echo esc_attr( $results ); ?>" />

                        <div class="l-awpcp-row">
                            <div class="l-awpcp-column-50">
                                <div class="form-field form-required">
                                    <label for="m-awpcp-category-form__name-field"><?php esc_html_e( 'Name', 'another-wordpress-classifieds-plugin' ); ?></label>
                                    <input name="category_name" id="m-awpcp-category-form__name-field" type="text" value="<?php echo esc_attr( $form_values['category_name'] ); ?>" size="40" aria-required="true">
                                </div>

                                <div class="form-field">
                                    <label for="m-awpcp-category-form__description-field"><?php esc_html_e( 'Description', 'another-wordpress-classifieds-plugin' ); ?></label>
                                    <textarea id="m-awpcp-category-form__description-field" type="text" name="category_description" rows="4"><?php echo esc_html( $form_values['category_description'] ); ?></textarea>
                                </div>
                            </div>

                            <div class="l-awpcp-column-50">
                                <div class="form-field">
                                    <label for="m-awpcp-category-form__parent-field"><?php esc_html_e( 'Parent Category', 'another-wordpress-classifieds-plugin' ); ?></label>
                                    <?php
                                    $parent_category_dropdown_args['id'] = 'm-awpcp-category-form__parent-field';

                                    wp_dropdown_categories( $parent_category_dropdown_args );
                                    ?>
                                </div>

                                <div class="form-field">
                                    <label for="m-awpcp-category-form__order-field"><?php esc_html_e( 'Category list order', 'another-wordpress-classifieds-plugin' ); ?></label>
                                    <input id="m-awpcp-category-form__order-field" type="text" name="category_order" value="<?php echo esc_attr( $form_values['category_order'] ); ?>" size="20"/>
                                </div>
                            </div>
                        </div>

                        <?php // TODO: allow other sections to enter content before the submit button ?>
                        <?php // echo $promptmovetocat; ?>

                        <p class="submit inline-edit-save">
                            <input type="submit" class="button-primary button" name="createeditadcategory" value="<?php echo esc_attr( $form_submit ); ?>" />
                        </p>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="awpcp-manage-categories-icons-meaning">
    <ul>
        <li class="awpcp-manage-categories-icons-meaning-header">
            <span><?php esc_html_e( 'Icon Meanings:', 'another-wordpress-classifieds-plugin' ); ?></span>
        </li>
    <?php foreach ( $icons as $icon ) : ?>
        <li class="awpcp-manage-categories-icons-meaning-icon">
            <i class="<?php echo esc_attr( $icon['class'] ); ?>"></i>
            <span><?php echo esc_html( $icon['label'] ); ?></span>
        </li>
    <?php endforeach; ?>
    </ul>
</div>

<form id="mycats" class="awpcp-clearboth" name="mycats" method="post">
    <p>
        <label for="m-awpcp-category-bulk-actions-form__target-category-field"><?php esc_html_e( 'Move categories or ads under:', 'another-wordpress-classifieds-plugin' ); ?></label>
        <?php
        $target_category_dropdown_args['id'] = 'm-awpcp-category-bulk-actions-form__target-category-field';

        wp_dropdown_categories( $target_category_dropdown_args );
        ?>
        <br/>
        <?php esc_html_e( 'Delete categories should do this with existing ads:', 'another-wordpress-classifieds-plugin' ); ?>
        <label><input type="radio" name="movedeleteads" value="1" checked='checked' ><?php esc_html_e( 'Move ads to new category', 'another-wordpress-classifieds-plugin' ); ?></label>
        <label><input type="radio" name="movedeleteads" value="2" ><?php esc_html_e( 'Delete ads too', 'another-wordpress-classifieds-plugin' ); ?></label>
    </p>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $pager1;
    ?>

    <p>
        <input type="hidden" name="awpcp-multiple-form-nonce" value="<?php echo esc_attr( $multi_form_nonce ); ?>">
        <input type="submit" name="awpcp-move-multiple-categories" class="button" value="<?php esc_attr_e( 'Move Selected Categories', 'another-wordpress-classifieds-plugin' ); ?>"/>
        <input type="submit" name="awpcp-delete-multiple-categories" class="button" value="<?php esc_attr_e( 'Delete Selected Categories', 'another-wordpress-classifieds-plugin' ); ?>"/>
    </p>

    <style>
        table.listcatsh { width: 100%; padding: 0px; border: none; border: 1px solid #dddddd;}
        table.listcatsh td { font-size: 12px; border: none; background-color: #F4F4F4;
        vertical-align: middle; font-weight: bold; }
        table.listcatsh tr.special td { border-bottom: 1px solid #ff0000;  }
        table.listcatsc { width: 100%; padding: 0px; border: none; border: 1px solid #dddddd;}
        table.listcatsc td { width:33%;border: none;
        vertical-align: middle; padding: 5px; font-weight: normal; }
        table.listcatsc tr.special td { border-bottom: 1px solid #ff0000;  }
    </style>

    <table class="listcatsh">
        <tr>
            <td style="width:4%;padding:5px;text-align:center">
                <label class="screen-reader-text" for="awpcp-category-select-all"><?php esc_html_e( 'Select all categories', 'another-wordpress-classifieds-plugin' ); ?></label>
                <input id="awpcp-category-select-all" type="checkbox" onclick="CheckAll()" />
            </td>
            <td style="width:15%; text-align: center;"><?php esc_html_e( 'Category ID', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="width:33%;padding:5px;">
                <?php esc_html_e( 'Category Name (Total Ads)', 'another-wordpress-classifieds-plugin' ); ?>
            </td>
            <td style="width:28%;padding:5px;"><?php esc_html_e( 'Parent', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="width:5%;padding:5px;"><?php esc_html_e( 'Order', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="width:15%;padding:5px;;"><?php esc_html_e( 'Action', 'another-wordpress-classifieds-plugin' ); ?></td>
        </tr>

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo smart_table2( $items, 1, '', '', false );
        ?>

        <tr>
            <td style="padding:5px"></td>
            <td style="width:10%; text-align: center;"><?php esc_html_e( 'Category ID', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="padding:5px;"><?php esc_html_e( 'Category Name (Total Ads)', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="padding:5px;"><?php esc_html_e( 'Parent', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="padding:5px;"><?php esc_html_e( 'Order', 'another-wordpress-classifieds-plugin' ); ?></td>
            <td style="padding:5px;"><?php esc_html_e( 'Action', 'another-wordpress-classifieds-plugin' ); ?></td>
        </tr>
    </table>
</form>
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $pager2;
?>
