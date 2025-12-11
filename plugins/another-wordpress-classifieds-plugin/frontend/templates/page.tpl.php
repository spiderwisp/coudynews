<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 awpcp_print_messages() ?>

<div class="<?php echo esc_attr( $page->page ); ?> awpcp-page" id="classiwrapper">

    <?php if ( $page->show_menu_items ): ?>
        <?php awpcp_render_classifieds_bar( $page->classifieds_bar_components, 'echo' ); ?>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $content;
    ?>
</div>
