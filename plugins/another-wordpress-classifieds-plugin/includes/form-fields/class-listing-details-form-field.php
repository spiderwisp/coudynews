<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_listing_details_form_field( $slug ) {
    return new AWPCP_ListingDetailsFormField(
        $slug,
        awpcp_listing_renderer(),
        awpcp_payments_api(),
        awpcp_template_renderer()
    );
}

class AWPCP_ListingDetailsFormField extends AWPCP_FormField {

    private $listing_renderer;
    private $payments;
    private $template_renderer;

    public function __construct( $slug, $listing_renderer, $payments, $template_renderer ) {
        parent::__construct( $slug );

        $this->listing_renderer = $listing_renderer;
        $this->payments = $payments;
        $this->template_renderer = $template_renderer;
    }

    public function get_name() {
        return _x( 'Ad Details', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    protected function is_required() {
        return true;
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data['post_fields']['post_content'] ) ) {
            return null;
        }

        return $data['post_fields']['post_content'];
    }

    public function render( $value, $errors, $listing, $context ) {
        $characters_limit = $this->get_characters_limit_for_listing( $listing );

        if ( $characters_limit['characters_allowed'] == 0 ) {
            $characters_allowed_text = _x( 'Unlimited characters', 'ad details form', 'another-wordpress-classifieds-plugin' );
            $remaining_characters_text = '';
        } else {
            $characters_allowed_text = _x( 'characters left', 'ad details form', 'another-wordpress-classifieds-plugin' );
            $remaining_characters_text = $characters_limit['remaining_characters'];
        }

        $params = array(
            'required' => $this->is_required(),
            'value' => $this->format_value( $value ),
            'errors' => $errors,

            'label' => $this->get_label(),
            'help_text' => nl2br( get_awpcp_option( 'htmlstatustext' ) ),

            'characters_allowed' => $characters_limit['characters_allowed'],
            'characters_allowed_text' => $characters_allowed_text,
            'remaining_characters' => $characters_limit['remaining_characters'],
            'remaining_characters_text' => $remaining_characters_text,

            'html' => array(
                'id' => str_replace( '_', '-', $this->get_slug() ),
                'name' => $this->get_slug(),
                'readonly' => false,
            ),
        );

        return $this->template_renderer->render_template( 'frontend/form-fields/listing-details-form-field.tpl.php', $params );
    }

    /**
     * TODO: Move to Listing Logic or Listing Properties.
     */
    private function get_characters_limit_for_listing( $listing ) {
        $transaction = $this->payments ? $this->payments->get_transaction() : false;
        if ( is_object( $listing ) ) {
            $payment_term = $this->listing_renderer->get_payment_term( $listing );
            $characters_used = strlen( $this->listing_renderer->get_listing_title( $listing ) );
        } elseif ( $transaction ) {
            $payment_term = $this->payments->get_transaction_payment_term( $transaction );
            $characters_used = 0;
        } else {
            $payment_term = null;
        }

        if ( ! is_null( $payment_term ) ) {
            $characters_allowed = $payment_term->get_characters_allowed();
            $remaining_characters = max( 0, $characters_allowed - $characters_used );
        } else {
            $characters_allowed   = 0;
            $remaining_characters = 0;
        }

        return compact( 'characters_allowed', 'remaining_characters' );
    }
}
