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
class AWPCP_PaymentSettings {

    public $settings;

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
                'id'       => 'payment-settings',
                'name'     => __( 'Payment', 'another-wordpress-classifieds-plugin' ),
                'priority' => 40,
            ]
        );

        $this->register_general_payment_settings( $settings_manager );
        $this->register_paypal_settings( $settings_manager );
        $this->register_2checkout_settings( $settings_manager );
        $this->register_credit_system_settings( $settings_manager );
    }

    /**
     * @since 4.0.0
     */
    private function register_general_payment_settings( $settings_manager ) {
        $settings_manager->add_settings_subgroup( [
            'id'       => 'general-payment-settings',
            'name'     => __( 'General Settings', 'another-wordpress-classifieds-plugin' ),
            'priority' => 10,
            'parent'   => 'payment-settings',
        ] );

        $key = 'general-payment-settings';

        $settings_manager->add_section( 'general-payment-settings', __( 'Payment Settings', 'another-wordpress-classifieds-plugin' ), 'general-payment-settings', 10, array( $settings_manager, 'section' ) );

        $link = sprintf( '<a href="%s">', esc_attr( awpcp_get_admin_fees_url() ) );
        // translators: %s is the fees admin page URL
        $helptext = __( 'When this is turned on, people will use <manage-fees-link>fee plans</a> to pay for your classifieds. Leave it off if you never want to charge for any ads.', 'another-wordpress-classifieds-plugin' );
        $helptext = str_replace( '<manage-fees-link>', $link, $helptext );

        $settings_manager->add_setting( $key, 'freepay', __( 'Charge Listing Fee?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, $helptext );

        $order_options = array(
            1 => __( 'Name', 'another-wordpress-classifieds-plugin' ),
            2 => __( 'Price', 'another-wordpress-classifieds-plugin' ),
            3 => __( 'Images Allowed', 'another-wordpress-classifieds-plugin' ),
            5 => __( 'Duration', 'another-wordpress-classifieds-plugin' ),
        );

        $settings_manager->add_setting(
            $key,
            'fee-order',
            __( 'Sort Fee Plans By', 'another-wordpress-classifieds-plugin' ),
            'radio',
            1,
            __( 'The order used to sort Fees in the payment screens.', 'another-wordpress-classifieds-plugin' ),
            array( 'options' => $order_options )
        );

        $direction_options = array(
            'ASC' => __( 'Ascending', 'another-wordpress-classifieds-plugin' ),
            'DESC' => __( 'Descending', 'another-wordpress-classifieds-plugin' ),
        );

        $settings_manager->add_setting(
            $key,
            'fee-order-direction',
            __( 'Sort Direction of Fee Plans', 'another-wordpress-classifieds-plugin' ),
            'radio',
            'ASC',
            __( 'The direction used to sort Fees in the payment screens.', 'another-wordpress-classifieds-plugin' ),
            array( 'options' => $direction_options )
        );

        $settings_manager->add_setting(
            $key,
            'hide-all-payment-terms-if-no-category-is-selected',
            __( 'Hide all fee plans if no category is selected', 'another-wordpress-classifieds-plugin' ),
            'checkbox',
            false,
            ''
        );

        $settings_manager->add_setting( $key, 'pay-before-place-ad', _x( 'Pay before entering Ad details', 'settings', 'another-wordpress-classifieds-plugin' ), 'checkbox', 1, _x( 'Check to ask for payment before entering Ad details. Uncheck if you want users to pay for Ads at the end of the process, after images have been uploaded.', 'settings', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'paylivetestmode', __( 'Put payment gateways in test mode?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Leave this OFF to accept real payments, turn it on to perform payment tests.', 'another-wordpress-classifieds-plugin' ) );
        $settings_manager->add_setting( $key, 'force-secure-urls', __( 'Force secure URLs on payment pages', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'If checked all classifieds pages that involve payments will be accessed through a secure (HTTPS) URL. Do not enable this feature if your server does not support HTTPS.', 'another-wordpress-classifieds-plugin' ) );
    }

    public function validate_group_settings( $options ) {
        if ( isset( $options[ 'force-secure-urls' ] ) && $options[ 'force-secure-urls' ] ) {
            if ( ! $this->is_https_available() ) {
                $message = __( "Force Secure URLs was not enabled because your website couldn't be accessed using a secure connection.", 'another-wordpress-classifieds-plugin' );
                awpcp_flash_error( $message );

                $options['force-secure-urls'] = 0;
            }
        }

        return $options;
    }

    /**
     * @since 4.0.0
     */
    private function is_https_available() {
        $url      = set_url_scheme( awpcp_get_page_url( 'place-ad-page-name' ), 'https' );
        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );

        return ! is_wp_error( $response );
    }

    private function register_paypal_settings( $settings_manager ) {
        $group = 'paypal-settings';
        $key   = 'paypal';

        $settings_manager->add_settings_subgroup( [
            'id'       => 'paypal-settings',
            'name'     => __( 'PayPal', 'another-wordpress-classifieds-plugin' ),
            'priority' => 30,
            'parent'   => 'payment-settings',
        ] );

        $settings_manager->add_section( $group, __( 'PayPal Settings', 'another-wordpress-classifieds-plugin' ), 'paypal', 20, array( $settings_manager, 'section' ) );

        $settings_manager->add_setting($key, 'activatepaypal', __( 'Activate PayPal?', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Activate PayPal?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( [
            'id'          => 'paypalemail',
            'name'        => __( 'PayPal receiver email', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => '',
            'description' => __( 'Email address for PayPal payments (if running in pay mode and if PayPal is activated).', 'another-wordpress-classifieds-plugin' ),
            'behavior'   => [
                'enabledIf' => 'activatepaypal',
            ],
            'validation'  => [
                'required' => [
                    'depends' => 'activatepaypal',
                ],
                'email' => [
                    'message' => __( 'Please enter a valid email address.', 'another-wordpress-classifieds-plugin' ),
                ],
            ],
            'section'     => $key,
        ] );

        $settings_manager->add_setting( [
            'section'     => $key,
            'id'          => 'paypal_merchant_id',
            'name'        => __( 'PayPal Merchant ID', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => '',
            'description' => sprintf(
                // translators: %s is the PayPal settings URL
                esc_html__( 'Merchant ID associated with the PayPal account that will receive the payments. Go to %s to obtain your Merchant ID.', 'another-wordpress-classifieds-plugin' ),
                '<a href="https://www.paypal.com/myaccount/settings/" target="_blank">https://www.paypal.com/myaccount/settings/</a>'
            ),
            'behavior'   => [
                'enabledIf' => 'activatepaypal',
            ],
        ] );

        $supported_currencies = awpcp_paypal_supported_currencies();

        $message = __( 'The PayPal Currency Code must be one of <currency-codes>.', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<currency-codes>', implode( ', ', $supported_currencies ), $message );

        $settings_manager->add_setting( [
            'id'          => 'paypalcurrencycode',
            'name'        => __( 'PayPal currency code', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => 'USD',
            'description' => __( 'The currency in which you would like to receive your PayPal payments', 'another-wordpress-classifieds-plugin' ),
            'validation'  => [
                'required' => [
                    'depends' => 'activatepaypal',
                ],
                'oneof' => [
                    'param'   => $supported_currencies,
                    'message' => $message,
                ],
            ],
            'behavior'   => [
                'enabledIf' => 'activatepaypal',
            ],
            'section'     => $key,
        ] );
    }

    private function register_2checkout_settings( $settings_manager ) {
        $group = '2checkout-settings';
        $key = '2checkout';

        $settings_manager->add_settings_subgroup( [
            'id'       => '2checkout-settings',
            'name'     => __( '2Checkout', 'another-wordpress-classifieds-plugin' ),
            'priority' => 30,
            'parent'   => 'payment-settings',
        ] );

        $settings_manager->add_section($group, __( '2Checkout Settings', 'another-wordpress-classifieds-plugin'), '2checkout', 30, array($settings_manager, 'section'));

        $settings_manager->add_setting( $key, 'activate2checkout', __( 'Activate 2Checkout', 'another-wordpress-classifieds-plugin' ), 'checkbox', 0, __( 'Activate 2Checkout?', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting( [
            'id'          =>'2checkout',
            'name'        => __( '2Checkout account', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => '',
            'description' => __( 'Account for 2Checkout payments.', 'another-wordpress-classifieds-plugin' ),
            'validation'  => [
                'required' => [
                    'depends' => 'activate2checkout',
                ],
            ],
            'behavior'   => [
                'enabledIf' => 'activate2checkout',
            ],
            'section'     => $key,
        ] );

        $settings_manager->add_setting( [
            'section'     => $key,
            'id'          => '2checkoutcurrencycode',
            'name'        => __( '2Checkout Currency Code', 'another-wordpress-classifieds-plugin' ),
            'type'        => 'textfield',
            'default'     => 'USD',
            'validation'  => [
                'required' => [
                    'depends' => 'activate2checkout',
                ],
            ],
            'behavior'   => [
                'enabledIf' => 'activate2checkout',
            ],
            'description' => __( 'The currency in which you would like to receive your 2Checkout payments', 'another-wordpress-classifieds-plugin' ),
        ] );
    }

    /**
     * @since 4.0.0
     */
    private function register_credit_system_settings( $settings_manager ) {
        $key = 'credit-system-settings';

        $settings_manager->add_settings_subgroup( [
            'id' => 'credit-system-settings',
            'name' => __( 'Credit System', 'another-wordpress-classifieds-plugin' ),
            'priority' => 20,
            'parent' => 'payment-settings',
        ] );

        $settings_manager->add_section( 'credit-system-settings' , __( 'Credit System', 'another-wordpress-classifieds-plugin' ), $key, 5, array( $settings_manager, 'section' ) );

        $options = array(
            AWPCP_Payment_Transaction::PAYMENT_TYPE_MONEY => __( 'Currency', 'another-wordpress-classifieds-plugin' ),
            AWPCP_Payment_Transaction::PAYMENT_TYPE_CREDITS => __( 'Credits', 'another-wordpress-classifieds-plugin' ),
            'both' => __( 'Currency & Credits', 'another-wordpress-classifieds-plugin' ),
        );

        $settings_manager->add_setting( $key, 'enable-credit-system', __( 'Enable Credit System', 'another-wordpress-classifieds-plugin'), 'checkbox', 0, __( 'The Credit System allows users to purchase credit that can later be used to pay for placing Ads.', 'another-wordpress-classifieds-plugin' ) );

        $settings_manager->add_setting(
            $key,
            'accepted-payment-type',
            __( 'Accepted payment type', 'another-wordpress-classifieds-plugin' ),
            'radio',
            'both',
            __( 'Select the type of payment that can be used to purchase Ads.', 'another-wordpress-classifieds-plugin' ),
            array( 'options' => $options )
        );
    }

    public function validate_credit_system_settings( $options ) {
        $only_credits_accepted         = $options['accepted-payment-type'] == AWPCP_Payment_Transaction::PAYMENT_TYPE_CREDITS;
        $credit_system_will_be_enabled = $options['enable-credit-system'];

        if ( $only_credits_accepted && ! $credit_system_will_be_enabled ) {
            $options['accepted-payment-type'] = 'both';

            $message = __( 'You cannot configure Credits as the only accepted payment type unless you enable the Credit System as well. The setting was set to accept both Currency and Credits.', 'another-wordpress-classifieds-plugin' );
            awpcp_flash_error( $message );
        }

        return $options;
    }

    /**
     * Payment Settings checks.
     *
     * XXX: Referenced in FAQ: https://awpcp.com/forum/faq/why-doesnt-my-currency-code-change-when-i-set-it/
     */
    public function validate_payment_settings( $options ) {
        $setting = 'paypalcurrencycode';

        if ( isset( $options[ $setting ] ) && ! awpcp_paypal_supports_currency( $options[ $setting ] ) ) {
            $currency_codes = awpcp_paypal_supported_currencies();
            $message = __( 'There is a problem with the PayPal Currency Code you have entered. It does not match any of the codes in our list of curencies supported by PayPal.', 'another-wordpress-classifieds-plugin' );
            $message.= '<br/><br/><strong>' . __( 'The available currency codes are', 'another-wordpress-classifieds-plugin' ) . '</strong>:<br/>';
            $message.= join(' | ', $currency_codes);
            awpcp_flash($message);

            $options[$setting] = 'USD';
        }

        $setting = 'enable-credit-system';
        if (isset($options[$setting]) && $options[$setting] == 1 && !get_awpcp_option('requireuserregistration')) {
            awpcp_flash(__( 'Require Registration setting was enabled automatically because you activated the Credit System.', 'another-wordpress-classifieds-plugin'));
            $options['requireuserregistration'] = 1;
        }

        if (isset($options[$setting]) && $options[$setting] == 1 && !get_awpcp_option('freepay')) {
            awpcp_flash(__( 'Charge Listing Fee setting was enabled automatically because you activated the Credit System.', 'another-wordpress-classifieds-plugin'));
            $options['freepay'] = 1;
        }

        $setting = 'freepay';
        if (isset($options[$setting]) && $options[$setting] == 0 && get_awpcp_option('enable-credit-system')) {
            awpcp_flash(__( 'Credit System was disabled automatically because you disabled Charge Listing Fee.', 'another-wordpress-classifieds-plugin'));
            $options['enable-credit-system'] = 0;
        }

        return $options;
    }
}
