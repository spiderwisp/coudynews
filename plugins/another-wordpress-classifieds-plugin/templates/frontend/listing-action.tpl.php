<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><button data-action="<?php echo esc_attr( $this->get_slug() ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php echo esc_html( $this->get_submit_button_label() ); ?></button>
