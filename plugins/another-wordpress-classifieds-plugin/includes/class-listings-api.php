<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Listings logic.
 */
class AWPCP_ListingsAPI {

    private $disabled_status = 'disabled';
    private $attachments_logic;
    private $attachments;
    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;
    private $listings;
    private $roles;

    /**
     * var AWPCP_Request
     */
    private $request = null;
    private $settings = null;
    private $wordpress;

    public function __construct( $attachments_logic, $attachments, $listing_renderer, $listings, $roles, $settings, $wordpress ) {
        $this->attachments_logic = $attachments_logic;
        $this->attachments = $attachments;
        $this->listing_renderer = $listing_renderer;
        $this->listings = $listings;
        $this->roles             = $roles;
        $this->settings = $settings;
        $this->wordpress = $wordpress;
        $this->request   = awpcp_request();

        add_action( 'template_redirect', array( $this, 'dispatch' ) );
    }

    /**
     * @since 3.0.2
     * @deprecated 3.4
     */
    public static function instance() {
        _deprecated_function( __FUNCTION__, '3.4', 'awpcp_listings_api' );
        return awpcp_listings_api();
    }

    /**
     * @since 3.0.2
     * @tested
     */
    public function dispatch() {
        $awpcpx = $this->request->get_query_var( 'awpcpx' );
        $module = $this->request->get_query_var( 'awpcp-module', $this->request->get_query_var( 'module' ) );
        $action = $this->request->get_query_var( 'awpcp-action', $this->request->get_query_var( 'action' ) );

        if ( $awpcpx && $module == 'listings' ) {
            switch ( $action ) {
                case 'verify':
                    $this->handle_email_verification_link();
            }
        }
    }

    /**
     * @since 3.0.2
     */
    public function handle_email_verification_link() {
        $ad_id = $this->request->get_query_var( 'awpcp-ad' );
        $hash = $this->request->get_query_var( 'awpcp-hash' );

        try {
            $ad = $this->listings->get( $ad_id );
        } catch ( AWPCP_Exception $e ) {
            $ad = null;
        }

        if ( is_null( $ad ) || ! awpcp_verify_email_verification_hash( $ad_id, $hash ) ) {
            wp_redirect( awpcp_get_main_page_url() );
            return;
        }

        $this->verify_ad( $ad );

        wp_redirect( esc_url_raw( add_query_arg( 'verified', true, url_showad( $ad->ID ) ) ) );
        return;
    }

    /**
     * API Methods
     */

    public function create_listing( $listing_data ) {
        $now = current_time( 'mysql' );

        $post_fields = wp_parse_args( $listing_data['post_fields'], array(
            'post_type' => AWPCP_LISTING_POST_TYPE,
            'post_status'   => $this->disabled_status,
            'post_date' => $now,
            'post_date_gmt' => get_gmt_from_date( $now ),
        ) );

        $listing_id = $this->wordpress->insert_post( $post_fields , true );

        if ( is_wp_error( $listing_id ) ) {
            $message = __( 'There was an unexpected error trying to save the listing details. Please try again or contact an administrator.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $listing = $this->listings->get( $listing_id );

        if (isset($listing_data['terms'])) {
            $this->update_listing_terms( $listing, $listing_data['terms'] );
        }

        $metadata = $this->fill_default_listing_metadata(
            $listing,
            $listing_data['metadata']
        );

        $this->update_listing_metadata( $listing, $metadata );

        return $listing;
    }

    /**
     * @since 4.0.0
     */
    private function update_listing_terms( $listing, $terms ) {
        foreach ( $terms as $taxonomy => $taxonomy_terms ) {
            $this->wordpress->set_object_terms( $listing->ID, $taxonomy_terms, $taxonomy );
        }
    }

    /**
     * @since 4.0.0
     */
    private function update_listing_metadata( $listing, $metadata ) {
        $metadata = $this->maybe_update_most_recent_start_date( $listing, $metadata );

        foreach ( $metadata as $field_name => $field_value ) {
            $this->wordpress->update_post_meta( $listing->ID, $field_name, $field_value );
        }
    }

    /**
     * @since 4.0.5
     */
    private function maybe_update_most_recent_start_date( $listing, $metadata ) {
        $start_date   = isset( $metadata['_awpcp_start_date'] ) ? $metadata['_awpcp_start_date'] : '';
        $renewed_date = isset( $metadata['_awpcp_renewed_date'] ) ? $metadata['_awpcp_renewed_date'] : '';

        /*
         * Neither the start date nor the renewed date are being modified, so
         * there is no need to update the most recent start date meta property
         * either.
         */
        if ( ! $start_date && ! $renewed_date ) {
            return $metadata;
        }

        // Load the other date if only one of the properties is being modified.
        if ( ! $start_date ) {
            $start_date = $this->listing_renderer->get_plain_start_date( $listing );
        }

        if ( ! $renewed_date ) {
            $renewed_date = $this->listing_renderer->get_renewed_date( $listing );
        }

        /*
         * The most recent start date is the date the ad was renewed or the
         * start date if no renewed date has been defined yet.
         *
         * We also use the renewed date only if it occurs after the current
         * start date.
         */
        $most_recent_start_date = $start_date;

        if ( $renewed_date && strtotime( $renewed_date ) > strtotime( $start_date ) ) {
            $most_recent_start_date = $renewed_date;
        }

        $metadata['_awpcp_most_recent_start_date'] = $most_recent_start_date;

        return $metadata;
    }

    /**
     * @since 4.0.0
     * @since 4.0.1 This method is no longer used and not recommended. Use
     *              fill_default_listing_metadata() instead.
     *
     * @see fill_default_listing_metadata()
     */
    public function get_default_listing_metadata( $metadata ) {
        _doing_it_wrong(
            __FUNCTION__,
            esc_html__( 'To avoid overwritting existing metadata, use fill_default_listing_metadata() instead.', 'another-wordpress-classifieds-plugin' ),
            '4.0.1'
        );

        $metadata = wp_parse_args( $metadata, array(
            '_awpcp_payment_status'         => 'Unpaid',
            '_awpcp_verification_needed'    => true,
            '_awpcp_most_recent_start_date' => current_time( 'mysql' ),
            '_awpcp_renewed_date'           => '',
            '_awpcp_poster_ip'              => awpcp_getip(),
            '_awpcp_is_paid'                => false,
            '_awpcp_is_featured'            => 0,
            '_awpcp_views'                  => 0,
        ) );

        if ( ! isset( $metadata['_awpcp_access_key'] ) || empty( $metadata['_awpcp_access_key'] ) ) {
            /* This filter is documented in fill_default_listing_metadata(). */
            $metadata['_awpcp_access_key'] = apply_filters( 'awpcp-listing-access-key', $this->generate_access_key(), $this );
        }

        return $metadata;
    }

    /**
     * @since 4.0.0
     */
    public function fill_default_listing_metadata( $listing, $metadata ) {
        $stored_metadata = get_post_meta( $listing->ID );

        $default_metadata = [
            '_awpcp_payment_status'         => 'Unpaid',
            '_awpcp_verification_needed'    => true,
            '_awpcp_most_recent_start_date' => current_time( 'mysql' ),
            '_awpcp_renewed_date'           => '',
            '_awpcp_poster_ip'              => awpcp_getip(),
            '_awpcp_is_paid'                => false,
            '_awpcp_is_featured'            => 0,
            '_awpcp_views'                  => 0,
        ];

        if (awpcp_current_user_is_moderator()) {
            $default_metadata['_awpcp_verified'] = true;
            unset($default_metadata['_awpcp_verification_needed']);
        }

        // We want an array with the default keys defined above, but using the
        // stored values if one is available for the listing.
        //
        // This approach avoids unnecessary overwrites of information in case
        // some of those key are already defined, for example, if during the
        // creation of a new ad from the admin dashboard, the payment term
        // is assigned asynchronously before the Update button is clicked for
        // the first time.
        //
        // See https://github.com/drodenbaugh/awpcp/issues/2502.
        foreach ( array_keys( $default_metadata ) as $key ) {
            if ( isset( $stored_metadata[ $key ] ) ) {
                // FIXME: is the value always an array?
                $default_metadata[ $key ] = current( $stored_metadata[ $key ] );
            }
        }

        // Now we merge the metadata that the user provided with defaults that
        // take into account the stored metadata.
        $metadata = wp_parse_args( $metadata, $default_metadata );

        // In addition to avoid overwritting existing data, we also need to make
        // sure not to add defaults that are incosistent with the stored metadata.
        //
        // _awpcp_verification_needed and _awpcp_verified should never exist for
        // the same listing at the same time.
        if ( isset( $stored_metadata['_awpcp_verified'] ) && $stored_metadata['_awpcp_verified'] ) {
            unset( $metadata['_awpcp_verification_needed'] );
        }

        if ( ! isset( $stored_metadata['_awpcp_access_key'] ) || empty( $stored_metadata['_awpcp_access_key'] ) ) {
            /**
             * Filter the newly generated Access Key for the given listing.
             *
             * @since unknown
             *
             * @param $access_key A newley genreated access key for the listing.
             * @param $listing    The listing associated with the access key.
             */
            $metadata['_awpcp_access_key'] = apply_filters( 'awpcp-listing-access-key', $this->generate_access_key(), $listing );
        }

        return $metadata;
    }

    public function update_listing( $listing, $listing_data ) {
        $listing_data = wp_parse_args( $listing_data, array(
            'post_fields' => array(),
            'terms' => array(),
            'metadata' => array(),
        ) );

        $post_fields = wp_parse_args( $listing_data['post_fields'], array(
            'ID' => $listing->ID,
        ) );

        $listing_id = $this->wordpress->update_post( $post_fields, true );

        if ( is_wp_error( $listing_id ) ) {
            $error_message = $listing_id->get_error_message();

            if ( $error_message ) {
                $message = __( 'There was an error trying to save the listing details:', 'another-wordpress-classifieds-plugin' );
                $message.= '<br/><br/>';
                $message.= $error_message;
            } else {
                $message = __( 'There was an unexpected error trying to save the listing details. Please try again or contact an administrator.', 'another-wordpress-classifieds-plugin' );
            }

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $this->update_listing_terms( $listing, $listing_data['terms'] );
        $this->update_listing_metadata( $listing, $listing_data['metadata'] );

        if ( ! empty( $listing_data['regions'] ) ) {
            foreach( (array) $listing_data['regions'] as $region ) {
                if( ! empty( implode( $region ) ) ) {
                    $this->update_listing_regions( $listing, $listing_data['regions'] );
                    break;
                }
            }
        }

        return $this->listings->get( $listing_id );
    }

    /**
     * Update region information.
     *
     * @since 4.0.0
     */
    private function update_listing_regions( $listing, $regions ) {
        awpcp_basic_regions_api()->update_ad_regions( $listing, $regions );
    }

    /**
     * @since 3.0.2
     */
    public function consolidate_new_ad( $ad, $transaction ) {
        do_action( 'awpcp-place-ad', $ad, $transaction );

        $this->wordpress->update_post_meta( $ad->ID, '_awpcp_content_needs_review', true );

        $is_listing_verified = $this->listing_renderer->is_verified( $ad );

        if ( $is_listing_verified  ) {
            $this->send_ad_posted_email_notifications( $ad, array(), $transaction );
        } elseif ( ! $is_listing_verified ) {
            $this->send_verification_email( $ad );
        }

        if ( ! $is_listing_verified && $this->listing_renderer->is_public( $ad ) ) {
            $this->disable_listing( $ad );
        }

        $transaction->set( 'ad-consolidated-at', current_time( 'mysql' ) );
    }

    /**
     * @since 3.0.2
     */
    public function consolidate_existing_ad( $ad ) {
        $should_disable_listing = awpcp_should_disable_existing_listing( $ad );

        // if Ad is enabled and should be disabled, then disable it, otherwise
        // do not alter the Ad disabled status.
        if ( $should_disable_listing && $this->listing_renderer->is_public( $ad )  ) {
            $this->disable_listing( $ad );
            $this->wordpress->delete_post_meta( $ad->ID, '_awpcp_disabled_date' );
        } elseif ( $should_disable_listing ) {
            $this->wordpress->delete_post_meta( $ad->ID, '_awpcp_disabled_date' );
        }

        $is_listing_verified = $this->listing_renderer->is_verified( $ad );

        if ( $is_listing_verified && ! awpcp_current_user_is_moderator() ) {
            $this->send_ad_updated_email_notifications( $ad );
        }
    }

    /**
     */
    public function update_listing_verified_status( $listing, $transaction ) {
        if ( $this->listing_renderer->is_verified( $listing ) ) {
            return;
        }

        if ( $this->should_mark_listing_as_verified( $listing, $transaction ) ) {
            return $this->mark_listing_as_verified( $listing );
        }

        $this->mark_listing_as_needs_verification( $listing );
    }

    private function should_mark_listing_as_verified( $listing, $transaction ) {
        if ( ! $this->settings->get_option( 'enable-email-verification' ) ) {
            return true;
        } elseif ( is_user_logged_in() ) {
            return true;
        } elseif ( $transaction->payment_is_completed() || $transaction->payment_is_pending() ) {
            return true;
        }
        return false;
    }

    private function mark_listing_as_verified( $listing ) {
        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_verification_needed' );
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_verified', true );
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_verification_date', current_time( 'mysql' ) );
    }

    private function mark_listing_as_needs_verification( $listing ) {
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_verification_needed', true );
        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_verified' );
        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_verification_date' );
    }

    /**
     * @since 3.0.2
     * @tested
     */
    public function verify_ad( $ad ) {
        if ( $this->listing_renderer->is_verified( $ad ) ) {
            return;
        }

        $this->mark_listing_as_verified( $ad );

        $payment_status = $this->listing_renderer->get_payment_status( $ad );

        $post_data = [
            'metadata' => $this->calculate_start_and_end_dates_using_payment_term(
                $this->listing_renderer->get_payment_term( $ad )
            ),
        ];

        $this->update_listing( $ad, $post_data );
        $this->set_new_listing_post_status( $ad, $payment_status, true );

        if ( ! awpcp_current_user_is_moderator() ) {
            $this->send_ad_posted_email_notifications( $ad );
        }
    }

    /**
     * Calculate start and end dates for a listing using information from the
     * given payment term.
     *
     * @since 4.0.0
     *
     * @param object|false $payment_term The payment term used to calculate the dates.
     * @param string|null  $start_date   Optional. If given, the end date will be
     *                             calculated adding the duration of the payment
     *                             term to the $start_date.
     *
     * @return array {
     *     @type string $_awpcp_start_date The new start date for the listing.
     *     @type string $_awpcp_end_date   The new end date for the listing.
     * }
     */
    public function calculate_start_and_end_dates_using_payment_term( $payment_term, $start_date = null ) {
        $timestamp = strtotime( $start_date );

        if ( ! $start_date ) {
            $start_date = current_time( 'mysql' );
            $timestamp  = current_time( 'timestamp' );
        }

        // Let's assume a null payment term last zero seconds.
        $end_date = $start_date;

        if ( $payment_term ) {
            $end_date = $payment_term->calculate_end_date( $timestamp );
        }

        return [
            '_awpcp_start_date' => $start_date,
            '_awpcp_end_date'   => $end_date,
        ];
    }

    /**
     * @since 4.0.0
     */
    public function get_modified_listing_post_status( $listing ) {
        if ( $this->roles->current_user_is_moderator() ) {
            return $listing->post_status;
        }

        if ( $this->settings->get_option( 'disable-edited-listings-until-admin-approves' ) ) {
            return 'pending';
        }

        return $listing->post_status;
    }

    /**
     * @since 4.0.0
     */
    public function set_new_listing_post_status( $listing, $payment_status, $trigger_actions ) {
        if ( $this->roles->current_user_is_moderator() ) {
            return $this->enable_listing_maybe_triggering_actions( $listing, $trigger_actions );
        }

        if ( $this->settings->get_option( 'adapprove' ) ) {
            return $this->mark_listing_as_awating_approval( $listing );
        }

        if ( $payment_status == AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING && ! $this->settings->get_option( 'enable-ads-pending-payment' ) ) {
            return $this->mark_listing_as_awating_approval( $listing );
        }

        return $this->enable_listing_maybe_triggering_actions( $listing, $trigger_actions );
    }

    /**
     * @since 4.0.0
     */
    public function set_modified_listing_post_status( $listing ) {
        $post_status = $this->get_modified_listing_post_status( $listing );

        if ( $post_status === $listing->post_status ) {
            return true;
        }

        if ( 'pending' === $post_status ) {
            return $this->mark_listing_as_awating_approval( $listing );
        }

        if ( 'publish' === $post_status ) {
            return $this->enable_listing( $listing );
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    private function enable_listing_maybe_triggering_actions( $listing, $trigger_actions ) {
        if ( $trigger_actions ) {
            return $this->enable_listing( $listing );
        }

        return $this->enable_listing_without_triggering_actions( $listing );
    }

    /**
     * @since 4.0.0
     */
    public function mark_listing_as_awating_approval( $listing ) {
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_awaiting_approval', true );

        $listing->post_status = 'pending';

        $post_data = [
            'ID'          => $listing->ID,
            'post_status' => $listing->post_status,
        ];

        return $this->wordpress->update_post( $post_data );
    }

    /**
     * @since 4.0.0
     */
    public function mark_as_having_images_awaiting_approval( $listing ) {
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_has_images_awaiting_approval', true );
    }

    /**
     * @since 4.0.0
     */
    public function remove_having_images_awaiting_approval_mark( $listing ) {
        return $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_has_images_awaiting_approval' );
    }

    /**
     * @since 4.0.0
     */
    public function enable_listing( $listing ) {
        if ( $this->enable_listing_without_triggering_actions( $listing ) ) {
            do_action( 'awpcp_approve_ad', $listing );
            return true;
        }

        return false;
    }

    /**
     * Set the listing's status to enabled, but don't trigger the
     * awpcp_approve_ad action.
     *
     * @since 4.0.0
     *
     * @return bool if the listing was enabled, false otherwise.
     */
    public function enable_listing_without_triggering_actions( $listing ) {
        if ( apply_filters( 'awpcp_before_approve_ad', $this->listing_renderer->is_public( $listing ) ) ) {
            return false;
        }

        $post_data = [
            'post_fields' => [
                'post_status' => 'publish',
            ],
        ];

        // TODO: this is kind of useles... if images don't need to be approved,
        // they are likely already enabled...
        //
        // Also, why don't we disable images when the listing is disabled?
        $images_must_be_approved = $this->settings->get_option( 'imagesapprove', false );

        if ( ! $images_must_be_approved ) {
            $images = $this->attachments->find_attachments_of_type_awaiting_approval( 'image', array( 'post_parent' => $listing->ID ) );

            foreach ( $images as $image ) {
                $this->attachments_logic->approve_attachment( $image );
            }
        }

        // Make sure the duration of the ad is modified to account for the
        // number of days it remained disabled waiting for admin approval.
        if ( $this->listing_renderer->is_pending_approval( $listing ) ) {
            $post_data['metadata'] = $this->calculate_start_and_end_dates_using_payment_term(
                $this->listing_renderer->get_payment_term( $listing )
            );
        }

        $this->update_listing( $listing, $post_data );
        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_disabled_date' );

        // Allow other parts of the code to access the latest post status
        // without reloading the instance from the database.
        $listing->post_status = $post_data['post_fields']['post_status'];

        return true;
    }

    public function disable_listing( $listing ) {
        $this->disable_listing_without_triggering_actions( $listing );

        do_action( 'awpcp_disable_ad', $listing );

        return true;
    }

    public function disable_listing_without_triggering_actions( $listing ) {
        $listing->post_status = $this->disabled_status;

        $this->wordpress->update_post( array( 'ID' => $listing->ID, 'post_status' => $listing->post_status ) );
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_disabled_date', current_time( 'mysql' ) );
    }

    /**
     * @since 4.0.0
     */
    public function expire_listing( $listing ) {
        $this->wordpress->update_post_meta( $listing->ID, '_awpcp_expired', true );

        return $this->disable_listing( $listing );
    }

    /**
     * @since 4.1.7
     */
    public function expire_listing_with_notice( $listing, $email_info = array() ) {
        $this->expire_listing( $listing );
        AWPCP_SendEmails::send_expiring( $listing, $email_info );
    }

    public function renew_listing( $listing, $end_date = false ) {
        if ( $end_date === false ) {
            $period_start_date = null;
            $current_end_date  = $this->listing_renderer->get_plain_end_date( $listing );
            $timestamp         = awpcp_datetime( 'timestamp', $current_end_date );

            // If the listing's end date is in the future, use that date as
            // starting point for the new end date.
            if ( $timestamp > current_time( 'timestamp' ) ) {
                $period_start_date = $current_end_date;
            }

            $calculated_dates = $this->calculate_start_and_end_dates_using_payment_term(
                $this->listing_renderer->get_payment_term( $listing ),
                $period_start_date
            );

            $end_date = $calculated_dates['_awpcp_end_date'];
        }

        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_renew_email_sent' );
        $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_expired' );

        // Let update_listing_metadata() update the most recent start date if necessary.
        $this->update_listing_metadata(
            $listing,
            [
                '_awpcp_end_date'     => $end_date,
                '_awpcp_renewed_date' => current_time( 'mysql' ),
            ]
        );

        if ( ! $this->listing_renderer->is_public( $listing ) ) {
            $this->enable_listing( $listing );
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function generate_access_key() {
        return md5( sprintf( '%s%s%d', wp_salt(), uniqid( '', true ), wp_rand( 1, 1000 ) ) );
    }

    /**
     * @since 3.0.2
     */
    public function get_ad_alerts( $ad ) {
        $alerts = array();

        if ( get_awpcp_option( 'adapprove' ) == 1 && $this->listing_renderer->is_pending_approval( $ad ) ) {
            $alerts[] = get_awpcp_option( 'notice_awaiting_approval_ad' );
        }

        if ( get_awpcp_option( 'imagesapprove' ) == 1 ) {
            $alerts[] = __( 'If you have uploaded images your images will not show up until an admin has approved them.', 'another-wordpress-classifieds-plugin' );
        }

        return $alerts;
    }

    /**
     * @since 3.0.2
     */
    public function send_ad_posted_email_notifications( $ad, $messages = array(), $transaction = null ) {
        $messages = array_merge( $messages, $this->get_ad_alerts( $ad ) );

        awpcp_send_listing_posted_notification_to_user( $ad, $transaction, join( "\n\n", $messages ) );
        awpcp_send_listing_posted_notification_to_moderators( $ad, $transaction, join( "\n\n", $messages ) );

        $moderate_listings = get_awpcp_option( 'adapprove' );
        $moderate_images = get_awpcp_option('imagesapprove') == 1;

        if ( ( $moderate_listings || $moderate_images ) && $this->listing_renderer->is_pending_approval( $ad ) ) {
            awpcp_send_listing_awaiting_approval_notification_to_moderators(
                $ad, $moderate_listings, $moderate_images
            );
        }
    }

    /**
     * @since 3.0.2
     */
    public function send_ad_updated_email_notifications( $ad, $messages = array() ) {
        $messages = array_merge( $messages, $this->get_ad_alerts( $ad ) );

        awpcp_send_listing_updated_notification_to_user( $ad, join( "\n\n", $messages ) );
        awpcp_send_listing_updated_notification_to_moderators( $ad, join( "\n\n", $messages ) );

        $moderate_modifications = get_awpcp_option( 'disable-edited-listings-until-admin-approves' );
        $moderate_images = get_awpcp_option('imagesapprove') == 1;

        if ( ( $moderate_modifications || $moderate_images ) && $this->listing_renderer->is_disabled( $ad ) ) {
            awpcp_send_listing_awaiting_approval_notification_to_moderators(
                $ad, $moderate_modifications, $moderate_images
            );
        }
    }

    /**
     * @since 3.0.2
     */
    public function send_verification_email( $ad ) {
        $contact_email = $this->listing_renderer->get_contact_email( $ad );
        $contact_name = $this->listing_renderer->get_contact_name( $ad );
        $listing_title = $this->listing_renderer->get_listing_title( $ad );

        $replacement = [
            'listing_title'     => $listing_title,
            'author_name'       => $contact_name,
            'verification_link' => awpcp_get_email_verification_url( $ad->ID ),
            'website_title'     => awpcp_get_blog_name(),
            'website_url'       => home_url(),
        ];

        $email = awpcp()->container['EmailHelper']->prepare_email_from_template_setting( 'verify-email-message-email-template', $replacement );

        $email->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );

        $email_sent = $email->send();
        if ( $email_sent ) {
            $emails_sent = intval( $this->wordpress->get_post_meta( $ad->ID, '_awpcp_verification_emails_sent', 1 ) );
            $this->wordpress->update_post_meta( $ad->ID, '_awpcp_verification_email_sent_at', current_time( 'mysql' ) );
            $this->wordpress->update_post_meta( $ad->ID, '_awpcp_verification_emails_sent', $emails_sent + 1 );
        }
        return $email_sent;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 3.4
     */
    public function flag_listing( $listing ) {
        $meta_updated = (bool) $this->wordpress->update_post_meta( $listing->ID, '_awpcp_flagged', true );

        if ( $meta_updated ) {
            awpcp_send_listing_was_flagged_notification( $listing );
        }

        return $meta_updated;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 3.4
     */
    public function unflag_listing( $listing ) {
        return (bool) $this->wordpress->delete_post_meta( $listing->ID, '_awpcp_flagged' );
    }

    public function increase_visits_count( $listing ) {
        $number_of_visits = absint( get_post_meta( $listing->ID, '_awpcp_views', true ) );

        return update_post_meta( $listing->ID, '_awpcp_views', 1 + $number_of_visits );
    }

    /**
     * @since 4.0.0
     */
    public function delete_listing( $listing ) {
        return wp_delete_post( $listing->ID, true ) !== false;
    }

    /**
     * @since 4.0.0
     */
    public function can_payment_information_be_modified_during_submit( $listing ) {
        $payment_term = $this->listing_renderer->get_payment_term( $listing );

        if ( is_null( $payment_term ) ) {
            return true;
        }

        $payment_status = $this->listing_renderer->get_payment_status( $listing );

        // Legacy free listings (internally associated with the Fee width ID = 0)
        // had an empty payment status, even after payment information was
        // consolidated.
        //
        // We check for empty payment status only when the listing is associated
        // with one of the payment terms defined by the admin.
        if ( $payment_term->id && empty( $payment_status ) ) {
            return true;
        }

        if ( 'Unpaid' === $payment_status ) {
            return true;
        }

        return false;
    }

    /**
     * @since 4.0.0
     */
    public function update_listing_payment_term( $listing, $payment_term ) {
        $dates = [
            '_awpcp_start_date' => $this->listing_renderer->get_plain_start_date( $listing ),
            '_awpcp_end_date'   => $this->listing_renderer->get_plain_end_date( $listing ),
        ];

        if ( empty( $dates['_awpcp_start_date'] ) ) {
            $dates['_awpcp_start_date'] = current_time( 'mysql' );
        }

        if ( empty( $dates['_awpcp_end_date'] ) ) {
            $dates = $this->calculate_start_and_end_dates_using_payment_term(
                $payment_term,
                $dates['_awpcp_start_date']
            );
        }

        $post_data = [
            'metadata' => [
                '_awpcp_payment_term_type' => $payment_term->type,
                '_awpcp_payment_term_id'   => $payment_term->id,
                '_awpcp_is_featured'       => $payment_term->featured,
                '_awpcp_start_date'        => $dates['_awpcp_start_date'],
                '_awpcp_end_date'          => $dates['_awpcp_end_date'],
                '_awpcp_is_paid'           => $payment_term->is_paid(),
            ],
        ];

        return $this->update_listing( $listing, $post_data );
    }
}
