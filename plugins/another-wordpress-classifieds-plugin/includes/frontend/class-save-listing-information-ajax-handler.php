<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajax handler for the action that saves information for new and existing listings.
 */
class AWPCP_SaveListingInformationAjaxHandler extends AWPCP_AjaxHandler {

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingRenderer
     */
    public $listing_renderer;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_ListingsPaymentTransactions
     */
    private $listings_transactions;

    /**
     * @var AWPCP_FormFieldsValidator
     */
    private $form_fields_validator;

    /**
     * @var AWPCP_PaymentInformationValidator
     */
    protected $payment_information_validator;

    /**
     * @var AWPCP_ListingPostedData
     */
    private $posted_data;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    protected $roles;

    /**
     * @var AWPCP_Settings_API
     */
    public $settings;

    /**
     * @since 4.0.0
     */
    public function __construct(
        $listings_logic,
        $listing_renderer,
        $listings,
        $listings_transactions,
        $form_fields_validator,
        $payment_information_validator,
        $posted_data,
        $roles,
        $settings,
        $response
    ) {
        parent::__construct( $response );

        $this->listings_logic                = $listings_logic;
        $this->listing_renderer              = $listing_renderer;
        $this->listings                      = $listings;
        $this->listings_transactions         = $listings_transactions;
        $this->form_fields_validator         = $form_fields_validator;
        $this->payment_information_validator = $payment_information_validator;
        $this->posted_data                   = $posted_data;
        $this->roles                         = $roles;
        $this->settings                      = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function ajax() {
        // TODO: Throw an error if listing ID is not provided.
        // TODO: How to delete attachments uploaded to listings that were never consolidated?
        // TODO: Allow categories to be updated.
        try {
            return $this->try_to_save_listing_information();
        } catch ( AWPCP_Exception $e ) {
            return $this->multiple_errors_response( $e->getMessage() );
        }
    }

    /**
     * TODO: Validate re-captcha.
     *
     * @since 4.0.0
     * @throws AWPCP_Exception  If current user is not authorized to save the
     *                          listing's information.
     */
    private function try_to_save_listing_information() {
        $listing = $this->listings->get( awpcp_get_var( array( 'param' => 'ad_id' ) ) );
        $nonce   = awpcp_get_var( array( 'param' => 'nonce' ), 'post' );

        if ( ! wp_verify_nonce( $nonce, "awpcp-save-listing-information-{$listing->ID}" ) ) {
            throw new AWPCP_Exception( esc_html__( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( $this->listings_logic->can_payment_information_be_modified_during_submit( $listing ) ) {
            return $this->save_new_listing_information( $listing );
        }

        return $this->save_existing_listing_information( $listing );
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If a transaction cannot be found.
     */
    private function save_new_listing_information( $listing ) {
        $transaction = $this->listings_transactions->get_current_transaction();

        if ( is_null( $transaction ) ) {
            $message = __( 'There is no payment transaction associated with this request. Aborting.', 'another-wordpress-classifieds-plugin' );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        // TODO: I believe the post_status is never going to be auto-draft when
        // pay before place ad is enabled.
        //
        // Hence save_information_for_new_listing_pending_payment() is never
        // called.
        //
        // XXX: It could be called if someone tries to bypass payment by returning to
        // the listing-information step passing the newly generated listing_id and
        // transaction_id.
        if ( $this->settings->get_option( 'pay-before-place-ad' ) ) {
            return $this->save_information_for_new_listing_already_paid( $listing, $transaction );
        }

        return $this->save_information_for_new_listing_pending_payment( $listing, $transaction );
    }

    /**
     * TODO: create payment transaction and redirect to payment page.
     * TODO: Test trying to provide the ID of a listing that hasn't been paid. Does it let the user edit the listing information?
     *
     * @since 4.0.0
     */
    private function save_information_for_new_listing_already_paid( $listing, $transaction ) {
        $posted_data = $this->posted_data->get_posted_data_for_already_paid_listing( $listing );

        $this->save_information_for_already_paid_listing( $listing, $posted_data );

        // TODO: Handle redirects when the listing is still a draft.
        // TODO: Shouldn't this sent the user to the finish step?
        $redirect_params = [
            'step'           => 'finish',
            'listing_id'     => $listing->ID,
            'transaction_id' => $transaction->id,
        ];

        $response = [
            'redirect_url' => add_query_arg( $redirect_params, $posted_data['current_url'] ),
        ];

        return $this->success( $response );
    }

    /**
     * @since 4.0.0
     */
    private function save_information_for_already_paid_listing( $listing, $posted_data ) {
        $errors = $this->form_fields_validator->get_validation_errors( $posted_data['post_data'], $listing );

        if ( ! empty( $errors ) ) {
            return $this->multiple_errors_response( $errors );
        }

        $this->save_listing_information( $listing, $posted_data['post_data'] );
    }

    /**
     * XXX: This is a copy of AWPCP_ListingFieldsMetabox::save_listing_information.
     *
     * TODO: trigger awpcp-place-listing-listing-data filter
     * TODO: trigger awpcp_before_edit_ad action.
     *
     * @since 4.0.0
     */
    private function save_listing_information( $listing, $post_data ) {
        do_action( 'awpcp-before-save-listing', $listing, $post_data );

        $this->listings_logic->update_listing( $listing, $post_data );

        /**
         * Fires once the information for a classified ad has been saved.
         *
         * @since 4.0.0     A transaction object is no longer passsed as the second argument.
         * @deprecated 4.0.0    Use awpcp_listing_information_saved instead.
         */
        do_action( 'awpcp-save-ad-details', $listing, null );

        /**
         * Fires once the information for a classified ad has been saved.
         *
         * @since 4.0.0
         */
        do_action( 'awpcp_listing_information_saved', $listing, $post_data );
    }

    /**
     * @since 4.0.0
     */
    private function save_information_for_new_listing_pending_payment( $listing, $transaction ) {
        $posted_data = $this->posted_data->get_posted_data_for_listing_pending_payment( $listing );

        $errors = array_merge(
            $this->payment_information_validator->get_validation_errors( $posted_data['post_data'] ),
            $this->form_fields_validator->get_validation_errors( $posted_data['post_data'], $listing )
        );

        if ( ! empty( $errors ) ) {
            return $this->multiple_errors_response( $errors );
        }

        $this->listings_transactions->prepare_transaction_for_checkout( $transaction, $posted_data );
        $this->save_listing_information( $listing, $posted_data['post_data'] );
        $this->listings_logic->update_listing_payment_term( $listing, $posted_data['payment_term'] );

        // TODO: Redirect to checkout page.
        return $this->redirect_to_checkout_page( $listing, $transaction, $posted_data );
    }

    /**
     * @since 4.0.0
     */
    private function redirect_to_checkout_page( $listing, $transaction, $posted_data ) {
        $redirect_params = [
            'step'           => 'checkout',
            'listing_id'     => $listing->ID,
            'transaction_id' => $transaction->id,
        ];

        $response = [
            'listing'      => [
                'id' => $listing->ID,
            ],
            'transaction'  => $transaction->id,
            'redirect_url' => add_query_arg( $redirect_params, $posted_data['current_url'] ),
        ];

        return $this->success( $response );
    }

    /**
     * TODO: Trigger awpcp_before_edit_ad action.
     * TODO: Trigger awpcp_edit_ad action.
     *
     * @since 4.0.0
     */
    private function save_existing_listing_information( $listing ) {
        $posted_data = $this->posted_data->get_posted_data_for_already_paid_listing( $listing );

        $this->save_information_for_already_paid_listing( $listing, $posted_data );

        $redirect_params = [
            'step'       => 'finish',
            'listing_id' => $listing->ID,
            'edit_nonce' => wp_create_nonce( "awpcp-edit-listing-{$listing->ID}" ),
        ];

        $response = [
            'redirect_url' => add_query_arg( $redirect_params, $posted_data['current_url'] ),
        ];

        return $this->success( $response );
    }
}
