<?php
/**
 * @package AWPCP\Admin\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Update Category Admin Page.
 */
function awpcp_update_category_admin_page() {
    return new AWPCP_Update_Category_Admin_Page(
        awpcp_categories_logic(),
        awpcp_categories_collection(),
        awpcp_router()
    );
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Update_Category_Admin_Page {

    private $categories_logic;
    private $categories;
    private $router;

    public function __construct( $categories_logic, $categories, $router ) {
        $this->categories_logic = $categories_logic;
        $this->categories       = $categories;
        $this->router           = $router;
    }

    public function dispatch() {
        try {
            $this->try_to_update_category();
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( $e->getMessage(), 'error' );
        }

        $route = [
            'parent' => 'awpcp.php',
            'page'   => 'awpcp-admin-categories',
        ];

        $this->router->serve_admin_page( $route );

        return false; // halt rendering process. Ugh!
    }

    private function try_to_update_category() {
        $nonce = awpcp_get_var( array( 'param' => 'awpcp-cat-form-nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'category-form' ) ) {
            throw new AWPCP_Exception( esc_html__( 'invalid nonce', 'another-wordpress-classifieds-plugin' ) );
        }

        $category_id = awpcp_get_var( array( 'param' => 'category_id' ) );

        try {
            $category = $this->categories->get( $category_id );
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The category you're trying to update doesn't exist.", 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $category->name        = awpcp_get_var( array( 'param' => 'category_name' ) );
        $category->description = awpcp_get_var( array( 'param' => 'category_description', 'sanitize' => 'sanitize_textarea_field' ) );
        $category->parent      = awpcp_get_var( array( 'param' => 'category_parent_id', 'sanitize' => 'absint' ) );
        $category_order        = awpcp_get_var( array( 'param' => 'category_order', 'sanitize' => 'absint' ) );

        $this->categories_logic->update_category( $category, $category_order );

        awpcp_flash( __( 'The category was successfully updated.', 'another-wordpress-classifieds-plugin' ) );
    }
}
