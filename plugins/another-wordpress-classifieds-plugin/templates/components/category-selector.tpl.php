<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php if ( $label ) : ?>
<label class="awpcp-category-dropdown-label" for="awpcp-category-dropdown-<?php echo esc_attr( $hash ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? '<span class="required">*</span>' : ''; ?></label>
<?php endif; ?>

<?php if ( $use_multiple_dropdowns ) : ?>

    <p class="awpcp-multiple-category-dropdown-container">
        <input id="awpcp-multiple-category-dropdown-<?php echo esc_attr( $hash ); ?>" type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( wp_json_encode( $selected ) ); ?>" />
        <select class="awpcp-multiple-category-dropdown awpcp-dropdown <?php echo $required ? 'required' : ''; ?>" data-hash="<?php echo esc_attr( $hash ); ?>" id="awpcp-multiple-category-dropdown" target="<?php echo esc_attr( $hash ); ?>">
        <option class="default" value=""><?php echo esc_html__( 'Select a Category', 'another-wordpress-classifieds-plugin' ); ?></option>
        <?php foreach ( $categories_hierarchy['root'] as $category ) : ?>
            <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
        <?php endforeach; ?>
    </select>
    </p>

<?php else : ?>

<select id="awpcp-category-dropdown-<?php echo esc_attr( $hash ); ?>" class="awpcp-category-dropdown awpcp-dropdown <?php echo $required ? 'required' : ''; ?>" name="<?php echo esc_attr( $name ); ?>" data-hash="<?php echo esc_attr( $hash ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"<?php echo $multiple ? ' multiple="multiple"' : ''; ?><?php echo $auto ? ' data-auto="auto"' : ''; ?> style="width: 100%">
    <?php if ( ! $multiple ) : ?>
    <option class="awpcp-dropdown-placeholder"><?php echo esc_html( $placeholder ); ?></option>
    <?php
    endif;

    echo awpcp_render_categories_dropdown_options( $categories_hierarchy['root'], $categories_hierarchy, $selected ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</select>

<?php endif; ?>

<script type="text/javascript">var categories_<?php echo esc_attr( $hash ); ?> = <?php echo wp_json_encode( $javascript ); ?>;</script>
