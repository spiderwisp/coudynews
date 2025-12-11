<?php
/**
 * Renew Listing Table Action for Moderators.
 *
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ModeratorRenewListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    /**
     * Implements some of the methods from the AWPCP_ListTableActionInterface.
     */
    use AWPCP_RenewListingTableAction;

    /**
     * @var object
     */
    private $listings_logic;

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $email_notifications;

    /**
     * @var object
     */
    private $roles;

    /**
     * @var object
     */
    private $settings;

    /**
     * @param object $listings_logic        An instance of Listings API.
     * @param object $listing_renderer      An instance of Listing Renderer.
     * @param object $email_notifications   An instance of ListingRenewedEmailNotifications.
     * @param object $roles                 An instance of Roles and Capabilities.
     * @param object $settings              An instance of AWPCP_Settings_API.
     */
    public function __construct( $listings_logic, $listing_renderer, $email_notifications, $roles, $settings ) {
        $this->listings_logic      = $listings_logic;
        $this->listing_renderer    = $listing_renderer;
        $this->email_notifications = $email_notifications;
        $this->roles               = $roles;
        $this->settings            = $settings;
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
    public function should_show_as_bulk_action() {
        return $this->roles->current_user_is_moderator();
    }

    /**
     * @param object $post          An instance of WP_Post.
     * @param string $current_url   The URL of the current page.
     * @since 4.0.0
     */
    public function get_url( $post, $current_url ) {
        $params = array(
            'action' => 'renew',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        if ( ! $this->listing_renderer->has_expired( $post ) && ! $this->listing_renderer->is_about_to_expire( $post ) ) {
            return 'not-expired';
        }

        $payment_term = $this->listing_renderer->get_payment_term( $post );

        if ( ! $payment_term ) {
            return 'no-payment';
        }

        if ( ! $payment_term->ad_can_be_renewed( $post ) ) {
            return 'error';
        }

        if ( ! $this->listings_logic->renew_listing( $post ) ) {
            return 'error';
        }

        $this->email_notifications->send_user_notification( $post );

        if ( $this->settings->get_option( 'send-listing-renewed-notification-to-admin' ) ) {
            $this->email_notifications->send_admin_notification( $post );
        }

        // TODO: MOVE inside Ad::renew() ?
        do_action( 'awpcp-renew-ad', $post->ID, null );

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
            $message = _n( 'Ad renewed successfully.', '{count} ads renewed successfully.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'not-expired' === $code ) {
            $message = _n( "The ad couldn't be renewed because it hasn't expired yet.", "{count} ads couldn't be renewed because they haven't expired yet.", $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'no-payment' === $code ) {
            $message = _n( "The ad couldn't be renewed because we couldn't find the associated payment.", "{count} ads couldn't be renewed because we couldn't finde the associated payments.", $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'There was an error trying to renew one ad.', 'There was an error trying to renew {count} ads.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
