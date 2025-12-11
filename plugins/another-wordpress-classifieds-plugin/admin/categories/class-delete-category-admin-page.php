<?php
/**
 * Admin page used to delete categories.
 *
 * @package AWPCP\Admin\Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Delete_Category_Admin_Page.
 */
function awpcp_delete_category_admin_page() {
    return new AWPCP_Delete_Category_Admin_Page(
        awpcp_categories_logic(),
        awpcp_categories_collection(),
        awpcp_template_renderer(),
        awpcp_router()
    );
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class AWPCP_Delete_Category_Admin_Page {

    private $categories_logic;
    private $categories;
    private $template_renderer;
    private $router;

    public function __construct( $categories_logic, $categories, $template_renderer, $router ) {
        $this->categories_logic  = $categories_logic;
        $this->categories        = $categories;
        $this->template_renderer = $template_renderer;
        $this->router            = $router;
    }

    public function dispatch() {
        $category_id         = awpcp_get_var( array( 'param' => 'cat_ID' ) );
        $operation_confirmed = awpcp_get_var( array( 'param' => 'awpcp-confirm-delete-category' ), 'post' );

        try {
            $category = $this->categories->get( $category_id );
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The category you are trying to delete doesn't exist.", 'another-wordpress-classifieds-plugin' );
            awpcp_flash( $message, 'error' );

            return $this->redirect_to_main_page();
        }

        if ( ! $operation_confirmed ) {
            return $this->show_delete_category_form( $category );
        }
        try {
            $this->try_to_delete_category( $category );
        } catch ( AWPCP_Exception $e ) {
            $message = __( 'There was an error trying to delete the category. <error-message>', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<error-message>', $e->getMessage(), $message );

            awpcp_flash( esc_html( $message ), 'error' );
        }

        return $this->redirect_to_main_page();
    }

    private function try_to_delete_category( $category ) {
        $nonce = awpcp_get_var( array( 'param' => 'awpcp-del-cat-nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, 'delete-category' ) ) {
            throw new AWPCP_Exception( esc_html__( 'invalid nonce', 'another-wordpress-classifieds-plugin' ) );
        }

        $target_category_id   = awpcp_get_var( array( 'param' => 'target_category_id' ), 'post' );
        $should_move_listings = ads_exist_cat( $category->term_id );

        try {
            $target_category = $this->categories->get( $target_category_id );
        } catch ( AWPCP_Exception $e ) {
            if ( $should_move_listings ) {
                $message = __( 'There was an error trying to load the selected category. <error-message>', 'another-wordpress-classifieds-plugin' );
                $message = str_replace( '<error-message>', $e->getMessage(), $message );

                throw new AWPCP_Exception( esc_html( $message ) );
            }

            $target_category = null;
        }

        $this->delete_category( $category, $target_category, $should_move_listings );

        awpcp_flash( __( 'The category was deleted successfully', 'another-wordpress-classifieds-plugin' ) );
    }

    /**
     * @since 4.0.0
     */
    private function delete_category( $category, $target_category, $should_move_listings ) {
        if ( $should_move_listings ) {
            $this->categories_logic->delete_category_moving_listings_to( $category, $target_category );
            return;
        }

        $this->categories_logic->delete_category_and_associated_listings( $category, $target_category );
    }

    private function show_delete_category_form( $category ) {
        $template = AWPCP_DIR . '/templates/admin/delete-category-admin-page.tpl.php';

        $form_title = __( 'Are you sure you want to delete "<category-name>" category?', 'another-wordpress-classifieds-plugin' );
        $form_title = str_replace( '<category-name>', $category->name, $form_title );

        $params = array(
            'category_has_listings' => ads_exist_cat( $category->term_id ),
            'category_has_children' => category_has_children( $category->term_id ),
            'form_title'            => $form_title,
            'form_values'           => array(
                'category_id'        => $category->term_id,
                'target_category_id' => 0,
                'action'             => 'delete-category',
                'nonce'              => wp_create_nonce( 'delete-category' ),
            ),
            'form_submit'           => __( 'Delete category', 'another-wordpress-classifieds-plugin' ),
            'form_cancel'           => __( 'Cancel', 'another-wordpress-classifieds-plugin' ),
            'offset'                => (int) awpcp_get_var( array( 'param' => 'offset' ) ),
            'results'               => max( (int) awpcp_get_var( array( 'param' => 'results', 'default' => 10 ) ), 1 ),
        );

        return $this->template_renderer->render_template( $template, $params );
    }

    private function redirect_to_main_page() {
        $this->router->serve_admin_page(
            array(
                'parent' => 'awpcp.php',
                'page'   => 'awpcp-admin-categories',
            )
        );

        return false; // halt rendering process. Ugh!
    }
}
