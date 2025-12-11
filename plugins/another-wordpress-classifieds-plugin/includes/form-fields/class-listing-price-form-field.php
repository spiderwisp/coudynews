<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Listing Price Form Field.
 */
function awpcp_listing_price_form_field( $slug ) {
    return new AWPCP_ListingPriceFormField( $slug, awpcp()->settings );
}

class AWPCP_ListingPriceFormField extends AWPCP_FormField {

    protected $settings;

    public function __construct( $slug, $settings ) {
        parent::__construct( $slug );
        $this->settings = $settings;
    }

    public function get_name() {
        return _x( 'Item Price', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    protected function is_required() {
        return $this->settings->get_option( 'displaypricefieldreqop' );
    }

    public function is_allowed_in_context( $context ) {
        if ( ! $this->settings->get_option( 'displaypricefield' ) ) {
            return false;
        }

        return parent::is_allowed_in_context( $context );
    }

    protected function format_value( $value ) {
        return is_numeric( $value ) ? awpcp_format_money_without_currency_symbol( $value ) : '';
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data['metadata']['_awpcp_price'] ) ) {
            return null;
        }

        // Listing prices have been historically stored in cents, so we have to
        // devide them by 100.
        return $data['metadata']['_awpcp_price'] / 100;
    }

    public function render( $value, $errors, $listing, $context ) {
        $params = array(
            'required'                      => $this->is_required(),
            'value'                         => $this->format_value( $value ),
            'errors'                        => $errors,

            'label'                         => $this->get_label(),
            'help_text'                     => '',
            'validators'                    => $this->is_required() ? 'required money' : 'money',

            'html'                          => array(
                'id'       => str_replace( '_', '-', $this->get_slug() ),
                'name'     => $this->get_slug(),
                'readonly' => false,
            ),

            'currency_symbol'               => awpcp_get_currency_symbol(),
            'show_currency_symbol_on_right' => $this->should_show_currency_symbol_on_right(),
        );

        return awpcp_render_template( 'frontend/form-fields/listing-price-form-field.tpl.php', $params );
    }

    private function should_show_currency_symbol_on_right() {
        return $this->settings->get_option( 'show-currency-symbol' ) === 'show-currency-symbol-on-right';
    }
}
