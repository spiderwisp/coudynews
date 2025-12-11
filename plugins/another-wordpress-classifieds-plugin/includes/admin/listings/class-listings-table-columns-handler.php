<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for custom columns on Listings table.
 */
class AWPCP_ListingsTableColumnsHandler {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $listings_collection;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_post_type, $listing_category_taxonomy, $listing_renderer, $listings_collection ) {
        $this->listing_post_type         = $listing_post_type;
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->listing_renderer          = $listing_renderer;
        $this->listings_collection       = $listings_collection;
    }

    /**
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( ! isset( $query->query_vars['orderby'] ) ) {
            return;
        }

        if ( 'payment_term' === $query->query_vars['orderby'] ) {
            $query->query_vars['meta_key'] = '_awpcp_payment_term_id';
            $query->query_vars['orderby']  = 'meta_value_num';
        }
    }

    /**
     * @since 4.0.0
     */
    public function posts_orderby( $sql, $query ) {
        if ( ! $query->is_main_query() ) {
            return $sql;
        }

        if ( ! isset( $query->query_vars['orderby'] ) ) {
            return $sql;
        }

        if ( 'post_status' !== $query->query_vars['orderby'] ) {
            return $sql;
        }

        return "post_status {$query->query_vars['order']}";
    }

    /**
     * @param array $columns    An array of available columns.
     * @since 4.0.0
     */
    public function manage_posts_columns( $columns ) {
        // Remove Date column.
        unset( $columns['date'] );

        // Move Categories column.
        $columns_keys   = array_keys( $columns );
        $columns_values = array_values( $columns );

        $position = array_search( 'taxonomy-' . $this->listing_category_taxonomy, $columns_keys, true );

        $categories_column_key   = array_splice( $columns_keys, $position, 1 );
        $categories_column_value = array_splice( $columns_values, $position, 1 );

        array_splice( $columns_keys, 2, 0, $categories_column_key );
        array_splice( $columns_values, 2, 0, $categories_column_value );

        // Add custom columns.
        $new_columns['awpcp-dates']        = _x( 'Dates', 'listings table column', 'another-wordpress-classifieds-plugin' );
        $new_columns['awpcp-payment-term'] = _x( 'Payment Term', 'listings table column', 'another-wordpress-classifieds-plugin' );
        $new_columns['awpcp-status']       = _x( 'Status', 'listings table column', 'another-wordpress-classifieds-plugin' );

        array_splice( $columns_keys, 3, 0, array_keys( $new_columns ) );
        array_splice( $columns_values, 3, 0, array_values( $new_columns ) );

        // Add Actions column at the end.
        $columns_keys[]   = 'awpcp-actions';
        $columns_values[] = _x( 'Actions', 'listings table column', 'another-wordpress-classifieds-plugin' );

        return array_combine( $columns_keys, $columns_values );
    }

    /**
     * @since 4.0.0
     */
    public function manage_sortable_columns( $sortable_columns ) {
        $sortable_columns['awpcp-payment-term'] = 'payment_term';
        $sortable_columns['awpcp-status']       = 'post_status';

        return $sortable_columns;
    }

    /**
     * @param string $column    The name of the column that is being rendered.
     * @param int    $post_id   The ID of the current post.
     * @since 4.0.0
     */
    public function manage_posts_custom_column( $column, $post_id ) {
        try {
            $post = $this->listings_collection->get( $post_id );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        switch ( $column ) {
            case 'awpcp-dates':
                echo esc_html( __( 'Start Date:', 'another-wordpress-classifieds-plugin' ) ) . ' <strong>' . esc_html( $this->listing_renderer->get_start_date( $post ) ) . '</strong><br/>';
                echo esc_html( __( 'End Date:', 'another-wordpress-classifieds-plugin' ) ) . ' <strong>' . esc_html( $this->listing_renderer->get_end_date_formatted( $post ) ) . '</strong><br/>';
                echo esc_html( __( 'Renewed Date:', 'another-wordpress-classifieds-plugin' ) ) . ' <strong>' . esc_html( $this->listing_renderer->get_renewed_date_formatted( $post ) ) . '</strong><br/>';
                echo esc_html( __( 'Published:', 'another-wordpress-classifieds-plugin' ) ) . ' <strong>' . esc_html( awpcp_datetime( 'awpcp-date', $post->post_date ) ) . '</strong><br/>';
                return;
            case 'awpcp-start-date':
                echo esc_html( $this->listing_renderer->get_start_date( $post ) );
                return;
            case 'awpcp-end-date':
                echo esc_html( $this->listing_renderer->get_end_date( $post ) );
                return;
            case 'awpcp-renewed-date':
                $renewed_date = $this->listing_renderer->get_renewed_date_formatted( $post );

                echo $renewed_date ? esc_html( $renewed_date ) : '&mdash;';
                return;
            case 'awpcp-payment-term':
                $this->render_payment_term_column( $post );
                return;
            case 'awpcp-status':
                $this->render_status_column( $post );
                return;
            case 'awpcp-actions':
                $this->render_actions_column( $post );
                return;
        }
    }

    /**
     * @since 4.0.0
     */
    private function render_payment_term_column( $post ) {
        $payment_term = $this->listing_renderer->get_payment_term( $post );

        if ( ! $payment_term ) {
            return;
        }

        $payment_status = $this->get_payment_status_formatted( $post );

        echo '<strong>' . esc_html( $payment_term->name ) . '</strong>';
        echo '<br/>';
        echo esc_html( $payment_status );
    }

    /**
     * @since 4.0.0  Moved from Ad class.
     */
    public function get_payment_status_formatted( $post ) {
        $payment_status = $this->listing_renderer->get_payment_status( $post );

        switch ( $payment_status ) {
            case AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING:
                return _x( 'Payment Pending', 'ad payment status', 'another-wordpress-classifieds-plugin' );
            case AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED:
                return _x( 'Payment Completed', 'ad payment status', 'another-wordpress-classifieds-plugin' );
            case AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED:
                return _x( 'Payment Not Required', 'ad payment status', 'another-wordpress-classifieds-plugin' );
            case 'Unpaid':
                return _x( 'Unpaid', 'ad payment status', 'another-wordpress-classifieds-plugin' );
            default:
                return _x( 'Payment Status Unknown', 'ad payment status', 'another-wordpress-classifieds-plugin' );
        }
    }

    /**
     * @since 4.0.0
     */
    private function render_status_column( $post ) {
        if ( $this->listing_renderer->is_public( $post ) ) {
            echo esc_html_x( 'Active', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }

        if ( $this->listing_renderer->is_pending_approval( $post ) ) {
            echo esc_html_x( 'Pending Approval', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }

        if ( $this->listing_renderer->is_expired( $post ) ) {
            echo esc_html_x( 'Expired', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }

        if ( $this->listing_renderer->is_disabled( $post ) ) {
            echo esc_html_x( 'Disabled', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }

        if ( ! $this->listing_renderer->has_payment( $post ) ) {
            echo esc_html_x( 'Pending Payment', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }

        if ( ! $this->listing_renderer->is_verified( $post ) ) {
            echo esc_html_x( 'Pending Verification', 'listing status', 'another-wordpress-classifieds-plugin' );
            return;
        }
    }

    /**
     * @since 4.0.0
     */
    private function render_actions_column( $post ) {
        $actions = apply_filters( "{$this->listing_post_type}_row_actions", [], $post );

        echo wp_kses_post( implode( '', $actions ) );
    }
}
