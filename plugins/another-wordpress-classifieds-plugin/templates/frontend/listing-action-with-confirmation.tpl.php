<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><button data-action="<?php echo esc_attr( $this->get_slug() ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-confirmation-message="<?php echo esc_attr( $this->get_confirmation_message() ); ?>" data-cancel-button="<?php echo esc_attr( $this->get_cancel_button_label() ); ?>"><?php echo esc_html( $this->get_submit_button_label() ); ?></button>
