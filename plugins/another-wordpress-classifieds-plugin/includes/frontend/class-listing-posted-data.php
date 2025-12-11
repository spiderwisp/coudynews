<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gathers listing data submitted through the Single Submit Listing page.
 */
class AWPCP_ListingPostedData {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var AWPCP_FormFieldsData
     */
    private $form_fields_data;

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_ListingAuthorization
     */
    private $authorization;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy, $form_fields_data, $listings_logic, $listing_renderer, $payments, $authorization, $roles, $request ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->form_fields_data          = $form_fields_data;
        $this->listings_logic            = $listings_logic;
        $this->listing_renderer          = $listing_renderer;
        $this->payments                  = $payments;
        $this->authorization             = $authorization;
        $this->roles                     = $roles;
        $this->request                   = $request;
    }

    /**
     * @since 4.0.0
     */
    public function get_posted_data_for_listing_pending_payment( $listing ) {
        $categories        = array_map( 'intval', $this->request->post( 'categories' ) );
        $payment_term_id   = $this->request->post( 'payment_term_id' );
        $payment_term_type = $this->request->post( 'payment_term_type' );

        $payment_term = $this->payments->get_payment_term( $payment_term_id, $payment_term_type );

        $posted_data = $this->get_common_posted_data( $listing );
        $posted_data = $this->update_posted_data_for_payment_term( $posted_data, $listing, $payment_term );

        $posted_data['categories']   = $categories;
        $posted_data['payment_term'] = $payment_term;
        $posted_data['payment_type'] = $this->request->post( 'payment_type' );

        $posted_data['post_data']['metadata']['_awpcp_payment_term_id']   = $payment_term->id;
        $posted_data['post_data']['metadata']['_awpcp_payment_term_type'] = $payment_term->type;

        $posted_data['post_data']['terms'][ $this->listing_category_taxonomy ] = $categories;

        return $posted_data;
    }

    /**
     * @since 4.0.0
     */
    private function get_common_posted_data( $listing ) {
        $post_data   = $this->form_fields_data->get_posted_data( $listing );
        $user_id     = $this->request->post( 'user_id' );
        $current_url = $this->request->post( 'current_url' );

        if ( ! $this->roles->current_user_is_moderator() ) {
            $user_id = $this->request->get_current_user_id();
        }

        $now         = current_time( 'mysql' );
        $post_status = 'draft';

        if ( 'auto-draft' !== $listing->post_status ) {
            $post_status = $this->listings_logic->get_modified_listing_post_status( $listing );
        }

        // TODO: Should we validate that the payment term can be used?
        // TODO: For pending payment listings, we should use the payment term info sent with the request.
        $post_data['post_fields'] = array_merge(
            $post_data['post_fields'],
            [
                'post_name'         => '',
                'post_author'       => $user_id,
                'post_modified'     => $now,
                'post_modified_gmt' => get_gmt_from_date( $now ),
                // TODO: Use appropriate post status for new and existing listings.
                'post_status'       => $post_status,
            ]
        );

        // TODO: Make sure users are allowed to change the start/end date fields when authorized.
        /** @phpstan-ignore-next-line */
        if ( empty( $post_data['metadata']['_awpcp_start_date'] ) ) {
            $post_data['metadata']['_awpcp_start_date'] = $now;
        }

        return [
            'listing'     => $listing,
            'user_id'     => $user_id,
            'current_url' => $current_url,
            'post_data'   => $post_data,
        ];
    }

    /**
     * @since 4.0.0
     */
    private function update_posted_data_for_payment_term( $posted_data, $listing, $payment_term ) {
        $posted_data['payment_term'] = $payment_term;

        $post_title   = $posted_data['post_data']['post_fields']['post_title'];
        $post_content = $posted_data['post_data']['post_fields']['post_content'];

        // TODO: Should we validate that the payment term can be used?
        // TODO: For pending payment listings, we should use the payment term info sent with the request.
        $posted_data['post_data']['post_fields']['post_title']   = $this->prepare_title( $post_title, $payment_term->get_characters_allowed_in_title() );
        $posted_data['post_data']['post_fields']['post_content'] = $this->prepare_content( $post_content, $payment_term->get_characters_allowed() );

        if ( empty( $posted_data['post_data']['metadata']['_awpcp_end_date'] ) || ( $this->authorization->is_current_user_allowed_to_edit_listing_start_date( $listing ) && ! $this->authorization->is_current_user_allowed_to_edit_listing_end_date( $listing ) ) ) {
            $start_date_timestamp = awpcp_datetime( 'timestamp', $posted_data['post_data']['metadata']['_awpcp_start_date'] );

            $posted_data['post_data']['metadata']['_awpcp_end_date'] = $payment_term->calculate_end_date( $start_date_timestamp );
        }

        return $posted_data;
    }

    /**
     * @since 4.0.0
     */
    private function prepare_title( $title, $characters_allowed ) {
        if ( $characters_allowed > 0 && awpcp_utf8_strlen( $title ) > $characters_allowed ) {
            $title = awpcp_utf8_substr( $title, 0, $characters_allowed );
        }

        return $title;
    }

    /**
     * @since 4.0.0
     */
    private function prepare_content( $content, $characters_allowed ) {
        $allow_html = (bool) get_awpcp_option( 'allowhtmlinadtext' );

        if ( $allow_html ) {
            $content = wp_kses_post( $content );
        } else {
            $content = wp_strip_all_tags( $content );
        }

        if ( $characters_allowed > 0 && awpcp_utf8_strlen( $content ) > $characters_allowed ) {
            $content = awpcp_utf8_substr( $content, 0, $characters_allowed );
        }

        if ( $allow_html ) {
            $content = force_balance_tags( $content );
        } else {
            $content = esc_html( $content );
        }

        return $content;
    }

    /**
     * @since 4.0.0
     */
    public function get_posted_data_for_already_paid_listing( $listing ) {
        $posted_data  = $this->get_common_posted_data( $listing );
        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        return $this->update_posted_data_for_payment_term( $posted_data, $listing, $payment_term );
    }
}
