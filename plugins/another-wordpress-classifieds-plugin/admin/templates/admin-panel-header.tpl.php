<?php
/**
 * Admin panel header template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div id="<?php echo esc_attr( $page_id ); ?>" class="<?php echo esc_attr( isset( $page_class ) ? $page_class : $page_id ); ?> wrap">
    <div class="page-content">
        <?php if ( version_compare( get_bloginfo('version'), '4.2.4', '<=' ) ): ?>
        <h2 class="awpcp-page-header"><?php echo esc_html( $page_title ); ?></h2>
        <?php else: ?>
        <h1 class="awpcp-page-header"><?php echo esc_html( $page_title ); ?></h1>
        <?php endif; ?>

        <?php
        $show_sidebar = isset( $show_sidebar ) ? $show_sidebar : true;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Squiz.PHP.DisallowMultipleAssignments
        echo $sidebar = $show_sidebar ? awpcp_admin_sidebar() : '';
        ?>

        <div class="awpcp-main-content <?php echo (empty($sidebar) ? 'without-sidebar' : 'with-sidebar') ?>">
