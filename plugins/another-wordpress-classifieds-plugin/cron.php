<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ensure we get the expiration hooks scheduled properly:
function awpcp_schedule_activation() {
    $cron_jobs = array(
        'doadexpirations_hook' => 'hourly',
        'doadcleanup_hook' => 'daily',
        'awpcp_ad_renewal_email_hook' => 'hourly',
        'awpcp-clean-up-payment-transactions' => 'daily',
        'awpcp-clean-up-non-verified-ads' => 'daily',
        'awpcp-task-queue-cron' => 'hourly',
    );

    foreach ( $cron_jobs as $cron_job => $frequency ) {
        if ( ! wp_next_scheduled( $cron_job ) ) {
            wp_schedule_event( time(), $frequency, $cron_job );
        }
    }

    add_action('doadexpirations_hook', 'doadexpirations');
    add_action('doadcleanup_hook', 'doadcleanup');
    add_action('awpcp_ad_renewal_email_hook', 'awpcp_ad_renewal_email');
    add_action('awpcp-clean-up-payment-transactions', 'awpcp_clean_up_payment_transactions');
    add_action( 'awpcp-clean-up-payment-transactions', 'awpcp_clean_up_non_verified_ads_handler' );
}

/*
 * Cron job handler executed every hour to disable ads that already expired.
 *
 * Notifications, if enabled, are always sent, even if the plugin is configured
 * to delete expired ads instead of disabling them.
 *
 * See https://github.com/drodenbaugh/awpcp/issues/808#issuecomment-42561940
 */
function doadexpirations() {
    $listings_logic = awpcp_listings_api();

    $ads = awpcp_listings_collection()->find_valid_listings(array(
        'post_type'   => AWPCP_LISTING_POST_TYPE,
        'post_status' => 'publish',
        'meta_query'  => array(
            array(
                'key'     => '_awpcp_end_date',
                'value'   => current_time( 'mysql' ),
                'compare' => '<=',
                'type'    => 'DATETIME',
            ),
        ),
    ));

    $email_info = AWPCP_SendEmails::get_expiring_email();
    foreach ( $ads as $ad ) {
        $listings_logic->expire_listing_with_notice( $ad, $email_info );
    }
}

/**
 * Function run once per month to cleanup incomplete and expired ads.
 */
function doadcleanup() {
    $listings_logic = awpcp_listings_api();
    $listings       = awpcp_listings_collection();

    if ( get_awpcp_option( 'delete-expired-listings' ) ) {
        $days_before = get_awpcp_option( 'days-before-expired-listings-are-deleted' );
        awpcp_delete_listings_expired_more_than_days_ago( intval( $days_before ), $listings_logic, $listings );
    }

    awpcp_delete_unpaid_listings_older_than_a_month( $listings_logic, $listings );
}

/**
 * @since 4.0.0
 */
function awpcp_delete_listings_expired_more_than_days_ago( $number_of_days, $listings_logic, $listings ) {
    $date_query = new WP_Date_Query( [] );
    $end_date   = $date_query->build_mysql_datetime( sprintf( '%d days ago', $number_of_days ) );

    $query_vars = [
        'post_status' => 'disabled',
        'meta_query'  => [
            [
                'relation' => 'OR',
                [
                    'key'     => '_awpcp_disabled_date',
                    'compare' => '<',
                    'value'   => $end_date,
                    'type'    => 'DATE',
                ],
                [
                    'key'     => '_awpcp_end_date',
                    'compare' => '<',
                    'value'   => $end_date,
                    'type'    => 'DATE',
                ],
            ],
            [
                'key'     => '_awpcp_expired',
                'compare' => 'EXISTS',
            ],
        ],
    ];

    foreach ( $listings->find_listings( $query_vars ) as $listing ) {
        $listings_logic->delete_listing( $listing );
    }
}

/**
 * @access private
 * @since 4.0.0
 */
function awpcp_delete_unpaid_listings_older_than_a_month( $listings_logic, $listings ) {
    $query = array(
        'meta_query' => array(
            array(
                'key' => '_awpcp_payment_status',
                'value' => 'Unpaid',
                'compare' => '=',
            ),
        ),
        'date_query' => array(
            array(
                'column' => 'post_date_gmt',
                'before' => '30 days ago',
            ),
        ),
    );

    foreach ( $listings->find_listings( $query ) as $listing ) {
        $listings_logic->delete_listing( $listing );
    }
}

/**
 * Check if any Ad is about to expire and send an email to the poster.
 *
 * This functions runs daily.
 */
function awpcp_ad_renewal_email() {
    if (!(get_awpcp_option('sent-ad-renew-email') == 1)) {
        return;
    }

    foreach ( awpcp_listings_collection()->find_listings_about_to_expire() as $listing ) {
        AWPCP_SendEmails::send_renewal( $listing );
    }
}

function awpcp_calculate_end_of_renew_email_date_range_from_now() {
    $threshold = intval( get_awpcp_option( 'ad-renew-email-threshold' ) );
    $target_date = strtotime( "+ $threshold days", current_time( 'timestamp' ) );

    return $target_date;
}

/**
 * Remove incomplete payment transactions
 */
function awpcp_clean_up_payment_transactions() {
    $threshold = awpcp_datetime( 'mysql', current_time( 'timestamp' ) - 24 * 60 * 60 );

    $transactions = AWPCP_Payment_Transaction::query(array(
        'status' => array(
            AWPCP_Payment_Transaction::STATUS_NEW,
            AWPCP_Payment_Transaction::STATUS_OPEN,
        ),
        'created' => array('<', $threshold),
    ));

    foreach ($transactions as $transaction) {
        $transaction->delete();
    }
}

/**
 * @since 3.3
 */
function awpcp_clean_up_non_verified_ads_handler() {
    return awpcp_clean_up_non_verified_ads(
        awpcp_listings_collection(),
        awpcp_listings_api(),
        awpcp()->settings,
        awpcp_wordpress()
    );
}

/**
 * @since 4.0.0  Updated to load listings using Listings Collection methods.
 * @since 3.0.2
 */
function awpcp_clean_up_non_verified_ads( $listings_collection, /* AWPCP_ListingsAPI */ $listings, $settings, $wordpress ) {
    if ( ! $settings->get_option( 'enable-email-verification' ) ) {
        return;
    }

    $resend_email_threshold = $settings->get_option( 'email-verification-first-threshold' );
    $delete_ads_threshold = $settings->get_option( 'email-verification-second-threshold' );

    // delete Ads that have been in a non-verified state for more than M days
    $results = $listings_collection->find_listings_awaiting_verification( array(
        'date_query' => array(
            'relation' => 'AND',
            array(
                'before' => awpcp_datetime( 'mysql', current_time( 'timestamp' ) - $delete_ads_threshold * DAY_IN_SECONDS ),
            ),
        ),
    ) );

    foreach ( $results as $listing ) {
        $listings->delete_listing( $listing );
    }

    // re-send verificaiton email for Ads that have been in a non-verified state for more than N days
    $results = $listings_collection->find_listings_awaiting_verification( array(
        'date_query' => array(
            'relation' => 'AND',
            array(
                'before' => awpcp_datetime( 'mysql', current_time( 'timestamp' ) - $resend_email_threshold * DAY_IN_SECONDS ),
            ),
        ),
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_awpcp_verification_emails_sent',
                'value' => 1,
                'compare' => '<=',
                'type'    => 'UNSIGNED',
            ),
        ),
    ) );

    foreach ( $results as $listing ) {
        $emails_sent = intval( $wordpress->get_post_meta( $listing->ID, '_awpcp_verification_emails_sent', 1 ) );

        if ( $emails_sent >= 2 ) {
            continue;
        }

        $listings->send_verification_email( $listing );
    }
}
