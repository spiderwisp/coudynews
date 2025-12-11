<?php
/**
 * @package AWPCP\Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A helper class used to clear ads information from Facebook cache so that
 * the social snippets show up to date content when the URLs are shared.
 */
class AWPCP_FacebookCacheHelper {

    /**
     * @var AWPCP_FacebookIntegration
     */
    private $facebook_integration;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $ads;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    public function __construct( $facebook_integration, $listing_renderer, $ads, $settings ) {
        $this->facebook_integration = $facebook_integration;
        $this->listing_renderer     = $listing_renderer;
        $this->ads                  = $ads;
        $this->settings             = $settings;
    }

    public function handle_clear_cache_event_hook( $ad_id ) {
        try {
            $ad = $this->ads->get( $ad_id );
        } catch ( AWPCP_Exception $e ) {
            return;
        }

        $this->clear_ad_cache( $ad );
    }

    /**
     * @SuppressWarnings(ElseExpression)
     */
    private function clear_ad_cache( $ad ) {
        if ( is_null( $ad ) || ! $this->listing_renderer->is_public( $ad ) ) {
            return;
        }

        $user_token = $this->settings->get_option( 'facebook-user-access-token' );

        if ( ! $user_token ) {
            return;
        }

        $args = array(
            'timeout' => 30,
            'body'    => array(
                'id'           => url_showad( $ad->ID ),
                'scrape'       => true,
                'access_token' => $user_token,
            ),
        );

        $response = wp_remote_post( 'https://graph.facebook.com/', $args );

        if ( $this->is_successful_response( $response ) ) {
            do_action( 'awpcp-listing-facebook-cache-cleared', $ad );
        } else {
            $this->facebook_integration->schedule_clear_cache_action( $ad, 5 * MINUTE_IN_SECONDS );
        }
    }

    private function is_successful_response( $response ) {
        if ( is_wp_error( $response ) || ! is_array( $response ) ) {
            return false;
        } elseif ( ! isset( $response['response']['code'] ) ) {
            return false;
        } elseif ( intval( $response['response']['code'] ) !== 200 ) {
            return false;
        }

        $listing_info = json_decode( $response['body'] );

        if ( ! isset( $listing_info->type ) || $listing_info->type !== 'article' ) {
            return false;
        } elseif ( empty( $listing_info->title ) ) {
            return false;
        } elseif ( ! isset( $listing_info->description ) ) {
            return false;
        }

        return true;
    }
}
