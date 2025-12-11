<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A replacement for the Author metabox that uses {@see AWPCP_UserSelector} to
 * render a users dropdown or autocomplete field.
 *
 * @since 4.0.0
 */
class AWPCP_ListingOwnerMetabox {

    /**
     * @var AWPCP_UserSelector
     */
    private $user_selector;

    /**
     * @var AWPCP_UsersCollection
     */
    private $users;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @since 4.0.0
     */
    public function __construct( $user_selector, $users, $roles ) {
        $this->user_selector = $user_selector;
        $this->users         = $users;
        $this->roles         = $roles;
    }

    /**
     * @since 4.0.0
     */
    public function render( $post ) {
        if ( $this->roles->current_user_is_moderator() ) {
            $this->render_user_selector( $post );
            return;
        }

        $this->render_owner_name( $post );
    }

    /**
     * @since 4.0.0
     */
    private function render_user_selector( $post ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->user_selector->render(
            [
                'selected'                      => (int) $post->post_author,
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'label'                         => __( 'Ad Owner', 'another-wordpress-classifieds-plugin' ),
                'label_class'                   => 'screen-reader-text',
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'default'                       => __( 'Please select a user', 'another-wordpress-classifieds-plugin' ),
                'id'                            => 'post_author_override',
                'name'                          => 'post_author_override',
                'class'                         => array( 'awpcp-user-selector' ),
                'include_selected_user_only'    => false,
                'include_full_user_information' => false,
            ]
        );
    }

    /**
     * @since 4.0.0
     */
    private function render_owner_name( $post ) {
        if ( ! $post->post_author ) {
            return;
        }

        $owner_data = $this->users->get( $post->post_author, [ 'public_name' ] );

        if ( ! $owner_data ) {
            return;
        }

        echo esc_html( $owner_data->public_name );
    }
}
