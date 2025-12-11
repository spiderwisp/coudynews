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
class AWPCP_PagesSettings {

    /**
     * Handler for awpcp_register_settings action.
     */
    public function register_settings( $settings_manager ) {
        $settings_manager->add_settings_group(
            [
                'name'     => __( 'Pages', 'another-wordpress-classifieds-plugin' ),
                'id'       => 'pages-settings',
                'priority' => 20,
            ]
        );

        $settings_manager->add_settings_subgroup(
            [
                'name'     => __( 'Pages', 'another-wordpress-classifieds-plugin' ),
                'id'       => 'pages-settings',
                'priority' => 10,
                'parent'   => 'pages-settings',
            ]
        );

        $group   = 'pages-settings';
        $section = 'pages-settings';

        $settings_manager->add_section(
            $group,
            __( 'Classifieds Pages', 'another-wordpress-classifieds-plugin' ),
            'pages-settings',
            10,
            array( $settings_manager, 'section' )
        );

        $settings_manager->add_setting(
            $section,
            'main-plugin-page',
            __( 'Classifieds page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( "Plugin's main page.", 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'show-listing-page',
            __( 'Show Listing page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to display individual listings.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'submit-listing-page',
            __( 'Submit Listing page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to submit new listings.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'edit-listing-page',
            __( 'Edit Listing page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to edit listings.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'reply-to-listing-page',
            __( 'Reply to listing page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to contact the owner of a listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'renew-listing-page',
            __( 'Renew listing page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to renew listings.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'browse-listings-page',
            __( 'Browse listings page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to explore listings.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $section,
            'search-listings-page',
            __( 'Search listings page', 'another-wordpress-classifieds-plugin' ),
            'wordpress-page',
            null,
            __( 'Page used to search listings.', 'another-wordpress-classifieds-plugin' )
        );

        $section = 'dynamic-pages';

        $settings_manager->add_section(
            $group,
            __( 'Dynamic Pages', 'another-wordpress-classifieds-plugin' ),
            'dynamic-pages',
            20,
            array( $settings_manager, 'section' )
        );

        $settings_manager->add_setting(
            $section,
            'view-categories-page-name',
            __( 'View Categories page', 'another-wordpress-classifieds-plugin' ),
            'textfield',
            'View Categories',
            __( "This page is one AWPCP will generate for you. We just need a title of the page to show the categories. You can use any name, just make sure it's unique.", 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * Flush rewrite rules when Page settings change.
     *
     * TODO: We should check that the selected page has the corresponding shortcode
     *       and/or update the plugin to show that page's content even if the
     *       shortcode is not set.
     */
    public function page_settings_validated() {
        update_option( 'awpcp-flush-rewrite-rules', true );
    }
}
