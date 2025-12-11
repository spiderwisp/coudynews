<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mark Listings as Verified table action.
 */
class AWPCP_MarkVerifiedListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    use AWPCP_ListTableActionWithMessages;

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
        return 'fa fa-check-double';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Mark as Verified', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'mark-verified',
            'ids'    => $post->ID,
        ];

        return add_query_arg( $params, $current_url );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $post ) {
        $this->listings_logic->verify_ad( $post );

        return 'success';
    }

    /**
     * @param string $code      Result code.
     * @param int    $count     Number of posts associated with the given result
     *                          code.
     * @since 4.0.0
     */
    protected function get_message( $code, $count ) {
        if ( 'success' === $code ) {
            $message = _n( 'Ad marked as verified.', '{count} ads marked as verified.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'An error occurred trying to mark an ad as verified.', 'An error occurred trying to mark {count} ads as verified.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
