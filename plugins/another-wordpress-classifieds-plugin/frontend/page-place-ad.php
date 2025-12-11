<?php
/**
 * @package AWPCP\Frontend
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once AWPCP_DIR . '/includes/helpers/page.php';

function awpcp_place_listing_page() {
    return new AWPCP_Place_Ad_Page(
        'awpcp-place-ad',
        null,
        awpcp_attachments_collection(),
        awpcp_listing_upload_limits(),
        awpcp_listing_authorization(),
        awpcp_listing_renderer(),
        awpcp_listings_api(),
        awpcp_listings_collection(),
        awpcp_payments_api(),
        awpcp_template_renderer(),
        awpcp_wordpress()
    );
}

/**
 * @since  2.1.4
 */
class AWPCP_Place_Ad_Page extends AWPCP_Page {

    protected $context = 'place-ad';

    public $messages = array();

    protected $attachments;
    protected $listing_upload_limits;
    protected $authorization;
    protected $listing_renderer;
    protected $listings_logic;
    protected $listings;
    protected $payments;
    protected $wordpress;
    protected $request;
    protected $transaction;

    public function __construct( $page, $title, $attachments, $listing_upload_limits, $authorization, $listing_renderer, $listings_logic, $listings, $payments, $template_renderer, $wordpress ) {
        parent::__construct( $page, $title, $template_renderer );

        $this->show_menu_items = false;

        $this->attachments = $attachments;
        $this->listing_upload_limits = $listing_upload_limits;
        $this->authorization = $authorization;
        $this->listing_renderer = $listing_renderer;
        $this->listings_logic = $listings_logic;
        $this->listings = $listings;
        $this->payments = $payments;
        $this->wordpress = $wordpress;
    }

    public function get_current_action($default=null) {
        $step = awpcp_get_var( array( 'param' => 'step', 'default' => $default ) );
        return awpcp_get_var( array( 'param' => 'step', 'default' => $step ), 'post' );
    }

    public function url($params=array()) {
        $url = parent::url($params);
        // Payments API redirects to this page including this two parameters.
        // Those URL paramters are necessary only to *arrive* to the Payment
        // Completed step page for the first time. The same parameters are
        // then passed in the POST requests.
        return remove_query_arg(array('step', 'transaction_id'), $url);
    }

    public function transaction_error() {
        return __( 'There was an error processing your Payment Request. Please try again or contact an Administrator.', 'another-wordpress-classifieds-plugin');
    }

    public function get_transaction($create=false) {
        if ( $create ) {
            $this->transaction = $this->payments->get_or_create_transaction();
        } else {
            $this->transaction = $this->payments->get_transaction();
        }

        if (!is_null($this->transaction) && $this->transaction->is_new()) {
            $this->transaction->user_id = wp_get_current_user()->ID;
            $this->transaction->set('context', $this->context);
            $this->transaction->set('redirect', $this->url());
            $this->transaction->set('redirect-data', array('step' => 'payment-completed'));

            $logged_in = awpcp_get_var( array( 'param' => 'loggedin', 'default' => false ) );
            $this->transaction->set( 'user-just-logged-in', $logged_in );
        }

        return $this->transaction;
    }

    protected function get_preview_hash($ad) {
        return wp_create_nonce( "preview-ad-{$ad->ID}" );
    }

    protected function verify_preview_hash($ad) {
        $hash = awpcp_get_var( array( 'param' => 'preview-hash' ), 'post' );
        return wp_verify_nonce( $hash, "preview-ad-{$ad->ID}" );
    }

    protected function is_user_allowed_to_edit($ad) {
        if ( $this->authorization->is_current_user_allowed_to_edit_listing( $ad ) ) {
            return true;
        }

        if ( $this->request_includes_authorized_hash( $ad ) ) {
            return true;
        }

        return false;
    }

    protected function request_includes_authorized_hash( $ad ) {
        return $this->verify_preview_hash( $ad );
    }

    public function dispatch($default=null) {
        do_action( 'awpcp-before-post-listing-page' );

        wp_enqueue_style('awpcp-jquery-ui');
        wp_enqueue_style( 'select2' );
        wp_enqueue_script('awpcp-page-place-ad');

        $awpcp = awpcp();

        $awpcp->js->localize( 'page-place-ad-order', array(
            'category'     => __( 'Please select a category.', 'another-wordpress-classifieds-plugin' ),
            'user'         => __( 'Please select the Ad owner.', 'another-wordpress-classifieds-plugin' ),
            'payment_term' => __( 'Please select a payment term.', 'another-wordpress-classifieds-plugin' ),
        ) );

        $awpcp->js->localize( 'page-place-ad-details', awpcp_listing_form_fields_validation_messages() );

        $this_page = awpcp_get_var( array( 'param' => 'page' ), 'get' );
        if ( is_admin() && in_array( $this_page, array( 'awpcp-listings', 'awpcp-panel' ), true ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->_dispatch( $default );
        } else {
            return $this->_dispatch( $default );
        }
    }

    protected function _dispatch($default=null) {
        $is_admin_user = awpcp_current_user_is_admin();

        // only admin users are allowed to place Ads
        if (get_awpcp_option('onlyadmincanplaceads') && ($is_admin_user != 1)) {
            $message = __("You do not have permission to perform the function you are trying to perform. Access to this page has been denied",'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        // only registered users are allowed to place Ads
        if (get_awpcp_option('requireuserregistration') && !is_user_logged_in()) {
            return $this->login_step();
        }

        $transaction = $this->get_transaction();

        if (!is_null($transaction) && $transaction->get('context') != $this->context) {
            $page_name = awpcp_get_page_name('place-ad-page-name');
            $page_url = awpcp_get_page_url('place-ad-page-name');
            $message = sprintf(
                // translators: %1$s is the page name, %2$s is the transaction ID
                esc_html__( 'You are trying to post an Ad using a transaction created for a different purpose. Please go back to the %1$s page. If you think this is an error please contact the administrator and provide the following transaction ID: %2$s', 'another-wordpress-classifieds-plugin'),
                '<a href="' . esc_url( $page_url ) . '">' . esc_html( $page_name ) . '</a>',
                esc_html( $transaction->id )
            );
            return $this->render('content', awpcp_print_error($message));
        }

        $action = $this->get_current_action($default);

        if (!is_null($transaction) && $transaction->is_payment_completed()) {
            if ( $transaction->payment_is_unknown() || $transaction->payment_is_invalid() || $transaction->payment_is_failed() ) {
                $message = __( 'You can\'t post an Ad at this time because the payment associated with this transaction failed (see reasons below).', 'another-wordpress-classifieds-plugin');
                $message = awpcp_print_message($message);
                $message = $message . $this->payments->render_transaction_errors($transaction);
                return $this->render('content', $message);
            }

            if ( $transaction->payment_is_not_verified() ) {
                $action = 'payment-completed';
            }

            $forbidden = in_array($action, array('order', 'checkout'));
            if ( $forbidden ) {
                $action = 'payment-completed';
            }
        }

        if (!is_null($transaction) && $transaction->is_completed()) {
            $action = 'finish';
        }

        switch ($action) {
            case 'order':
                return $this->order_step();
            case 'checkout':
                return $this->checkout_step();
            case 'payment-completed':
                return $this->payment_completed_step();
            case 'details':
            case 'save-details':
                return $this->details_step();
            case 'upload-images':
                return $this->upload_images_step();
            case 'preview-ad':
                return $this->preview_step();
            case 'finish':
                return $this->finish_step();
            default:
                return $this->place_ad();
        }
    }

    protected function get_settings() {
        return awpcp()->settings;
    }

    public function place_ad() {
        return $this->order_step();
    }

    /**
     * @since 3.0.2
     */
    protected function get_required_fields() {
        $required['start-date'] = false;
        $required['end-date'] = false;
        $required['ad-title'] = true;
        $required['website-url'] = get_awpcp_option( 'displaywebsitefieldreqop' );
        $required['ad-contact-name'] = true;
        $required['ad-contact-email'] = true;
        $required['ad-contact-phone'] = get_awpcp_option( 'displayphonefieldreqop' );
        $required['ad-item-price'] = get_awpcp_option( 'displaypricefieldreqop' );
        $required['ad-details'] = true;
        $required['country'] = get_awpcp_option( 'displaycountryfieldreqop' );
        $required['state'] = get_awpcp_option( 'displaystatefieldreqop' );
        $required['county'] = get_awpcp_option( 'displaycountyvillagefieldreqop' );
        $required['city'] = get_awpcp_option( 'displaycityfieldreqop' );
        $required['terms-of-service'] = true;

        return $required;
    }

    /**
     * TODO: Move into create_listing or create_empty_listing methods on ListingsLogic.
     */
    protected function validate_order($data, &$errors=array()) {
        $this->validate_selected_categories( $data, 'category', $errors );

        if (awpcp_current_user_is_moderator() && empty($data['user'])) {
            $errors['user'] = __( 'You should select an owner for this Ad.', 'another-wordpress-classifieds-plugin');
        }

        if (is_null($data['payment_term'])) {
            $errors['payment-term'] = __( 'You should choose one of the available Payment Terms.', 'another-wordpress-classifieds-plugin');
        }

        if ( ! awpcp_current_user_is_admin() && ! is_null( $data['payment_term'] ) && $data['payment_term']->private ) {
            $message = __( 'The Payment Term you selected is not available for non-administrator users.', 'another-wordpress-classifieds-plugin' );
            $errors['payment-term'] = $message;
        }

        $errors = apply_filters(
            'awpcp-validate-post-listing-order',
            $errors, $data
        );

        return $errors;
    }

    private function validate_selected_categories( $data, $field_name, &$errors = array() ) {
        $allow_categories_in_parent  = ! get_awpcp_option( 'noadsinparentcat' );

        if ( empty( $data[ $field_name ] ) ) {
            $errors[ $field_name ] = __( 'Ad Category field is required', 'another-wordpress-classifieds-plugin' );
        }

        foreach ( $data[ $field_name ] as $category_id ) {
            if ( ! $allow_categories_in_parent && ! category_is_child( $category_id ) ) {
                $errors[ $field_name ] = __( "You cannot list your Ad in top level categories.", 'another-wordpress-classifieds-plugin' );
            }
        }
    }

    public function login_step() {
        $message = __( 'Hi, You need to be a registered user to post Ads in this website. Please use the form below to login or click the link to register.', 'another-wordpress-classifieds-plugin');

        $params = array(
            'message' => $message,
            'page_url' => add_query_arg( 'loggedin', true, awpcp_get_page_url( 'place-ad-page-name' ) ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-login-step.tpl.php';

        return $this->render( $template, $params );
    }

    public function order_step() {
        $form_errors = array();
        $transaction_errors = array();

        $pay_first = get_awpcp_option('pay-before-place-ad');
        $skip_payment_term_selection = false;

        $payments = $this->payments;
        $available_payment_terms = $payments->get_payment_terms();

        // validate submitted data and set relevant transaction attributes
        // phpcs:ignore WordPress.Security.NonceVerification
        if (!empty($_POST)) {
            $transaction = $this->get_transaction(true);

            if ($transaction->is_new()) {
                $payments->set_transaction_status_to_open($transaction, $transaction_errors);
            }

            $skip_payment_term_selection = $transaction->get( 'skip-payment-term-selection' );

            $user = awpcp_get_var(
                array(
                    'param'   => 'user',
                    'default' => intval( $transaction->user_id ),
                ),
                'post'
            );

            $category = awpcp_get_var(
                array(
                    'param'   => 'category',
                    'default' => null,
                ),
                'post'
            );
            $category = $this->get_posted_categories( $category, $transaction );

            if ( $skip_payment_term_selection ) {
                $payment_terms_list = null;
                $payment_options = null;

                $payment_terms = null;
                $payment_term = $payments->get_transaction_payment_term($transaction);
                $payment_type = $transaction->get( 'payment-term-payment-type' );
            } else {
                $payment_terms_list = awpcp_payment_terms_list();
                $payment_terms_list->handle_request();

                $payment_options = $payment_terms_list->get_data();

                if ( ! is_null( $payment_options ) ) {
                    $payment_term = $payment_options['payment_term'];
                    $payment_type = $payment_options['payment_type'];
                } else {
                    $payment_term = null;
                    $payment_type = '';
                }
            }

            $this->validate_order(compact('user', 'category', 'payment_term'), $form_errors);

            /** @phpstan-ignore-next-line */
            if (empty($form_errors) && empty($transaction_errors)) {
                $number_of_categories_allowed = apply_filters(
                    'awpcp-number-of-categories-allowed-in-post-listing-order-step', 1, $payment_term
                );

                $transaction->user_id = $user;
                $transaction->set( 'category', array_slice( $category, 0, $number_of_categories_allowed ) );

                if ( ! $skip_payment_term_selection ) {
                    $transaction->set( 'payment-term-type', $payment_term->type );
                    $transaction->set( 'payment-term-id', $payment_term->id );
                    $transaction->set( 'payment-term-payment-type', $payment_type );
                    $transaction->remove_all_items();

                    $this->payments->set_transaction_item_from_payment_term(
                        $transaction, $payment_term, $payment_type
                    );

                    // process transaction to grab Credit Plan information
                    $this->payments->set_transaction_credit_plan($transaction);
                }
            }

            // Ignore errors if category and user parameters were not sent. This
            // happens every time someone tries to place an Ad starting in the
            // Buy Subscription page.
            // phpcs:ignore WordPress.Security.NonceVerification
            if ( $skip_payment_term_selection && ! isset( $_POST['category'] ) ) {
                unset( $form_errors['category'] );
            }

            // phpcs:ignore WordPress.Security.NonceVerification
            if ( $skip_payment_term_selection && ! isset( $_POST['user'] ) ) {
                unset( $form_errors['user'] );
            }

            // let other parts of the plugin know a transaction is being processed
            $payments->process_transaction($transaction);
        } else {
            $transaction = null;

            $payment_terms_list = awpcp_payment_terms_list();
            $payment_options = null;

            $user = wp_get_current_user()->ID;
            $category = array();
            $payment_term = null;
        }

        // are we done here? what next?
        if ( ! empty( $category ) && ! is_null( $payment_term ) ) {
            /** @phpstan-ignore-next-line */
            if (empty($form_errors) && empty($transaction_errors)) {
                $payments->set_transaction_status_to_ready_to_checkout($transaction, $transaction_errors);

                /** @phpstan-ignore-next-line */
                if ($pay_first && empty($transaction_errors)) {
                    return $this->checkout_step();
                } elseif (empty($transaction_errors)) { /** @phpstan-ignore-line */
                    return $this->details_step();
                }
            }
        }

        // display initial form and show errors, if any
        $messages = $this->messages;
        if (awpcp_current_user_is_admin()) {
            $messages[] = __("You are logged in as an administrator. Any payment steps will be skipped.", 'another-wordpress-classifieds-plugin');
        }

        $params = array(
            'page' => $this,
            'payments' => $payments,
            'payment_terms_list' => $payment_terms_list,
            'payment_options' => $payment_options,
            'transaction' => $transaction,

            'skip_payment_term_selection' => $skip_payment_term_selection,

            'payment_terms' => $available_payment_terms,
            'categories' => awpcp_get_categories(),
            'form' => compact('category', 'user'),

            'messages' => $messages,
            'form_errors' => $form_errors,
            'transaction_errors' => $transaction_errors,
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-order-step.tpl.php';

        return $this->render($template, $params);
    }

    /**
     * TODO: Move to Create Empty and Save Information ajax handlers?
     */
    private function get_posted_categories( $categories, $transaction = null ) {
        if ( is_null( $categories ) && is_object( $transaction ) ) {
            $categories = $transaction->get( 'category', array( array() ) );
        }

        if ( ! is_array( $categories ) && $categories ) {
            $most_specific_categories = array( intval( $categories ) );
        } elseif ( ! is_array( $categories ) ) {
            $most_specific_categories = array();
        } elseif ( awpcp_is_array_of_arrays( $categories ) ) {
            $categories = array_map( 'array_filter', $categories );
            $categories = array_values( $categories );

            $most_specific_categories = array_map( 'end', $categories );
        } else {
            $most_specific_categories = array_map( 'intval', $categories );
        }

        return array_filter( $most_specific_categories );
    }

    public function checkout_step() {
        $transaction = $this->get_transaction();
        $payments = $this->payments;

        $errors = array();

        // verify transaction pre-conditions

        if (is_null($transaction)) {
            $message = $this->transaction_error();
            return $this->render('content', awpcp_print_error($message));
        }

        if ($transaction->is_payment_completed()) {
            return $this->payment_completed_step();
        }

        if ( $transaction->is_ready_to_checkout() ) {
            $payments->set_transaction_status_to_checkout( $transaction, $errors );
        }

        /** @phpstan-ignore-next-line */
        if ( empty( $errors ) && $transaction->payment_is_not_required() ) {
            $payments->set_transaction_status_to_payment_completed($transaction, $errors);

            /** @phpstan-ignore-next-line */
            if ( empty( $errors ) ) {
                return $this->payment_completed_step();
            }
        }

        if ( !$transaction->is_doing_checkout() && !$transaction->is_processing_payment() ) {
            // translators: %s is the transaction ID
            $message = sprintf( __( 'We can\'t process payments for this Payment Transaction at this time. Please contact the website administrator and provide the following transaction ID: %s', 'another-wordpress-classifieds-plugin'), esc_html( $transaction->id ) );
            $message = sprintf($message, $transaction->id);
            return $this->render('content', awpcp_print_error($message));
        }

        // proceess transaction to grab Payment Method information
        $payments->set_transaction_payment_method($transaction);

        // show checkout page.

        // If a Payment Method was already selected, the Payments API already
        // processed the transaction and will (depending of the Payment Method):
        // show a checkout button, show a billing information form or
        // automatically redirect the user to the payment gateway.

        $params = array(
            'payments' => $payments,
            'transaction' => $transaction,
            'messages' => $this->messages,
            'hidden'      => array( 'step' => 'checkout' ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-checkout-step.tpl.php';

        return $this->render($template, $params);
    }

    public function payment_completed_step() {
        $transaction = $this->get_transaction();
        $payments = $this->payments;
        $pay_first = get_awpcp_option('pay-before-place-ad');

        if ($pay_first && $transaction->payment_is_not_required()) {
            return $this->details_step();
        } elseif ($transaction->payment_is_not_required()) {
            return $this->finish_step();
        }

        $params = array(
            'payments' => $payments,
            'transaction' => $transaction,
            'messages' => $this->messages,
            'url' => $this->url(),
            'hidden'      => array( 'step' => $pay_first ? 'details' : 'finish' ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-payment-completed-step.tpl.php';

        return $this->render($template, $params);
    }

    protected function get_ad_info($ad_id) {
        $listing = $this->listings->get( $ad_id );
        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        $data = array(
            'ad_id' => $listing->ID,
            'user_id' => $listing->post_author,
            'adterm_id' => $payment_term->id,
            'ad_title' => $this->listing_renderer->get_listing_title( $listing ),
            'ad_contact_name' => $this->listing_renderer->get_contact_name( $listing ),
            'ad_contact_email' => $this->listing_renderer->get_contact_email( $listing ),
            'ad_contact_phone' => $this->listing_renderer->get_contact_phone( $listing ),
            'ad_category_id' => $this->listing_renderer->get_category_id( $listing ),
            'categories' => $this->listing_renderer->get_categories_ids( $listing ),
            'ad_item_price' => $this->listing_renderer->get_price( $listing ),
            'ad_details' => $listing->post_content,
            'websiteurl' => $this->listing_renderer->get_website_url( $listing ),
            'ad_startdate' => $this->listing_renderer->get_plain_start_date( $listing ),
            'ad_enddate' => $this->listing_renderer->get_plain_end_date( $listing ),
            'ad_key' => $this->listing_renderer->get_access_key( $listing ),
        );

        if ( get_awpcp_option('allowhtmlinadtext') ) {
            $data['ad_details'] = awpcp_esc_textarea( $data['ad_details'] );
        }

        $data['ad_category'] = $data['categories'];
        // please note we are dividing the Ad price by 100
        // Ad prices have been historically stored in cents
        $data['ad_item_price'] = $data['ad_item_price'] / 100;
        $data['start_date'] = $data['ad_startdate'];
        $data['end_date'] = $data['ad_enddate'];

        $data['regions'] = $this->listing_renderer->get_regions( $listing );

        return $data;
    }

    protected function get_user_info($user_id=false) {
        $user_id = $user_id === false ? get_current_user_id() : $user_id;

        $data = awpcp_users_collection()->find_by_id(
            $user_id,
            array(
                'ID', 'user_login', 'user_email', 'user_url', 'display_name',
                'public_name', 'first_name', 'last_name', 'nickname', 'awpcp-profile',
            )
        );

        $translations = array(
            'ad_contact_name' => 'public_name',
            'ad_contact_email' => 'user_email',
            'ad_contact_phone' => 'phone',
            'websiteurl' => 'user_url',
            'ad_country' => 'country',
            'ad_state' => 'state',
            'ad_city' => 'city',
            'ad_county_village' => 'county',
        );

        $info = array();

        foreach ( $translations as $field => $key ) {
            if ( isset( $data->$key ) && !empty( $data->$key ) ) {
                $info[ $field ] = $data->$key;
            }
        }

        if ( empty( $info['ad_contact_name'] ) ) {
            $info['ad_contact_name'] = trim( $data->first_name . " " . $data->last_name );
        }

        $user_region = array_filter( array(
            'country' => awpcp_array_data( 'ad_country', '', $info ),
            'state' => awpcp_array_data( 'ad_state', '', $info ),
            'city' => awpcp_array_data( 'ad_city', '', $info ),
            'county' => awpcp_array_data( 'ad_county_village', '', $info ),
        ), 'strlen' );

        if ( ! empty( $user_region ) ) {
            $info['regions'][] = $user_region;
        }

        $info = apply_filters( 'awpcp-listing-details-user-info', $info, $user_id );

        return $info;
    }

    protected function get_characters_allowed($ad_id, $transaction=null) {
        $max_characters_in_title = false;
        $remaining_characters_in_title = false;
        $remaining_characters_in_body = false;
        $max_characters_in_body = false;

        try {
            $ad = $this->listings->get( $ad_id );
        } catch ( AWPCP_Exception $e ) {
            $ad = null;
        }

        if ( ! is_null( $ad ) ) {
            $max_characters_in_title = $this->get_characters_allowed_in_title( $ad );
            $remaining_characters_in_title = $this->get_remaining_characters_in_title( $ad );
            $max_characters_in_body = $this->get_characters_allowed_in_content( $ad );
            $remaining_characters_in_body = $this->get_remaining_characters_in_content( $ad );

        } elseif (!is_null($transaction)) {
            $term = $this->payments->get_transaction_payment_term($transaction);
            if ($term) {
                $max_characters_in_title = $term->get_characters_allowed_in_title();
                $max_characters_in_body  = $term->get_characters_allowed();
            } else {
                $max_characters_in_title = 0;
                $max_characters_in_body  = get_awpcp_option( 'maxcharactersallowed' );
            }
            $remaining_characters_in_title = $max_characters_in_title;
            $remaining_characters_in_body  = $max_characters_in_body;
        }

        return array(
            'characters_allowed_in_title' => $max_characters_in_title,
            'remaining_characters_in_title' => $remaining_characters_in_title,
            'characters_allowed' => $max_characters_in_body,
            'remaining_characters' => $remaining_characters_in_body,
        );
    }

    protected function get_characters_allowed_in_title( $listing ) {
        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        if ( ! is_object( $payment_term ) ) {
            return 0;
        }

        return $payment_term->get_characters_allowed_in_title();
    }

    protected function get_remaining_characters_in_title( $listing ) {
        $allowed_characters_count = $this->get_characters_allowed_in_title( $listing );
        $listing_title = $this->listing_renderer->get_listing_title( $listing );
        return max( $allowed_characters_count - strlen( $listing_title ), 0 );
    }

    protected function get_characters_allowed_in_content( $listing ) {
        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        if ( ! is_object( $payment_term ) ) {
            return 0;
        }

        return $payment_term->get_characters_allowed();
    }

    protected function get_remaining_characters_in_content( $listing ) {
        $allowed_characters_count = $this->get_characters_allowed_in_content( $listing );
        return max( $allowed_characters_count - strlen( $listing->post_content ), 0 );
    }

    protected function get_regions_allowed( $ad_id, $transaction=null ) {
        $regions_allowed = 1;

        try {
            $ad = $this->listings->get( $ad_id );
        } catch ( AWPCP_Exception $e ) {
            $ad = null;
        }

        if ( ! is_null( $ad ) ) {
            $payment_term = $this->listing_renderer->get_payment_term( $ad );
            $regions_allowed = $payment_term->get_regions_allowed();
        } elseif ( ! is_null( $transaction ) ) {
            $term = $this->payments->get_transaction_payment_term( $transaction );
            if ( $term ) {
                $regions_allowed = $term->get_regions_allowed();
            }
        }

        return $regions_allowed;
    }

    protected function get_posted_details($from, $transaction=null) {
        $defaults = array(
            'user_id' => '',

            'ad_id' => '',
            'adterm_id' => '',
            'ad_category' => null,
            'ad_title' => '',
            'ad_contact_name' => '',
            'ad_contact_phone' => '',
            'ad_contact_email' => '',
            'websiteurl' => '',
            'ad_item_price' => '',
            'ad_details' => '',
            'ad_payment_term' => '',
            'is_featured_ad' => '',

            'regions' => array(),

            'start_date' => '',
            'end_date' => '',

            'characters_allowed' => '',
            'remaining_characters' => '',

            'user_payment_term' => '',

            'terms-of-service' => '',
        );

        $data = array();
        foreach ($defaults as $name => $default) {
            $value = awpcp_array_data( $name, $default, $from );

            if ( 'ad_item_price' === $name && isset( $from[ $name ] ) ) {
                $value = $from[ $name ];
            }

            $value = stripslashes_deep( $value );

            if ( $name != 'ad_details' ) {
                $value = awpcp_strip_all_tags_deep( $value );
            }

            $data[ $name ] = $value;
        }

        $data['ad_title'] = str_replace( array( "\r", "\n" ), '', $data['ad_title'] );
        $data['ad_details'] = str_replace( "\r", '', $data['ad_details'] );
        $data['websiteurl'] = awpcp_maybe_add_http_to_url( $data['websiteurl'] );

        if (empty($data['user_id'])) {
            $data['user_id'] = (int) awpcp_array_data('user', 0, $from);
        }

        $data['ad_category'] = $this->get_posted_categories(
            $data['ad_category'],
            $transaction
        );

        if (!is_null($transaction)) {
            $data['user_id'] = (int) awpcp_get_property($transaction, 'user_id', $data['user_id']);

            $payment_term_type = $transaction->get('payment-term-type');
            $payment_term_id = $transaction->get('payment-term-id');
            if (!empty($payment_term_type) && !empty($payment_term_id)) {
                $data['user_payment_term'] = "{$payment_term_type}-{$payment_term_id}";
                $data['ad_payment_term'] = "{$payment_term_type}-{$payment_term_id}";
            }

            $data['transaction_id'] = $transaction->id;
        }

        // parse the value provided by the user and convert it to a float value
        $data['ad_item_price'] = awpcp_parse_money( $data['ad_item_price'] );

        $data['is_featured_ad'] = absint($data['is_featured_ad']);

        $data = apply_filters( 'awpcp-get-posted-data', $data, 'details', $from );

        return $data;
    }

    public function details_form( $params = array(), $form = array(), $edit = false, $hidden = array(), $required = array(), $errors = array() ) {
        global $hasregionsmodule, $hasextrafieldsmodule;

        $is_admin_user = awpcp_current_user_is_admin();
        $is_moderator = awpcp_current_user_is_moderator();
        $payments_enabled = get_awpcp_option('freepay') == 1;
        $pay_first = get_awpcp_option('pay-before-place-ad');

        $messages = $this->messages;

        if ( $edit ) {
            $messages[] = __("Your Ad details have been filled out in the form below. Make any changes needed and then resubmit the Ad to update it.", 'another-wordpress-classifieds-plugin');
        } elseif ($is_admin_user) {
            $messages[] = __("You are logged in as an administrator. Any payment steps will be skipped.", 'another-wordpress-classifieds-plugin');
        } elseif (empty($errors)) {
            $messages[] = __( "Fill out the form below to post your classified ad.", 'another-wordpress-classifieds-plugin' );
        }

        if (!empty($errors)) {
            $message = __( "We found errors in the details you submitted. A detailed error message is shown in front or below each invalid field. Please fix the errors and submit the form again.", 'another-wordpress-classifieds-plugin' );
            $errors = array_merge(array($message), $errors);
        }

        if ( isset( $this->ad ) && is_object( $this->ad ) ) {
            $current_listing = $this->ad;
        } else {
            $current_listing = null;
        }

        $ui = array();
        $ui['listing-actions'] = !is_admin() && $edit;
        // show categories dropdown if $category is not set
        $ui['category-field'] = ( $edit || empty( $form['ad_category'] ) ) && $is_moderator;
        $ui['user-dropdown'] = $edit && $is_admin_user;
        $ui['show-start-date-field'] = $this->user_can_modify_start_date( $current_listing, $edit, $is_moderator );
        $ui['show-end-date-field'] = $edit && $is_moderator;

        if ( $ui['show-start-date-field'] && $ui['show-end-date-field'] ) {
            $ui['date-fields-title'] = __( 'Start & End Date', 'another-wordpress-classifieds-plugin' );
        } elseif ( $ui['show-start-date-field'] ) {
            $ui['date-fields-title'] = __( 'Start Date', 'another-wordpress-classifieds-plugin' );
        } elseif ( $ui['show-end-date-field'] ) {
            $ui['date-fields-title'] = __( 'End Date', 'another-wordpress-classifieds-plugin' );
        }

        // $ui['payment-term-dropdown'] = !$pay_first || ($is_admin_user && !$edit && $payments_enabled);
        $ui['website-field'] = get_awpcp_option('displaywebsitefield') == 1;
        $ui['website-field-required'] = get_awpcp_option('displaywebsitefieldreqop') == 1;
        $ui['contact-name-field-readonly'] = !empty( $form['ad_contact_name'] ) && !$is_moderator;
        $ui['contact-email-field-readonly'] = !empty( $form['ad_contact_email'] ) && !$is_moderator;
        $ui['price-field'] = get_awpcp_option('displaypricefield') == 1;
        $ui['price-field-required'] = get_awpcp_option('displaypricefieldreqop') == 1;
        $ui['allow-regions-modification'] = $is_moderator || !$edit || get_awpcp_option( 'allow-regions-modification' );
        $ui['price-field'] = get_awpcp_option('displaypricefield') == 1;
        $ui['extra-fields'] = $hasextrafieldsmodule && function_exists( 'awpcp_extra_fields_module' );
        $ui['terms-of-service'] = !$edit && !$is_moderator && get_awpcp_option('requiredtos');
        $ui['captcha'] = !$edit && !is_admin() && ( get_awpcp_option( 'captcha-enabled-in-place-listing-form' ) == 1 );

        $hidden['step'] = 'save-details';
        $hidden['ad_id'] = $form['ad_id'];
        $hidden['ad_category'] = $form['ad_category'];
        $hidden['adterm_id'] = $form['adterm_id'];

        // propagate preview parameter sent when this step is accesed from the
        // Preview Ad screen
        $hidden['preview-hash'] = awpcp_get_var(
            array(
                'param'   => 'preview-hash',
                'default' => false,
            ),
            'post'
        );
        $preview = strlen( $hidden['preview-hash'] ) > 0;

        if ( isset( $form['transaction_id'] ) ) {
            $hidden['transaction_id'] = $form['transaction_id'];
        }

        $page = $this;
        $url = $this->url();

        $transaction = $this->get_transaction();
        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-details-step.tpl.php';

        $params = array_merge(
            $params,
            compact('transaction', 'page', 'ui', 'messages', 'form', 'hidden', 'required', 'url', 'edit', 'preview', 'errors')
        );

        if ( $current_listing ) {
            $params['listing'] = $current_listing;
        }

        return $this->render($template, $params);
    }

    protected function user_can_modify_start_date( $listing, $is_editing, $is_moderator ) {
        if ( ! awpcp_get_option( 'allow-start-date-modification' ) ) {
            return $is_editing && $is_moderator;
        }

        if ( ! $is_editing || $is_moderator ) {
            return true;
        }

        if ( ! isset( $listing->ad_startdate ) ) {
            return false;
        }

        if ( strtotime( $listing->ad_startdate ) > current_time( 'timestamp' ) ) {
            return true;
        }

        return false;
    }

    public function details_step_form($transaction, $form=array(), $errors=array()) {
        $form = $this->get_posted_details($form, $transaction);
        $form = array_merge( $form, $this->get_characters_allowed( $form['ad_id'], $transaction ) );

        // pre-fill user information if we are placing a new Ad
        if ($transaction->user_id) {
            foreach ($this->get_user_info($transaction->user_id) as $field => $value) {
                $form[$field] = empty($form[$field]) ? $value : $form[$field];
            }
        }

        $form['regions-allowed'] = $this->get_regions_allowed( $form['ad_id'], $transaction );
        $form['regions'] = array_slice( $form['regions'], 0, $form['regions-allowed'] );

        // pref-fill ad information if we are editing a new Ad
        if ($transaction->get('ad-id', false)) {
            $ad_id = $transaction->get('ad-id', $form['ad_id']);
            foreach ($this->get_ad_info($ad_id) as $field => $value) {
                $form[$field] = empty($form[$field]) ? $value : $form[$field];
            }
        }

        $required = $this->get_required_fields();

        return $this->details_form( array(), $form, false, array(), $required, $errors);
    }

    public function details_step() {
        $transaction = $this->get_transaction( ! $this->get_settings()->get_option( 'pay-before-place-ad' ) );

        $errors = array();
        $form = array();

        if (is_null($transaction)) {
            $message = __("Hi, Payment is required for posting Ads in this website and we couldn't find a Payment Transaction assigned to you. You can't post Ads this time. If you think this is an error please contact the website Administrator.", 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        if (strcmp($this->get_current_action(), 'save-details') === 0) {
            return $this->save_details_step($transaction, $errors);
        } else {
            return $this->details_step_form($transaction, array(), $errors);
        }
    }

    /**
     * @param  array  $data     Normalized array with Ad details. All fields are expected
     *                          to be present: isset($data['param']) === true
     * @param  array  $errors
     * @return boolean          true if data validates, false otherwise
     */
    protected function validate_details($data=array(), $edit=false, $payment_term = null, &$errors=array()) {
        global $hasextrafieldsmodule;

        // $edit = !empty($data['ad_id']);

        $is_moderator = awpcp_current_user_is_moderator();

        $user_id = awpcp_array_data('user_id', 0, $data);
        $user_payment_term = awpcp_array_data('user_payment_term', '', $data);
        if (get_awpcp_option('freepay') == 1 && $user_id > 0 && empty($user_payment_term) && !$edit) {
            $errors['user_payment_term'] = __( 'You did not select a Payment Term. Please select a Payment Term for this Ad.', 'another-wordpress-classifieds-plugin');
        }

        $start_date = strtotime($data['start_date']);
        if ($edit && $is_moderator && empty($data['start_date'])) {
            $errors['start_date'] = __( 'Please enter a start date for the Ad.', 'another-wordpress-classifieds-plugin');
        }

        $end_date = strtotime($data['end_date']);
        if ($edit && $is_moderator && empty($data['end_date'])) {
            $errors['end_date'] = __( 'Please enter an end date for the Ad.', 'another-wordpress-classifieds-plugin');
        }

        if ($edit && $is_moderator && $start_date > $end_date) {
            $errors['start_date'] = __( 'The start date must occur before the end date.', 'another-wordpress-classifieds-plugin');
        }

        // Check for ad title
        if (empty($data['ad_title'])) {
            $errors['ad_title'] = __("You did not enter a title for your Ad", 'another-wordpress-classifieds-plugin');
        }

        // Check for ad details
        if (empty($data['ad_details'])) {
            $errors['ad_details'] = __("You did not enter any text for your Ad. Please enter some text for your Ad.", 'another-wordpress-classifieds-plugin');
        }

        // Check for ad category
        if ( $edit ) {
            $this->validate_selected_categories( $data, 'ad_category', $errors );
        }

        // If website field is checked and required make sure website value was entered
        if ((get_awpcp_option('displaywebsitefield') == 1) &&
            (get_awpcp_option('displaywebsitefieldreqop') == 1))
        {
            if (empty($data['websiteurl'])) {
                $errors['websiteurl'] = __("You did not enter your website address. Your website address is required.",'another-wordpress-classifieds-plugin');
            }
        }

        //If they have submitted a website address make sure it is correctly formatted
        if (!empty($data['websiteurl']) && !isValidURL($data['websiteurl'])) {
            $errors['websiteurl'] = __("Your website address is not properly formatted. Please make sure you have included the http:// part of your website address",'another-wordpress-classifieds-plugin');
        }

        // Check for ad poster's name
        if (empty($data['ad_contact_name'])) {
            $errors['ad_contact_name'] = __("You did not enter your name. Your name is required.", 'another-wordpress-classifieds-plugin');
        }

        // Check for ad poster's email address
        if (empty($data['ad_contact_email'])) {
            $errors['ad_contact_email'] = __("You did not enter your email. Your email is required.", 'another-wordpress-classifieds-plugin');
        }

        // Check if email address entered is in a valid email address format
        if ( ! awpcp_is_valid_email_address( $data['ad_contact_email'] ) ) {
            $errors['ad_contact_email'] = __("The email address you entered was not a valid email address. Please check for errors and try again.", 'another-wordpress-classifieds-plugin');
        } elseif ( ! awpcp_is_email_address_allowed( $data['ad_contact_email'] ) ) {
            $domains_whitelist = explode( "\n", get_awpcp_option( 'ad-poster-email-address-whitelist' ) );
            // translators: %s is a comma separated list of domain names.
            $message = sprintf( __( 'The email address you entered is not allowed in this website. Please use an email address from one of the following domains: %s.', 'another-wordpress-classifieds-plugin'), implode( ', ', $domains_whitelist ) );
            $domains_whitelist = explode( "\n", get_awpcp_option( 'ad-poster-email-address-whitelist' ) );
            $domains_list = '<strong>' . implode( '</strong>, <strong>', $domains_whitelist ) . '</strong>';
            $errors['ad_contact_email'] = sprintf( $message, $domains_list );
        }

        // If phone field is checked and required make sure phone value was entered
        if ((get_awpcp_option('displayphonefield') == 1) &&
            (get_awpcp_option('displayphonefieldreqop') == 1))
        {
            if (empty($data['ad_contact_phone'])) {
                $errors['ad_contact_phone'] = __("You did not enter your phone number. Your phone number is required.", 'another-wordpress-classifieds-plugin');
            }
        }

        $region_fields = array();
        foreach ( $data['regions'] as $region ) {
            foreach ( $region as $type => $value ) {
                if ( !empty( $value ) ) {
                    $region_fields[ $type ] = true;
                }
            }
        }

        // If country field is checked and required make sure country value was entered
        if ( $payment_term->regions > 0 && (get_awpcp_option('displaycountryfield') == 1) &&
            (get_awpcp_option('displaycountryfieldreqop') == 1))
        {
            if ( ! awpcp_array_data( 'country', false, $region_fields ) ) {
                $errors['regions'] = __("You did not enter your country. Your country is required.", 'another-wordpress-classifieds-plugin');
            }
        }

        // If state field is checked and required make sure state value was entered
        if ( $payment_term->regions > 0 && (get_awpcp_option('displaystatefield') == 1) &&
            (get_awpcp_option('displaystatefieldreqop') == 1))
        {
            if ( ! awpcp_array_data( 'state', false, $region_fields ) ) {
                $errors['regions'] = __("You did not enter your state. Your state is required.", 'another-wordpress-classifieds-plugin');
            }
        }

        // If city field is checked and required make sure city value was entered
        if ( $payment_term->regions > 0 && (get_awpcp_option('displaycityfield') == 1) &&
            (get_awpcp_option('displaycityfieldreqop') == 1))
        {
            if ( ! awpcp_array_data( 'city', false, $region_fields ) ) {
                $errors['regions'] = __("You did not enter your city. Your city is required.", 'another-wordpress-classifieds-plugin');
            }
        }

        // If county/village field is checked and required make sure county/village value was entered
        if ( $payment_term->regions > 0 && (get_awpcp_option('displaycountyvillagefield') == 1) &&
            (get_awpcp_option('displaycountyvillagefieldreqop') == 1))
        {
            if ( ! awpcp_array_data( 'county', false, $region_fields ) ) {
                $errors['regions'] = __("You did not enter your county/village. Your county/village is required.", 'another-wordpress-classifieds-plugin');
            }
        }

        // If price field is checked and required make sure a price has been entered
        if ( get_awpcp_option('displaypricefield') == 1 && get_awpcp_option('displaypricefieldreqop') == 1 ) {
            if ( strlen($data['ad_item_price']) === 0 || $data['ad_item_price'] === false )
                $errors['ad_item_price'] = __("You did not enter the price of your item. The item price is required.",'another-wordpress-classifieds-plugin');
        }

        // Make sure the item price is a numerical value
        if ( get_awpcp_option('displaypricefield') == 1 && strlen( $data['ad_item_price'] ) > 0 ) {
            if ( !is_numeric( $data['ad_item_price'] ) )
                $errors['ad_item_price'] = __("You have entered an invalid item price. Make sure your price contains numbers only. Please do not include currency symbols.",'another-wordpress-classifieds-plugin');
        }

        if ($hasextrafieldsmodule == 1) {
            // backward compatibility with old extra fields
            if (function_exists('validate_extra_fields_form')) {
                $_errors = validate_extra_fields_form($data['ad_category']);
            } elseif (function_exists('validate_x_form')) {
                $_errors = validate_x_form();
            }

            if (isset($_errors) && !empty($_errors)) {
                $errors = array_merge($errors, (array) $_errors);
            }
        }

        // Terms of service required and accepted?
        if (!$edit && !$is_moderator && get_awpcp_option('requiredtos') && empty($data['terms-of-service'])) {
            $errors['terms-of-service'] = __("You did not accept the terms of service", 'another-wordpress-classifieds-plugin');
        }

        if ( !$edit && !is_admin() && get_awpcp_option( 'captcha-enabled-in-place-listing-form' ) ) {
            $captcha = awpcp_create_captcha( get_awpcp_option( 'captcha-provider' ) );

            $error = '';
            if ( !$captcha->validate( $error ) ) {
                $errors['captcha'] = $error;
            }
        }

        if ( get_awpcp_option( 'use-akismet-in-place-listing-form' ) ) {
            $spam_filter = awpcp_listing_spam_filter();

            if ( $spam_filter->is_spam( $data ) ) {
                $errors[] = __("Your Ad was flagged as spam. Please contact the administrator of this site.", 'another-wordpress-classifieds-plugin');
            }
        }

        $errors = apply_filters(
            'awpcp-validate-post-listing-details',
            $errors, $data, $payment_term
        );

        return count(array_filter($errors)) === 0;
    }

    protected function prepare_ad_title($title, $characters) {
        $$title = $title;

        if ( $characters > 0 && awpcp_utf8_strlen( $title ) > $characters ) {
            $title = awpcp_utf8_substr( $title, 0, $characters );
        }

        return $title;
    }

    protected function prepare_ad_details($details, $characters) {
        $allow_html = (bool) get_awpcp_option('allowhtmlinadtext');

        if (!$allow_html) {
            $details = wp_strip_all_tags( $details );
        } else {
            $details = wp_kses_post( $details );
        }

        if ( $characters > 0 && awpcp_utf8_strlen( $details ) > $characters ) {
            $details = awpcp_utf8_substr( $details, 0, $characters );
        }

        if ($allow_html) {
            $details = force_balance_tags($details);
        }else{
            $details = esc_html( $details );
        }

        return $details;
    }

    public function save_details_step($transaction, $errors=array()) {
        global $wpdb, $hasextrafieldsmodule;

        // phpcs:ignore WordPress.Security.NonceVerification
        $data = $this->get_posted_details( $_POST, $transaction );
        $characters = $this->get_characters_allowed( $data['ad_id'], $transaction );
        $errors = array();

        $payment_term = $this->payments->get_transaction_payment_term( $transaction );

        if (!$this->validate_details($data, false, $payment_term, $errors)) {
            return $this->details_step_form($transaction, $data, $errors);
        }

        if ($transaction->get('ad-id')) {
            $ad = $this->listings->get( $transaction->get( 'ad-id' ) );
        } else {
            $now = current_time('mysql');

            $payment_term_id = $transaction->get( 'payment-term-id' );
            $payment_term_type = $transaction->get( 'payment-term-type' );

            $amount_paid = $transaction->get_totals();

            $listing_data = array(
                'post_fields' => array(
                    'post_title' => 'Listing Draft',
                    'post_date' => $now,
                    'post_date_gmt' => get_gmt_from_date( $now ),
                ),
                'metadata' => array(
                    '_awpcp_payment_term_type' => $payment_term_type,
                    '_awpcp_payment_term_id' => $payment_term_id,
                    '_awpcp_payment_amount' => $amount_paid['money'],
                    '_awpcp_transaction_id' => $transaction->id,
                    '_awpcp_start_date' => $now,
                    '_awpcp_end_date' => $payment_term->calculate_end_date( strtotime( $now ) ),
                    '_awpcp_is_paid' => $amount_paid['money'] > 0,
                ),
            );

            try {
                $ad = $this->listings_logic->create_listing( $listing_data );
            } catch ( AWPCP_Exception $e ) {
                $errors[] = $e->getMessage();
                return $this->details_step_form( $transaction, $data, $errors );
            }
        }

        if ( !$transaction->get('ad-id') || $this->verify_preview_hash($ad) ) {
            $listing_data = array(
                'post_fields' => array(
                    'ID' => $ad->ID,
                    'post_author' => $data['user_id'],
                    'post_title' => $this->prepare_ad_title( $data['ad_title'], $characters['characters_allowed_in_title'] ),
                    'post_name' => '', // Force wp_insert_post() to calculate this again.
                    'post_content' => $this->prepare_ad_details( $data['ad_details'], $characters['characters_allowed'] ),
                    'post_modified' => $now,
                    'post_modified_gmt' => get_gmt_from_date( $now ),
                ),
                'metadata' => array(
                    '_awpcp_contact_name' => $data['ad_contact_name'],
                    '_awpcp_contact_phone' => $data['ad_contact_phone'],
                    '_awpcp_contact_email' => $data['ad_contact_email'],
                    '_awpcp_website_url' => $data['websiteurl'],
                    '_awpcp_price' => $data['ad_item_price'] * 100,
                    '_awpcp_poster_ip' => awpcp_getip(),
                ),
                'terms' => array(
                    AWPCP_CATEGORY_TAXONOMY => array_map( 'intval', $data['ad_category'] ),
                ),
                'regions' => $data['regions'],
                'regions-allowed' => $this->get_regions_allowed( $ad->ID, $transaction ),
            );

            if ( $this->user_can_modify_start_date( null, false, awpcp_current_user_is_moderator() ) ) {
                $listing_data['metadata']['_awpcp_start_date'] = $data['start_date'];
            } else {
                $listing_data['metadata']['_awpcp_start_date'] = $now;
            }

            $start_date_timestamp = awpcp_datetime( 'timestamp', $listing_data['metadata']['_awpcp_start_date'] );
            $end_date = $payment_term->calculate_end_date( $start_date_timestamp );
            $listing_data['metadata']['_awpcp_end_date'] = $end_date;

            do_action( 'awpcp-before-save-listing', $ad, $data );

            // TODO: make sure the Featured Ads module sets this meta attribute properly
            $ad->is_featured_ad = $data['is_featured_ad'];
            // TODO: Pass $data
            $listing_data = apply_filters( 'awpcp-place-listing-listing-data', $listing_data, $ad );

            try {
                $this->listings_logic->update_listing( $ad, $listing_data );
            } catch ( AWPCP_Exception $e ) {
                $errors[] = $e->getMessage();
                return $this->details_step_form( $transaction, $data, $errors );
            }

            $transaction->set('ad-id', $ad->ID);

            do_action('awpcp-save-ad-details', $ad, $transaction);

            $transaction->save();
        }

        $preview_hash = awpcp_get_var( array( 'param' => 'preview-hash' ), 'post' );
        if ( $preview_hash ) {
            return $this->preview_step();
        }

        if ( $this->should_show_upload_files_step( $ad ) ) {
            return $this->upload_images_step();
        }

        if ( (bool) get_awpcp_option( 'pay-before-place-ad' ) ) {
            return $this->finish_step();
        }

        if ( (bool) get_awpcp_option( 'show-ad-preview-before-payment' ) ) {
            return $this->preview_step();
        }

        return $this->checkout_step();
    }

    protected function should_show_upload_files_step( $listing ) {
        return $this->listing_upload_limits->are_uploads_allowed_for_listing( $listing );
    }

    public function get_images_config( $ad ) {
        $upload_limits = $this->listing_upload_limits->get_listing_upload_limits( $ad );

        if ( isset( $upload_limits['images']['allowed_file_count'] ) ) {
            $images_allowed = $upload_limits['images']['allowed_file_count'];
        } else {
            $images_allowed = 0;
        }

        if ( isset( $upload_limits['images']['uploaded_file_count'] ) ) {
            $images_uploaded = $upload_limits['images']['uploaded_file_count'];
        } else {
            $images_uploaded = 0;
        }

        return array(
            'images_allowed' => $images_allowed,
            'images_uploaded' => $images_uploaded,
        );
    }

    public function upload_images_step() {
        $transaction = $this->get_transaction();

        if (is_null($transaction)) {
            $message = __( 'We were unable to find a Payment Transaction assigned to this operation. No images can be added at this time.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        try {
            $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );
        } catch ( AWPCP_Exception $e ) {
            $message = __( 'The specified Ad doesn\'t exists. No images can be added at this time.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        $params = $this->get_images_config( $ad );
        extract( $params );

        // see if we can move to the next step
        $skip = ! $this->should_show_upload_files_step( $ad );
        $skip = $skip || awpcp_get_var( array( 'param' => 'submit-no-images' ), 'post' );
        $skip = $skip || $images_allowed == 0;

        $show_preview = (bool) get_awpcp_option('show-ad-preview-before-payment');
        $pay_first = (bool) get_awpcp_option('pay-before-place-ad');

        if ( $skip && $show_preview ) {
            return $this->preview_step();
        } elseif ( $skip && $pay_first ) {
            return $this->finish_step();
        } elseif ( $skip ) {
            return $this->checkout_step();
        } else {
            return $this->show_upload_images_form( $ad, $transaction, $params, array() );
        }
    }

    protected function show_upload_images_form( $ad, $transaction, $params, $errors ) {
        $allowed_files = $this->listing_upload_limits->get_listing_upload_limits( $ad );

        $params = array_merge( $params, array(
            'transaction' => $transaction,
            'hidden' => array( 'transaction_id' => $transaction->id ),
            'errors' => $errors,
            'media_manager_configuration' => array(
                'nonce' => wp_create_nonce( 'awpcp-manage-listing-media-' . $ad->ID ),
                'allowed_files' => $allowed_files,
                'show_admin_actions' => awpcp_current_user_is_moderator(),
            ),
            'media_uploader_configuration' => array(
                'listing_id' => $ad->ID,
                'context' => 'post-listing',
                'nonce' => wp_create_nonce( 'awpcp-upload-media-for-listing-' . $ad->ID ),
                'allowed_files' => $allowed_files,
            ),
        ) );

        return $this->upload_images_form( $ad, $params );
    }

    public function upload_images_form( $ad, $params=array() ) {
        $show_preview = (bool) get_awpcp_option('show-ad-preview-before-payment');
        $pay_first = (bool) get_awpcp_option('pay-before-place-ad');
        $payments_enabled = awpcp_get_option( 'freepay' ) == 1;

        extract( $params );

        if ( $show_preview ) {
            $next = _x( 'Preview Ad', 'upload listing images form', 'another-wordpress-classifieds-plugin' );
        } elseif ( $pay_first || ! $payments_enabled ) {
            $next = _x( 'Place Ad', 'upload listing images form', 'another-wordpress-classifieds-plugin' );
        } else {
            $next = _x( 'Checkout', 'upload listing images form', 'another-wordpress-classifieds-plugin' );
        }

        $params = array_merge( $params, array(
            'listing' => $ad,
            'files' => $this->attachments->find_attachments( array( 'post_parent' => $ad->ID ) ),
            'messages' => $this->messages,
            'next' => $next,
        ) );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-upload-images-step.tpl.php';

        return $this->render( $template, $params );
    }

    public function preview_step() {
        $transaction = $this->get_transaction();

        if ( is_null( $transaction ) ) {
            $message = __( 'We were unable to find a Payment Transaction assigned to this operation.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        try {
            $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );
        } catch ( AWPCP_Exception $e ) {
            $message = __( 'The Ad associated with this transaction doesn\'t exists.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        $pay_first = (bool) get_awpcp_option('pay-before-place-ad');

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_POST['edit-details'] ) ) {
            return $this->details_step();
        }

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_POST['manage-images'] ) ) {
            return $this->upload_images_step();
        }

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( $pay_first && isset( $_POST['finish'] ) ) {
            return $this->finish_step();
        }

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_POST['finish'] ) ) {
            return $this->checkout_step();
        }

        $payment_term = $this->listing_renderer->get_payment_term( $ad );
        $manage_images = awpcp_are_images_allowed() && $payment_term->images > 0;

        $params = array(
            'page' => $this,
            'ad' => $ad,
            'edit' => false,
            'messages' => $this->messages,
            'hidden' => array(
                'preview-hash' => $this->get_preview_hash( $ad ),
                'transaction_id' => $transaction->id,
            ),
            'ui' => array(
                'manage-images' => $manage_images,
            ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-preview-step.tpl.php';

        return $this->render($template, $params);
    }

    public function finish_step() {
        $transaction = $this->get_transaction();

        $messages = $this->messages;
        $send_email = false;

        if (is_null($transaction)) {
            $message = __( 'We were unable to find a Payment Transaction assigned to this operation.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        try {
            $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );
        } catch ( AWPCP_Exception $e ) {
            $message = __( 'The Ad associated with this transaction doesn\'t exists.', 'another-wordpress-classifieds-plugin');
            return $this->render('content', awpcp_print_error($message));
        }

        if (!$transaction->is_completed()) {
            $errors = array();
            $this->payments->set_transaction_status_to_completed( $transaction, $errors );

            /** @phpstan-ignore-next-line */
            if (!empty($errors)) {
                return $this->render('content', join(',', array_map('awpcp_print_error', $errors)));
            }

            $transaction->save();
        }

        // reload Ad, since modifications were probably made as part of the
        // transaction handling workflow
        $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );

        $params = array(
            'edit' => false,
            'ad' => $ad,
            'messages' => array_merge( $messages, $this->listings_logic->get_ad_alerts( $ad ) ),
            'transaction' => $transaction,
            'transaction_id' => $transaction->id,
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-finish-step.tpl.php';

        return $this->render($template, $params);
    }
}
