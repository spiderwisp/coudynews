<?php
/**
 * @package AWPCP\Admin\Fess
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_fee_details_admin_page() {
    return new AWPCP_Fee_Details_Admin_Page(
        awpcp_fee_details_form(),
        awpcp_fees_collection(),
        awpcp_html_renderer(),
        awpcp_router()
    );
}

class AWPCP_Fee_Details_Admin_Page {

    private $fee_details_form;
    private $fees;
    private $html_renderer;
    private $router;

    public function __construct( $fee_details_form, $fees, $html_renderer, $router ) {
        $this->fee_details_form = $fee_details_form;
        $this->fees = $fees;
        $this->html_renderer = $html_renderer;
        $this->router = $router;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-fee-details' );
    }

    public function dispatch() {
        $action = awpcp_get_var( array( 'param' => 'awpcp-action' ), 'get' );

        if ( 'add-fee' === $action ) {
            return $this->add_fee();
        }

        return $this->edit_fee();
    }

    private function add_fee() {
        $fee = new AWPCP_Fee();

        if ( $this->is_saving() ) {
            return $this->update_fee( $fee );
        }

        return $this->render_form( $fee );
    }

    private function update_fee( $fee ) {
        $fee->update( $this->get_posted_data() );

        $errors = array();

        if ( $fee->save( $errors ) === false ) {
            awpcp_flash( __( 'The form has errors', 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->render_form( $fee );
        }

        awpcp_flash( __( 'Fee successfully updated.', 'another-wordpress-classifieds-plugin' ) );

        $redirect_target = array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' );

        return $this->router->redirect( apply_filters( 'awpcp-fee-details-successful-redirect', $redirect_target, $fee ) );
    }

    private function get_posted_data() {
        $fee_data = array(
            'name'              => awpcp_get_var( array( 'param' => 'name' ), 'post' ),
            'description'       => awpcp_get_var(
                array( 'param' => 'description', 'sanitize' => 'sanitize_textarea_field' ),
                'post'
            ),
            'price'             => awpcp_parse_money(
                awpcp_get_var( array( 'param' => 'price_in_currency' ), 'post' )
            ),
            'credits'           => max( 0, intval( awpcp_get_var( array( 'param' => 'price_in_credits' ), 'post' ) ) ),
            'duration_amount'   => awpcp_get_var( array( 'param' => 'duration_amount' ), 'post' ),
            'duration_interval' => awpcp_get_var( array( 'param' => 'duration_interval' ), 'post' ),
            'images'            => awpcp_get_var( array( 'param' => 'images_allowed' ), 'post' ),
            'private'           => awpcp_get_var( array( 'param' => 'is_private', 'default' => false ), 'post' ),
            'featured'          => awpcp_get_var( array( 'param' => 'use_for_featured_listings', 'default' => false ), 'post' ),
        );

        $values = array(
            'title_characters' => 'characters_allowed_in_title',
            'characters'       => 'characters_allowed_in_description',
        );
        foreach ( $values as $name => $value ) {
            if ( ! awpcp_get_var( array( 'param' => $value . '_enabled' ), 'post' ) ) {
                $fee_data[ $name ] = 0;
            } else {
                $fee_data[ $name ] = awpcp_get_var( array( 'param' => $value ), 'post' );
            }
        }

        return apply_filters( 'awpcp-fee-details-posted-data', $fee_data );
    }

    private function render_form( $fee ) {
        $params = array(
            'form_title' => __( 'Create Fee Plan', 'another-wordpress-classifieds-plugin' ),
            'fee' => $fee,
            'action' => 'create-fee',
        );

        return $this->html_renderer->render( $this->fee_details_form->build( $params ) );
    }

    private function edit_fee() {
        $fee_id = awpcp_get_var( array( 'param' => 'id' ) );

        if ( empty( $fee_id ) ) {
            awpcp_flash( __( 'No Fee Plan id was specified.', 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->router->redirect( array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' ) );
        }

        try {
            $fee = $this->fees->get( $fee_id );
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( __( "The specified Fee Plan doesn't exist or couldn't be loaded.", 'another-wordpress-classifieds-plugin' ) );
            return $this->router->redirect( array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' ) );
        }

        if ( $this->is_saving() ) {
            return $this->update_fee( $fee );
        }

        return $this->render_form( $fee );
    }

    /**
     * @since 4.3
     * @return bool
     */
    private function is_saving() {
        return awpcp_get_var( array( 'param' => 'save' ), 'post' ) || awpcp_get_var( array( 'param' => 'save_and_continue' ), 'post' );
    }
}
