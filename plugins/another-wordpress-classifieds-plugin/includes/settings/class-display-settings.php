<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register settings for the Appearance group.
 */
class AWPCP_DisplaySettings {
    private $settings_manager;

    public function __construct( $settings_manager ) {
        $this->settings_manager = $settings_manager;
    }

    /**
     * @since 4.0.0
     */
    public function register_settings( $settings_manager ) {
        $settings_manager->add_settings_group(
            [
                'id'       => 'display-settings',
                'name'     => __( 'Display', 'another-wordpress-classifieds-plugin' ),
                'priority' => 50,
            ]
        );

        $this->register_layout_and_presentation_settings( $settings_manager );
        $this->register_classifieds_bar_settings( $settings_manager );
        $this->register_form_settings( $settings_manager );
        add_filter( 'awpcp_validate_settings', array( $this, 'validate_settings' ), 10 );
    }

    /**
     * @since 4.1.0
     *
     * Validate settings
     */
    public function validate_settings( $new_options ) {

        $displayadlyoutcode   = $this->settings_manager->get_setting( 'displayadlayoutcode' );
        $awpcpshowtheadlayout = $this->settings_manager->get_setting( 'awpcpshowtheadlayout' );
        if ( isset( $new_options['displayadlayoutcode-default'] ) && $new_options['displayadlayoutcode-default'] === '1' ) {
            $new_options['displayadlayoutcode-default'] = 0;
            $new_options['displayadlayoutcode']         = $displayadlyoutcode['default'];
        }
        if ( isset( $new_options['awpcpshowtheadlayout-default'] ) && $new_options['awpcpshowtheadlayout-default'] === '1' ) {
            $new_options['awpcpshowtheadlayout-default'] = 0;
            $new_options['awpcpshowtheadlayout']         = $awpcpshowtheadlayout['default'];
        }

        return $new_options;
    }

    /**
     * @since 4.0.0
     */
    private function register_layout_and_presentation_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'layout-and-presentation-settings',
            'name'     => __( 'Layout and Presentation', 'another-wordpress-classifieds-plugin' ),
            'priority' => 10,
            'parent'   => 'display-settings',
        ] );

        $group = 'layout-and-presentation-settings';
        $key   = 'layout-and-presentation-settings';

        $settings_manager->add_settings_section( [
            'subgroup' => $group,
            'name'     => __( 'Layout and Presentation', 'another-wordpress-classifieds-plugin' ),
            'id'       => 'layout-and-presentation-settings',
            'priority' => 30,
        ] );

        $settings_manager->add_setting( $key, 'show-ad-preview-before-payment', __( 'Show Ad preview before payment.', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'If enabled, a preview of the Ad being posted will be shown after the images have been uploaded and before the user is asked to pay. The user is allowed to go back and edit the Ad details and uploaded images or proceed with the posting process.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'allowhtmlinadtext', __( 'Allow HTML in Ad text', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Allow HTML in ad text (Not recommended).', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'htmlstatustext', __( 'Display this text above ad detail text input box on ad post page', 'another-wordpress-classifieds-plugin' ), 'textarea', __( 'No HTML Allowed', 'another-wordpress-classifieds-plugin' ), '' );
        $settings_manager->add_setting( $key, 'characters-allowed-in-title', __( 'Maximum Ad title length', 'another-wordpress-classifieds-plugin' ), 'textfield', 100, __( 'Number of characters allowed in Ad title. Please note this is the default value and can be overwritten in Fees and Subscription Plans.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'maxcharactersallowed', __( 'Maximum Ad details length', 'another-wordpress-classifieds-plugin' ), 'textfield', 750, __( 'Number of characters allowed in Ad details. Please note this is the default value and can be overwritten in Fees and Subscription Plans.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'words-in-listing-excerpt', __( 'Number of words in Ad excerpt', 'another-wordpress-classifieds-plugin' ), 'textfield', 20, __( 'Number of words shown by the Ad excerpt placeholder.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'hidelistingcontactname', __( 'Hide contact name to anonymous users?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Hide listing contact name to anonymous (non logged in) users.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'displayadlayoutcode',
            __( 'Ad Listings page layout', 'another-wordpress-classifieds-plugin' ),
            'textarea', '
<div class="awpcp-listing-excerpt $awpcpdisplayaditems $isfeaturedclass" data-breakpoints-class-prefix="awpcp-listing-excerpt" data-breakpoints=\'{"tiny": [0,328], "small": [328,600], "medium": [600,999999]}\'>
    <div class="awpcp-listing-excerpt-thumbnail">
        $awpcp_image_name_srccode
    </div>
    <div class="awpcp-listing-excerpt-inner" style="w">
        <h4 class="awpcp-listing-title">$title_link</h4>
        <div class="awpcp-listing-excerpt-content">$excerpt</div>
    </div>
    <div class="awpcp-listing-excerpt-extra">
        $awpcpadpostdate
        $awpcp_city_display
        $awpcp_state_display
        $awpcp_display_adviews
        $awpcp_display_price
        $awpcpextrafields
    </div>
    <span class="fixfloat"></span>
</div>
<div class="fixfloat"></div>',
            __( 'Modify as needed to control layout of ad listings page. Maintain code formatted as \$somecodetitle. Changing the code keys will prevent the elements they represent from displaying.', 'another-wordpress-classifieds-plugin' )
        );
        $settings_manager->add_setting( $key, 'displayadlayoutcode-default', '', 'button', __( 'Reset Default', 'another-wordpress-classifieds-plugin' ), '' );
        $settings_manager->add_setting(
            $key,
            'awpcpshowtheadlayout',
            __( 'Single Ad page layout', 'another-wordpress-classifieds-plugin' ),
            'textarea', '
                            <div id="showawpcpadpage">
                                $ad_actions
                                <div class="awpcp-title">$ad_title</div><br/>
                                <div class="showawpcpadpage">
                                    $featureimg
                                    <div class="awpcp-subtitle">' . __( 'Contact Information', 'another-wordpress-classifieds-plugin' ) . '</div>
                                    <a href="$codecontact">' . __( 'Contact', 'another-wordpress-classifieds-plugin' ) . ' $adcontact_name</a>
                                    $adcontactphone
                                    $location
                                    $awpcpvisitwebsite
                                </div>
                                $aditemprice
                                $awpcpextrafields
                                <div class="fixfloat"></div>
                                $showadsense1
                                <div class="showawpcpadpage">
                                    <div class="awpcp-subtitle">' . __( 'More Information', 'another-wordpress-classifieds-plugin' ) . '</div>
                                    $addetails
                                </div>
                                $showadsense2
                                <div class="fixfloat"></div>
                                <div id="displayimagethumbswrapper">
                                    <div id="displayimagethumbs">
                                        <ul>
                                            $awpcpshowadotherimages
                                        </ul>
                                    </div>
                                </div>
                                <span class="fixfloat">$tweetbtn $sharebtn $flagad</span>
                                $awpcpadviews
                                $showadsense3
                                $edit_listing_link
                            </div>',
            __( 'Modify as needed to control layout of single ad view page. Maintain code formatted as \$somecodetitle. Changing the code keys will prevent the elements they represent from displaying.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'awpcpshowtheadlayout-default', '', 'button', __( 'Reset Default', 'another-wordpress-classifieds-plugin' ), '' );
        $settings_manager->add_setting(
            $key,
            'allow-wordpress-shortcodes-in-single-template',
            __( 'Allow WordPress Shortcodes in Single Ad page layout', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            __( 'Shortcodes executed this way will be executed as if they were entered in the content of the WordPress page showing the listing (normally the Show Ad page, but in general any page that has the AWPCPSHOWAD shortcode).', 'another-wordpress-classifieds-plugin' )
        );

        $radio_options = array(
            1  => __( 'Date (newest first)', 'another-wordpress-classifieds-plugin' ),
            9  => __( 'Date (oldest first)', 'another-wordpress-classifieds-plugin' ),
            2  => __( 'Title (ascending)', 'another-wordpress-classifieds-plugin' ),
            10 => __( 'Title (descending)', 'another-wordpress-classifieds-plugin' ),
            3  => __( 'Paid status and date (paid first, then most recent)', 'another-wordpress-classifieds-plugin' ),
            4  => __( 'Paid status and title (paid first, then by title)', 'another-wordpress-classifieds-plugin' ),
            5  => __( 'Views (most viewed first, then by title)', 'another-wordpress-classifieds-plugin' ),
            6  => __( 'Views (most viewed first, then by date)', 'another-wordpress-classifieds-plugin' ),
            11 => __( 'Views (least viewed first, then by title)', 'another-wordpress-classifieds-plugin' ),
            12 => __( 'Views (least viewed first, then by date)', 'another-wordpress-classifieds-plugin' ),
            7  => __( 'Price (high to low, then by date)', 'another-wordpress-classifieds-plugin' ),
            8  => __( 'Price (low to high, then by date)', 'another-wordpress-classifieds-plugin' ),
        );

        $settings_manager->add_setting( $key, 'groupbrowseadsby', __( 'Order Ad Listings by', 'another-wordpress-classifieds-plugin' ), 'select', 1, '', array( 'options' => $radio_options ) );
        $settings_manager->add_setting( $key, 'search-results-order', __( 'Order Ad Listings in Search results by', 'another-wordpress-classifieds-plugin' ), 'select', 1, '', array( 'options' => $radio_options ) );
        // $settings_manager->add_setting($key, 'groupsearchresultsby', 'Group Ad Listings search results by', 'radio', 1, '', array('options' => $radio_options));

        $settings_manager->add_setting( [
            'id'      => 'search-form-in-results',
            'name'    => __( 'Search form display', 'another-wordpress-classifieds-plugin' ),
            'type'    => 'radio',
            'default' => 'none',
            'options' => [
                'above' => __( 'Above results', 'another-wordpress-classifieds-plugin' ),
                'below' => __( 'Below results', 'another-wordpress-classifieds-plugin' ),
                'none'  => __( "Don't show with results", 'another-wordpress-classifieds-plugin' ),
            ],
            'section' => $key,
        ] );

        $settings_manager->add_setting( $key, 'adresultsperpage', __( 'Default number of Ads per page', 'another-wordpress-classifieds-plugin' ), 'textfield', 10, '' );

        $pagination_options = array( 5, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 500 );
        $settings_manager->add_setting( $key, 'pagination-options', __( 'Pagination Options', 'another-wordpress-classifieds-plugin' ), 'choice', $pagination_options, '', array( 'choices' => array_combine( $pagination_options, $pagination_options ) ) );

        $settings_manager->add_setting( $key, 'showadcount', __( 'Show Ad count in categories', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Show how many ads a category contains.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'hide-empty-categories', __( 'Hide empty categories?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( "If checked, categories with 0 listings in it won't be shown.", 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'displayadviews',
            __( 'Show Ad views', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'Show the number of times the ad has been viewed (simple count made by AWPCP &endash; warning, may not be accurate!)', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( $key, 'hyperlinkurlsinadtext', __( 'Make URLs in ad text clickable', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, '' );
        $settings_manager->add_setting( $key, 'visitwebsitelinknofollow', __( 'Add no follow to links in Ads', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, '' );
    }

    /**
     * @since 4.0.0
     */
    private function register_classifieds_bar_settings( $settings_manager ) {
        $group = 'classifieds-bar-settings';
        $key   = 'classifieds-bar-settings';

        $settings_manager->add_settings_subgroup( [
            'id'       => 'classifieds-bar-settings',
            'name'     => __( 'Classifieds Bar', 'another-wordpress-classifieds-plugin' ),
            'priority' => 20,
            'parent'   => 'display-settings',
        ] );

        $settings_manager->add_section( $group, __( 'Classifieds Bar', 'another-wordpress-classifieds-plugin' ), 'classifieds-bar-settings', 60, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting(
            $key,
            'show-classifieds-bar',
            __( 'Show Classifieds Bar', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'The Classifieds Bar is a section shown at the top of the plugin pages, displaying a Search Bar and multiple menu items. Each element of the bar can be enabled or disabled using the settings below.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( [
            'id'       => 'show-classifieds-search-bar',
            'name'     => __( 'Show Search Bar', 'another-wordpress-classifieds-plugin' ),
            'type'     => 'checkbox',
            'default'  => 1,
            'behavior' => [
                'enabledIf' => 'show-classifieds-bar',
            ],
            'section'  => $key,
        ] );

        $settings_manager->add_setting( [
            'id'       => 'show-menu-item-place-ad',
            'name'     => __( 'Show Place Ad menu item', 'another-wordpress-classifieds-plugin' ),
            'type'     => 'checkbox',
            'default'  => 1,
            'behavior' => [
                'enabledIf' => 'show-classifieds-bar',
            ],
            'section'  => $key,
        ] );

        $settings_manager->add_setting( [
            'id'       => 'show-menu-item-edit-ad',
            'name'     => __( 'Show Edit Ad menu item', 'another-wordpress-classifieds-plugin' ),
            'type'     => 'checkbox',
            'default'  => 1,
            'behavior' => [
                'enabledIf' => 'show-classifieds-bar',
            ],
            'section'  => $key,
        ] );

        $settings_manager->add_setting( [
            'id'       => 'show-menu-item-browse-ads',
            'name'     => __( 'Show Browse Ads menu item', 'another-wordpress-classifieds-plugin' ),
            'type'     => 'checkbox',
            'default'  => 1,
            'behavior' => [
                'enabledIf' => 'show-classifieds-bar',
            ],
            'section'  => $key,
        ] );

        $settings_manager->add_setting( [
            'id'       => 'show-menu-item-search-ads',
            'name'     => __( 'Show Search Ads menu item', 'another-wordpress-classifieds-plugin' ),
            'type'     => 'checkbox',
            'default'  => 1,
            'behavior' => [
                'enabledIf' => 'show-classifieds-bar',
            ],
            'section'  => $key,
        ] );
    }

    /**
     * @since 4.0.0
     */
    private function register_form_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'form-fields-settings',
            'name'     => __( 'Form Fields', 'another-wordpress-classifieds-plugin' ),
            'priority' => 30,
            'parent'   => 'display-settings',
        ] );

        $group = 'form-fields-settings';
        $key   = 'form-steps';

        $settings_manager->add_settings_section( [
            'subgroup'    => $group,
            'name'        => __( 'Form Steps', 'another-wordpress-classifieds-plugin' ),
            'id'          => 'form-steps',
            'priority'    => 3,
            'description' => $this->get_form_fields_settings_description(),
        ] );

        $settings_manager->add_setting(
            $key,
            'show-create-listing-form-steps',
            __( 'Show Form Steps', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'If checked, when a user is creating a new listing, a list of steps will be shown at the top of the forms.', 'another-wordpress-classifieds-plugin' )
        );

        // Section: User Field

        // TODO: Is this the right place to put this setting?
        $key = 'user';

        $settings_manager->add_section( $group, __( 'User Field', 'another-wordpress-classifieds-plugin' ), 'user', 5, array( $settings_manager, 'section' ) );

        $options = array( 'dropdown' => __( 'Dropdown', 'another-wordpress-classifieds-plugin' ), 'autocomplete' => __( 'Autocomplete', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( $key, 'user-field-widget', __( 'HTML Widget for User field', 'another-wordpress-classifieds-plugin' ), 'radio', 'dropdown', __( 'The user field can be represented with an HTML dropdown or a text field with autocomplete capabilities. Using the dropdown is faster if you have a small number of users. If your website has a lot of registered users, however, the dropdown may take too long to render and using the autocomplete version may be a better idea.', 'another-wordpress-classifieds-plugin' ), array( 'options' => $options ) );
        $settings_manager->add_setting( $key, 'displaypostedbyfield', __( 'Show User Field on Search', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Show as "Posted By" in search form?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'overwrite-contact-information-on-user-change',
            __( 'Overwrite information in contact fields when a different listing owner is selected', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            1,
            __( 'If this setting is enabled, when an administrator is editing a listing and he changes the selected value in the User/Owner field, the information in the contact fields (Contact Name, Contact Email and Contact Phone Number) will be updated (overwriting the information already entered in those fields) using the information of the user just selected. The modifications will not be persisted until you click the Continue button.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting(
            $key,
            'user-name-format',
            __( "User's name format", 'another-wordpress-classifieds-plugin' ),
            'select',
            'display_name',
            __( "The selected format will be used to show a user's name in dropdown fields, text fields and templates.", 'another-wordpress-classifieds-plugin' ),
            array(
                'options' => array(
                    'user_login'      => esc_html( '<Username>' ),
                    'firstname_first' => esc_html( '<First Name> <Last Name>' ),
                    'lastname_first'  => esc_html( '<Last Name> <First Name>' ),
                    'firstname'       => esc_html( '<First Name>' ),
                    'lastname'        => esc_html( '<Last Name>' ),
                    'display_name'    => esc_html( '<Display Name>' ),
                ),
            )
        );

        $key = 'contact';

        $settings_manager->add_section( $group, __( 'Contact Fields', 'another-wordpress-classifieds-plugin' ), 'contact', 10, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting(
            $key,
            'make-contact-fields-writable-for-logged-in-users',
            __( 'Allow logged in users to overwrite Contact Name and Contact Email', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            false,
            __( "Normally registered users who are not administrators are not allowed to change the email address or contact name. The fields are rendered as read-only and pre-filled with the information from each user's profile. If this setting is enabled, logged in users will be allowed to overwrite those fields.", 'another-wordpress-classifieds-plugin' )
        );

        $this->register_form_field_settings_grid( $settings_manager );

        // Section: Phone Field

        $settings_manager->add_setting( 'private-settings', 'displayphonefield', __( 'Show Phone field', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Show phone field?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( 'private-settings', 'displayphonefieldreqop', __( 'Require Phone', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Require phone on Place Ad and Edit Ad forms?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            'private-settings',
            'displayphonefieldpriv',
            __( 'Show Phone Field only to registered users', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            __( 'This setting restricts viewing of this field so that only registered users that are logged in can see it.', 'another-wordpress-classifieds-plugin' )
        );

        // Section: Website Field

        $settings_manager->add_setting( 'private-settings', 'displaywebsitefield', __( 'Show Website field', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Show website field?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( 'private-settings', 'displaywebsitefieldreqop', __( 'Require Website', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Require website on Place Ad and Edit Ad forms?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            'private-settings',
            'displaywebsitefieldreqpriv',
            __( 'Show Website Field only to registered users', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            __( 'This setting restricts viewing of this field so that only registered users that are logged in can see it.', 'another-wordpress-classifieds-plugin' )
        );

        // Section: Price Field

        $settings_manager->add_setting( 'private-settings', 'displaypricefield', __( 'Show Price field', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, __( 'Show price field?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            [
                'id'      => 'display_price_field_on_search_form',
                'name'    => __( 'Show price field on search form', 'another-wordpress-classifieds-plugin' ),
                'type'    => 'checkbox',
                'default' => true,
                'section' => 'private-settings',
            ]
        );

        $settings_manager->add_setting( 'private-settings', 'displaypricefieldreqop', __( 'Require Price', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Require price on Place Ad and Edit Ad forms?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            'private-settings',
            'price-field-is-restricted',
            __( 'Show Price Field only to registered users', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            0,
            __( 'This setting restricts viewing of this field so that only registered users that are logged in can see it.', 'another-wordpress-classifieds-plugin' )
        );

        $settings_manager->add_setting( 'form-fields-behavior', 'hide-price-field-if-empty', __( 'Hide Price field if empty or zero', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'If checked all price placeholders will be replaced with an empty string when the price of the Ad is zero or was not set.', 'another-wordpress-classifieds-plugin' ) );
    }

    private function get_form_fields_settings_description() {
        $section_url  = awpcp_get_admin_form_fields_url();
        $section_link = sprintf( '<a href="%s">%s</a>', $section_url, __( 'Form Fields', 'another-wordpress-classifieds-plugin' ) );

        $message = __( 'Go to the <form-fields-section> admin section to change the order in which the fields mentioned below are shown to users in the Ad Details form.', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<form-fields-section>', $section_link, $message );

        return awpcp_print_message( $message );
    }

    /**
     * @since 4.0.0
     */
    private function register_form_field_settings_grid( $settings_manager ) {
        $settings_manager->add_settings_section( [
            'id'       => 'form-fields-behavior',
            'name'     => __( 'Form Fields Behavior', 'another-wordpress-classifieds-plugin' ),
            'subgroup' => 'form-fields-settings',
        ] );

        $settings_manager->add_setting( [
            'id'      => 'form-fields-behavior',
            'name'    => null,
            'type'    => 'settings-grid',
            'columns' => [
                __( 'Field', 'another-wordpress-classifieds-plugin' ),
                __( 'Show field on form', 'another-wordpress-classifieds-plugin' ),
                __( 'Show field on search form', 'another-wordpress-classifieds-plugin' ),
                __( 'Required', 'another-wordpress-classifieds-plugin' ),
                __( 'Show value to registered users only', 'another-wordpress-classifieds-plugin' ),
            ],
            'rows'    => [
                'website'       => [
                    __( 'Website', 'another-wordpress-classifieds-plugin' ),
                    'displaywebsitefield',
                    '',
                    'displaywebsitefieldreqop',
                    'displaywebsitefieldreqpriv',
                ],
                'contact-phone' => [
                    __( 'Contact Phone', 'another-wordpress-classifieds-plugin' ),
                    'displayphonefield',
                    '',
                    'displayphonefieldreqop',
                    'displayphonefieldpriv',
                ],
                'price'         => [
                    __( 'Price', 'another-wordpress-classifieds-plugin' ),
                    'displaypricefield',
                    'display_price_field_on_search_form',
                    'displaypricefieldreqop',
                    'price-field-is-restricted',
                ],
            ],
            'section' => 'form-fields-behavior',
        ] );
    }
}
