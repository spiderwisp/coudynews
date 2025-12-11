<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><ul class="awpcp-classifieds-menu awpcp-clearfix" data-breakpoints='{"tiny": [0,400], "small": [400,500]}' data-breakpoints-class-prefix="awpcp-classifieds-menu">
<?php foreach ( $buttons as $button_id => $button ) : ?>
    <li class="awpcp-classifieds-menu--menu-item awpcp-classifieds-menu--<?php echo esc_attr( $button_id ); ?>-menu-item">
        <a class="awpcp-classifieds-menu--menu-item-link button" href="<?php echo esc_url( $button['url'] ); ?>"><?php echo wp_kses_post( $button['title'] ); ?></a>
    </li>
<?php endforeach; ?>
</ul>
