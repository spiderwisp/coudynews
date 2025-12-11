<?php
/**
 * @package AWPCP\Admin\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_Delete_Categories_Admin_Page.
 */
function awpcp_delete_categories_admin_page() {
    return new AWPCP_Delete_Categories_Admin_Page(
        awpcp_categories_logic(),
        awpcp_categories_collection(),
        awpcp_router(),
        awpcp_request()
    );
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Delete_Categories_Admin_Page {

    private $categories_logic;
    private $categories;
    private $request;
    protected $router;

    public function __construct( $categories_logic, $categories, $router, $request ) {
        $this->categories_logic = $categories_logic;
        $this->categories       = $categories;
        $this->router           = $router;
        $this->request          = $request;
    }

    public function dispatch() {
        try {
            $this->try_to_delete_categories();
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

    public function try_to_delete_categories() {
        $nonce = awpcp_get_var( array( 'param' => 'awpcp-multiple-form-nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'cat-multiple-form' ) ) {
            throw new AWPCP_Exception( esc_html__( 'invalid nonce', 'another-wordpress-classifieds-plugin' ) );
        }

        $selected_categories  = $this->request->post( 'category_to_delete_or_move' );
        $target_category_id   = $this->request->post( 'moveadstocategory', 1 );
        $should_move_listings = $this->request->post( 'movedeleteads', 1 ) === 1;
        $target_category      = null;

        if ( $should_move_listings ) {
            try {
                $target_category = $this->categories->get( $target_category_id );
            } catch ( AWPCP_Exception $e ) {
                $message = sprintf(
                    // translators: %s is the error message
                    __( "The categories couldn't be deleted because there was an error trying to load the category that you selected to become the new category associated to affected ads. %s", 'another-wordpress-classifieds-plugin' ),
                    $e->getMessage()
                );

                throw new AWPCP_Exception( esc_html( $message ) );
            }
        }

        $result = $this->delete_categories( $selected_categories, $target_category, $should_move_listings );

        if ( $result['categories_not_deleted'] === 0 ) {
            awpcp_flash( esc_html__( 'The selected categories have been deleted.', 'another-wordpress-classifieds-plugin' ) );
            return;
        }

        if ( $result['categories_deleted'] === 0 ) {
            awpcp_flash_error( esc_html__( 'There was an error trying to delete the selected categories.', 'another-wordpress-classifieds-plugin' ) );
            return;
        }

        $message = __( '<categories-deleted> (out of <categories-count>) categories were deleted. However, there was an error trying to delete the other <categories-not-deleted> categories.', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<categories-deleted>', $result['categories_deleted'], $message );
        $message = str_replace( '<categories-not-deleted>', $result['categories_not_deleted'], $message );
        $message = str_replace( '<categories-count>', count( $selected_categories ), $message );

        awpcp_flash_error( esc_html( $message ) );
    }

    private function delete_categories( $selected_categories, $target_category, $should_move_listings ) {
        $categories_deleted     = 0;
        $categories_not_deleted = 0;

        foreach ( $selected_categories as $category_id ) {
            try {
                $category = $this->categories->get( $category_id );

                if ( $should_move_listings ) {
                    $this->categories_logic->delete_category_moving_listings_to( $category, $target_category );
                } else {
                    $this->categories_logic->delete_category_and_associated_listings( $category );
                }

                ++$categories_deleted;
            } catch ( AWPCP_Exception $e ) {
                ++$categories_not_deleted;
                continue;
            }
        }

        return compact( 'categories_deleted', 'categories_not_deleted' );
    }
}
