<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 if ( count( $menu_items ) > 0 ): ?>
<div <?php echo awpcp_html_attributes( $navigation_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <span class="awpcp-menu-toggle"><?php echo esc_html( __( 'Classifieds Menu', 'another-wordpress-classifieds-plugin' ) ); ?></span>
    <div class="awpcp-nav-menu">
        <ul class="awpcp-menu-items clearfix">
        <?php foreach ( $menu_items as $item => $parts ): ?>
            <li class="<?php echo esc_attr( $item ); ?>"><a href="<?php echo esc_attr( $parts['url'] ); ?>"><?php
                /* menu items' title should be already escaped for HTML. Do not escape again! */
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $parts['title'];
                ?></a></li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
