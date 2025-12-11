<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once AWPCP_DIR . '/admin/admin-panel-users.php';

function awpcp_admin_panel() {
    return new AWPCP_AdminPanel(
        awpcp_upgrade_tasks_manager()
    );
}

class AWPCP_AdminPanel {

    private $upgrade_tasks;

    public $title;

    public $menu;

    public $users;

    public function __construct( $upgrade_tasks ) {
        $this->upgrade_tasks = $upgrade_tasks;

        $this->title = awpcp_admin_page_title();
        $this->menu = _x('Classifieds', 'awpcp admin menu', 'another-wordpress-classifieds-plugin');

        // not a page, but an extension to the Users table
        $this->users = new AWPCP_AdminUsers();

        add_action('wp_ajax_disable-quick-start-guide-notice', array($this, 'disable_quick_start_guide_notice'));
        add_action('wp_ajax_disable-widget-modification-notice', array($this, 'disable_widget_modification_notice'));

        add_action('admin_init', array($this, 'init'));
        add_action( 'admin_init', array( awpcp()->router, 'on_admin_init' ) );
        add_action('admin_enqueue_scripts', array($this, 'scripts'));

        $admin_menu_builder = new AWPCP_AdminMenuBuilder( awpcp()->container['listing_post_type'], awpcp()->router );
        add_action( 'admin_menu', array( $admin_menu_builder, 'build_menu' ) );
        add_action( 'admin_menu', array( $admin_menu_builder, 'admin_menu_combine' ), 20 );
        add_action( 'admin_footer', array( $this, 'maybe_highlight_menu' ) );

        add_action('admin_notices', array($this, 'notices'));
        add_action( 'awpcp-admin-notices', array( $this, 'check_duplicate_page_names' ) );

        add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ),10, 2 );

        // make sure AWPCP admins (WP Administrators and/or Editors) can edit settings
        add_filter('option_page_capability_awpcp-options', 'awpcp_admin_capability');

        // hook filter to output Admin panel sidebar. To remove the sidebar
        // just remove this action
        add_filter('awpcp-admin-sidebar', 'awpcp_admin_sidebar_output', 10, 2);

        add_action( 'admin_init', array( awpcp_privacy_policy_content(), 'add_privacy_policy_content' ) );
    }

    public function configure_routes( $router ) {
        if ( $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin', 'blocking' => true ) ) ) {
            $this->configure_routes_for_blocking_manual_upgrades( 'awpcp-admin-upgrade', $router );
        } elseif ( $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin' ) ) ) {
            $this->configure_routes_for_non_blocking_manual_upgrades( 'awpcp.php', $router );
        } else {
            $this->configure_regular_routes( 'awpcp.php', $router );
        }
    }

    private function configure_routes_for_blocking_manual_upgrades( $parent_menu, $router ) {
        $parent_page = $this->add_main_classifieds_admin_page(
            __( 'Manual Upgrade', 'another-wordpress-classifieds-plugin' ),
            $parent_menu,
            'awpcp_manual_upgrade_admin_page',
            $router
        );

        $this->add_manual_upgrade_admin_page( $parent_page, __( 'Classifieds', 'another-wordpress-classifieds-plugin' ), $parent_menu, $router );
    }

    private function add_main_classifieds_admin_page( $page_title, $parent_menu, $handler_constructor, $router ) {
        return $router->add_admin_page(
            __( 'Classified Ads', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( $page_title ),
            $parent_menu,
            $handler_constructor,
            awpcp_roles_and_capabilities()->get_moderator_capability(),
            'none',
            26
        );
    }

    private function configure_routes_for_non_blocking_manual_upgrades( $parent_menu, $router ) {
        $parent_page = $this->configure_route_for_main_classifieds_admin_page( $parent_menu, $router );
        $this->configure_route_for_manual_upgrade_admin_page( $parent_page, $router );
        $this->configure_routes_for_admin_subpages( $parent_page, $router );
    }

    private function configure_route_for_main_classifieds_admin_page( $parent_menu, $router ) {
        return $this->add_main_classifieds_admin_page(
            'AWPCP',
            $parent_menu,
            'awpcp_main_classifieds_admin_page',
            $router
        );
    }

    private function configure_route_for_manual_upgrade_admin_page( $parent_page, $router ) {
        $this->add_manual_upgrade_admin_page(
            $parent_page,
            __( 'Manual Upgrade', 'another-wordpress-classifieds-plugin' ),
            'awpcp-admin-upgrade',
            $router
        );
    }

    private function add_manual_upgrade_admin_page( $parent_page, $menu_title, $menu_slug, $router ) {
        $router->add_admin_subpage(
            $parent_page,
            $menu_title,
            awpcp_admin_page_title( __( 'Manual Upgrade', 'another-wordpress-classifieds-plugin' ) ),
            $menu_slug,
            'awpcp_manual_upgrade_admin_page',
            awpcp_admin_capability(),
            0
        );
    }

    private function configure_routes_for_admin_subpages( $parent_page, $router ) {
        $admin_capability = awpcp_admin_capability();
        $moderator_capability = awpcp_roles_and_capabilities()->get_moderator_capability();

        add_submenu_page( $parent_page, 'AWPCP', __( 'Dashboard', 'another-wordpress-classifieds-plugin' ), $admin_capability, $parent_page, $router );

        $post_type    = awpcp()->container['listing_post_type'];
        add_submenu_page( $parent_page, 'AWPCP', __( 'Classifieds', 'another-wordpress-classifieds-plugin' ), $moderator_capability, 'edit.php?post_type=' . $post_type );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Settings', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Settings', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-admin-settings',
            'awpcp_settings_admin_page',
            $admin_capability,
            10
        );

        // TODO: This doesn't seem to be needed anymore.
        $router->add_private_ajax_action( 'listings-delete-ad', 'awpcp_delete_listing_ajax_handler' );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Categories', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Manage Categories', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-admin-categories',
            'awpcp_categories_admin_page',
            $admin_capability,
            40
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-categories',
            'create-category',
            'awpcp-action',
            'create-category',
            'awpcp_create_category_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-categories',
            'update-category',
            'awpcp-action',
            'update-category',
            'awpcp_update_category_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-categories',
            'delete-category',
            'awpcp-action',
            'delete-category',
            'awpcp_delete_category_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-categories',
            'move-multiple-categories',
            'awpcp-move-multiple-categories',
            null,
            'awpcp_move_categories_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-categories',
            'delete-multiple-categories',
            'awpcp-delete-multiple-categories',
            null,
            'awpcp_delete_categories_admin_page'
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Form Fields', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Form Fields', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-form-fields',
            'awpcp_form_fields_admin_page',
            $admin_capability,
            50
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Credit Plans', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Manage Credit Plans', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-admin-credit-plans',
            'awpcp_credit_plans_admin_page',
            $admin_capability,
            60
        );

        $router->add_admin_custom_link(
            $parent_page,
            __( 'Manage Credit', 'another-wordpress-classifieds-plugin' ),
            'awpcp-manage-credits',
            $admin_capability,
            $this->get_manage_credits_section_url(),
            70
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Fees', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Manage Listings Fees', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-admin-fees',
            'awpcp_fees_admin_page',
            $admin_capability,
            80
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-fees',
            'add-fee',
            'awpcp-action',
            'add-fee',
            'awpcp_fee_details_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-admin-fees',
            'edit-fee',
            'awpcp-action',
            'edit-fee',
            'awpcp_fee_details_admin_page'
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Import & Export', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Import & Export', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-tools',
            function () {
                return awpcp()->container['ToolsAdminPage'];
            },
            $admin_capability,
            8000
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'import-settings',
            'awpcp-view',
            'import-settings',
            'awpcp_import_settings_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'export-settings',
            'awpcp-view',
            'export-settings',
            'awpcp_export_settings_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'import-listings',
            'awpcp-view',
            'import-listings',
            'awpcp_import_listings_admin_page'
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'supported-csv-headers',
            'awpcp-view',
            'supported-csv-headers',
            function () {
                return awpcp()->container['SupportedCSVHeadersAdminPage'];
            }
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'example-csv-file',
            'awpcp-view',
            'example-csv-file',
            function () {
                return awpcp()->container['ExampleCSVFileAdminPage'];
            }
        );

        $router->add_admin_section(
            'awpcp.php::awpcp-tools',
            'export-listings',
            'awpcp-view',
            'export-listings',
            function () {
                return awpcp()->container['ExportListingsAdminPage'];
            }
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Debug', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Debug Information', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-debug',
            'awpcp_debug_admin_page',
            $admin_capability,
            9000
        );

        $router->add_admin_subpage(
            $parent_page,
            __( 'Uninstall', 'another-wordpress-classifieds-plugin' ),
            awpcp_admin_page_title( __( 'Uninstall', 'another-wordpress-classifieds-plugin' ) ),
            'awpcp-admin-uninstall',
            'awpcp_uninstall_admin_page',
            $admin_capability,
            9900
        );

        $quick_view_admin_page_slug = 'awpcp-admin-quick-view-listing';

        $page = awpcp_get_var( array( 'param' => 'page' ) );
        if ( $page === $quick_view_admin_page_slug ) {
            $this->maybe_redirect_quick_page();
        }

        $renew_listing_subscriber_admin_page_slug = 'awpcp-admin-renew-listing';

        if ( $page === $renew_listing_subscriber_admin_page_slug ) {
            $router->add_admin_subpage(
                'edit.php?post_type=awpcp_listing',
                __( 'Renew Ad', 'another-wordpress-classifieds-plugin' ),
                awpcp_admin_page_title( __( 'Renew Ad', 'another-wordpress-classifieds-plugin' ) ),
                $renew_listing_subscriber_admin_page_slug,
                function () {
                    return awpcp()->container['SubscriberRenewListingAdminPage'];
                },
                awpcp_roles_and_capabilities()->get_dashboard_capability()
            );
        }
    }

    /**
     * Reverse compatibility - redirect to view front-end.
     *
     * @since 4.01
     */
    private function maybe_redirect_quick_page() {
        $listing_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
        if ( $listing_id ) {
            wp_safe_redirect( get_permalink( $listing_id ) );
            exit();
        }
    }

    private function configure_regular_routes( $parent_menu, $router ) {
        $parent_page = $this->configure_route_for_main_classifieds_admin_page( $parent_menu, $router );

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'awpcp-admin-upgrade' ) { // phpcs:ignore WordPress.Security.NonceVerification
            $this->configure_route_for_manual_upgrade_admin_page( $parent_page, $router );
        }

        $this->configure_routes_for_admin_subpages( $parent_page, $router );
    }

    public function notices() {
        if ( ! awpcp_current_user_is_admin() ) {
            return;
        }

        if ( awpcp_get_var( array( 'param' => 'page' ) ) === 'awpcp-admin-upgrade' ) {
            return;
        }

        if ( $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin', 'blocking' => true ) ) ) {
            return $this->load_notice_for_blocking_manual_uprades();
        } elseif ( $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin' ) ) ) {
            return $this->load_notice_for_non_blocking_manual_uprades();
        }

        $show_quick_start_quide_notice = get_awpcp_option( 'show-quick-start-guide-notice' );
        $show_drip_autoresponder = get_awpcp_option( 'show-drip-autoresponder' );

        /**
         * Filters whether to show the quick start guide notice in the admin area.
         *
         * @since 4.3.5
         *
         * @param bool $show_quick_start_quide_notice Whether to show the quick start guide notice.
         */
        $show_quick_start_quide_notice = apply_filters( 'awpcp-show-quick-start-guide-notice', $show_quick_start_quide_notice );

        if ( $show_quick_start_quide_notice && is_awpcp_admin_page() && ! $show_drip_autoresponder ) {
            wp_enqueue_style( 'awpcp-admin-style' );

            include AWPCP_DIR . '/admin/templates/admin-quick-start-guide-notice.tpl.php';
        }

        if (get_awpcp_option('show-widget-modification-notice')) {
            include AWPCP_DIR . '/admin/templates/admin-widget-modification-notice.tpl.php';
        }

        if ( awpcp_get_var( array( 'param' => 'action' ) ) === 'awpcp-manage-credits' ) {
            $message = __( 'Use the Account Balance column on the table below to manage credit balance for users.', 'another-wordpress-classifieds-plugin' );

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_render_info_message( $message );
        }

        do_action( 'awpcp-admin-notices' );
    }

    private function load_notice_for_blocking_manual_uprades() {
        $message = $this->get_message_for_blocking_manual_upgrade_notice();

        return $this->load_notice_for_manual_upgrades( $message );
    }

    /**
     * @since 4.0.0
     */
    private function get_message_for_blocking_manual_upgrade_notice() {
        if ( $this->upgrade_tasks->is_upgrade_task_enabled( 'awpcp-store-listings-as-custom-post-types' ) ) {
            $message  = '<p>' . esc_html__( 'AWPCP features are currently disabled because the plugin needs you to perform a manual upgrade before continuing.', 'another-wordpress-classifieds-plugin' ) . '</p>';
            $message .= '<p><strong style="color: #CC0000">' . esc_html__( "The duration for this upgrade operation varies between several minutes and a few hours, depending on the size of your database, the current network conditions and the server's capabilities. Your users and you won't be able to use the Classified Admin pages or submit and explore ads on the frontend until the upgrade is complete.", 'another-wordpress-classifieds-plugin' ) . '</strong></p>';
            $message .= sprintf(
                /* translators: %1$s is the opening tag for the link to the page explaining how to downgrade to a previous version of the plugin, %2$s is the closing tag for the link. */
                '<p>' . esc_html__( 'If this is not a good time to go through the upgrade process, we recommend you to %1$sinstall the previous version again%2$s and plan to upgrade tonight or later this week when you have more time.', 'another-wordpress-classifieds-plugin' ) . '</p>',
                sprintf( '<a href="%s">', 'https://awpcp.com/forum/faq/how-to-downgrade-awpcp-4-0-to-something-earlier/' ),
                '</a>'
            );
            $message .= sprintf(
                /* translators: %1$s is the opening tag for the link to the upgrade page, %2$s is the closing tag for the link. */
                '<p>' . esc_html__( 'To upgrade, please %1$sgo to the Classifieds admin section%2$s or click the button below.', 'another-wordpress-classifieds-plugin' ) . '</p>',
                sprintf( '<a href="%s">', esc_url( awpcp_get_admin_upgrade_url() ) ),
                '</a>'
            );

            return $message;
        }

        return sprintf(
            /* translators: %1$s is the opening tag for the link to the upgrade page, %2$s is the closing tag for the link. */
            '<p>' . esc_html__( 'AWPCP features are currently disabled because the plugin needs you to perform a manual upgrade before continuing. Please %1$sgo to the Classifieds admin section to Upgrade%2$s or click the button below.', 'another-wordpress-classifieds-plugin' ) . '</p>',
            sprintf( '<a href="%s">', esc_url( awpcp_get_admin_upgrade_url() ) ),
            '</a>'
        );
    }

    private function load_notice_for_manual_upgrades( $message ) {
        wp_enqueue_style( 'awpcp-admin-style' );

        include AWPCP_DIR . '/admin/templates/admin-pending-manual-upgrade-notice.tpl.php';
    }

    private function load_notice_for_non_blocking_manual_uprades() {
        $message = sprintf(
            /* translators: %1$s is the opening tag for the link to the upgrade page, %2$s is the closing tag for the link. */
            '<p>' . esc_html__( 'AWPCP needs you to perform a manual upgrade to update the database schema and the information stored there. All plugin features will continue to work while the upgrade routines are executed. Please %1$sgo to the Classifieds admin section to Upgrade%2$s or click the button below.', 'another-wordpress-classifieds-plugin' ) . '</p>',
            sprintf( '<a href="%s">', esc_url( awpcp_get_admin_upgrade_url() ) ),
            '</a>'
        );

        return $this->load_notice_for_manual_upgrades( $message );
    }

    /**
     * Add settings link on plugins page
     *
     * @since  3.6.5
     *
     * @param array  $links
     * @param string $file
     */
    public function add_settings_link( $links, $file ) {
        if ( $this->upgrade_tasks->has_pending_tasks( array( 'context' => 'plugin', 'blocking' => true ) ) ) {
            return $links;
        }

        $settings_link = '<a href="' . admin_url( 'admin.php?page=awpcp-admin-settings' ) . '">' . esc_html__( 'Settings', 'another-wordpress-classifieds-plugin' ) . '</a>';

        if ( AWPCP_BASENAME === $file ) {
            array_unshift( $links, $settings_link );
        }

        return $links;
    }

    /**
     * Shows a notice if any of the AWPCP pages shares its name with the
     * dynamic page View Categories.
     *
     * If a page share its name with the View Categories page, that page
     * will become unreachable.
     *
     * @since 3.0.2
     */
    public function check_duplicate_page_names() {
        global $wpdb;

        $view_categories_option = 'view-categories-page-name';
        $view_categories = sanitize_title( awpcp_get_page_name( $view_categories_option ) );
        $view_categories_url = awpcp_get_view_categories_url();

        $duplicates = array();

        $posts = get_posts( array( 'post_type' => 'page', 'name' => $view_categories ) );

        foreach ( $posts as $post ) {
            if ( $view_categories_url != get_permalink( $post->ID ) ) {
                continue;
            }

            $duplicates[] = sprintf(
                '<a href="%s"><strong>%s</strong></a>',
                add_query_arg(
                    array(
                        'post' => $post->ID,
                        'action' => 'edit',
                    ),
                    admin_url( 'post.php' )
                ),
                get_the_title( $post )
            );
        }

        if ( ! empty( $duplicates ) ) {
            $duplicated_pages = join( ', ', $duplicates );

            $view_categories_label = awpcp()->settings->get_option_label( $view_categories_option );
            $view_categories_label = sprintf( '<strong>%s</strong>', ucwords( $view_categories_label ) );

            // translators: %1$s is the page name, %2$s is the view categories page name
            $first_line = _n(
                'Page %1$s has the same URL as the %2$s from AWPCP. The WordPress page %1$s is going to be unreachable until this changes.',
                'Pages %1$s have the same URL as the %2$s from AWPCP. The WordPress pages %1$s is going to be unreachable until this changes.',
                count( $duplicates ),
                'another-wordpress-classifieds-plugin'
            );
            $first_line = sprintf( $first_line, $duplicated_pages, $view_categories_label );

            // translators: %1$s is the page name
            $second_line = _n(
                'The %1$s is dynamic; you don\'t need to create a real WordPress page to show the list of categories, the plugin will generate it for you. If the WordPress page was created to show the default list of AWPCP categories, you can delete it and this error message will go away. Otherwise, please make sure you don\'t have duplicate page names.',
                'The %1$s is dynamic; you don\'t need to create a real WordPress page to show the list of categories, the plugin will generate it for you. If the WordPress pages were created to show the default list of AWPCP categories, you can delete them and this error message will go away. Otherwise, please make sure you don\'t have duplicate page names.',
                count( $duplicates ),
                'another-wordpress-classifieds-plugin'
            );
            $second_line = sprintf( $second_line, $view_categories_label );

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_error( $first_line . '<br/><br/>' . $second_line );
        }
    }

    public function init() {
        add_filter( 'parent_file', array( $this, 'parent_file' ) );
        add_filter( 'admin_body_class', [ $this, 'filter_admin_body_classes' ] );
    }

    public function scripts() {
    }

    private function get_manage_credits_section_url() {
        $full_url = add_query_arg( 'action', 'awpcp-manage-credits', admin_url( 'users.php' ) );

        $domain = awpcp_request()->domain();

        if ( ! empty( $domain ) ) {
            $domain_position = strpos( $full_url, $domain );
            $url = substr( $full_url, $domain_position + strlen( $domain ) );
        } else {
            $url = $full_url;
        }

        return $url;
    }

    /**
     * A hack to show the WP Users associated to a submenu under
     * Classifieds menu.
     *
     * @since 3.0.2
     */
    public function parent_file($parent_file) {
        global $current_screen, $submenu_file, $typenow;

        if ( $current_screen->base == 'users' && awpcp_get_var( array( 'param' => 'action' ) ) == 'awpcp-manage-credits' ) {
            // make Classifieds menu the current menu
            $parent_file = 'awpcp.php';
            // highlight Manage Credits submenu in Classifieds menu
            $submenu_file = $this->get_manage_credits_section_url();
            // make $typenow non empty so Users menu is not highlighted
            // in _wp_menu_output, despite the fact we are showing the
            // All Users page.
            $typenow = 'hide-users-menu';
        }

        return $parent_file;
    }

    /**
     * @since 4.0.0
     */
    public function filter_admin_body_classes( $admin_body_classes ) {
        global $current_screen;

        if ( $current_screen && $current_screen->base === 'users' && awpcp_get_var( array( 'param' => 'action' ) ) === 'awpcp-manage-credits' ) {
            $admin_body_classes = $admin_body_classes ? "$admin_body_classes awpcp-manage-credits-admin-page" : 'awpcp-manage-credits-admin-page';
        }

        return $admin_body_classes;
    }

    public function upgrade() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    public function disable_quick_start_guide_notice() {
        awpcp_check_admin_ajax();

        global $awpcp;
        $awpcp->settings->update_option('show-quick-start-guide-notice', false);
        die('Success!');
    }

    public function disable_widget_modification_notice() {
        awpcp_check_admin_ajax();

        global $awpcp;
        $awpcp->settings->update_option('show-widget-modification-notice', false);
        die('Success!');
    }

    public function maybe_highlight_menu() {
        global $post;

        $post_type = awpcp()->container['listing_post_type'];
        $selected_post = awpcp_get_var( array( 'param' => 'post_type' ) );
        $is_single_listing = $post_type === $selected_post || ( is_object( $post ) && $post_type === $post->post_type );

        if ( ! $is_single_listing ) {
            return;
        }

        echo "<script>
            jQuery(document).ready(function() {
                var awpcpMenu = jQuery( '#toplevel_page_awpcp' );
                jQuery( awpcpMenu ).removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
                jQuery( '#toplevel_page_awpcp a.wp-has-submenu' ).removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
                jQuery( '#toplevel_page_awpcp a[href=\"edit.php?post_type=awpcp_listing\"]' ).parent().addClass( 'current' );
            });
        </script>";
    }
}

/**
 */
function checkifclassifiedpage() {
    global $wpdb;

    $id = awpcp_get_page_id_by_ref( 'main-page-name' );
    $page_id = intval(
        $wpdb->get_var(
            $wpdb->prepare(
                'SELECT ID FROM ' . $wpdb->posts . ' WHERE ID = %d',
                $id
            )
        )
    );

    return $page_id === $id;
}

function awpcp_admin_categories_render_category_items($categories, &$children, $start, $per_page, &$count, $parent=0, $level=0) {
    $categories_collection = awpcp_categories_collection();

    $end = $start + $per_page;
    $items = array();

    foreach ( $categories as $category ) {
        if ( $count >= $end ) break;

        if ( $category->parent != $parent ) continue;

        if ( $count == $start && $category->parent > 0 ) {
            try {
                $category_parent = $categories_collection->get( $category->parent );
                $items[] = awpcp_admin_categories_render_category_item( $category_parent, $level - 1, $start, $per_page );

            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            } catch ( AWPCP_Exception $e ) {
                // pass
            }
        }

        if ( $count >= $start ) {
            $items[] = awpcp_admin_categories_render_category_item( $category, $level, $start, $per_page  );
        }

        ++$count;

        if ( isset( $children[ $category->term_id ] ) ) {
            $_children = awpcp_admin_categories_render_category_items( $categories, $children, $start, $per_page, $count, $category->term_id, $level + 1 );
            $items = array_merge( $items, $_children );
        }
    }

    return $items;
}

function awpcp_admin_categories_render_category_item($category, $level, $start, $per_page) {
    global $hascaticonsmodule;

    if ( function_exists( 'awpcp_get_category_icon' ) ) {
        $category_icon = awpcp_get_category_icon( $category );
    }

    if ( isset( $category_icon ) && !empty( $category_icon ) && function_exists( 'awpcp_category_icon_url' )  ) {
        $caticonsurl = awpcp_category_icon_url( $category_icon );
        $thecategoryicon = '<img style="vertical-align:middle;margin-right:5px;max-height:16px" src="%s" alt="%s" border="0" />';
        $thecategoryicon = sprintf( $thecategoryicon, esc_url( $caticonsurl ), esc_attr( $category->name ) );
    } else {
        $thecategoryicon = '';
    }

    $params = array( 'page' => 'awpcp-admin-categories', 'cat_ID' => $category->term_id );
    $admin_listings_url = add_query_arg( urlencode_deep( $params ), admin_url( 'admin.php' ) );

    $thecategory_parent_id = $category->parent;
    $thecategory_parent_name = stripslashes(get_adparentcatname($thecategory_parent_id));
    $thecategory_order       = intval( get_term_meta( $category->term_id, '_awpcp_order', true ) );
    $thecategory_name        = sprintf(
        '%s%s<a href="%s">%s</a>',
        str_repeat( '&mdash;&nbsp;', $level ),
        $thecategoryicon,
        esc_url( $admin_listings_url ),
        esc_html( stripslashes( $category->name ) )
    );

    $totaladsincat = total_ads_in_cat( $category->term_id );

    $params = array( 'cat_ID' => $category->term_id, 'offset' => $start, 'results' => $per_page );
    $admin_categories_url = add_query_arg( urlencode_deep( $params ), awpcp_get_admin_categories_url() );

    if ($hascaticonsmodule == 1 ) {
        $url = esc_url( add_query_arg( 'action', 'managecaticon', $admin_categories_url ) );
        $managecaticon = "<a class=\"awpcp-action-button button\" href=\"$url\" title=\"" . __("Manage Category Icon", 'another-wordpress-classifieds-plugin') . "\"><i class=\"fa fa-wrench\"></i></a>";
    } else {
        $managecaticon = '';
    }

    $awpcpeditcategoryword = __("Edit Category",'another-wordpress-classifieds-plugin');
    $awpcpdeletecategoryword = __("Delete Category",'another-wordpress-classifieds-plugin');

    $row = '<tr>';
    $row .= '<td style="padding:5px;text-align:center;">';
    $row .= '<label class="screen-reader-text" for="awpcp-category-select-' . esc_attr( $category->term_id ) . '">';
    $row .= esc_html( str_replace( '{category_name}', $thecategory_name, __( 'Select {category_name}', 'another-wordpress-classifieds-plugin' ) ) );
    $row .= '</label>';
    $row .= '<input id="awpcp-category-select-' . esc_attr( $category->term_id ) . '" type="checkbox" name="category_to_delete_or_move[]" value="' . esc_attr( $category->term_id ) . '" />';
    $row .= '</td>';
    $row .= '<td style="font-weight:normal; text-align: center;">' . $category->term_id . '</td>';
    $row.= "<td style=\"border-bottom:1px dotted #dddddd;font-weight:normal;\">$thecategory_name ($totaladsincat)</td>";
    $row.= "<td style=\"border-bottom:1px dotted #dddddd;font-weight:normal;\">$thecategory_parent_name</td>";
    $row.= "<td style=\"border-bottom:1px dotted #dddddd;font-weight:normal;\">$thecategory_order</td>";
    $row.= "<td style=\"border-bottom:1px dotted #dddddd;font-size:smaller;font-weight:normal;\">";
    $url = esc_url( add_query_arg( 'awpcp-action', 'edit-category', $admin_categories_url ) );
    $row.= "<a class=\"awpcp-action-button button\" href=\"$url\" title=\"$awpcpeditcategoryword\"><i class=\"fa fa-pen fa-pencil\"></i></a>";
    $url = esc_url( add_query_arg( 'awpcp-action', 'delete-category', $admin_categories_url ) );
    $row.= "<a class=\"awpcp-action-button button\" href=\"$url\" title=\"$awpcpdeletecategoryword\"><i class=\"fa fa-trash-alt fa-trash\"></i></a>";
    $row.= $managecaticon;
    $row.= "</td>";
    $row.= "</tr>";

    return $row;
}

function awpcp_pages() {
    $pages = array(
        'main-page-name' => array(
            __( 'Classifieds', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPCLASSIFIEDSUI]',
        ),
    );

    return $pages + awpcp_subpages();
}

function awpcp_subpages() {
    $pages = array(
        'show-ads-page-name' => array(
            _x( 'Show Ad', 'page name', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPSHOWAD]',
        ),
        'reply-to-ad-page-name' => array(
            __( 'Reply to Ad', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPREPLYTOAD]',
        ),
        'edit-ad-page-name' => array(
            _x( 'Edit Ad', 'page name', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPEDITAD]',
        ),
        'place-ad-page-name' => array(
            __( 'Place Ad', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPPLACEAD]',
        ),
        'renew-ad-page-name' => array(
            __( 'Renew Ad', 'another-wordpress-classifieds-plugin' ),
            '[AWPCP-RENEW-AD]',
        ),
        'browse-ads-page-name' => array(
            __( 'Browse Ads', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPBROWSEADS]',
        ),
        'search-ads-page-name' => array(
            __( 'Search Ads', 'another-wordpress-classifieds-plugin' ),
            '[AWPCPSEARCHADS]',
        ),
    );

    $pages = apply_filters('awpcp_subpages', $pages);

    return $pages;
}

function awpcp_create_pages($awpcp_page_name, $subpages=true) {
    $refname = 'main-page-name';
    $shortcode = '[AWPCPCLASSIFIEDSUI]';

    // create AWPCP main page if it does not exist
    if (!awpcp_find_page($refname)) {
        $id = awpcp_create_page( $awpcp_page_name, $shortcode );
        awpcp_update_plugin_page_id( $refname, $id );
    } else {
        $id = awpcp_get_page_id_by_ref($refname);
    }

    // create subpages
    if ($subpages) {
        awpcp_create_subpages($id);
    }
}

/**
 * @since 4.0.0
 */
function awpcp_create_page( $title, $content, $parent_id = 0 ) {
    $date        = current_time( 'mysql' );
    $date_gmt    = get_gmt_from_date( $date );
    $post_author = is_user_logged_in() ? get_current_user_id() : 1;

    $page = array(
        'post_author'           => $post_author,
        'post_date'             => $date,
        'post_date_gmt'         => $date_gmt,
        'post_content'          => $content,
        'post_title'            => add_slashes_recursive( $title ),
        'post_status'           => 'publish',
        'post_name'             => sanitize_title( $title ),
        'post_modified'         => $date,
        'comments_status'       => 'closed',
        'post_content_filtered' => $content,
        'post_parent'           => $parent_id,
        'post_type'             => 'page',
        'menu_order'            => 0,
    );

    $page_id = wp_insert_post( $page );

    if ( ! $page_id ) {
        return null;
    }

    return $page_id;
}

function awpcp_create_subpages($awpcp_page_id) {
    $pages = awpcp_subpages();

    foreach ($pages as $key => $page) {
        awpcp_create_subpage($key, $page[0], $page[1], $awpcp_page_id);
    }

    do_action('awpcp_create_subpage');
}

/**
 * Creates a subpage of the main AWPCP page.
 *
 * This functions takes care of checking if the main AWPCP
 * page exists, finding its id and verifying that the new
 * page doesn't exist already. Useful for module plugins.
 */
function awpcp_create_subpage($refname, $name, $shortcode, $awpcp_page_id=null) {
    $id = 0;
    if (!empty($name)) {
        // it is possible that the main AWPCP page does not exist, in that case
        // we should create Subpages without a parent.
        if (is_null($awpcp_page_id) && awpcp_find_page('main-page-name')) {
            $awpcp_page_id = awpcp_get_page_id_by_ref('main-page-name');
        } elseif (is_null(($awpcp_page_id))) {
            $awpcp_page_id = '';
        }

        if (!awpcp_find_page($refname)) {
            $id = awpcp_create_page( $name, $shortcode, $awpcp_page_id );
        } else {
            $id = awpcp_get_page_id_by_ref( $refname );
        }
    }

    if ($id > 0) {
        awpcp_update_plugin_page_id( $refname, $id );
    }

    return $id;
}

/**
 * Calls awpcp-admin-sidebar filter to output Admin panel sidebar.
 *
 * To remove Admin panel sidebar remove the mentioned filter on init.
 *
 * XXX: this may belong to AdminPage class
 */
function awpcp_admin_sidebar($float='') {
    $html = apply_filters('awpcp-admin-sidebar', '', $float);
    return $html;
}

/**
 * XXX: this may belong to AdminPage class
 */
function awpcp_admin_sidebar_output($html, $float) {
    global $awpcp;

    $modules = array(
        'premium' => array(
            'installed' => array(),
            'not-installed' => array(),
        ),
        'other' => array(
            'installed' => array(),
            'not-installed' => array(),
        ),
    );

    $premium_modules = $awpcp->get_premium_modules_information();
    foreach ($premium_modules as $module) {
        if ( isset( $module['private'] ) && $module['private'] ) {
            continue;
        }

        if ( isset( $module['removed'] ) && $module['removed'] ) {
            continue;
        }

        if ( $module['installed'] ) {
            $modules['premium']['installed'][] = $module;
        } else {
            $modules['premium']['not-installed'][] = $module;
        }
    }

    $apath = get_option('siteurl') . '/wp-admin/images';
    $float = '' == $float ? 'float:right !important' : $float;
    $url = AWPCP_URL;

    ob_start();
        include(AWPCP_DIR . '/admin/templates/admin-sidebar.tpl.php');
        $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
