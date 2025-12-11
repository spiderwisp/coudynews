<?php
/**
 * Admin screen that allows administrators to manage Fees.
 *
 * @package AWPCP\Admin\Fees
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_AdminFees class.
 */
function awpcp_fees_admin_page() {
    return new AWPCP_AdminFees( awpcp_listings_collection() );
}

/**
 * @since 2.1.4
 */
class AWPCP_AdminFees extends AWPCP_AdminPageWithTable {

    private $listings;

    public function __construct( $listings ) {
        parent::__construct(
            'awpcp-admin-fees',
            awpcp_admin_page_title( __( 'Manage Listing Fees', 'another-wordpress-classifieds-plugin' ) ),
            __( 'Fees', 'another-wordpress-classifieds-plugin' )
        );

        $this->listings = $listings;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-fees' );
    }

    public function get_table() {
        if ( ! is_null( $this->table ) ) {
            return $this->table;
        }

        $this->table = new AWPCP_FeesTable( $this, array( 'screen' => 'classifieds_page_awpcp-admin-fees' ) );

        return $this->table;
    }

    public function page_url( $params = array() ) {
        $base = add_query_arg( 'page', $this->page, admin_url( 'admin.php' ) );
        return $this->url( $params, $base );
    }

    public function actions( $fee, $filter = false ) {
        $actions = array();

        $actions['edit'] = array(
            __( 'Edit', 'another-wordpress-classifieds-plugin' ),
            $this->url(
                array(
                    'awpcp-action' => 'edit-fee',
                    'id'           => $fee->id,
                )
            ),
        );

        $actions['trash'] = array(
            __( 'Delete', 'another-wordpress-classifieds-plugin' ),
            $this->url(
                array(
                    'action' => 'delete',
                    'id'     => $fee->id,
                )
            ),
        );

        $actions = apply_filters( 'awpcp-admin-fees-table-actions', $actions, $this, $fee, $filter );

        if ( is_array( $filter ) ) {
            $actions = array_intersect_key( $actions, array_combine( $filter, $filter ) );
        }

        return $actions;
    }

    public function dispatch() {
        $this->get_table();

        $action = $this->get_current_action();

        switch ( $action ) {
            case 'delete':
                return $this->delete();
            case 'index':
                return $this->index();
            default:
                awpcp_flash( "Unknown action: $action", 'error' );
                return $this->index();
        }
    }

    public function delete() {
        $id  = awpcp_get_var( array( 'param' => 'id', 'default' => 0 ) );
        $fee = AWPCP_Fee::find_by_id( $id );

        if ( is_null( $fee ) ) {
            awpcp_flash( __( "The specified Fee doesn't exists.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->index();
        }

        $errors = array();

        if ( AWPCP_Fee::delete( $fee->id, $errors ) ) {
            awpcp_flash( __( 'The Fee was successfully deleted.', 'another-wordpress-classifieds-plugin' ) );
        } else {
            $ads = $this->listings->find_listings(
                array(
                    'meta_query' => array(
                        '_awpcp_payment_term_id'   => $fee->id,
                        '_awpcp_payment_term_type' => 'fee',
                    ),
                )
            );

            if ( empty( $ads ) ) {
                foreach ( $errors as $error ) {
                    awpcp_flash( $error, 'error' );
                }
            } else {
                $fees = AWPCP_Fee::query();

                if ( count( $fees ) > 1 ) {
                    $message = __( "The Fee couldn't be deleted because there are active Ads in the system that are associated with the Fee ID. You need to switch the Ads to a different Fee before you can delete the plan.", 'another-wordpress-classifieds-plugin' );
                    awpcp_flash( $message, 'error' );

                    return;
                }

                $message = __( "The Fee couldn't be deleted because there are active Ads in the system that are associated with the Fee ID. Please create a new Fee and try the delete operation again. AWPCP will help you to switch existing Ads to the new fee.", 'another-wordpress-classifieds-plugin' );

                awpcp_flash( $message, 'error' );
            }
        }

        return $this->index();
    }

    public function index() {
        $this->table->prepare_items();

        $params = array(
            'page'  => $this,
            'table' => $this->table,
        );

        $template = AWPCP_DIR . '/admin/templates/admin-panel-fees.tpl.php';

        return awpcp_render_template( $template, $params );
    }

    /**
     * @deprecated 4.0.0
     */
    public function transfer() {
        _deprecated_function( __FUNCTION__, '4.0.0' );
    }
}
