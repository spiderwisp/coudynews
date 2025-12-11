<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><form class="awpcp-category-switcher" method="post" action="<?php echo esc_attr( $action_url ); ?>">
    <?php foreach ( $hidden as $field_name => $value ) : ?>
    <input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
    <?php endforeach; ?>

    <div class="awpcp-category-dropdown-container">
        <?php awpcp_categories_selector()->show( $category_dropdown_params ); ?>
    </div>
</form>
