<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mark as SPAM listing admin action.
 */
class AWPCP_MarkAsSPAMListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    /**
     * @var object
     */
    private $spam_submitter;

    /**
     * @var object
     */
    private $listings_logic;

    /**
     * @var object
     */
    private $roles;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @since 4.0.0
     *
     * @param object $spam_submitter    Akismet API wrapper.
     * @param object $listings_logic    An instance of Listings API.
     * @param object $roles             An instance of Roles and Capabilities.
     * @param object $wordpress         An instance of WordPress.
     */
    public function __construct( $spam_submitter, $listings_logic, $roles, $wordpress ) {
        $this->spam_submitter = $spam_submitter;
        $this->listings_logic = $listings_logic;
        $this->roles          = $roles;
        $this->wordpress      = $wordpress;
    }

    /**
     * @since 4.0.0
     */
    public function is_needed() {
        return $this->roles->current_user_is_moderator();
    }

    /**
     * @since 4.1.6
     */
    public function should_show_as_bulk_action() {
        return function_exists( 'akismet_init' );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        if ( ! function_exists( 'akismet_init' ) ) {
            return false;
        }

        if ( ! $this->wordpress->get_option( 'wordpress_api_key' ) ) {
            return false;
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'fa fa-flag';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'SPAM', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'spam',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        if ( ! $this->listings_logic->delete_listing( $post ) ) {
            return 'error';
        }

        if ( ! $this->spam_submitter->submit( $post ) ) {
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
            $message = _n( 'The ad was marked as SPAM and removed.', '{count} ads were marked as SPAM and removed.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'There was an error trying to mark the ad as SPAM.', 'There was an error trying to mark {count} ads as SPAM.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
