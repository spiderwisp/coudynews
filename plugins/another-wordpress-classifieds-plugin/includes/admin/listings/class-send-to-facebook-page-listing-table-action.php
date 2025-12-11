<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send to Facebook Page listing admin action.
 */
class AWPCP_SendToFacebookPageListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    /**
     * @var object
     */
    private $facebook_helper;

    /**
     * @var object
     */
    private $roles;

    /**
     * @since 4.0.0
     *
     * @param object $facebook_helper An instance of Send To Facebook Helper.
     * @param object $roles           An instance of Roles and Capabilities.
     */
    public function __construct( $facebook_helper, $roles ) {
        $this->facebook_helper = $facebook_helper;
        $this->roles           = $roles;
    }

    /**
     * @since 4.0.0
     */
    public function is_needed() {
        return $this->roles->current_user_is_moderator();
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        return false; // Available as a bulk action only.
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return awpcp_add_font_awesome_style_class_for_brands( 'fa-facebook-square' );
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Send to Facebook Page', 'listing row action', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function get_label( $post ) {
        return $this->get_title();
    }

    /**
     * @param object $post          An instance of WP_Post.
     * @param string $current_url   The URL of the current page.
     * @since 4.0.0
     */
    public function get_url( $post, $current_url ) {
        $params = array(
            'action' => 'send-to-facebook-page',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        try {
            $this->facebook_helper->send_listing_to_facebook_page( $post );
        } catch ( AWPCP_NoIntegrationMethodDefined $e ) {
            return 'no-integration-method';
        } catch ( AWPCP_NoFacebookObjectSelectedException $e ) {
            return 'no-page';
        } catch ( AWPCP_ListingAlreadySharedException $e ) {
            return 'already-sent';
        } catch ( AWPCP_ListingDisabledException $e ) {
            return 'disabled';
        } catch ( AWPCP_Exception $e ) {
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
            $message = _n( 'Ad sent to Facebook page.', '{count} ads sent to Facebook page.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'no-integration-method' === $code ) {
            $url = awpcp_get_admin_settings_url( [ 'g' => 'facebook-settings' ] );

            $message = _n( "1 ad couldn't be sent to Facebook because there is no integration method selected on {facebook_settings_link}Facebook Settings{/facebook_settings_link}.", "{count} ads couldn't be sent to Facebook because there is no integration method selected on {facebook_settings_link}Facebook Settings{/facebook_settings_link}.", $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );
            $message = str_replace( '{facebook_settings_link}', "<a href='{$url}'>", $message );
            $message = str_replace( '{/facebook_settings_link}', '</a>', $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'no-page' === $code ) {
            $message = _n( "1 ad couldn't be sent to Facebook because there is no page selected.", "{count} ads couldn't be sent to Facebook because there is no page selected.", $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'disabled' === $code ) {
            $message = _n( "1 ad was not sent to Facebook because it is currenlty disabled. If you share it, Facebook servers and users won't be able to access it.", "{count} ads were not sent to Facebook because they are currenlty disabled. If you share them, Facebook servers and users won't be able to access them.", $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'already-sent' === $code ) {
            $message = _n( '1 ad was already sent to the Facebook page.', '{count} ads were already sent to the Facebook page.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'An error occurred trying to sent an ad to the Facebook page.', 'An error occurred trying to sent {count} ads to the Facebook page.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
