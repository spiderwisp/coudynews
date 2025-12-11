<?php
/**
 * @package AWPCP\Admin\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for AWPCP_Create_Category_Admin_Page.
 */
function awpcp_create_category_admin_page() {
    return new AWPCP_Create_Category_Admin_Page(
        awpcp_categories_logic(),
        awpcp_router()
    );
}

/**
 * Handles admin requests to create categories.
 */
// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Create_Category_Admin_Page {

    private $categories_data_mapper;
    private $router;

    public function __construct( $categories_data_mapper, $router ) {
        $this->categories_data_mapper = $categories_data_mapper;
        $this->router                 = $router;
    }

    public function dispatch() {
        $nonce = awpcp_get_var( array( 'param' => 'awpcp-cat-form-nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'category-form' ) ) {
            throw new AWPCP_Exception( esc_html__( 'invalid nonce', 'another-wordpress-classifieds-plugin' ) );
        }

        $category_order = awpcp_get_var( array( 'param' => 'category_order', 'sanitize' => 'absint' ) );
        $category_data  = array(
            'name'        => awpcp_get_var( array( 'param' => 'category_name' ) ),
            'description' => awpcp_get_var( array( 'param' => 'category_description', 'sanitize' => 'sanitize_textarea_field' ) ),
            'parent'      => awpcp_get_var( array( 'param' => 'category_parent_id', 'sanitize' => 'intval' ) ),
        );

        try {
            $this->categories_data_mapper->create_category( $category_data, $category_order );
            awpcp_flash( esc_html__( 'The new category was successfully added.', 'another-wordpress-classifieds-plugin' ) );
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
}
