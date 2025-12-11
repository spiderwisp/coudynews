<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



require_once( AWPCP_DIR . '/includes/helpers/admin-page.php' );
require_once( AWPCP_DIR . '/admin/admin-panel-credit-plans-table.php' );

function awpcp_credit_plans_admin_page() {
    return new AWPCP_CreditPlansAdminPage();
}

class AWPCP_CreditPlansAdminPage extends AWPCP_AdminPageWithTable {

    public function __construct() {
        parent::__construct( null, null, null );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-credit-plans' );
    }

    public function actions($plan, $filter=false) {
        $actions = array();
        $actions['edit'] = array(__( 'Edit', 'another-wordpress-classifieds-plugin' ), $this->url(array('action' => 'edit', 'id' => $plan->id)));
        $actions['trash'] = array(__( 'Delete', 'another-wordpress-classifieds-plugin' ), $this->url(array('action' => 'delete', 'id' => $plan->id)));

        if (is_array($filter))
            $actions = array_intersect_key($actions, array_combine($filter, $filter));

        return $actions;
    }

    public function dispatch() {
        $action = $this->get_current_action();

        switch ($action) {
            case 'index':
                $output = $this->index();
                break;
            default:
                awpcp_flash("Unknown action: $action", 'error');
                $output = $this->index();
                break;
        }

        return $output;
    }

    public function index() {
        global $awpcp;

        $this->get_table()->prepare_items();

        $params = array(
            'page' => $this,
            'table' => $this->get_table(),
            'option' => $awpcp->settings->setting_name,
        );

        $template = AWPCP_DIR . '/admin/templates/admin-panel-credit-plans.tpl.php';

        return awpcp_render_template( $template, $params );
    }

    public function get_table() {
        if ( is_null( $this->table ) ) {
            $this->table = new AWPCP_CreditPlansTable( $this, array( 'screen' => 'classifieds_page_awpcp-admin-credit-plans' ) );
        }
        return $this->table;
    }
}
