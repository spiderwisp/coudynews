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
class AWPCP_SendVerificationEmailTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    use AWPCP_ListTableActionWithMessages;

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $roles;

    /**
     * @since 4.0.0
     *
     * @param object $listings_logic    An instance of Listings API.
     * @param object $listing_renderer  An instance of Listing Renderer.
     * @param object $roles             An instance of Roles and Capabilities.
     */
    public function __construct( $listings_logic, $listing_renderer, $roles ) {
        $this->listings_logic   = $listings_logic;
        $this->listing_renderer = $listing_renderer;
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
        return _x( 'Send Verification Email', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'send-verification-email',
            'ids'    => $post->ID,
        ];

        return add_query_arg( $params, $current_url );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $post ) {
        $is_listing_verified = $this->listing_renderer->is_verified( $post );
        if ( ! $is_listing_verified ) {
            $email_sent = $this->listings_logic->send_verification_email( $post );
            if ( $email_sent ) {
                return 'success';
            }
            return 'error';
        }
    }

    /**
     * @param string $code      Result code.
     * @param int    $count     Number of posts associated with the given result
     *                          code.
     * @since 4.0.0
     */
    protected function get_message( $code, $count ) {
        if ( 'success' === $code ) {
            $message = _n( 'Verification Email Sent.', '{count} ads marked as verified.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'An error occurred trying to send verification email.', 'An error occurred trying to send {count} verification email.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
