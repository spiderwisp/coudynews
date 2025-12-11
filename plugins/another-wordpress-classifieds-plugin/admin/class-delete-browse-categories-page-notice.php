<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return AWPCP_Delete_Browse_Categories_Page_Notice
 */
function awpcp_delete_browse_categories_page_notice() {
    return new AWPCP_Delete_Browse_Categories_Page_Notice();
}

class AWPCP_Delete_Browse_Categories_Page_Notice {

    public function maybe_show_notice() {
        if ( ! get_option( 'awpcp-show-delete-browse-categories-page-notice' ) ) {
            return;
        }

        $browse_categories_page_id = awpcp_get_page_id_by_ref( 'browse-categories-page-name' );

        if ( ! $browse_categories_page_id || ! current_user_can( 'delete_page', $browse_categories_page_id ) ) {
            return;
        }

        $page = awpcp_get_var( array( 'param' => 'page' ), 'get' );

        if ( substr( $page, 0, 5 ) !== 'awpcp' ) {
            return;
        }

        if ( $page === 'awpcp-admin-upgrade' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput
        echo $this->render_notice( $browse_categories_page_id );
    }

    private function render_notice( $browse_categories_page_id ) {
        $template = AWPCP_DIR . '/templates/admin/delete-browse-categories-page-notice.tpl.php';

        $params = array(
            'browse_categories_page_name' => awpcp_get_page_name( 'browse-categories-page-name' ),
            'browse_listings_page_name'   => awpcp_get_page_name( 'browse-ads-page-name' ),
            'action_params'               => array(
                'id'          => $browse_categories_page_id,
                '_ajax_nonce' => wp_create_nonce( "delete-page_$browse_categories_page_id" ),
            ),
        );

        return awpcp_render_template( $template, $params );
    }
}
