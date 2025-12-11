<?php
/**
 * @package AWPCP\Placeholders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for objects that verify that a particular placeholder was added to
 * listings templates.
 */
class AWPCP_PlaceholdersInstallationVerifier {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Subclasses will override this.
     */
    public function check_placeholder_installation() {
    }

    /**
     * Checks whether the given placeholder is missing the listing's Single layout.
     */
    protected function is_placeholder_missing( $placeholder ) {
        return $this->is_placeholder_missing_in_single_listing_layout( $placeholder );
    }

    /**
     * Checks whether the given placeholder is missing the listing's Single layout.
     */
    protected function is_placeholder_missing_in_single_listing_layout( $placeholder ) {
        return $this->is_placeholder_missing_in_setting( 'awpcpshowtheadlayout', $placeholder );
    }

    /**
     * Checks whether the given placeholder is missing in the layout for the list of listings.
     */
    protected function is_placeholder_missing_in_listings_layout( $placeholder ) {
        return $this->is_placeholder_missing_in_setting( 'displayadlayoutcode', $placeholder );
    }

    /**
     * Checks whether the given placeholder is missing in the layout stored in the
     * option with the given setting name.
     */
    private function is_placeholder_missing_in_setting( $setting_name, $placeholder ) {
        return strpos( $this->settings->get_option( $setting_name ), $placeholder ) === false;
    }

    /**
     * Prints a notice indicating that a placeholder is missing.
     */
    protected function show_missing_placeholder_notice( $warning_message ) {
        $warning_message = sprintf( '<strong>%s:</strong> %s', __( 'Warning', 'another-wordpress-classifieds-plugin' ), $warning_message );

        $url  = awpcp_get_admin_settings_url( [ 'sg' => 'layout-and-presentation-settings' ] );
        $link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $url ),
            esc_html( __( 'Display > Layout and Presentation settings page', 'another-wordpress-classifieds-plugin' ) )
        );

        /* translators: %s an HTML A element */
        $go_to_settings_message = sprintf( __( 'Go to the %s to change the Single Ad layout.', 'another-wordpress-classifieds-plugin' ), $link );

        echo wp_kses_post( awpcp_print_error( sprintf( '%s<br/><br/>%s', $warning_message, $go_to_settings_message ) ) );
    }
}
