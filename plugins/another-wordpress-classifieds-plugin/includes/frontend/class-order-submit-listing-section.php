<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Order section for the Submit Listing page.
 */
class AWPCP_OrderSubmitListingSection {

    use AWPCP_SubmitListingSectionTrait;

    /**
     * @var string
     */
    private $template = 'frontend/order-submit-listing-section.tpl.php';

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $payments;

    /**
     * @var object
     */
    private $roles;

    /**
     * @var AWPCP_CAPTCHA
     */
    private $captcha;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $payments, $listings_logic, $listing_renderer, $roles, $captcha, $template_renderer, $settings ) {
        $this->payments          = $payments;
        $this->listings_logic    = $listings_logic;
        $this->listing_renderer  = $listing_renderer;
        $this->roles             = $roles;
        $this->captcha           = $captcha;
        $this->template_renderer = $template_renderer;
        $this->settings          = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function get_id() {
        return 'order';
    }

    /**
     * @since 4.0.0
     */
    public function get_position() {
        return 5;
    }

    /**
     * See AWPCP_OrderSubmitListingSectionTest::test_get_state_returns_preview().
     *
     * @since 4.0.0
     */
    public function get_state( $listing ) {
        if ( is_null( $listing ) ) {
            return 'edit';
        }

        if ( ! $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            return 'read';
        }

        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        if ( $payment_term ) {
            return 'preview';
        }

        return 'edit';
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
        awpcp_enqueue_select2();
    }

    /**
     * TODO: Lock this section for regular users that just completed payment.
     *
     * @since 4.0.0
     */
    public function render( $listing, $transaction ) {
        $messages = [];

        if ( awpcp_current_user_is_admin() ) {
            $messages[] = __( 'You are logged in as an administrator. Any payment steps will be skipped.', 'another-wordpress-classifieds-plugin' );
        }

        $stored_data = $this->get_stored_data( $listing, $transaction );
        $nonces      = $this->maybe_generate_nonces( $listing );

        $payment_terms = $this->payments->get_payment_terms();
        $payment_terms = apply_filters( 'awpcp_submit_listing_payment_terms', $payment_terms, $listing );

        $current_user_is_moderator = $this->roles->current_user_is_moderator();

        $params = array(
            'transaction'               => null,

            'payment_terms'             => $payment_terms,
            'form'                      => $stored_data,
            'nonces'                    => $nonces,

            'form_errors'               => [],

            'show_user_field'           => $current_user_is_moderator,
            'show_account_balance'      => false,
            'show_captcha'              => $this->should_show_captcha( $listing ),
            'disable_parent_categories' => $this->settings->get_option( 'noadsinparentcat' ),

            'section_title'             => $this->get_section_title( $current_user_is_moderator ),
            'account_balance'           => '',
            'payment_terms_list'        => $this->render_payment_terms_list( $stored_data, $payment_terms ),
            'credit_plans_table'        => $this->payments->render_credit_plans_table( null ),
            'captcha'                   => $this->captcha,
        );

        if ( ! $this->roles->current_user_is_administrator() ) {
            $params['show_account_balance'] = true;
            $params['account_balance']      = $this->payments->render_account_balance();
        }

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_stored_data( $listing, $transaction ) {
        $data = [
            'listing_id'                => null,
            'category'                  => null,
            'user'                      => null,
            'payment_term_id'           => null,
            'payment_term_type'         => null,
            'payment_term_payment_type' => null,
            'transaction_id'            => null,
        ];

        if ( is_null( $listing ) ) {
            return $data;
        }

        $data['listing_id'] = $listing->ID;
        $data['category']   = $this->listing_renderer->get_categories_ids( $listing );
        $data['category'][] = $this->listing_renderer->get_category_name( $listing );
        $data['user']       = $listing->post_author;

        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        $data['payment_term_id']           = $payment_term->id;
        $data['payment_term_type']         = $payment_term->type;
        $data['payment_term_payment_type'] = 'money'; // TODO: Get actual payment type from listing metadata?

        if ( $transaction ) {
            $data['transaction_id'] = $transaction->id;
        }

        return $data;
    }

    /**
     * @since 4.0.0
     */
    private function maybe_generate_nonces( $listing ) {
        $create_empty_listing_nonce = '';
        $update_listing_order_nonce = '';

        if ( $this->can_payment_information_be_modified_during_submit( $listing ) ) {
            $create_empty_listing_nonce = wp_create_nonce( 'awpcp-create-empty-listing' );
            $update_listing_order_nonce = wp_create_nonce( 'awpcp-update-listing-order' );
        }

        return compact( 'create_empty_listing_nonce', 'update_listing_order_nonce' );
    }

    /**
     * @since 4.0.0
     */
    private function should_show_captcha( $listing ) {
        if ( ! $this->captcha->is_captcha_required() ) {
            return false;
        }

        return $this->can_payment_information_be_modified_during_submit( $listing );
    }

    /**
     * @since 4.0.0
     */
    private function get_section_title( $current_user_is_moderator ) {
        $payments_are_enabled = $this->payments->payments_enabled();

        if ( $current_user_is_moderator && $payments_are_enabled ) {
            return _x( 'Category, owner and payment term selection', 'order submit listing section', 'another-wordpress-classifieds-plugin' );
        } elseif ( $current_user_is_moderator ) {
            return _x( 'Category and owner selection', 'order submit listing section', 'another-wordpress-classifieds-plugin' );
        } elseif ( $payments_are_enabled ) {
            return _x( 'Category and payment term selection', 'order submit listing section', 'another-wordpress-classifieds-plugin' );
        }

        return _x( 'Category selection', 'order submit listing section', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * TODO: Update Payment Term List to work using stored data.
     *
     * @since 4.0.0
     */
    private function render_payment_terms_list( $data, $payment_terms ) {
        $payment_terms_list = awpcp_payment_terms_list();

        return $payment_terms_list->render(
            [
                'payment_term' => (object) [
                    'id'   => $data['payment_term_id'],
                    'type' => $data['payment_term_type'],
                ],
                'payment_type' => $data['payment_term_payment_type'],
            ],
            [
                'payment_terms' => $payment_terms,
            ]
        );
    }
}
