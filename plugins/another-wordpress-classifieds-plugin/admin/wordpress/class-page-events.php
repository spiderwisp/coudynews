<?php
/**
 * @package AWPCP\Admin\WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handlers for several Page events triggered on the Admin Dashboard.
 */
class AWPCP_WordPressPageEvents {

    /**
     * Fires 'awpcp-pages-updated' action every time an AWPCP page is updated.
     */
    public function post_updated( $post_id, $post_after, $post_before ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'page' != $post_after->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

        if ( ! is_awpcp_page( $post_id ) ) {
            return;
        }

        do_action( 'awpcp-pages-updated', $post_id, $post_after, $post_before );

        update_option( 'awpcp-flush-rewrite-rules', true );
    }
}
