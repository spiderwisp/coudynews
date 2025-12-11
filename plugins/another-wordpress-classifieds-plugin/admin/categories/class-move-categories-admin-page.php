<?php
/**
 * @package AWPCP\Admin\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Move_Categories_Admin_Page class.
 */
function awpcp_move_categories_admin_page() {
    return new AWPCP_Move_Categories_Admin_Page(
        awpcp_categories_logic(),
        awpcp_categories_collection(),
        awpcp_router(),
        awpcp_request()
    );
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Move_Categories_Admin_Page {

    private $categories_logic;
    private $categories;
    private $router;
    private $request;

    public function __construct( $categories_logic, $categories, $router, $request ) {
        $this->categories_logic = $categories_logic;
        $this->categories       = $categories;
        $this->router           = $router;
        $this->request          = $request;
    }

    public function dispatch() {
        try {
            $this->try_to_move_categories();
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash_error( $e->getMessage() );
        }

        $this->router->serve_admin_page(
            [
                'parent' => 'awpcp.php',
                'page'   => 'awpcp-admin-categories',
            ]
        );

        return false; // halt rendering process. Ugh!
    }

    private function try_to_move_categories() {
        $nonce = awpcp_get_var( array( 'param' => 'awpcp-multiple-form-nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'cat-multiple-form' ) ) {
            throw new AWPCP_Exception( esc_html__( 'invalid nonce', 'another-wordpress-classifieds-plugin' ) );
        }

        $selected_categories = $this->request->post( 'category_to_delete_or_move' );
        $target_category_id  = $this->request->post( 'moveadstocategory' );

        try {
            $target_category = $this->categories->get( $target_category_id );
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The categories couldn't be moved because there was an error trying to load the target category. <specific-error-message>", 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<specific-error-message>', $e->getMessage(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $result = $this->move_categories( $selected_categories, $target_category );

        if ( $result['categories_not_moved'] === 0 ) {
            awpcp_flash( esc_html__( 'The selected categories have been moved.', 'another-wordpress-classifieds-plugin' ) );
            return;
        }

        if ( $result['categories_moved'] === 0 ) {
            awpcp_flash( esc_html__( 'There was an error trying to move the selected categories.', 'another-wordpress-classifieds-plugin' ), 'error' );
            return;
        }

        $message = __( '<categories-moved> (out of <categories-count>) categories were moved. However, there was an error trying to move the other <categories-not-moved> categories.', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<categories-moved>', $result['categories_moved'], $message );
        $message = str_replace( '<categories-not-moved>', $result['categories_not_moved'], $message );
        $message = str_replace( '<categories-count>', count( $selected_categories ), $message );

        awpcp_flash( esc_html( $message ), 'error' );
    }

    private function move_categories( $selected_categories, $target_category ) {
        $categories_moved     = 0;
        $categories_not_moved = 0;

        foreach ( $selected_categories as $category_id ) {
            try {
                $this->categories_logic->move_category( $this->categories->get( $category_id ), $target_category );
                ++$categories_moved;
            } catch ( AWPCP_Exception $e ) {
                ++$categories_not_moved;
            }
        }

        return compact( 'categories_moved', 'categories_not_moved' );
    }
}
