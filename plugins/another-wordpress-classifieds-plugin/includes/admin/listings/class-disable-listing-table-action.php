<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable Listing row action for Listings.
 */
class AWPCP_DisableListingTableAction implements
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
        return $this->listing_renderer->is_public( $post );
    }

    /**
     * @since 4.0.0
     */
    public function get_icon_class( $post ) {
        return 'fa fa-times';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Disable', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'disable',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        if ( $this->listing_renderer->is_disabled( $post ) ) {
            return 'already-disabled';
        }

        if ( $this->listings_logic->disable_listing( $post ) ) {
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
            /* translators: %d is the number of ads that were successfully disabled. */
            $message = _n( '%d ad was successfully disabled.', '%d ads were successfully disabled.', $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'already-disabled' === $code ) {
            /* translators: %d is the number of ads that were already disabled. */
            $message = _n( '%d ad was already disabled.', '%d ads were already disabled.', $count, 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $count );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
