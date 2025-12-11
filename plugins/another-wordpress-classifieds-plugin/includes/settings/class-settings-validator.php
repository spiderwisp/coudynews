<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_SettingsValidator {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_Request.
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings, $request ) {
        $this->settings = $settings;
        $this->request  = $request;
    }

    /**
     * Validates AWPCP settings before being saved.
     */
    public function sanitize_settings( $new_options ) {
        $group    = $this->request->post( 'group', '' );
        $subgroup = $this->request->post( 'subgroup', '' );

        if ( ! is_array( $new_options ) ) {
            $new_options = [];
        }

        // Populate array with all plugin options before attempt validation.
        $new_options = array_merge( $this->settings->options, $new_options );

        if ( $subgroup ) {
            $new_options = apply_filters( 'awpcp_validate_settings_subgroup_' . $subgroup, $new_options, $group, $subgroup );
        }

        if ( $group ) {
            $new_options = apply_filters( 'awpcp_validate_settings_' . $group, $new_options, $group, $subgroup );
        }

        $new_options = apply_filters( 'awpcp_validate_settings', $new_options, $group, $subgroup );

        if ( $subgroup ) {
            do_action( 'awpcp_settings_validated_subgroup_' . $subgroup, $new_options, $group, $subgroup );
        }

        if ( $group ) {
            do_action( 'awpcp_settings_validated_' . $group, $new_options, $group, $subgroup );
        }

        do_action( 'awpcp_settings_validated', $new_options, $group, $subgroup );

        // Filters and actions need to be executed before we update the in-memory
        // options to allow handlers to compare existing values with the ones that
        // are about to be saved.
        $this->settings->options = $new_options;

        return $this->settings->options;
    }
}
