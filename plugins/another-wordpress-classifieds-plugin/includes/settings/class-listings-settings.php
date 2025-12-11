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
class AWPCP_ListingsSettings {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Handler for awpcp_register_settings action.
     */
    public function register_settings( $settings_manager ) {
        $settings_manager->add_settings_group(
            [
                'id'       => 'listings-settings',
                'name'     => __( 'Classifieds', 'another-wordpress-classifieds-plugin' ),
                'priority' => 30,
            ]
        );

        $this->register_moderation_settings( $settings_manager );
        $this->register_notification_settings( $settings_manager );
        $this->register_seo_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    private function register_moderation_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup(
            [
                'id'       => 'listings-moderation',
                'name'     => __( 'Moderation', 'another-wordpress-classifieds-plugin' ),
                'priority' => 10,
                'parent'   => 'listings-settings',
            ]
        );

        // Section: Ad/Listings - Moderation.

        $key = 'listings-moderation';

        $settings_manager->add_section( 'listings-moderation', __( 'Moderation', 'another-wordpress-classifieds-plugin' ), 'listings-moderation', 10, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting( $key, 'onlyadmincanplaceads', __( 'Only admin can post Ads', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'If checked only administrator users will be allowed to post Ads.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'adapprove',
            __( 'Disable listings until administrator approves', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            __( 'New Ads will be in a disabled status, not visible to visitors, until the administrator approves them.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'disable-edited-listings-until-admin-approves',
            __( 'Disable listings until administrator approves modifications', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'adapprove' ),
            __( 'Listings will be in a disabled status after the owners modifies them and until the administrator approves them.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'enable-ads-pending-payment', __( 'Enable paid ads that are pending payment.', 'another-wordpress-classifieds-plugin' ), 'checkbox', get_awpcp_option( 'disablependingads', 1 ), __( 'Enable paid ads that are pending payment.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'enable-email-verification', __( 'Have non-registered users verify the email address used to post new Ads', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'A message with an email verification link will be sent to the email address used in the contact information. New Ads will remain disabled until the user clicks the verification link.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'email-verification-first-threshold', __( 'Number of days before the verification email is sent again', 'another-wordpress-classifieds-plugin' ), 'textfield', 5, '' );
        $settings_manager->add_setting( $key, 'email-verification-second-threshold', __( 'Number of days before Ads that remain in a unverified status will be deleted', 'another-wordpress-classifieds-plugin' ), 'textfield', 30, '' );

        $settings_manager->add_setting(
            $key,
            'notice_awaiting_approval_ad',
            __( 'Awaiting approval message', 'another-wordpress-classifieds-plugin' ),
            'textarea',
            __( 'All listings must be approved by an administrator before they are activated on the system. As soon as an administrator has approved your listing it will become visible. Thank you for your business.', 'another-wordpress-classifieds-plugin' ),
            __( 'This message is shown to users right after they post an Ad, if that Ad is awaiting approval from the administrator. The message may also be included in email notifications sent when a new Ad is posted.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'ad-poster-email-address-whitelist', __( 'Allowed domains in Ad poster email', 'another-wordpress-classifieds-plugin' ), 'textarea', '', __( 'Only email addresses with a domain in the list above will be allowed. *.foo.com will match a.foo.com, b.foo.com, etc. but foo.com will match foo.com only. Please type a domain per line.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'addurationfreemode',
            __( 'Duration of listings in free mode (in days)', 'another-wordpress-classifieds-plugin' ),
            'textfield',
            0,
            __( 'The end date for listings posted in free mode will be calculated using the value in this field. You can enter 0 to keep listings enabled for 10 years.', 'another-wordpress-classifieds-plugin' )
        );

        $setting_name = __( 'Delete expired ads', 'another-wordpress-classifieds-plugin' );

        $settings_manager->add_setting(
            [
                'id'          => 'delete-expired-listings',
                'name'        => $setting_name,
                'default'     => ! (bool) $this->settings->get_option( 'autoexpiredisabledelete' ),
                'type'        => 'checkbox',
                'description' => __( "Check to delete ads after the number of days configured in the next setting have passed since the ads were marked as expired. If not checked, ads will continue to be stored in the system but won't be visible in the frontend. They'll remain disabled.", 'another-wordpress-classifieds-plugin' ),
                'section'     => $key,
            ]
        );

        $description = __( 'If the <setting-name> setting is checked, the ads will be permanently deleted from the system after the number of days configured in this field have passed since each ad was marked as expired.', 'another-wordpress-classifieds-plugin' );
        $description = str_replace( '<setting-name>', '<strong>' . $setting_name . '</strong>', $description );

        $settings_manager->add_setting(
            [
                'id'          => 'days-before-expired-listings-are-deleted',
                'name'        => __( 'Number of days before expired listings are deleted', 'another-wordpress-classifieds-plugin' ),
                'type'        => 'textfield',
                'priority'    => 7,
                'description' => $description,
                'behavior'    => [
                    'enabledIf' => 'delete-expired-listings',
                ],
                'section'     => $key,
            ]
        );

        $settings_manager->add_setting(
            $key,
            'allow-start-date-modification',
            __( 'Allow users to edit the start date of their listings?', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            ''
        );

        awpcp_files_settings()->register_images_moderation_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    private function register_notification_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup(
            [
                'id'       => 'listings-notifications',
                'name'     => __( 'Notifications', 'another-wordpress-classifieds-plugin' ),
                'priority' => 20,
                'parent'   => 'listings-settings',
            ]
        );

        $this->register_subscriber_notifications_settings( $settings_manager );
        $this->register_moderator_notifications_settings( $settings_manager );
        $this->register_administrator_notifications_settings( $settings_manager );
    }

    /**
     * Register settings for subscriber notifications.
     */
    private function register_subscriber_notifications_settings( $settings_manager ) {
        $key = 'user-notifications';

        $settings_manager->add_section( 'listings-notifications', __( 'User Notifications', 'another-wordpress-classifieds-plugin' ), 'user-notifications', 3, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting(
            $key,
            'send-user-ad-posted-notification',
            __( 'Listing Created', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'An email will be sent when a listing is created.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'send-user-ad-edited-notification',
            __( 'Listing Edited', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'An email will be sent when a listing is edited.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'send-ad-enabled-email', __( 'Listing Enabled', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Notify Ad owner when the Ad is enabled.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'sent-ad-renew-email', __( 'Listing Needs to be Renewed', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'An email will be sent to remind the user to Renew the Ad when the Ad is about to expire.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'ad-renew-email-threshold',
            __( 'When should AWPCP send the expiration notice?', 'another-wordpress-classifieds-plugin' ),
            'textfield',
            5,
            __( 'Enter the number of days before the ad expires to send the email.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'notifyofadexpiring', __( 'Listing Expired', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'An email will be sent when the Ad expires.', 'another-wordpress-classifieds-plugin' ) );
    }

    /**
     * Register settings for moderator notifications.
     *
     * @param AWPCP_SettingsManager $settings_manager The plugin's settings manager.
     */
    private function register_moderator_notifications_settings( $settings_manager ) {
        $key = 'moderator-notifications';

        $settings_manager->add_section( 'listings-notifications', __( 'Moderator Notifications', 'another-wordpress-classifieds-plugin' ), 'moderator-notifications', 4, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting(
            $key,
            'send-listing-posted-notification-to-moderators',
            __( 'Listing Created', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'notifyofadposted' ),
            __( 'An email will be sent to moderators when a listing is created.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'send-listing-updated-notification-to-moderators',
            __( 'Listing Edited', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'notifyofadposted' ),
            __( 'An email will be sent to moderators when a listing is edited.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'send-listing-awaiting-approval-notification-to-moderators',
            __( 'Listing Awaiting Approval', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'notifyofadposted' ),
            __( 'An email will be sent to moderator users every time a listing needs to be approved.', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * Register settings for administrator notifications.
     *
     * @param AWPCP_SettingsManager $settings_manager The plugin's settings manager.
     */
    private function register_administrator_notifications_settings( $settings_manager ) {
        $key = 'admin-notifications';

        $settings_manager->add_section( 'listings-notifications', __( 'Admin Notifications', 'another-wordpress-classifieds-plugin' ), 'admin-notifications', 5, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting(
            $key,
            'notifyofadposted',
            __( 'Listing Created', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'An email will be sent when a listing is created.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'notifyofadexpired', __( 'Listing Expired', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'An email will be sent when the Ad expires.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'send-listing-updated-notification-to-administrators',
            __( 'Listing Edited', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'notifyofadposted' ),
            __( 'An email will be sent to administrator when a listing is edited.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            [
                'id'          => 'send-listing-renewed-notification-to-admin',
                'name'        => __( 'Listing Renewed', 'another-wordpress-classifieds-plugin' ),
                'type'        => 'checkbox',
                'description' => __( 'An email will be sent to administrator users when a listing is renewed.', 'another-wordpress-classifieds-plugin' ),
                'default'     => true,
                'validation'  => [],
                'behavior'    => [],
                'section'     => $key,
            ]
        );

        $settings_manager->add_setting(
            $key,
            'send-listing-awaiting-approval-notification-to-administrators',
            __( 'Listing Awaiting Approval', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'notifyofadposted' ),
            __( 'An email will be sent to administrator users every time a listing needs to be approved.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'send-listing-flagged-notification-to-administrators',
            __( 'Listing Was Flagged', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            true,
            __( 'An email will be sent to administrator users when a listing is flagged.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'send-media-uploaded-notification-to-administrators',
            __( 'New media was uploaded', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            false,
            __( 'An email will be sent to administrator users when new media is added to a listing.', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_seo_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup(
            [
                'id'       => 'seo-settings',
                'name'     => __( 'SEO', 'another-wordpress-classifieds-plugin' ),
                'priority' => 30,
                'parent'   => 'listings-settings',
            ]
        );

        $this->register_friendly_urls_settings( $settings_manager );
        $this->register_window_title_settings( $settings_manager );
        $this->register_redirect_settings( $settings_manager );
        $this->register_url_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    private function register_friendly_urls_settings( $settings_manager ) {
        $key = 'friendly-urls-settings';

        $settings_manager->add_settings_section(
            [
                'id'       => 'friendly-urls-settings',
                'name'     => __( 'Search Engine Friendly URLs', 'another-wordpress-classifieds-plugin' ),
                'subgroup' => 'seo-settings',
            ]
        );

        $settings_manager->add_setting(
            $key,
            'seofriendlyurls',
            __( 'Turn on Search Engine Friendly URLs', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Turn on Search Engine Friendly URLs? (SEO Mode)', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * @since 4.0.10
     */
    private function register_redirect_settings( $settings_manager ) {
        $key = 'redirect-settings';

        $settings_manager->add_settings_section(
            [
                'id'       => $key,
                'name'     => __( 'Redirection', 'another-wordpress-classifieds-plugin' ),
                'subgroup' => 'seo-settings',
                'priority' => 11,
            ]
        );

        $settings_manager->add_setting(
            $key,
            '301redirection',
            __( 'Redirect deleted listings', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Perform a 301 redirect to the main classifieds page when landing on a deleted listing url.', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_window_title_settings( $settings_manager ) {
        $key = 'window-title';

        $introduction = _x( 'These settings affect the title shown in the title bar of the browser for the listing. You can include or remove certain elements if you wish.', 'window title settings section', 'another-wordpress-classifieds-plugin' );

        $settings_manager->add_settings_section(
            [
                'id'          => 'window-title',
                'name'        => __( 'Window Title', 'another-wordpress-classifieds-plugin' ),
                'description' => '<p>' . $introduction . '</p>',
                'priority'    => 40,
                'subgroup'    => 'seo-settings',
            ]
        );

        $settings_manager->add_setting(
            $key,
            'awpcptitleseparator',
            __( 'Window title separator', 'another-wordpress-classifieds-plugin' ),
            'textfield',
            '-',
            __( 'The character to use to separate ad details used in browser page title. Example: | / -', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'showcityinpagetitle',
            __( 'Show city in window title', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show city in browser page title when viewing individual Ad', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'showstateinpagetitle',
            __( 'Show state in window title', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show state in browser page title when viewing individual Ad', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'showcountryinpagetitle',
            __( 'Show country in window title', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show country in browser page title when viewing individual Ad', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'showcountyvillageinpagetitle',
            __( 'Show county/village/other in window title', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show county/village/other setting in browser page title when viewing individual Ad', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'showcategoryinpagetitle',
            __( 'Show category in title', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show category in browser page title when viewing individual Ad', 'another-wordpress-classifieds-plugin' )
        );
    }

    /**
     * @since 4.0.0
     */
    private function register_url_settings( $settings_manager ) {
        $key = 'listing-url';

        $settings_manager->add_section( 'seo-settings', 'Listing URL', 'listing-url', 50, array( $this, 'render_section_header' ) );

        $show_listings_page = awpcp_get_page_by_ref( 'show-ads-page-name' );

        if ( $show_listings_page ) {
            $show_listings_url  = awpcp_get_page_link( $show_listings_page->ID );
            $show_listings_link = sprintf( '<a href="%s">%s</a>', $show_listings_url, $show_listings_page->post_title );
        } else {
            $show_listings_link = _x( 'Show Ad', 'page name', 'another-wordpress-classifieds-plugin' );
        }

        $description = sprintf(
            // translators: %s is the show listings page link
            __( "Enable this setting to display each listing on its own page, instead of showing the listing's content inside the %s page.", 'another-wordpress-classifieds-plugin' ),
            $show_listings_link
        );

        $settings_manager->add_setting(
            $key,
            'display-listings-as-single-posts',
            __( 'Display listings on their own page', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            $description
        );

        $default_slug = $this->get_default_listings_slug();

        $description = __( "Portion of the URL that appears between the website's domain and the listing's information. Example: <example-slug> in <example-url>.", 'another-wordpress-classifieds-plugin' );
        $description = str_replace( '<example-slug>', '<code>' . $default_slug . '</code>', $description );
        $description = str_replace( '<example-url>', '<code>https://example.com/' . $default_slug . '/id/listing-title/city/state/category/</code>', $description );

        // TODO: Update the slug if the show listing page's uri changes and
        // listings are not being displayed on their own page.
        $settings_manager->add_setting(
            [
                'section'     => $key,
                'id'          => 'listings-slug',
                'name'        => __( 'Listings slug', 'another-wordpress-classifieds-plugin' ),
                'type'        => 'textfield',
                'default'     => $default_slug,
                'description' => $description,
                'behavior'    => [
                    'enabledIf' => 'display-listings-as-single-posts',
                ],
            ]
        );

        $main_page_slug = $this->get_main_page_slug();

        if ( $main_page_slug ) {
            $description = __( "Include the slug of the plugin's main page (<main-page-slug>) in the URL that points to the page of an individual listing.", 'another-wordpress-classifieds-plugin' );
            $description = str_replace( '<main-page-slug>', '<code>' . $main_page_slug . '</code>', $description );
        } else {
            $description = __( "Include the slug of the plugin's main page in the URL that points to the page of an individual listing.", 'another-wordpress-classifieds-plugin' );
        }

        $settings_manager->add_setting(
            [
                'id'          => 'include-main-page-slug-in-listing-url',
                'name'        => __( "Include the slug of the plugin's main page in the listing URL", 'another-wordpress-classifieds-plugin' ),
                'type'        => 'checkbox',
                'default'     => 0,
                'description' => $description,
                'behavior'    => [
                    'enabledIf' => 'display-listings-as-single-posts',
                ],
                'section'     => $key,
            ]
        );

        $settings_manager->add_setting(
            $key,
            'include-title-in-listing-url',
            __( 'Include the title in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Include the title in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'include-category-in-listing-url',
            __( 'Include the name of the category in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'showcategoryinpagetitle' ),
            __( 'Include the name of the category in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'include-country-in-listing-url',
            __( 'Include the name of the country in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'showcountryinpagetitle' ),
            __( 'Include the name of the country in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'include-state-in-listing-url',
            __( 'Include the name of the state in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'showstateinpagetitle' ),
            __( 'Include the name of the state in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'include-city-in-listing-url',
            __( 'Include the name of the city in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'showcityinpagetitle' ),
            __( 'Include the name of the city in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'include-county-in-listing-url',
            __( 'Include the name of the county in the listing URL', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            $this->settings->get_option( 'showcountyvillageinpagetitle' ),
            __( 'Include the name of the county in the URL that points to the page of an individual listing.', 'another-wordpress-classifieds-plugin' )
        );
    }

    private function get_main_page_slug() {
        $main_plugin_page = awpcp_get_page_by_ref( 'main-page-name' );

        if ( ! $main_plugin_page ) {
            return null;
        }

        return get_page_uri( $main_plugin_page );
    }

    private function get_default_listings_slug() {
        return _x( 'classifieds', 'listing post type slug', 'another-wordpress-classifieds-plugin' );
    }

    public function render_section_header() {
        $introduction = _x( 'These settings affect the URL path shown for listings. You can include or remove elements for SEO purposes.', 'listing url settings section', 'another-wordpress-classifieds-plugin' );

        $main_page_slug = $this->get_main_page_slug();
        $default_slug   = $this->get_default_listings_slug();

        echo '<p>' . esc_html( $introduction ) . '<br/><br/>';

        printf(
            /* translators: %s: example URL */
            esc_html_x( 'Example path: %s.', 'listing url settings section', 'another-wordpress-classifieds-plugin' ),
            '<code>/' . esc_html( $main_page_slug . '/' . $default_slug ) . '/id/listing-title/city/state/category</code>'
        );

        echo '</p>';
    }

    /**
     * TODO: Refactor register_settings() to store a list of settings that need to
     *       be monitored.
     *
     *       Alternatively, we could add filters and actions for sections and
     *       invidiual settings.
     */
    public function seo_settings_validated( $options, $group, $subgroup ) {
        update_option( 'awpcp-flush-rewrite-rules', true );
    }
}
