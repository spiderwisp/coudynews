<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Edit Listing Page.
 */
class AWPCP_EditListingPage extends AWPCP_Page {

    /**
     * @var bool
     */
    public $show_menu_items = false;

    /**
     * @var WP_Post|null
     */
    private $ad;

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
    private $listings_logic;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_ListingAuthorization
     */
    private $authorization;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $sections_generator, $listing_renderer, $listings_logic, $listings, $authorization, $settings, $request ) {
        parent::__construct( null, null, awpcp()->container['TemplateRenderer'] );

        $this->sections_generator = $sections_generator;
        $this->listing_renderer   = $listing_renderer;
        $this->listings_logic     = $listings_logic;
        $this->listings           = $listings;
        $this->authorization      = $authorization;
        $this->settings           = $settings;
        $this->request            = $request;
    }

    /**
     * @since 4.0.0
     */
    public function dispatch() {
        try {
            return $this->do_current_step();
        } catch ( AWPCP_Exception $e ) {
            return $this->render( 'content', $e->getMessage() );
        }
    }

    /**
     * @since 4.0.0
     */
    private function do_current_step() {
        $step = $this->get_current_step();

        if ( 'send-access-key' === $step ) {
            return $this->do_send_access_key_step();
        }

        if ( 'verify-access-token' === $step ) {
            return $this->do_verify_access_token_step();
        }

        $listing_id = $this->request->get_current_listing_id();

        if ( empty( $listing_id ) || 'enter-access-key' === $step ) {
            return $this->do_enter_email_and_access_key_step();
        }

        $listing = $this->get_ad();

        if ( is_null( $listing ) ) {
            $message = __( "There specified ad doesn't exist.", 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        if ( ! $this->is_current_user_allowed_to_edit_listing( $listing ) ) {
            $message = __( 'You are not allowed to edit the specified ad.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        // If payment information is not consolidated yet, the listing needs to be
        // edited through the Submit Listing page.
        if ( $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            $message = __( 'The selected ad cannot be edited right now.', 'another-wordpress-classifieds-plugin' );

            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        switch ( $step ) {
            case 'listings-information':
                return $this->do_listing_information_step();
            case 'finish':
                return $this->do_finish_step();
            default:
                return $this->do_listing_information_step();
        }
    }

    /**
     * @since 4.0.0
     */
    private function get_current_step() {
        $step = awpcp_get_var( array( 'param' => 'step' ) );
        return $this->request->post( 'step', $step );
    }

    /**
     * @since 4.0.0
     */
    private function is_current_user_allowed_to_edit_listing( $listing ) {
        if ( $this->request_includes_valid_edit_nonce( $listing ) ) {
            return true;
        }

        return $this->authorization->is_current_user_allowed_to_edit_listing( $listing );
    }

    /**
     * @since 4.0.0
     */
    private function request_includes_valid_edit_nonce( $listing ) {
        $nonce  = awpcp_get_var( array( 'param' => 'edit_nonce' ) );
        $nonce  = $this->request->post( 'edit_nonce', $nonce );
        $action = "awpcp-edit-listing-{$listing->ID}";

        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * @since 4.0.0
     */
    private function do_listing_information_step() {
        do_action( 'awpcp_before_edit_listing_page' );

        wp_enqueue_script( 'awpcp-submit-listing-page' );

        $listing = $this->get_ad();

        $page_data = [
            'sections' => $this->sections_generator->get_sections( [], 'edit', $listing ),
            'mode'     => 'edit',
        ];

        return $this->render( 'content', '<form class="awpcp-submit-listing-page-form"></form><script type="text/javascript">var AWPCPSubmitListingPageData = ' . wp_json_encode( $page_data ) . ';</script>' );
    }

    /**
     * @since 4.0.0
     */
    public function do_finish_step() {
        $ad       = $this->get_ad();
        $messages = [];

        if ( is_null( $ad ) ) {
            $message = __( 'The specified Ad doesn\'t exists.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        awpcp_listings_api()->consolidate_existing_ad( $ad );

        if ( is_admin() ) {
            /* translators: %s is the URL to the listing's individual page. */
            $message = __( 'The Ad has been edited successfully. <a href="%s">Go back to view listings</a>.', 'another-wordpress-classifieds-plugin' );

            if ( awpcp_currency_symbols() ) {
                $url = awpcp_get_admin_listings_url();
            } else {
                $url = awpcp_get_user_panel_url();
            }

            $messages[] = sprintf( $message, esc_url( $url ) );
        }

        $params = array(
            'edit'     => true,
            'ad'       => $ad,
            'messages' => $this->get_listing_messages( $ad ),
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
            $messages[] = __( 'Your changes have been published. This is the content that will be seen by visitors of the website.', 'another-wordpress-classifieds-plugin' );
        }

        if ( $this->listing_renderer->is_pending_approval( $listing ) ) {
            $messages[] = __( 'Your changes have been saved. This is a preview of the content as seen by visitors of the website.', 'another-wordpress-classifieds-plugin' );
        }

        return array_merge( $messages, $this->listings_logic->get_ad_alerts( $listing ) );
    }

    /**
     * @since 4.0.0
     */
    public function get_ad() {
        if ( is_null( $this->ad ) ) {
            try {
                $this->ad = $this->listings->get( $this->request->get_current_listing_id() );
            } catch ( AWPCP_Exception $e ) {
                $this->ad = null;
            }
        }

        return $this->ad;
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    public function do_enter_email_and_access_key_step( $show_errors = true ) {
        $errors   = [];
        $messages = [];

        $form = array(
            'ad_email' => trim( $this->request->post( 'ad_email' ) ),
            'ad_key'   => trim( $this->request->post( 'ad_key' ) ),
            'attempts' => intval( $this->request->post( 'attempts', 0 ) ),
        );

        $send_access_key_url = add_query_arg( array( 'step' => 'send-access-key' ), $this->url() );

        if ( empty( $form['ad_email'] ) ) {
            $errors['ad_email'] = __( 'Please enter the email address you used when you created your Ad in addition to the Ad access key that was emailed to you after your Ad was submitted.', 'another-wordpress-classifieds-plugin' );
        } elseif ( ! is_email( $form['ad_email'] ) ) {
            $errors['ad_email'] = __( 'Please enter a valid email address.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $form['ad_key'] ) ) {
            $errors['ad_key'] = __( 'Please enter your Ad access key.', 'another-wordpress-classifieds-plugin' );
        }

        if ( empty( $errors ) ) {
            $listings = $this->listings->find_listings(
                [
                    'meta_query' => [
                        [
                            'key'     => '_awpcp_contact_email',
                            'value'   => $form['ad_email'],
                            'compare' => '=',
                        ],
                        [
                            'key'     => '_awpcp_access_key',
                            'value'   => $form['ad_key'],
                            'compare' => '=',
                        ],
                    ],
                ]
            );

            if ( ! empty( $listings ) ) {
                $this->ad = $listings[0];

                return $this->do_listing_information_step();
            }

            $errors[] = __( 'The email address and access key you entered does not match any of the Ads in our system.', 'another-wordpress-classifieds-plugin' );
        } elseif ( 0 === $form['attempts'] || false === $show_errors ) {
            $errors = array();
        }

        $page = $this;

        $hidden = array(
            'attempts' => $form['attempts'] + 1,
            'step'     => 'enter-access-key',
        );

        $params = compact( 'page', 'form', 'hidden', 'messages', 'errors', 'send_access_key_url' );

        $template = AWPCP_DIR . '/frontend/templates/page-edit-ad-email-key-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    public function do_send_access_key_step() {
        $form = array(
            'ad_email' => trim( $this->request->post( 'ad_email' ) ),
            'attempts' => intval( $this->request->post( 'attempts', 0 ) ),
        );

        if ( $form['attempts'] >= 1 ) {
            return $this->process_send_access_key_form( $form );
        }

        return $this->render_send_access_key_form( $form );
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    private function process_send_access_key_form( $form, $errors = array() ) {
        if ( empty( $form['ad_email'] ) ) {
            $errors['ad_email'] = __( 'Please enter the email address you used when you created your Ad.', 'another-wordpress-classifieds-plugin' );
        } elseif ( ! is_email( $form['ad_email'] ) ) {
            $errors['ad_email'] = __( 'Please enter a valid email address.', 'another-wordpress-classifieds-plugin' );
        }

        $ads = array();
        if ( empty( $errors ) ) {
            $ads = $this->listings->find_listings(
                [
                    'meta_query' => [
                        [
                            'key'     => '_awpcp_contact_email',
                            'value'   => $form['ad_email'],
                            'compare' => '=',
                        ],
                    ],
                ]
            );

            if ( empty( $ads ) ) {
                $errors[] = __( 'The email address you entered does not match any of the Ads in our system.', 'another-wordpress-classifieds-plugin' );
            }
        }

        // TODO: define what a valid listing really looks like and use that everywhere:
        // - here!
        // - AWPCP_Ad::get_where_conditions_for_valid_ads().
        $valid_listings = array();

        $accepted_payment_statuses = array(
            AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED,
            AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED,
        );

        if ( $this->settings->get_option( 'enable-ads-pending-payment' ) ) {
            $accepted_payment_statuses[] = AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;
        }

        foreach ( $ads as $listing ) {
            if ( ! $this->listing_renderer->is_verified( $listing ) ) {
                continue;
            }

            if ( ! in_array( $this->listing_renderer->get_payment_status( $listing ), $accepted_payment_statuses, true ) ) {
                continue;
            }

            array_push( $valid_listings, $listing );
        }

        $access_keys_sent = false;

        // If $ads is non-empty then $errors is empty.
        if ( ! empty( $valid_listings ) ) {
            $access_keys_sent = $this->send_access_keys( $valid_listings, $errors );
        }

        if ( empty( $valid_listings ) ) {
            $errors[] = __( 'The email address you entered does not match any of the listing in our system.', 'another-wordpress-classifieds-plugin' );
        }

        if ( ! $access_keys_sent ) {
            return $this->render_send_access_key_form( $form, $errors );
        }

        return $this->do_enter_email_and_access_key_step( false );
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    public function send_access_keys( $ads, &$errors = array() ) {
        $ad = reset( $ads );

        $contact_name  = $this->listing_renderer->get_contact_name( $ad );
        $contact_email = $this->listing_renderer->get_contact_email( $ad );

        $recipient = awpcp_format_recipient_address( $contact_email, $contact_name );
        $template  = AWPCP_DIR . '/frontend/templates/email-send-all-ad-access-keys.tpl.php';

        $message          = new AWPCP_Email();
        $message->to[]    = $recipient;
        $message->subject = get_awpcp_option( 'resendakeyformsubjectline' );

        $message->prepare(
            $template,
            array(
                'ads'              => $ads,
                'introduction'     => get_awpcp_option( 'resendakeyformbodymessage' ),
                'listing_renderer' => $this->listing_renderer,
            )
        );

        if ( ! $message->send() ) {
            /* translators: %s is the email address that user provided. */
            $errors[] = sprintf( __( 'There was an error trying to send the email to %s.', 'another-wordpress-classifieds-plugin' ), esc_html( $recipient ) );
            return false;
        }

        /* translators: %s is the email address that user provided. */
        awpcp_flash( sprintf( __( 'The access keys were sent to %s.', 'another-wordpress-classifieds-plugin' ), esc_html( $recipient ) ) );

        return true;
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    private function render_send_access_key_form( $form, $errors = array() ) {
        $messages = [];

        $send_access_key_url = add_query_arg( array( 'step' => 'send-access-key' ), $this->url() );

        $hidden = array(
            'attempts' => $form['attempts'] + 1,
        );

        $params = compact( 'form', 'hidden', 'messages', 'errors', 'send_access_key_url' );

        $template = AWPCP_DIR . '/frontend/templates/page-edit-ad-send-access-key-step.tpl.php';

        return $this->render( $template, $params );
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    public function do_verify_access_token_step() {
        $access_token = awpcp_get_var( array( 'param' => 'access_token' ) );
        $token_parts  = explode( '-', $access_token );

        if ( 2 !== count( $token_parts ) ) {
            $message = __( 'Invalid access token.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        // Substr: 0:10 -> nonce, 10: -> id_hash.
        $access_token = $token_parts[0];

        $listing_id = $token_parts[1];

        try {
            $listing = awpcp_listings_collection()->get( $listing_id );
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The specified listing doesn't exists.", 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        $access_token_status = awpcp_verify_edit_listing_access_token( $access_token, $listing );

        if ( 'invalid' === $access_token_status ) {
            $message = __( 'The sepecified listing cannot be edited using this access code.', 'another-wordpress-classifieds-plugin' );
            return $this->render( 'content', awpcp_print_error( $message ) );
        }

        if ( 'expired' === $access_token_status ) {
            return $this->send_new_access_token( $listing );
        }

        $this->ad = $listing;

        return $this->do_listing_information_step();
    }

    /**
     * Copied from old Edit Listing page.
     *
     * @since 4.0.0
     */
    private function send_new_access_token( $listing ) {
        $listing_title = $this->listing_renderer->get_listing_title( $listing );
        $contact_name  = $this->listing_renderer->get_contact_name( $listing );
        $contact_email = $this->listing_renderer->get_contact_email( $listing );
        $access_key    = $this->listing_renderer->get_access_key( $listing );

        $recipient = awpcp_format_recipient_address( $contact_email, $contact_name );

        $email = new AWPCP_Email();

        $email->to[] = $recipient;
        /* translators: %s is the title of the listing. */
        $email->subject = __( 'Edit link for listing: %s', 'another-wordpress-classifieds-plugin' );
        $email->subject = sprintf( $email->subject, $listing_title );

        $email->prepare(
            AWPCP_DIR . '/templates/email/listing-edit-link-with-access-token.tpl.php',
            array(
                'listing_title' => $listing_title,
                'contact_name'  => $contact_name,
                'email_address' => $contact_email,
                'access_key'    => $access_key,
                'edit_link'     => awpcp_get_edit_listing_url_with_access_key( $listing ),
            )
        );

        $email->send();

        $message = __( 'The link you used already expired. Please check your email to receive a link with a new access token.', 'another-wordpress-classifieds-plugin' );

        return $this->render( 'content', awpcp_print_message( $message ) );
    }
}
