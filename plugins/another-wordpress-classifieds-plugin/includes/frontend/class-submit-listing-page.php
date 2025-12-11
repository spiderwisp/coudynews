<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Submit Listing Page.
 */
class AWPCP_SubmitListingPage extends AWPCP_Page {

    /**
     * @var bool
     */
    public $show_menu_items = false;

    /**
     * @var object|bool|null
     */
    private $listing = false;

    /**
     * @var AWPCP_Payment_Transaction|bool|null
     */
    private $transaction = false;

    /**
     * @var AWPCP_SubmitLisitngSectionsGenerator
     */
    private $sections_generator;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_ListingsAPI
     */
    protected $listings_logic;

    /**
     * @var AWPCP_ListingsCollection
     */
    protected $listings;

    /**
     * @var AWPCP_ListingAuthorization
     */
    private $authorization;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_Settings_API
     */
    protected $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $sections_generator, $listing_renderer, $listings_logic, $listings, $authorization, $payments, $settings ) {
        parent::__construct( null, null, awpcp()->container['TemplateRenderer'] );

        $this->sections_generator = $sections_generator;
        $this->listing_renderer   = $listing_renderer;
        $this->listings_logic     = $listings_logic;
        $this->listings           = $listings;
        $this->authorization      = $authorization;
        $this->payments           = $payments;
        $this->settings           = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function dispatch() {
        try {
            return $this->process_request();
        } catch ( AWPCP_Exception $e ) {
            return $this->render( 'content', $e->getMessage() );
        }
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception If the current user is not allowed to access the page.
     */
    private function process_request() {
        if ( ! $this->authorization->is_current_user_allowed_to_submit_listing() ) {
            $message = __( 'Users are not allowed to publish ads at this time.', 'another-wordpress-classifieds-plugin' );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_Exception( awpcp_print_error( $message ) );
        }

        if ( $this->settings->get_option( 'requireuserregistration' ) && ! is_user_logged_in() ) {
            return $this->do_login_step();
        }

        $listing     = $this->get_listing();
        $transaction = $this->get_transaction();

        if ( is_object( $listing ) && ! is_object( $transaction ) && ! $this->authorization->is_current_user_allowed_to_edit_listing( $listing ) ) {
            $message = __( 'You are not allowed to edit the specified ad.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        // If payment information cannot be changed and there is no transaction available,
        // it is likely that the listing was already posted and someone is trying to
        // edit it from the Submit Listing page.
        if ( is_object( $listing ) && ! is_object( $transaction ) && ! $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            $message = __( 'The information for the selected ad was already saved.', 'another-wordpress-classifieds-plugin' );

            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        return $this->do_current_step();
    }

    /**
     * @since 4.0.0
     */
    private function do_current_step() {
        $step = $this->get_current_step();

        switch ( $step ) {
            case 'listing-information':
                return $this->do_listing_information_step();
            case 'checkout':
                return $this->do_checkout_step();
            case 'payment-completed':
                return $this->do_payment_completed_step();
            case 'finish':
                return $this->do_finish_step();
            default:
                return $this->do_initial_step();
        }
    }

    /**
     * @since 4.0.0
     */
    private function get_current_step() {
        $step = awpcp_get_var( array( 'param' => 'step' ) );
        return awpcp_get_var( array( 'param' => 'step', 'default' => $step ), 'post' );
    }

    /**
     * @since 4.0.0
     */
    public function get_transaction() {
        // We compare with false instead of using is_null() because get_transaction()
        // may return null the first time the page is loaded.
        if ( false === $this->transaction ) {
            $this->transaction = $this->payments->get_transaction();
        }

        return $this->transaction;
    }

    /**
     * @since 4.0.0
     */
    private function get_listing() {
        if ( false === $this->listing ) {
            $transaction = $this->get_transaction();
            $listing_id  = awpcp_get_var( array( 'param' => 'listing_id' ) );

            if ( ! $listing_id && $transaction ) {
                $listing_id = $transaction->get( 'ad-id' );
            }

            $this->listing = null;

            if ( $listing_id ) {
                $this->listing = $this->listings->get( $listing_id );
            }
        }

        return $this->listing;
    }

    /**
     * TODO: Remove form steps from login page?
     *
     * @since 4.0.0
     */
    public function do_login_step() {
        $message = __( 'Hi, You need to be a registered user to post Ads in this website. Please use the form below to login or click the link to register.', 'another-wordpress-classifieds-plugin' );

        $params = array(
            'message'  => $message,
            'page_url' => add_query_arg( 'loggedin', true, awpcp_get_page_url( 'place-ad-page-name' ) ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-login-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function do_initial_step() {
        if ( $this->payments->payments_enabled() && $this->settings->get_option( 'pay-before-place-ad' ) ) {
            return $this->do_select_category_step();
        }

        return $this->do_listing_information_step();
    }

    /**
     * @since 4.0.0
     */
    private function do_select_category_step() {
        return $this->render_submit_listing_sections( 'listing-category' );
    }

    /**
     * @since 4.0.0
     */
    private function render_submit_listing_sections( $current_step ) {
        $transaction = $this->payments->get_transaction();

        $this->verify_payment_transaction_was_successful( $transaction );

        $listing_id = awpcp_get_var( array( 'param' => 'listing_id' ) );
        $listing    = null;

        if ( ! $listing_id && $transaction ) {
            $listing_id = $transaction->get( 'ad-id' );
        }

        if ( $listing_id ) {
            $listing = $this->listings->get( $listing_id );
        }

        do_action( 'awpcp-before-post-listing-page' );

        wp_enqueue_script( 'awpcp-submit-listing-page' );

        $params = [
            'current_step' => $current_step,
            'transaction'  => $transaction,
            'page_data' => [
                'sections'         => $this->sections_generator->get_sections( [], 'create', $listing, $transaction ),
                'mode'             => 'create',
                'payments_enabled' => get_awpcp_option('freepay'),
            ],
        ];

        $template = AWPCP_DIR . '/templates/frontend/submit-listing-page/listing-information-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function do_listing_information_step() {
        return $this->render_submit_listing_sections( 'listing-information' );
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  When the transaction was not created to submit a lsiting
     *                          or the associated payment failed.
     */
    private function verify_payment_transaction_was_successful( $transaction ) {
        if ( is_null( $transaction ) ) {
            return;
        }

        if ( $transaction->get( 'context' ) !== 'place-ad' ) {
            $page_name = awpcp_get_page_name( 'place-ad-page-name' );
            $page_url  = awpcp_get_page_url( 'place-ad-page-name' );

            /* translators: %1$s URL, %2$s name of the submit listing page, %3$s an alphanumeric string that identifies a payment transaction. */
            $message = __( 'You are trying to post an Ad using a transaction created for a different purpose. Please go back to the <a href="%1$s">%2$s</a> page.<br>If you think this is an error please contact the administrator and provide the following transaction ID: %3$s', 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $page_url, $page_name, $transaction->id );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_Exception( awpcp_print_error( $message ) );
        }

        if ( ! $transaction->is_payment_completed() ) {
            return;
        }

        if ( $transaction->payment_is_unknown() || $transaction->payment_is_invalid() || $transaction->payment_is_failed() ) {
            $message = __( 'You can\'t post an Ad at this time because the payment associated with this transaction failed (see reasons below).', 'another-wordpress-classifieds-plugin' );
            $message = awpcp_print_message( $message );
            $message = $message . $this->payments->render_transaction_errors( $transaction );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_Exception( awpcp_print_error( $message ) );
        }
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception When the specified payment transaction cannot be
     *                         processed for checkout.
     */
    private function do_checkout_step() {
        $transaction = $this->payments->get_transaction();

        // Verify transaction pre-conditions.
        if ( is_null( $transaction ) ) {
            $message = $this->transaction_error();
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        if ( $transaction->is_payment_completed() ) {
            return $this->do_payment_completed_step();
        }

        $errors = array();

        if ( $transaction->is_ready_to_checkout() ) {
            $this->payments->set_transaction_status_to_checkout( $transaction, $errors );
        }

        if ( empty( $errors ) && $transaction->payment_is_not_required() ) {
            $this->payments->set_transaction_status_to_payment_completed( $transaction, $errors );

            if ( empty( $errors ) ) {
                return $this->do_payment_completed_step();
            }
        }

        if ( ! $transaction->is_doing_checkout() && ! $transaction->is_processing_payment() ) {
            /* translators: %s is an alphanumeric string that identifies a payment transaction. */
            $message = __( 'We can\'t process payments for this Payment Transaction at this time. Please contact the website administrator and provide the following transaction ID: %s', 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $transaction->id );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_Exception( awpcp_print_error( $message ) );
        }

        // Proceess transaction to grab Payment Method information.
        $this->payments->set_transaction_payment_method( $transaction );

        // Show checkout page.
        //
        // If a Payment Method was already selected, the Payments API already
        // processed the transaction and will (depending of the Payment Method):
        // show a checkout button, show a billing information form or
        // automatically redirect the user to the payment gateway.
        $params = array(
            'payments'    => $this->payments,
            'transaction' => $transaction,
            'messages'    => [],
            'hidden'      => array( 'step' => 'checkout' ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-checkout-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function do_payment_completed_step() {
        $transaction = $this->payments->get_transaction();
        $pay_first   = $this->settings->get_option( 'pay-before-place-ad' );

        if ( $pay_first && $transaction->payment_is_not_required() ) {
            return $this->do_listing_information_step();
        } elseif ( $transaction->payment_is_not_required() ) {
            return $this->do_finish_step();
        }

        $params = array(
            'payments'    => $this->payments,
            'transaction' => $transaction,
            'messages'    => [],
            'url'         => $this->url(),
            'hidden'      => array( 'step' => $pay_first ? 'listing-information' : 'finish' ),
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-payment-completed-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function do_finish_step() {
        $transaction = $this->payments->get_transaction();

        if ( is_null( $transaction ) ) {
            $message = __( 'We were unable to find a Payment Transaction assigned to this operation.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        try {
            $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );
        } catch ( AWPCP_Exception $e ) {
            $message = __( 'The Ad associated with this transaction doesn\'t exists.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        if ( ! $transaction->is_completed() ) {
            $errors = array();
            $this->payments->set_transaction_status_to_completed( $transaction, $errors );

            if ( ! empty( $errors ) ) {
                return $this->render( 'content', join( ',', array_map( 'awpcp_print_error', $errors ) ) );
            }

            $transaction->save();
        }

        // Reload Ad, since modifications were probably made as part of the
        // transaction handling workflow.
        $ad = $this->listings->get( $transaction->get( 'ad-id', 0 ) );

        $params = array(
            'edit'           => false,
            'ad'             => $ad,
            'messages'       => $this->get_listing_messages( $ad ),
            'transaction'    => $transaction,
            'transaction_id' => $transaction->id,
        );

        $template = AWPCP_DIR . '/frontend/templates/page-place-ad-finish-step.tpl.php';

        // Do not show the Classifieds Bar in ad previews.
        remove_filter( 'awpcp-content-before-listing-page', 'awpcp_insert_classifieds_bar_before_listing_page' );

        return $this->render( $template, $params );
    }

    /**
     * @since 4.0.0
     */
    protected function get_listing_messages( $listing ) {
        $messages = [];

        if ( $this->listing_renderer->is_public( $listing ) ) {
            $messages[] = __( 'Your listing has been published. This is the content that will be seen by visitors of the website.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $this->listing_renderer->is_pending_approval( $listing ) ) {
            $messages[] = sprintf(
                '%s %s',
                __( 'Your listing has been submitted.', 'another-wordpress-classifieds-plugin' ),
                __( 'This is a preview of the content as seen by visitors of the website.', 'another-wordpress-classifieds-plugin' )
            );
        }

        if ( $this->listing_renderer->is_disabled( $listing ) ) {
            $messages[] = __( 'Your listing has been submitted but it requires your email address to be validated. To do so, please click on the verification link we have sent to the email address used to post the listing.', 'another-wordpress-classifieds-plugin' );
            $messages[] = __( 'This is a preview of the content as seen by visitors of the website.', 'another-wordpress-classifieds-plugin' );
        }

        return array_merge( $messages, $this->listings_logic->get_ad_alerts( $listing ) );
    }
}
