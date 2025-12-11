<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for frontend listing actions.
 */
abstract class AWPCP_ListingAction {

    public function is_enabled_for_listing( $listing ) {
        return true;
    }

    abstract public function get_slug();
    abstract public function get_name();
    abstract public function get_description();

    public function get_endpoint( $listing, $config ) {
        return $config['current-url'];
    }

    public function filter_params( $params ) {
        return array_merge( $params, array( 'step' => $this->get_slug() ) );
    }

    public function get_submit_button_label() {
        return $this->get_name();
    }

    /**
     * @since 4.0.0     $config is now optional.
     */
    public function render( $listing, $config = [] ) {
        $slug  = $this->get_slug();
        $nonce = wp_create_nonce( "awpcp-listing-action-{$listing->ID}-{$slug}" );

        ob_start();
        include $this->get_template();
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    protected function get_template() {
        return AWPCP_DIR . '/templates/frontend/listing-action.tpl.php';
    }
}
