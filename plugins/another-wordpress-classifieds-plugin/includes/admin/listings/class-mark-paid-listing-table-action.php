<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mark Listing as Paid table action.
 */
class AWPCP_MarkPaidListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var object
     */
    private $roles;

    /**
     * @since 4.0.0
     *
     * @param object $listings_logic    An instance of Listings API.
     * @param object $roles             An instance of Roles and Capabilities.
     */
    public function __construct( $listings_logic, $roles ) {
        $this->listings_logic   = $listings_logic;
        $this->roles            = $roles;
    }

    /**
     * @since 4.0.0
     */
    public function is_needed() {
        return $this->roles->current_user_is_moderator();
    }

    /**
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        return false; // Available as a bulk action only.
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'far fa-money-bill-alt';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Mark as Paid', 'listing row action', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    public function get_label( $post ) {
        return $this->get_title();
    }

    /**
     * @since 4.0.0
     */
    public function get_url( $post, $current_url ) {
        $params = [
            'action' => 'mark-paid',
            'ids'    => $post->ID,
        ];

        return add_query_arg( $params, $current_url );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $post ) {
        $post_data = [
            'metadata' => [
                '_awpcp_payment_status' => AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED,
            ],
        ];

        if ( ! $this->listings_logic->update_listing( $post, $post_data ) ) {
            return 'error';
        }

        return 'success';
    }

    /**
     * @param array $result_codes   An array of result codes from this action.
     * @since 4.0.0
     */
    public function get_messages( $result_codes ) {
        $messages = array();

        foreach ( $result_codes as $code => $count ) {
            $messages[] = $this->get_message( $code, $count );
        }

        return $messages;
    }

    /**
     * @param string $code      Result code.
     * @param int    $count     Number of posts associated with the given result
     *                          code.
     * @since 4.0.0
     */
    private function get_message( $code, $count ) {
        if ( 'success' === $code ) {
            $message = _n( 'Ad marked as paid.', '{count} ads marked as paid.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'An error occurred trying to mark an ad as paid.', 'An error occurred trying to mark {count} ads as paid.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
