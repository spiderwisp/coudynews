<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register moderation settings for listings.
 */
class AWPCP_ListingsModerationSettings {

    public $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function validate_all_settings( $options, $group ) {
        if ( isset( $options['requireuserregistration'] ) && $options['requireuserregistration'] && $this->settings->get_option( 'enable-email-verification' ) ) {
            $message = __( "Email verification was disabled because you enabled Require Registration. Registered users don't need to verify the email address used for contact information.", 'another-wordpress-classifieds-plugin' );
            awpcp_flash_warning( $message );

            $options['enable-email-verification'] = 0;
        }

        return $options;
    }

    public function validate_group_settings( $options, $group ) {
        if ( isset( $options['enable-email-verification'] ) && $options['enable-email-verification'] && $this->settings->get_option( 'requireuserregistration' ) ) {
            $message = __( "Email verification was not enabled because Require Registration is on. Registered users don't need to verify the email address used for contact information.", 'another-wordpress-classifieds-plugin' );
            awpcp_flash_warning( $message );

            $options['enable-email-verification'] = 0;
        }

        return $options;
    }
}
