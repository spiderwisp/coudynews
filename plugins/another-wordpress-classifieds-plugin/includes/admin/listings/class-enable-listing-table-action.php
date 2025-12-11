<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enable Listing row action for Listings.
 */
class AWPCP_EnableListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

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
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function should_show_action_for( $post ) {
        return $this->listing_renderer->is_disabled( $post ) || $this->listing_renderer->is_pending_approval( $post );
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'fa fa-check';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Enable', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'enable',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        if ( $this->listing_renderer->is_public( $post ) ) {
            return 'already-enabled';
        }

        if ( $this->listings_logic->enable_listing( $post ) ) {
            return 'success';
        }

        return 'error';
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
            /* translators: %d is the number of ads that were successfully enabled. */
            $message = _n( '%d ad was successfully enabled.', '%d ads were successfully enabled.', $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'already-enabled' === $code ) {
            /* translators: %d is the number of ads that were already enabled. */
            $message = _n( '%d ad was already enabled.', '%d ads were already enabled.', $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
