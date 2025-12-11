<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function.
 */
function awpcp_user_panel() {
    return new AWPCP_User_Panel();
}

/**
 * Register admin menu items for subscribers.
 */
class AWPCP_User_Panel {

    /**
     * @var AWPCP_Upgrade_Tasks_Manager
     */
    private $upgrade_tasks;

    public $account;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->upgrade_tasks = awpcp_upgrade_tasks_manager();

        $this->account = awpcp_account_balance_page();
    }

    /**
     * Handler for the awpcp-configure-routes action.
     */
    public function configure_routes( $router ) {
        $params = [
            'context'  => 'plugin',
            'blocking' => true,
        ];

        if ( $this->upgrade_tasks->has_pending_tasks( $params ) ) {
            return;
        }

        if ( awpcp_payments_api()->credit_system_enabled() && ! awpcp_current_user_is_admin() ) {
            $this->add_users_page( $router );
        }
    }

    /**
     * Registers the page used by subscribers to see their credit account balance.
     */
    private function add_users_page( $router ) {
        $router->add_admin_users_page(
            __( 'Account Balance', 'another-wordpress-classifieds-plugin' ),
            __( 'Account Balance', 'another-wordpress-classifieds-plugin' ),
            'awpcp-user-account',
            'awpcp_account_balance_page',
            awpcp_user_capability()
        );
    }
}
