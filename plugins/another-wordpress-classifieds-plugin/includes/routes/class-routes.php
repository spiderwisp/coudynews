<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Routes {

    private $admin_pages = array();
    private $ajax_actions = array( 'private' => array(), 'anonymous' => array() );

    public function add_admin_page( $menu_title, $page_title, $slug, $handler, $capability, $menu_icon = null, $position = 27 ) {
        $admin_page = $this->get_or_create_admin_page( $slug );

        $admin_page->menu_title = $menu_title;
        $admin_page->title = $page_title;
        $admin_page->slug = $slug;
        $admin_page->handler = $handler;
        $admin_page->capability = $capability;
        $admin_page->menu_icon = $menu_icon;
        $admin_page->position   = $position;

        return $slug;
    }

    private function get_or_create_admin_page( $slug ) {
        if ( ! isset( $this->admin_pages[ $slug ] ) ) {
            $this->admin_pages[ $slug ] = new stdClass();
            $this->admin_pages[ $slug ]->slug = $slug;
            $this->admin_pages[ $slug ]->subpages = array();
        }

        return $this->admin_pages[ $slug ];
    }

    public function add_admin_subpage( $parent_page, $menu_title, $page_title, $slug, $handler = null, $capability = 'install_plugins', $priority = 10 ) {
        if ( $parent_page === 'edit.php?post_type=' . awpcp()->container['listing_post_type'] ) {
            // Add any submenu for post type to admin menu.
            $parent_page = 'awpcp.php';
        }

        $admin_page = $this->get_or_create_admin_page( $parent_page );

        $admin_page->subpages[ $slug ] = $this->create_admin_subpage(
            $menu_title,
            $page_title,
            $slug,
            $handler,
            $capability,
            $priority
        );

        return "$parent_page::$slug";
    }

    private function create_admin_subpage( $menu_title, $page_title, $slug, $handler = null, $capability = 'install_plugins', $priority = 10, $type = 'subpage' ) {
        $subpage = new stdClass();

        $subpage->menu_title = $menu_title;
        $subpage->title = $page_title;
        $subpage->slug = $slug;
        $subpage->handler = $handler;
        $subpage->capability = $capability;
        $subpage->sections = array();
        $subpage->priority = $priority;
        $subpage->type = $type;

        return $subpage;
    }

    public function add_admin_section( $page, $section_slug, $section_param, $param_value, $handler = null ) {
        $subpage = $this->get_admin_subpage( $page );

        if ( ! is_null( $subpage ) ) {
            $section = new stdClass();

            $section->param = $section_param;
            $section->value = $param_value;
            $section->slug = $section_slug;
            $section->handler = $handler;

            $subpage->sections[ $section_slug ] = $section;
        }

        return is_null( $subpage ) ? false : true;
    }

    private function get_admin_subpage( $ref ) {
        $parts = explode( '::', $ref );

        if ( count( $parts ) !== 2 ) {
            return null;
        }

        $parent_page = $this->get_or_create_admin_page( $parts[0] );

        if ( ! isset( $parent_page->subpages[ $parts[1] ] ) ) {
            return null;
        }

        return $parent_page->subpages[ $parts[1] ];
    }

    public function add_admin_users_page( $menu_title, $page_title, $slug, $handler = null, $capability = 'install_plugins', $priority = 10 ) {
        $parent = 'profile.php';

        if ( current_user_can( 'edit_users' ) ) {
            $parent = 'users.php';
        }

        return $this->add_admin_subpage(
            $parent,
            $menu_title,
            $page_title,
            $slug,
            $handler,
            $capability,
            $priority
        );
    }

    public function add_admin_custom_link( $parent_page, $menu_title, $slug, $capability, $url, $priority ) {
        $custom_page = new stdClass();

        $custom_page->menu_title = $menu_title;
        $custom_page->slug = $slug;
        $custom_page->capability = $capability;
        $custom_page->priority = $priority;
        $custom_page->url = $url;
        $custom_page->type = 'custom-link';

        $admin_page = $this->get_or_create_admin_page( $parent_page );
        $admin_page->subpages[ $slug ] = $custom_page;

        return "custom:$parent_page::$slug::$url";
    }

    public function add_anonymous_ajax_action( $action_name, $action_handler ) {
        return $this->add_ajax_action( 'anonymous', $action_name, $action_handler );
    }

    private function add_ajax_action( $type, $action_name, $action_handler ) {
        $action = new stdClass();

        $action->name = $action_name;
        $action->handler = $action_handler;

        $this->ajax_actions[ $type ][ $action->name ] = $action;
    }

    public function add_private_ajax_action( $action_name, $action_handler ) {
        return $this->add_ajax_action( 'private', $action_name, $action_handler );
    }

    public function get_anonymous_ajax_actions() {
        return $this->ajax_actions['anonymous'];
    }

    public function get_private_ajax_actions() {
        return $this->ajax_actions['private'];
    }

    public function get_admin_pages() {
        return $this->admin_pages;
    }

    public function get_admin_page( $parent_page, $subpage ) {
        if ( isset( $this->admin_pages[ $parent_page ] ) && $subpage === $parent_page ) {
            return $this->admin_pages[ $parent_page ];
        }

        if ( isset( $this->admin_pages[ $parent_page ]->subpages[ $subpage ] ) ) {
            return $this->admin_pages[ $parent_page ]->subpages[ $subpage ];
        }

        return null;
    }
}
