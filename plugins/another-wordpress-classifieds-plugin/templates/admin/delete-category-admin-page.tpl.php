<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="awpcp-manage-categories-category-form-container postbox-container">
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
                    <?php if ( $category_has_listings && $category_has_children ) : ?>
                    <p><?php echo esc_html__( 'The category has associated listings and children categories. Please select a category to move the listings to and to set as the new parent of the children categories.', 'another-wordpress-classifieds-plugin' ); ?></p>
                    <?php elseif ( $category_has_listings ) : ?>
                    <p><?php echo esc_html__( 'The category has associated listings. Please select a category to move the listings to.', 'another-wordpress-classifieds-plugin' ); ?></p>
                    <?php elseif ( $category_has_children ) : ?>
                    <p><?php echo esc_html__( 'The category has children categories. Please select a category to set as the new parent of the children categories.', 'another-wordpress-classifieds-plugin' ); ?></p>
                    <?php endif; ?>

                    <p>
                    <?php
                    printf(
                        /* translators: %1$s: Cancel button , %2$s: Submit button label */
                        esc_html__( 'Click %1$s to go back to the list of categories or click %2$s to proceed.', 'another-wordpress-classifieds-plugin' ),
                        '<strong>' . esc_html( $form_cancel ) . '</strong>',
                        '<strong>' . esc_html( $form_submit ) . '</strong>'
                    );
                    ?>
                    </p>

                    <form id="awpcp_launch" class="awpcp-delete-categories-category-form" method="post">
                        <input type="hidden" name="awpcp-action" value="<?php echo esc_attr( $form_values['action'] ); ?>" />
                        <input type="hidden" name="category_id" value="<?php esc_attr( $form_values['category_id'] ); ?>" />
                        <input type="hidden" name="offset" value="<?php echo esc_attr( $offset ); ?>" />
                        <input type="hidden" name="results" value="<?php echo esc_attr( $results ); ?>" />
                        <input type="hidden" name="awpcp-del-cat-nonce" value="<?php echo esc_attr( $form_values['nonce'] ); ?>" />

                        <?php if ( $category_has_children || $category_has_listings ) : ?>
                        <div class="awpcp-clearfix clearfix">
                            <div class="awpcp-manage-categories-category-form-field">
                                <label for="awpcp-category-parent-field"><?php echo esc_html__( 'Selected Category', 'another-wordpress-classifieds-plugin' ); ?></label>
                                <select id="awpcp-category-parent-field" name="target_category_id">
                                    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php echo get_categorynameid( $form_values['category_id'], $form_values['target_category_id'] ); ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <p class="submit inline-edit-save">
                            <a href="<?php echo esc_url( awpcp_get_admin_categories_url() ); ?>" class="button"><?php echo esc_html( $form_cancel ); ?></a>
                            <input type="submit" class="button-primary button" name="awpcp-confirm-delete-category" value="<?php echo esc_attr( $form_submit ); ?>" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
