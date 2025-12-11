<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for Delete Listing frontend action.
 */
class AWPCP_DeleteListingActionHandler {

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings_logic ) {
        $this->listings_logic = $listings_logic;
    }

    /**
     * @since 4.0.0
     */
    public function do_action( $response, $listing ) {
        if ( ! $this->listings_logic->delete_listing( $listing ) ) {
            $response['error'] = __( 'There was a problem trying to delete your ad. The ad was not deleted.', 'another-wordpress-classifieds-plugin' );

            return $response;
        }

        $response['message']      = __( 'The ad was successfully deleted.', 'another-wordpress-classifieds-plugin' );
        $response['redirect_url'] = awpcp_get_page_url( 'browse-ads-page-name' );

        return $response;
    }
}
