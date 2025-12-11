<?php
/**
 * @package AWPCP\Integrations\Facebook
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.8.6
 */
class AWPCP_FacebookIntegration {

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    public $wordpress;

    /**
     * @since 3.8.6
     */
    public function __construct( $listing_renderer, $settings, $wordpress ) {
        $this->listing_renderer = $listing_renderer;
        $this->settings         = $settings;
        $this->wordpress        = $wordpress;
    }

    /**
     * @since 3.8.6
     */
    public function on_ad_modified( $ad ) {
        if ( $this->settings->get_option( 'clear-facebook-cache-when-listings-are-modified' ) ) {
            $this->maybe_schedule_clear_cache_action( $ad );

            return;
        }

        $this->maybe_schedelue_send_to_facebook_action( $ad );
    }

    /**
     * @since 3.8.6
     */
    private function maybe_schedule_clear_cache_action( $ad ) {
        $user_token = $this->settings->get_option( 'facebook-user-access-token' );

        if ( ! $user_token ) {
            return;
        }

        $this->schedule_clear_cache_action( $ad );
    }

    /**
     * @since 3.8.6
     */
    public function schedule_clear_cache_action( $ad, $wait_time = 10 ) {
        $this->schedule_action_seconds_from_now( $ad, 'awpcp-clear-ad-facebook-cache', $wait_time );
    }

    /**
     * @since 3.8.6
     */
    private function schedule_action_seconds_from_now( $ad, $action, $wait_time ) {
        $params = array( $ad->ID, $this->wordpress->current_time( 'timestamp' ) );

        if ( ! wp_next_scheduled( $action, $params ) ) {
            $this->wordpress->schedule_single_event( time() + $wait_time, $action, $params );
        }
    }

    /**
     * @since 3.8.6
     */
    public function maybe_schedelue_send_to_facebook_action( $ad ) {
        if ( ! $this->settings->get_option( 'sends-listings-to-facebook-automatically', true ) ) {
            return;
        }

        if ( ! $this->listing_renderer->is_public( $ad ) ) {
            return;
        }

        $page_integration_configured = $this->is_facebook_page_integration_configured();
        $already_sent_to_a_fb_page   = $this->wordpress->get_post_meta( $ad->ID, '_awpcp_sent_to_facebook_page', true );

        if ( $page_integration_configured && ! $already_sent_to_a_fb_page ) {
            $this->schedule_send_to_facebook_action( $ad );

            return;
        }

        $group_integration_configured = $this->is_facebook_group_integration_configured();
        $already_sent_to_a_fb_group   = $this->wordpress->get_post_meta( $ad->ID, '_awpcp_sent_to_facebook_group', true );

        if ( $group_integration_configured && ! $already_sent_to_a_fb_group ) {
            $this->schedule_send_to_facebook_action( $ad );

            return;
        }
    }

    /**
     * @since 3.8.6
     */
    public function is_facebook_page_integration_configured() {
        $integration_method = $this->settings->get_option( 'facebook-integration-method' );

        if ( 'facebook-api' === $integration_method && $this->settings->get_option( 'facebook-page' ) ) {
            return true;
        }

        if ( 'webhooks' === $integration_method && $this->settings->get_option( 'zapier-webhook-for-facebook-page-integration' ) ) {
            return true;
        }

        if ( 'webhooks' === $integration_method && $this->is_ifttt_webhook_configured() ) {
            return true;
        }

        return false;
    }

    /**
     * @since 3.8.6
     */
    private function is_ifttt_webhook_configured() {
        if ( ! $this->settings->get_option( 'ifttt-webhook-base-url-for-facebook-page-integration' ) ) {
            return false;
        }

        if ( ! $this->settings->get_option( 'ifttt-webhook-event-name-for-facebook-page-integration' ) ) {
            return false;
        }

        return true;
    }

    /**
     * @since 3.8.6
     */
    public function is_facebook_group_integration_configured() {
        $integration_method = $this->settings->get_option( 'facebook-integration-method' );

        if ( 'facebook-api' === $integration_method && $this->settings->get_option( 'facebook-group' ) ) {
            return true;
        }

        return false;
    }

    /**
     * @since 3.8.6
     */
    private function schedule_send_to_facebook_action( $ad, $wait_time = 10 ) {
        $this->schedule_action_seconds_from_now( $ad, 'awpcp-send-listing-to-facebook', $wait_time );
    }

    /**
     * @since 3.8.6
     */
    public function on_ad_facebook_cache_cleared( $ad ) {
        $this->maybe_schedelue_send_to_facebook_action( $ad );
    }
}
