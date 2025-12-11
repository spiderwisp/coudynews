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
class AWPCP_ApproveImagesTableAction implements AWPCP_ListTableActionInterface {

    /**
     * @var object
     */
    private $listings_api;

    /**
     * @var object
     */
    private $roles;

    /**
     * @var object
     */
    private $attachments_logic;

    /**
     *
     * @since 4.0.0
     */
    public function __construct( $listings_api, $roles, $attachments_logic ) {
        $this->listings_api      = $listings_api;
        $this->roles             = $roles;
        $this->attachments_logic = $attachments_logic;
    }

    /**
     * @param object $post An instance of WP_Post.
     *
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        return false;
    }

    /**
     *
     * @since 4.0.0
     */
    public function should_show_as_bulk_action() {
        return $this->roles->current_user_is_moderator();
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
        return _x( 'Approve Images', 'listing row action', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $post An instance of WP_Post.
     *
     * @since 4.0.0
     */
    public function get_label( $post ) {
        return $this->get_title();
    }

    /**
     * @param object $post An instance of WP_Post.
     * @param string $current_url The URL of the current page.
     *
     * @since 4.0.0
     */
    public function get_url( $post, $current_url ) {
        $params = array(
            'action' => 'approve-images',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post An instance of WP_Post.
     *
     * @since 4.0.0
     */
    public function process_item( $post ) {
        $images   = get_attached_media( 'image', $post->ID );
        $approved = [];
        foreach ( $images as $image ) {
            $allowed_status = get_metadata( 'post', $image->ID, '_awpcp_allowed_status', true );
            if ( $allowed_status === 'Approved' ) {
                continue;
            }
            if ( $this->attachments_logic->approve_attachment( $image ) ) {
                $approved[] = 'success';
                continue;
            }
            $approved[] = 'error';
        }

        if ( ! in_array( 'error', $approved, true ) ) {
            $this->listings_api->remove_having_images_awaiting_approval_mark( $post );
            return 'success';
        }

        return 'error';
    }

    /**
     * @param array $result_codes An array of result codes from this action.
     *
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
     * @param string $code Result code.
     * @param int    $count Number of posts associated with the given result
     *                             code.
     *
     * @since 4.0.0
     */
    private function get_message( $code, $count ) {
        if ( 'success' === $code ) {
            /* translators: %d is the number of ads that were successfully enabled. */
            $message = _n( 'Images from %d ad were successfully approved.', 'Images from %d ads were successfully approved.', $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            /* translators: %d is the number of ads that couldnt be approved. */
            $message = _n( "Images from %d ad couldn't be approved.", "Images from %d ads couldn't be approved.", $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
