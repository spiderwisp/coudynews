<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Make Standard listing table action.
 */
class AWPCP_MakeStandardListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

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
     * @param object $roles             An instance of Roles and Capabilities.
     * @param object $wordpress         An instance of WordPress.
     */
    public function __construct( $roles, $wordpress ) {
        $this->roles            = $roles;
        $this->wordpress        = $wordpress;
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
        return 'far fa-square';
    }

    /**
     * @since 4.0.0
     */
    public function get_title() {
        return _x( 'Make Standard', 'listing row action', 'another-wordpress-classifieds-plugin' );
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
            'action' => 'make-standard',
            'ids'    => $post->ID,
        );

        return add_query_arg( $params, $current_url );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function process_item( $post ) {
        if ( $this->wordpress->update_post_meta( $post->ID, '_awpcp_is_featured', 0 ) ) {
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
            $message = _n( 'Ad marked as standard.', '{count} ads marked as standard.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_success_message( $message );
        }

        if ( 'error' === $code ) {
            $message = _n( 'An error occurred trying to mark an ad as standard.', 'An error occurred trying to mark {count} ads as standard.', $count, 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{count}', $count, $message );

            return awpcp_render_dismissible_error_message( $message );
        }

        return '';
    }
}
