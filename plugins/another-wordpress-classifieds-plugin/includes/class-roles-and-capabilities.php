<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_RolesAndCapabilities {

    private $settings;

    private $stop_nesting = false;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function setup_roles_capabilities() {
        $this->create_moderator_role();

        $administrator_roles = $this->get_administrator_roles_names();
        $subscriber_roles    = $this->get_subscriber_roles_names();
        $current_user        = wp_get_current_user();

        array_walk( $administrator_roles, array( $this, 'add_administrator_capabilities_to_role' ) );
        array_walk( $subscriber_roles, array( $this, 'add_subscriber_capabilities_to_role' ) );

        if ( $current_user instanceof WP_User ) {
            // Force WordPress to load role capabilities. Otherwise the current user won't have AWPCP
            // capabilities during this request.
            $current_user->get_role_caps();
        }
    }

    public function get_administrator_roles_names() {
        $selected_roles = $this->settings->get_option( 'awpcpadminaccesslevel' );
        return $this->get_administrator_roles_names_from_string( $selected_roles );
    }

    public function get_administrator_roles_names_from_string( $string ) {
        $configured_roles = explode( ',', $string );

        if ( in_array( 'editor', $configured_roles ) ) {
            $roles_names = array( 'administrator', 'editor' );
        } else {
            $roles_names = array( 'administrator' );
        }

        return $roles_names;
    }

    /**
     * @since 4.0.0
     */
    public function get_subscriber_roles_names() {
        $standard_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

        return array_diff( $standard_roles, $this->get_administrator_roles_names() );
    }

    /**
     * @param string $role_name     The name of the role to modify.
     */
    public function add_administrator_capabilities_to_role( $role_name ) {
        return $this->add_capabilities_to_role( get_role( $role_name ), $this->get_administrator_capabilities() );
    }

    public function get_administrator_capabilities() {
        return array_merge( array( $this->get_administrator_capability() ), $this->get_moderator_capabilities() );
    }

    private function get_administrator_capability() {
        return 'manage_awpcp';
    }

    /**
     * @since 4.0.0
     */
    public function get_moderator_capabilities() {
        $capabilities = array(
            $this->get_moderator_capability(),
        );

        return array_merge( $capabilities, $this->get_subscriber_capabilities() );
    }

    /**
     * @since 4.0.0
     */
    public function get_moderator_capability() {
        $cap = 'edit_others_awpcp_classified_ads';
        if ( ! $this->stop_nesting && current_user_can( 'administrator' ) && ! current_user_can( $cap ) ) {
            $this->stop_nesting = true;
            $this->setup_roles_capabilities();
        }
        return $cap;
    }

    private function add_capabilities_to_role( $role, $capabilities ) {
        return array_map( array( $role, 'add_cap' ), $capabilities );
    }

    public function remove_administrator_capabilities_from_role( $role_name ) {
        $this->remove_capabilities_from_role( get_role( $role_name ), $this->get_administrator_capabilities() );
    }

    /**
     * @since 4.0.0
     */
    public function remove_capabilities_from_role( $role, $capabilities ) {
        return array_map( array( $role, 'remove_cap' ), $capabilities );
    }

    /**
     * @since 4.0.0
     */
    public function remove_capabilities( $capabilities ) {
        $roles = wp_roles();

        if ( ! is_a( $roles, 'WP_Roles' ) ) {
            return;
        }

        if ( ! is_array( $roles->role_objects ) ) {
            return;
        }

        foreach ( $roles->role_objects as $role ) {
            $this->remove_capabilities_from_role( $role, $capabilities );
        }
    }

    public function add_subscriber_capabilities_to_role( $role_name ) {
        return $this->add_capabilities_to_role( get_role( $role_name ), $this->get_subscriber_capabilities() );
    }

    public function get_subscriber_capabilities() {
        return array(
            $this->get_subscriber_capability(),
        );
    }

    /**
     * @since 4.0.0
     */
    public function get_subscriber_capability() {
        return 'edit_awpcp_classified_ads';
    }

    /**
     * @since 4.0.0
     */
    public function remove_subscriber_capabilities_from_role( $role_name ) {
        $this->remove_capabilities_from_role( get_role( $role_name ), $this->get_subscriber_capabilities() );
    }

    /**
     * @since 4.0.0
     */
    public function get_dashboard_capability() {
        return $this->get_moderator_capability();
    }

    private function create_moderator_role() {
        $role = get_role( 'awpcp-moderator' );

        $capabilities = array_merge( array( 'read' ), $this->get_moderator_capabilities() );
        $capabilities = array_combine( $capabilities, array_pad( array(), count( $capabilities ), true ) );

        if ( is_null( $role ) ) {
            $role = add_role( 'awpcp-moderator', __( 'Classifieds Moderator', 'another-wordpress-classifieds-plugin' ), $capabilities );
        } else {
            $this->add_capabilities_to_role( $role, array_keys( $capabilities ) );
        }
    }

    public function remove_moderator_role() {
        if ( get_role( 'awpcp-moderator' ) ) {
            return remove_role( 'awpcp-moderator' );
        } else {
            return false;
        }
    }

    public function current_user_is_administrator() {
        return $this->current_user_can( $this->get_administrator_capability() );
    }

    private function current_user_can( $capabilities ) {
        // If the current user is being setup before the "init" action has fired,
        // strange (and difficult to debug) role/capability issues will occur.
        if ( ! did_action( 'set_current_user' ) ) {
            _doing_it_wrong( __FUNCTION__, 'Trying to call current_user_is_*() before the current user has been set.', '3.3.1' );
        }

        return $this->user_can( wp_get_current_user(), $capabilities );
    }

    private function user_can( $user, $capabilities ) {
        if ( ! is_object( $user ) || empty( $capabilities ) ) {
            return false;
        }

        if ( ! is_array( $capabilities ) ) {
            $capabilities = array( $capabilities );
        }

        foreach ( $capabilities as $capability ) {
            if ( ! user_can( $user, $capability ) ) {
                return false;
            }
        }

        return true;
    }

    public function current_user_is_moderator() {
        return $this->current_user_can( $this->get_moderator_capability() );
    }

    /**
     * @since 4.0.7
     */
    public function current_user_is_subscriber() {
        return $this->current_user_can( $this->get_subscriber_capability() );
    }

    public function user_is_administrator( $user_id ) {
        return $this->user_can( get_userdata( $user_id ), $this->get_administrator_capability() );
    }
}
