<?php
/**
 * @since 2.1.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_AdminUsers {

    const USERS_SCREEN = 'users';

    private $table = null;

    public function __construct() {
        add_filter('manage_' . self::USERS_SCREEN . '_columns', array($this, 'get_columns'), 20);
        add_filter('manage_users_custom_column', array($this, 'custom_column'), 100, 3);
        add_action('load-users.php', array($this, 'scripts'));

        add_action('wp_ajax_awpcp-users-credit', array($this, 'ajax'));
        add_action('wp_ajax_awpcp-users-debit', array($this, 'ajax'));
    }

    private function get_table() {
        if (is_null($this->table)) {
            if (!get_current_screen()) {
                set_current_screen(self::USERS_SCREEN);
            }

            $this->table = _get_list_table( 'WP_Users_List_Table', array( 'screen' => self::USERS_SCREEN ) );
        }

        return $this->table;
    }

    public function scripts() {
        $options = array(
            'nonce' => wp_create_nonce( 'awpcp_ajax' ),
        );
        wp_localize_script( 'awpcp-admin-users', 'AWPCPAjaxOptions', $options );
        wp_enqueue_script( 'awpcp-admin-users' );
    }

    public function get_columns($columns) {
        $columns['balance'] = _x('Account Balance', 'credit system on users table', 'another-wordpress-classifieds-plugin');
        return $columns;
    }

    public function custom_column($value, $column, $user_id) {
        switch ($column) {
            case 'balance':
                $balance = awpcp_payments_api()->format_account_balance($user_id);
                $actions = array();

                if (awpcp_current_user_is_admin()) {
                    $url = add_query_arg('action', 'credit', awpcp_current_url());
                    $actions['credit'] = "<a class='credit' href='" . esc_url( $url ) . "'>" . __( 'Add Credit', 'another-wordpress-classifieds-plugin') . '</a>';

                    $url = add_query_arg('action', 'debit', awpcp_current_url());
                    $actions['debit'] = "<a class='debit' href='" . esc_url( $url ) . "'>" . __( 'Remove Credit', 'another-wordpress-classifieds-plugin') . "</a>";
                }

                $table = $this->get_table();
                $value = '<span class="balance">' . $balance . '</span>' . $table->row_actions($actions);
        }

        return $value;
    }

    public function ajax_edit_balance($user_id, $action) {
        $user = get_user_by('id', $user_id);

        if ( ! $user ) {
            $message = __("The specified User doesn't exists.", 'another-wordpress-classifieds-plugin');
            $response = array('status' => 'error', 'message' => $message);
        }

        // The nonce was already checked by the AJAX handler.
        // phpcs:ignore WordPress.Security.NonceVerification
        if (isset($_POST['save'])) {
            $payments = awpcp_payments_api();
            $amount = (int) awpcp_get_var( array( 'param' => 'amount', 'default' => 0 ), 'post' );

            if ($action == 'debit')
                $payments->remove_credit($user->ID, $amount);
            else
                $payments->add_credit($user->ID, $amount);

            $balance = $payments->format_account_balance($user->ID);

            $response = array('status' => 'success', 'balance' => $balance);
        } else {
            // load the table so the get_columns methods is properly called
            // when attempt to find out the number of columns in the table
            $table = $this->get_table();
            $columns = absint( awpcp_get_var( array( 'param' => 'columns' ), 'post' ) );

            ob_start();
                include(AWPCP_DIR . '/admin/templates/admin-panel-users-balance-form.tpl.php');
                $html = ob_get_contents();
            ob_end_clean();
            $response = array('html' => $html);
        }

        return $response;
    }

    public function ajax() {
        awpcp_check_admin_ajax();

        $user_id = awpcp_get_var( array( 'param' => 'user', 'default' => 0 ), 'post' );
        $action  = awpcp_get_var( array( 'param' => 'action' ), 'post' );
        $action  = str_replace( 'awpcp-users-', '', $action );

        switch ($action) {
            case 'debit':
            case 'credit':
                $response = $this->ajax_edit_balance($user_id, $action);
                break;
            default:
                $response = array();
                break;
        }

        header('Content-Type: application/json');
        echo wp_json_encode( $response );
        exit();
    }
}
