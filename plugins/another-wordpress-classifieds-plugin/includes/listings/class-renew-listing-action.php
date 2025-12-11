<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AWPCP_RenewListingAction
 */
class AWPCP_RenewListingAction extends AWPCP_ListingAction {

    public function is_enabled_for_listing( $listing ) {
        if ( awpcp_listing_renderer()->has_expired_or_is_about_to_expire( $listing ) ) {
            return true;
        }
        return false;
    }

    public function get_name() {
        return __( 'Renew', 'another-wordpress-classifieds-plugin' );
    }

    public function get_slug() {
        return 'renew-ad';
    }

    public function get_description() {
        return __( 'You can use this button to renew your ad.', 'another-wordpress-classifieds-plugin' );
    }

    public function render( $listing, $config = [] ) {
        $renew_url = awpcp_get_renew_ad_url( $listing->ID );

        return "<button type='button' onclick=\"window.location.href='{$renew_url}'\">{$this->get_name()}</button>";
    }
}
