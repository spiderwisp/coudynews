<?php
/**
 * @package AWPCP\Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_PaymentsAPI {

    private $request = null;

    private $terms = null;
    private $types = array();
    private $methods = array();

    private $cache = array();

    public $current_transaction = null;

    /**
     * @var bool
     */
    private $echo = false;

    public function __construct( /*AWPCP_Request*/ $request = null ) {
        if ( ! is_null( $request ) ) {
            $this->request = $request;
        } else {
            $this->request = new AWPCP_Request();
        }

        add_action( 'init', array( $this, 'register_payment_term_types' ), 9999 );
        add_action( 'init', array( $this, 'register_payment_methods' ), 9999 );

        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'wp' ), 1 );
        } else {
            add_action( 'template_redirect', array( $this, 'wp' ), 1 );
        }

        add_action('awpcp-transaction-status-updated', array($this, 'update_account_balance'), 10, 1);
    }

    public function register_payment_term_types() {
        do_action('awpcp-register-payment-term-types', $this);
    }

    public function register_payment_methods() {
        do_action('awpcp-register-payment-methods', $this);
    }

    private function get_url($action, $transaction) {
        if (get_option('permalink_structure')) {
            return awpcp_get_url_with_page_permastruct( "/awpcpx/payments/$action/{$transaction->id}" );
        } else {
            $params = array(
                'awpcpx' => true,
                'module' => 'payments',
                'action' => $action,
                'awpcp-txn' => $transaction->id,
            );
            return add_query_arg( urlencode_deep( $params ), home_url('index.php'));
        }
    }

    public function get_return_url($transaction) {
        return $this->get_url('return', $transaction);
    }

    public function get_notify_url($transaction) {
        return $this->get_url('notify', $transaction);
    }

    public function get_cancel_url($transaction) {
        return $this->get_url('cancel', $transaction);
    }

    public function payments_enabled() {
        return get_awpcp_option('freepay') == 1;
    }

    public function credit_system_enabled() {
        if (!$this->payments_enabled())
            return false;
        return get_awpcp_option('enable-credit-system') == 1;
    }

    public function is_currency_accepted() {
        return in_array( AWPCP_Payment_Transaction::PAYMENT_TYPE_MONEY, $this->get_accepted_payment_types() );
    }

    public function is_credit_accepted() {
        if ( ! $this->credit_system_enabled() ) {
            return false;
        }

        return in_array( AWPCP_Payment_Transaction::PAYMENT_TYPE_CREDITS, $this->get_accepted_payment_types() );
    }

    /* Credit Plans */

    /**
     * Handler for awpcp-transaction-status-updated action
     *
     * XXX: Make sure the user has enough credit to pay for the plans.
     *  We already check that at the beginning of the transaction but I
     *  think is necessary to check again here.
     *  We need a way to mark individual items as paid or unpaid so
     *  other parts of the plugin can decide what to do.
     */
    public function update_account_balance($transaction) {
        if ( awpcp_user_is_admin( $transaction->user_id ) ) {
            return;
        }

        if ( $transaction->was_payment_successful() ) {
            $this->maybe_increase_account_balance( $transaction );
        }

        if ( $transaction->was_payment_successful() && $transaction->is_completed() ) {
            $this->maybe_decrease_account_balance( $transaction );
        }
    }

    private function maybe_increase_account_balance( $transaction ) {
        if ( $transaction->get( 'credits-purchase-processed' ) ) {
            return;
        }

        $credit_plan = $this->get_transaction_credit_plan( $transaction );

        if ( ! is_null( $credit_plan ) ) {
            $balance = $this->get_account_balance( $transaction->user_id );
            $this->set_account_balance( $transaction->user_id, $balance + $credit_plan->credits );
        }

        $transaction->set( 'credits-purchase-processed', true );
    }

    private function maybe_decrease_account_balance( $transaction ) {
        if ( $transaction->get( 'credits-payment-processed' ) ) {
            return;
        }

        $totals = $transaction->get_totals();

        if ( $totals['credits'] > 0 ) {
            $balance = $this->get_account_balance( $transaction->user_id );
            $this->set_account_balance( $transaction->user_id, $balance - $totals['credits'] );
        }

        $transaction->set( 'credits-payment-processed', true );
    }

    public function set_account_balance($user_id, $balance) {
        if (is_null($user_id) && is_user_logged_in())
            $user_id = wp_get_current_user()->ID;

        if (is_null($user_id)) return false;

        return update_user_meta($user_id, 'awpcp-account-balance', $balance);
    }

    public function get_account_balance($user_id=null) {
        if (is_null($user_id) && is_user_logged_in())
            $user_id = wp_get_current_user()->ID;

        if (is_null($user_id)) return 0;

        return (float) get_user_meta($user_id, 'awpcp-account-balance', true);
    }

    public function add_credit($user_id, $amount) {
        $balance = $this->get_account_balance($user_id);
        return $this->set_account_balance($user_id, $balance + max(0, $amount));
    }

    public function remove_credit($user_id, $amount) {
        $balance = $this->get_account_balance($user_id);
        return $this->set_account_balance($user_id, $balance - max(0, $amount));
    }

    public function format_account_balance($user_id=null) {
        return number_format($this->get_account_balance($user_id), 0);
    }

    public function get_credit_plans() {
        $credit_plans = [];

        foreach ( AWPCP_CreditPlan::find() as $credit_plan ) {
            $summary = __( '{credit-plan-name} ({credit-plan-credits} credits for {credit-plan-price})', 'another-wordpress-classifieds-plugin' );

            $summary = str_replace( '{credit-plan-name}', $credit_plan->name, $summary );
            $summary = str_replace( '{credit-plan-credits}', awpcp_format_integer( $credit_plan->credits ), $summary );
            $summary = str_replace( '{credit-plan-price}', awpcp_format_money( $credit_plan->price ), $summary );

            $credit_plan->summary = $summary;

            $credit_plans[] = $credit_plan;
        }

        return $credit_plans;
    }

    public function get_credit_plan($id) {
        return AWPCP_CreditPlan::find_by_id($id);
    }

    public function get_transaction_credit_plan($transaction) {
        return $this->get_credit_plan($transaction->get('credit-plan'));
    }

    /* Payment Terms */

    public function register_payment_term_type($type) {
        if (is_a($type, 'AWPCP_PaymentTermType'))
            $this->types[$type->slug] = $type;
    }

    public function get_payment_term_type($term_type) {
        if (!isset($this->types[$term_type]))
            return null;
        return $this->types[$term_type];
    }

    /**
     * @return object|null
     */
    public function get_payment_term($term_id, $term_type) {
        if (!isset($this->types[$term_type]))
            return null;
        return $this->types[$term_type]->find_by_id($term_id);
    }

    public function get_transaction_payment_term($transaction) {
        $term_type = $transaction->get('payment-term-type');
        $term_id = $transaction->get('payment-term-id');

        return $this->get_payment_term($term_id, $term_type);
    }

    public function get_payment_terms() {
        if (is_array($this->terms)) return $this->terms;

        $this->terms = array();
        foreach ($this->types as $slug => $type) {
            $this->terms[$slug] = $type->get_payment_terms();
        }

        return $this->terms;
    }

    public function get_user_payment_terms($user_id) {
        $terms = array();
        foreach ($this->types as $slug => $type)
            $terms[$slug] = $type->get_user_payment_terms($user_id);
        return $terms;
    }

    /**
     * @since 4.0.0
     */
    public function payment_terms_are_equals( $payment_term_one, $payment_term_two ) {
        if ( ! $payment_term_one || ! $payment_term_two ) {
            return false;
        }

        if ( $payment_term_one->type !== $payment_term_two->type ) {
            return false;
        }

        if ( $payment_term_one->id !== $payment_term_two->id ) {
            return false;
        }

        return true;
    }

    public function payment_term_requires_payment($term) {
        $credits = intval($this->credit_system_enabled() ? $term->credits : 0);
        $money = floatval($term->price);

        return $money > 0 || $credits > 0;
    }

    /**
     * @since 3.0.2
     */
    public function get_accepted_payment_types() {
        $payment_type = get_awpcp_option( 'accepted-payment-type', false );

        $payment_types = array();
        if ( 'money' === $payment_type || 'both' === $payment_type ) {
            $payment_types[] = AWPCP_Payment_Transaction::PAYMENT_TYPE_MONEY;
        }
        if ( 'credits' === $payment_type || 'both' === $payment_type ) {
            $payment_types[] = AWPCP_Payment_Transaction::PAYMENT_TYPE_CREDITS;
        }

        return $payment_types;
    }

    /* Payment Gateways */

    public function register_payment_method($gateway) {
        if (is_a($gateway, 'AWPCP_PaymentGateway'))
            $this->methods[$gateway->slug] = $gateway;
    }

    public function get_payment_methods() {
        return $this->methods;
    }

    public function get_payment_method($slug) {
        if (!isset($this->methods[$slug]))
            return null;
        return $this->methods[$slug];
    }

    public function get_transaction_payment_method($transaction) {
        return $this->get_payment_method($transaction->get('payment-method', ''));
    }

    /* Transactions Management */

    public function get_transaction() {
        return $this->get_transaction_with_method( 'find_by_id' );
    }

    private function get_transaction_with_method( $method_name ) {
        if ( is_null( $this->current_transaction ) ) {
            $transaction_id = awpcp_get_var( array( 'param' => 'transaction_id' ) );
            $this->current_transaction = call_user_func( array( 'AWPCP_Payment_Transaction', $method_name ), $transaction_id );
        }

        return $this->current_transaction;
    }

    public function get_or_create_transaction() {
        return $this->get_transaction_with_method( 'find_or_create' );
    }

    /**
     * @since 4.0.0
     */
    public function create_transaction() {
        return $this->get_transaction_with_method( 'create' );
    }

    /**
     * TODO: should throw an exception if the status can't be set
     *
     * @param AWPCP_Payment_Transaction $transaction
     * @param string                    $status
     * @param array                     &$errors
     */
    private function set_transaction_status($transaction, $status, &$errors) {
        $result = $transaction->set_status( $status, $errors );
        if ( $result ) {
            do_action('awpcp-transaction-status-updated', $transaction, $status, $errors);
        }

        $transaction->save();

        return $result;
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_open($transaction, &$errors=array()) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_OPEN, $errors);
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_ready_to_checkout($transaction, &$errors=array()) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_READY, $errors);
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_checkout($transaction, &$errors=array()) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_CHECKOUT, $errors);
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_payment($transaction, &$errors=array()) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_PAYMENT, $errors);
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_payment_completed($transaction, &$errors=array()) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_PAYMENT_COMPLETED, $errors);
    }

    /**
     * @param AWPCP_Payment_Transaction $transaction
     * @param array                     &$errors
     */
    public function set_transaction_status_to_completed( $transaction, &$errors = array() ) {
        return $this->set_transaction_status($transaction, AWPCP_Payment_Transaction::STATUS_COMPLETED, $errors);
    }

    public function set_transaction_credit_plan($transaction) {
        if (!$this->credit_system_enabled())
            return;

        // grab Credit Plan information
        $plan = awpcp_get_var( array( 'param' => 'credit_plan', 'default' => 0 ), 'post' );
        $plan = $this->get_credit_plan( $plan );

        if (!is_null($plan)) {
            $transaction->set('credit-plan', $plan->id);

            $transaction->add_item(
                $plan->id,
                $plan->name,
                $plan->description,
                AWPCP_Payment_Transaction::PAYMENT_TYPE_MONEY,
                $plan->price
            );
        }
    }

    public function set_transaction_payment_method($transaction) {
        $payment_method = awpcp_get_var( array( 'param' => 'payment_method' ), 'post' );
        $payment_method = $this->get_payment_method( $payment_method );

        if ( !is_null( $payment_method ) ) {
            $transaction->set('payment-method', $payment_method->slug);
        }
    }

    public function set_transaction_item_from_payment_term( $transaction, $payment_term, $payment_type = null ) {
        if ( is_null( $payment_type ) ) {
            $payment_type = AWPCP_Payment_Transaction::PAYMENT_TYPE_MONEY;
        }

        if ( ! in_array( $payment_type, $this->get_accepted_payment_types(), true ) ) {
            awpcp_flash( __( "The selected payment type can't be used in this kind of transaction.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return;
        }

        if ( ! $payment_term->is_suitable_for_transaction( $transaction ) ) {
            awpcp_flash( __( "The selected payment term can't be used in this kind of transaction.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return;
        }

        return $transaction->add_item(
            "{$payment_term->type}-{$payment_term->id}-{$payment_type}",
            $payment_term->get_name(),
            $payment_term->description,
            $payment_type,
            $this->calculate_payment_term_price( $payment_term, $payment_type, $transaction )
        );
    }

    public function calculate_payment_term_price( $payment_term, $payment_type, $transaction ) {
        if ( $payment_type == 'credits' ) {
            $payment_amount = $payment_term->credits;
        } else {
            $payment_amount = $payment_term->price;
        }

        $payment_amount = apply_filters(
            'awpcp-payment-term-payment-amount',
            $payment_amount,
            $payment_term,
            $payment_type,
            $transaction
        );

        return $payment_amount;
    }

    public function process_transaction($transaction) {
        /**
         * Used by the main plugin and premium modules to modify or take actions
         * based on the current payment transaction.
         */
        do_action( 'awpcp-process-payment-transaction', $transaction );
    }

    public function process_payment_request($action) {
        $transaction = AWPCP_Payment_Transaction::find_by_id( get_query_var( 'awpcp-txn' ) );
        $messages    = [];

        $payment_method = null;

        if (is_null($transaction)) {
            $messages[] = esc_html__( 'The specified payment transaction doesn\'t exists. We can\'t process your payment.', 'another-wordpress-classifieds-plugin' );
        } else {
            $payment_method = $this->get_transaction_payment_method( $transaction );

            if ( is_null( $payment_method ) ) {
                $messages[] = esc_html__( 'The payment method associated with this transaction is not available at this time. We can\'t process your payment.', 'another-wordpress-classifieds-plugin' );
            }
        }

        if ( is_null( $payment_method ) || is_null( $transaction ) ) {
            $messages[] = esc_html__( 'Please contact customer service if you are viewing this message after having made a payment. If you have not tried to make a payment and you are viewing this message, it means this message is being shown in error and can be disregarded.', 'another-wordpress-classifieds-plugin' );

            /* translators: %s link HTML */
            $messages[] = sprintf(
                // translators: %s is the home page URL
                esc_html__( 'Return to %shome page', 'another-wordpress-classifieds-plugin' ),
                '<a href="' . esc_url( home_url() ) . '">'
            ) . '</a>';

            wp_die( wp_kses_post( '<p>' . join( '</p><p>', $messages ) . '</p>' ) );
        }

        switch ($action) {
            case 'return':
                $payment_method->process_payment_completed($transaction);
                return $this->process_payment_completed($transaction);

            case 'cancel':
                $payment_method->process_payment_canceled($transaction);
                return $this->process_payment_completed($transaction);

            case 'notify':
                $payment_method->process_payment_notification($transaction);
                return $this->process_payment_completed($transaction, false);
        }
    }

    public function process_payment_completed($transaction, $redirect=true) {
        $errors = array();

        /**
         * Only attempt to complete the payment if we are in a previous state.
         *
         * IPN notifications are likely to be associated to transactions that
         * are already completed.
         */
        if (!$transaction->is_payment_completed() && !$transaction->is_completed()) {
            $this->set_transaction_status_to_payment_completed($transaction, $errors);

            if (!empty($errors)) {
                $transaction->errors['payment-completed'] = $errors;
            } else {
                unset($transaction->errors['payment-completed']);
            }
        }

        try {
            $this->process_transaction( $transaction );
        } catch ( AWPCP_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // We simply ignore exceptions here because we are currently using them
            // in the Coupons module only for transactions that are doing checkout.
        }

        $transaction->save();

        if ($redirect) {
            $url = $transaction->get('redirect', $transaction->get('success-redirect'));
            $url = add_query_arg('step', 'payment-completed', $url);
            $url = add_query_arg('transaction_id', $transaction->id, $url);
            wp_redirect( esc_url_raw( $url ) );
        }

        exit();
    }

    public function process_payment() {
        $transaction_id = awpcp_get_var( array( 'param' => 'transaction_id' ) );
        if ( empty( $transaction_id ) ) {
            return;
        }

        $transaction = AWPCP_Payment_Transaction::find_by_id( $transaction_id );

        if ( is_null( $transaction ) ) {
            return;
        }

        $result = null;

        if ( $transaction->is_doing_checkout() ) {
            $result = $this->process_payment_for_transaction_doing_checkout( $transaction );
        } elseif ( $transaction->is_processing_payment() ) {
            $result = $this->process_payment_for_transaction_processing_payment( $transaction );
        }

        if ( $result ) {
            $this->cache[ $transaction->id ] = $result;
        }
    }

    /**
     * @since 3.9.4
     */
    private function process_payment_for_transaction_doing_checkout( $transaction ) {
        $this->set_transaction_payment_method( $transaction );

        try {
            $this->process_transaction( $transaction );
        } catch ( AWPCP_Exception $e ) {
            return [
                'errors' => [ $e->getMessage() ],
            ];
        }

        $errors = array();

        $this->set_transaction_status_to_payment( $transaction, $errors );

        // No errors means we are now processing a payment. Stop if a real
        // payment is not required.
        if ( empty( $errors ) && $transaction->payment_is_not_required() ) {
            $this->set_transaction_status_to_payment_completed( $transaction, $errors );

            // Nothing else to do here, pass control to the (api) user.
            if ( empty( $errors ) ) {
                return null;
            }
        }

        // Most likely because the payment method hasn't been properly set.
        if ( ! empty( $errors ) ) {
            return compact( 'errors' );
        }

        // No errors, so we must have a payment method defined.
        $payment_method = $this->get_transaction_payment_method( $transaction );

        return [
            'output' => $payment_method->process_payment( $transaction ),
        ];
    }

    /**
     * @since 3.9.4
     */
    private function process_payment_for_transaction_processing_payment( $transaction ) {
        try {
            $this->process_transaction( $transaction );
        } catch ( AWPCP_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // We simply ignore exceptions here because we are currently using them
            // in the Coupons module only for transactions that are doing checkout.
        }

        $payment_method = $this->get_transaction_payment_method( $transaction );

        return [
            'output' => $payment_method->process_payment( $transaction ),
        ];
    }

    public function wp() {
        $awpcpx = $this->request->get_query_var( 'awpcpx' );
        $module = $this->request->get_query_var( 'awpcp-module', $this->request->get_query_var( 'module' ) );
        $action = $this->request->get_query_var( 'awpcp-action', $this->request->get_query_var( 'action' ) );

        if ($awpcpx && $module == 'payments' && !empty($action)) {
            return $this->process_payment_request($action);
        } else {
            return $this->process_payment();
        }
    }

    /* Render functions */

    /**
     * Render the credits account balance in the context of the given transaction,
     * including the number of credits available when the transaction is completed.
     *
     * @since 4.0.0
     */
    public function render_account_balance_for_transaction( $transaction ) {
        $credit_plan     = $this->get_credit_plan( $transaction->get( 'credit-plan' ) );
        $account_balance = $this->get_account_balance( $transaction->user_id );
        $credits_used    = $transaction->get_total_credits();
        $credits_after   = false;

        if ( $credit_plan ) {
            $credits_after = number_format( $account_balance + $credit_plan->credits - $credits_used );
        } elseif ( $credits_used ) {
            $credits_after = number_format( $account_balance - $credits_used );
        }

        if ( $credits_after === false ) {
            // No need to show the balance if credits are not used nor purchased in
            // the transaction.
            return '';
        }

        $message = sprintf(
            // translators: %1$s is the current credit balance, %2$s is the credit balance after the transaction is completed
            __( 'You currently have %1$s credits in your account. The balance after this transaction is completed successfully will be %2$s.', 'another-wordpress-classifieds-plugin' ),
            $this->format_account_balance( $transaction->user_id ),
            $credits_after
        );

        return awpcp_print_message( $message );
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_account_balance() {
        $this->echo = true;
        $this->render_account_balance();
        $this->echo = false;
    }

    public function render_account_balance() {
        if (!$this->credit_system_enabled())
            return '';

        $balance = $this->format_account_balance();

        /* translators: %s credit balance */
        $text = sprintf( __( 'You currently have %s credits in your account.', 'another-wordpress-classifieds-plugin' ), $balance );

        if ( $this->echo ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_message( $text );
            return;
        }

        return awpcp_print_message( $text );
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_credit_plans_table( $transaction = null, $table_only = false ) {
        $this->echo = true;
        $this->render_credit_plans_table( $transaction, $table_only );
        $this->echo = false;
    }

    /**
     * @since  2.2.2
     */
    public function render_credit_plans_table($transaction=null, $table_only=false) {
        if (!$this->credit_system_enabled() || !$this->is_credit_accepted() )
            return '';

        $credit_plans = $this->get_credit_plans();
        $selected = is_null($transaction) ? '' : $transaction->get('credit-plan');

        if ( empty( $credit_plans ) ) {
            return '';
        }

        $column_names = array(
            'plan' => _x( 'Plan', 'credit plans table', 'another-wordpress-classifieds-plugin' ),
            'description' => _x( 'Description', 'credit plans table', 'another-wordpress-classifieds-plugin' ),
            'credits' => _x( 'Credits', 'credit plans table', 'another-wordpress-classifieds-plugin' ),
            'price' => _x( 'Price', 'credit plans table', 'another-wordpress-classifieds-plugin' ),
        );

        $file = AWPCP_DIR . '/frontend/templates/payments-credit-plans-table.tpl.php';
        if ( $this->echo ) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_transaction_items( $transaction ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_transaction_items( $transaction );
    }

    public function render_transaction_items($transaction) {
        $show_credits = get_awpcp_option('enable-credit-system');

        ob_start();
            include(AWPCP_DIR . '/frontend/templates/payments-transaction-items-table.tpl.php');
            $html = ob_get_contents();
        ob_end_clean();

        return apply_filters('awpcp-render-transaction-items', $html, $transaction);
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_transaction_errors( $transaction ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_transaction_errors( $transaction );
    }

    public function render_transaction_errors($transaction) {
        $errors = array();
        foreach ($transaction->errors as $index => $error) {
            if (is_array($error)) {
                $errors = array_merge($errors, array_map('awpcp_print_error', $error));
            } else {
                $errors[] = awpcp_print_error($error);
            }
        }
        return join("\n", $errors);
    }

    public function render_payment_methods($transaction) {
        $payment_methods = $this->get_payment_methods();
        $selected_payment_method = $transaction->get( 'payment-method' );

        if ( count( $payment_methods ) === 1 ) {
            $selected_payment_method = reset( $payment_methods )->slug;
        }

        ob_start();
            include(AWPCP_DIR . '/templates/components/payment-methods-list.tpl.php');
            $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function render_checkout_payment_template($output, $message, $transaction) {
        $file = AWPCP_DIR . '/frontend/templates/payments-checkout-payment-page.tpl.php';
        if ( $this->echo ) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_checkout_page( $transaction, $hidden = array() ) {
        $this->echo = true;
        $this->render_checkout_page( $transaction, $hidden );
        $this->echo = false;
    }

    public function render_checkout_page($transaction, $hidden=array()) {
        $payment_method = $this->get_transaction_payment_method($transaction);
        $attempts = awpcp_get_var( array( 'param' => 'attempts', 'default' => 0 ), 'post' );

        $result = awpcp_array_data($transaction->id, array(), $this->cache);
        $html   = '';

        if (is_null($payment_method) || isset($result['errors'])) {
            $transaction_errors = awpcp_array_data('errors', array(), $result);
            $file = AWPCP_DIR . '/frontend/templates/payments-checkout-page.tpl.php';
            if ( $this->echo ) {
                include $file;
                return;
            }

            ob_start();
            include $file;
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        if ( ! isset( $result['output'] ) ) {
            return '';
        }

        $integration = $payment_method->get_integration_type();
        if ( $integration === AWPCP_PaymentGateway::INTEGRATION_BUTTON ) {
            $message = _x('Please use the button below to complete your payment.', 'checkout-payment page', 'another-wordpress-classifieds-plugin');
            $html = $this->render_checkout_payment_template($result['output'], $message, $transaction);
            if ( $this->echo ) {
                return;
            }
        } elseif ( $integration === AWPCP_PaymentGateway::INTEGRATION_CUSTOM_FORM || $integration === AWPCP_PaymentGateway::INTEGRATION_REDIRECT ) {
            $html = $result['output'];
        }

        if ( $this->echo ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $html;
            return;
        }

        return $html;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_payment_completed_page( $transaction, $action = '', $hidden = array() ) {
        $this->echo = true;
        $this->render_payment_completed_page( $transaction, $action, $hidden );
        $this->echo = false;
    }

    public function render_payment_completed_page($transaction, $action='', $hidden=array()) {
        $success = false;
        $text    = '';

        if ($transaction->payment_is_completed() || $transaction->payment_is_pending()) {
            $title = __( 'Payment Completed', 'another-wordpress-classifieds-plugin');

            if ($transaction->payment_is_completed())
                $text = __( 'Your Payment has been processed successfully. Please press the button below to continue with the process.', 'another-wordpress-classifieds-plugin');
            elseif ($transaction->payment_is_pending())
                $text = __( 'Your Payment has been processed successfully. However is still pending approvation from the payment gateway. Please press the button below to continue with the process.', 'another-wordpress-classifieds-plugin');

            $success = true;

        } elseif ($transaction->payment_is_not_required()) {
            $title = __( 'Payment Not Required', 'another-wordpress-classifieds-plugin');
            $text = __( 'No Payment is required for this transaction. Please press the button below to continue with the process.', 'another-wordpress-classifieds-plugin');

            $success = true;

        } elseif ($transaction->payment_is_failed()) {
            $title = __( 'Payment Failed', 'another-wordpress-classifieds-plugin');
            $text = __("Your Payment has been processed successfully. However, the payment gateway didn't return a payment status that allows us to continue with the process. Please contact the website administrator to solve this issue.", 'another-wordpress-classifieds-plugin');

        } elseif ($transaction->payment_is_canceled()) {
            $title = __( 'Payment Canceled', 'another-wordpress-classifieds-plugin');
            $text = __("The Payment transaction was canceled. You can't post an Ad this time.", 'another-wordpress-classifieds-plugin');

        } elseif ( $transaction->payment_is_not_verified() ) {
            $title = __( 'Waiting on Confirmation', 'another-wordpress-classifieds-plugin' );
            $text = __( 'The payment gateway is taking a bit longer than expected to confirm your payment. Please wait a few seconds while we verify the transaction. The page will reload automatically.', 'another-wordpress-classifieds-plugin' );
        } else {
            $title = __( 'Payment Error', 'another-wordpress-classifieds-plugin');
            $text = __("There was an error processing your payment. The payment status couldn't be found. Please contact the website admin to solve this issue.", 'another-wordpress-classifieds-plugin');
        }

        $redirect = $transaction->get('redirect');
        $hidden = array_merge(
            $transaction->get( 'redirect-data' ),
            array(
                'payment_status' => $transaction->payment_status,
            ),
            $hidden
        );

        $file = AWPCP_DIR . '/frontend/templates/payments-payment-completed-page.tpl.php';
        if ( $this->echo ) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show_payment_completed_page_title( $transaction ) {
        echo esc_html( $this->render_payment_completed_page_title( $transaction ) );
    }

    public function render_payment_completed_page_title($transaction) {
        if ($transaction->was_payment_successful()) {
            return __( 'Payment Completed', 'another-wordpress-classifieds-plugin');
        } elseif ($transaction->payment_is_canceled()) {
            return __( 'Payment Canceled', 'another-wordpress-classifieds-plugin');
        } elseif ( $transaction->payment_is_not_verified() ) {
            return __( 'Payment Not Verified', 'another-wordpress-classifieds-plugin' );
        } else {
            return __( 'Payment Failed', 'another-wordpress-classifieds-plugin');
        }
    }
}
