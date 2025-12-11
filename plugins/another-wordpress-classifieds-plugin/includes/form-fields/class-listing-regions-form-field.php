<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Listing Region Form Field.
 */
function awpcp_listing_regions_form_field( $slug ) {
    return new AWPCP_ListingRegionsFormField(
        $slug,
        awpcp_listing_renderer(),
        awpcp_payments_api(),
        awpcp()->settings
    );
}

/**
 * Form fields used to enter region information.
 */
class AWPCP_ListingRegionsFormField extends AWPCP_FormField {

    private $listing_renderer;
    private $payments;
    private $settings;

    public function __construct( $slug, $listing_renderer, $payments, $settings ) {
        parent::__construct( $slug );

        $this->listing_renderer = $listing_renderer;
        $this->payments         = $payments;
        $this->settings         = $settings;
    }

    public function get_name() {
        return _x( 'Regions', 'listing form field', 'another-wordpress-classifieds-plugin' );
    }

    protected function is_read_only() {
        if ( awpcp_current_user_is_moderator() ) {
            return false;
        }

        if ( $this->settings->get_option( 'allow-regions-modification' ) ) {
            return false;
        }

        $transaction = $this->payments->get_transaction();

        // Ugly hack to figure out if we are editing or creating a list.
        if ( $transaction ) {
            return false;
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data[ $this->get_slug() ] ) ) {
            return null;
        }

        return $data[ $this->get_slug() ];
    }

    public function render( $value, $errors, $listing, $context ) {
        $options = array(
            'showTextField' => false,
            'maxRegions'    => $this->listing_renderer->get_number_of_regions_allowed( $listing ),
            'disabled'      => $this->is_read_only(),
        );

        $region_selector = awpcp_multiple_region_selector( $value, $options );

        return $region_selector->render( 'details', array(), $errors );
    }
}
