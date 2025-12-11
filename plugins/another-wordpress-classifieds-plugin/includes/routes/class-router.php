<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.5.4
 */
function awpcp_router() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_Router();
    }

    return $instance;
}

/**
 * @since 3.5.4
 */
class AWPCP_Router {

    private $template_renderer;
    private $request;

    private $current_page = null;
    private $request_handler = null;

    public $routes;

    public function __construct() {
        $this->routes            = new AWPCP_Routes();
        $this->template_renderer = awpcp_template_renderer();
        $this->request           = awpcp_request();
    }

    public function get_routes() {
        return $this->routes;
    }

    public function configure_routes() {
        // this action needs to be executed before building the admin menu
        // and configuring rewrite rules and handlers for shortcodes and ajax actions.
        do_action( 'awpcp-configure-routes', $this->routes );
    }

    public function register_ajax_request_handler( $request_handler ) {
        foreach ( $this->routes->get_anonymous_ajax_actions() as $action ) {
            add_action( "wp_ajax_nopriv_awpcp-{$action->name}", array( $request_handler, 'handle_anonymous_ajax_request' ) );
        }

        foreach ( $this->routes->get_private_ajax_actions() as $action ) {
            add_action( "wp_ajax_awpcp-{$action->name}", array( $request_handler, 'handle_private_ajax_request' ) );
        }
    }

    public function on_admin_init() {
        $this->current_page = $this->get_active_admin_page();
        $this->request_handler = $this->get_request_handler( $this->current_page );

        $this->init_admin_page( $this->current_page, $this->request_handler );
    }

    public function init_admin_page( $admin_page, $request_handler ) {
        if ( ! is_object( $admin_page ) ) {
            return;
        }

        if ( method_exists( $request_handler, 'on_admin_init' ) ) {
            $request_handler->on_admin_init();
        }

        do_action( 'awpcp-admin-init-' . $admin_page->slug );
    }

    public function on_admin_load() {
        $this->load_admin_page( $this->current_page, $this->request_handler );
    }

    private function get_active_admin_page() {
        return $this->routes->get_admin_page( get_admin_page_parent(), $GLOBALS['plugin_page'] );
    }

    private function get_request_handler( $page ) {
        if ( is_null( $page ) ) {
            return null;
        }

        if ( isset( $page->sections ) ) {
            $section_handler = $this->get_request_handler_from_page_sections( $page );
        } else {
            $section_handler = null;
        }

        return $this->pick_request_handler( array( $section_handler, $page->handler ) );
    }

    private function get_request_handler_from_page_sections( $page ) {
        foreach ( (array) $page->sections as $section_slug => $section ) {
            $param_value = $this->request->param( $section->param, false );

            if ( $param_value === false ) {
                continue;
            }

            if ( ! is_null( $section->value ) && $param_value != $section->value ) {
                continue;
            }

            return $section->handler;
        }

        return null;
    }

    private function pick_request_handler( $request_handlers ) {
        foreach ( $request_handlers as $constructor_function ) {
            if ( ! is_callable( $constructor_function ) ) {
                continue;
            }

            $request_handler = call_user_func( $constructor_function );

            if ( ! is_null( $request_handler ) ) {
                return $request_handler;
            }
        }

        return null;
    }

    public function load_admin_page( $admin_page, $request_handler ) {
        if ( method_exists( $request_handler, 'on_load' ) ) {
            $request_handler->on_load();
        }

        do_action( "awpcp_admin_load_{$admin_page->slug}" );
    }

    public function serve_admin_page( $route ) {
        $route = wp_parse_args( $route, array( 'parent' => null, 'page' => null, 'section' => null ) );

        $admin_page = $this->routes->get_admin_page( $route['parent'], $route['page'] );
        $request_handler = $this->get_request_handler_for_section( $admin_page, $route['section'] );

        $this->load_admin_page( $admin_page, $request_handler );
        $this->handle_admin_page( $admin_page, $request_handler );
    }

    private function get_request_handler_for_section( $page, $section_slug ) {
        if ( isset( $page->sections[ $section_slug ] ) ) {
            $section_handler = $page->sections[ $section_slug ]->handler;
        } else {
            $section_handler = null;
        }

        return $this->pick_request_handler( array( $section_handler, $page->handler ) );
    }

    public function on_admin_dispatch() {
        $this->handle_admin_page( $this->current_page, $this->request_handler );
    }

    private function handle_admin_page( $admin_page, $request_handler ) {
        if ( method_exists( $request_handler, 'enqueue_scripts' ) ) {
            $request_handler->enqueue_scripts();
        }

        if ( method_exists( $request_handler, 'get_display_options' ) ) {
            $admin_page->options = $request_handler->get_display_options();
        }

        if ( method_exists( $request_handler, 'dispatch' ) ) {
            $page_content = $request_handler->dispatch();
        } else {
            $page_content = false;
        }

        if ( $page_content ) {
            $this->render_admin_page( $admin_page, $page_content );
        }
    }

    private function render_admin_page( $admin_page, $content ) {
        $template = AWPCP_DIR . '/admin/templates/admin-page.tpl.php';

        $params = array(
            'current_page' => $this->current_page,
            'page_slug' => $admin_page->slug,
            'page_title' => $this->title(),
            'should_show_title' => true,
            'show_sidebar' => $this->show_sidebar( $this->current_page ),
            'content' => $content,
            'echo'              => true,
        );

        $this->template_renderer->render_template( $template, $params );
    }

    public function redirect( $redirect ) {
        $this->serve_admin_page( $redirect );
        return false;
    }

    /* Admin Page template expects user class to have the following methods defined */

    private function title() {
        return $this->current_page->title;
    }

    private function show_sidebar( $current_page ) {
        if ( isset( $current_page->options['show_sidebar'] ) ) {
            return $current_page->options['show_sidebar'];
        } else {
            return awpcp_current_user_is_admin();
        }
    }
}
