<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_AdminMenuBuilder {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var AWPCP_Router
     */
    private $router;

    public function __construct( $listing_post_type, $router ) {
        $this->listing_post_type = $listing_post_type;
        $this->router            = $router;
    }

    public function build_menu() {
        $routes = $this->router->get_routes();

        foreach ( $routes->get_admin_pages() as $admin_page ) {
            uasort( $admin_page->subpages, function ( $a, $b ) {
                return $a->priority - $b->priority;
            } );

            if ( isset( $admin_page->handler ) ) {
                $this->register_admin_page( $admin_page );
            }

            foreach ( $admin_page->subpages as $subpage ) {
                if ( current_user_can( $subpage->capability ) ) {
                    $this->register_subpage( $admin_page, $subpage );
                }
            }
        }

        // allow plugins to define additional sub menu entries
        do_action('awpcp_admin_add_submenu_page', 'awpcp.php', awpcp_admin_capability() );

        // allow plugins to define additional menu entries
        do_action('awpcp_add_menu_page');

        // Allow plugins to define additional user panel sub menu entries.
        do_action( 'awpcp_panel_add_submenu_page', "edit.php?post_type={$this->listing_post_type}", awpcp_user_capability() );
    }

    public function register_subpage( $admin_page, $subpage ) {
        switch ( $subpage->type ) {
            case 'users-page':
                $this->register_users_page( $subpage );
                break;

            case 'custom-link':
                $this->register_custom_link( $admin_page->slug, $subpage );
                break;

            default:
                $this->register_admin_subpage( $admin_page->slug, $subpage );
                break;
        }
    }

    public function register_admin_page( $admin_page ) {
        $hook = add_menu_page(
            $admin_page->title,
            $admin_page->menu_title,
            $admin_page->capability,
            $admin_page->slug,
            array( $this->router, 'on_admin_dispatch' ),
            $admin_page->menu_icon,
            $admin_page->position
        );

        add_action( "load-{$hook}", array( $this->router, 'on_admin_load' ) );
        return $hook;
    }

    public function register_admin_subpage( $parent_menu, $subpage ) {
        $hook = add_submenu_page( $parent_menu, $subpage->title, $subpage->menu_title, $subpage->capability, $subpage->slug, array( $this->router, 'on_admin_dispatch' ) );

        $this->add_submenu_class( $parent_menu, $subpage->slug );
        add_action( "load-{$hook}", array( $this->router, 'on_admin_load' ) );

        return $hook;
    }

    /**
     * @since 4.0.0
     */
    private function add_submenu_class( $parent_menu, $menu_slug ) {
        global $submenu;

        if ( ! isset( $submenu[ $parent_menu ] ) ) {
            return;
        }

        foreach ( $submenu[ $parent_menu ] as $index => $submenu_item ) {
            if ( $submenu_item[2] !== $menu_slug ) {
                continue;
            }

            $submenu[ $parent_menu ][ $index ][4] = "$menu_slug-submenu-item";
        }
    }

    public function register_users_page( $subpage ) {
        return add_users_page( $subpage->title, $subpage->menu_title, $subpage->capability, $subpage->slug, array( $this->router, 'on_admin_dispatch' ) );
    }

    public function register_custom_link( $parent_menu, $custom_link ) {
        global $submenu;
        $submenu[ $parent_menu ][] = array( $custom_link->menu_title, $custom_link->capability, $custom_link->url );
    }

    /**
     * Combine submenus from post type and awpcp.php
     * together and asign it to awpcp.php
     * This is here for reverse compatibility.
     *
     * @since 4.1
     */
    public function admin_menu_combine() {
        global $submenu;

        $cpt_menu   = 'edit.php?post_type=' . $this->listing_post_type;
        $admin_menu = 'awpcp.php';

        if ( isset( $submenu[ $cpt_menu ] ) && isset( $submenu[ $admin_menu ] ) ) {
            array_splice( $submenu[ $admin_menu ], 1, 0, $submenu[ $cpt_menu ] );
        }
    }
}
