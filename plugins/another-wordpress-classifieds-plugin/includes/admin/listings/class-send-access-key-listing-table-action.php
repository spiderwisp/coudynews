<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send Access Key Listing Action
 */
class AWPCP_SendAccessKeyListingTableAction implements AWPCP_ListTableActionInterface {

    /**
     * @var object
     */
    private $email_factory;

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @param object $email_factory     An instance of Email Factory.
     * @param object $listing_renderer  An instance of Listing Renderer.
     * @since 4.0.0
     */
    public function __construct( $email_factory, $listing_renderer ) {
        $this->email_factory    = $email_factory;
        $this->listing_renderer = $listing_renderer;
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        return true;
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'fa fa-key';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Send Access Key', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'send-access-key',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        $message = $this->prepare_access_key_messsage( $post );

        if ( $message->send() ) {
            return 'success';
        }

        return 'error';
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function prepare_access_key_messsage( $post ) {
        $listing_title = $this->listing_renderer->get_listing_title( $post );
        $contact_name  = $this->listing_renderer->get_contact_name( $post );
        $contact_email = $this->listing_renderer->get_contact_email( $post );
        $recipient     = awpcp_format_recipient_address( $contact_email, $contact_name );

        $message = $this->email_factory->get_email();

        $message->to[] = $recipient;

        // translators: %s is the title of the ad.
        $message->subject = sprintf( __( 'Access Key for "%s"', 'another-wordpress-classifieds-plugin' ), $listing_title );

        $message->prepare(
            AWPCP_DIR . '/frontend/templates/email-send-ad-access-key.tpl.php',
            array(
                'listing_title' => $listing_title,
                'contact_name'  => $contact_name,
                'contact_email' => $contact_email,
                'access_key'    => $this->listing_renderer->get_access_key( $post ),
                'edit_link'     => awpcp_get_edit_listing_url_with_access_key( $post ),
            )
        );

        return $message;
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
            $message = _n( 'The access key was successfully sent.', '{count} access keys were successfully sent.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'There was an error trying to send the email message with the access key.', 'There was an error tring to send {count} email messages with the access keys.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
