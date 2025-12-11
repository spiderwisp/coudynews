<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 if ( $options['page'] ): ?>
<div class="<?php echo esc_attr( $options['page'] ); ?> awpcp-page" id="classiwrapper">
<?php else: ?>
<div id="classiwrapper">
<?php endif; ?>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $before_content;
    ?>

    <?php if ( $options['show_intro_message'] ): ?>
    <div class="uiwelcome"><?php echo wp_kses_post( stripslashes_deep( get_awpcp_option( 'uiwelcome' ) ) ); ?></div>
    <?php endif; ?>

    <?php if ( $options['show_menu_items'] ): ?>
    <?php awpcp_render_classifieds_bar( $options['classifieds_bar_components'], 'echo' ); ?>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo implode( '', $before_pagination );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $top_pagination;

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $before_list;
    ?>

    <div class="awpcp-listings awpcp-clearboth">
        <?php
        if ( count( $items ) ):
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo implode( '', $items );
        else:
            ?>
            <p><?php echo esc_html( __( 'There were no listings found.', 'another-wordpress-classifieds-plugin' ) ); ?></p>
        <?php endif;?>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $bottom_pagination;

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo implode( '', $after_pagination );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $after_content;
    ?>
</div>
