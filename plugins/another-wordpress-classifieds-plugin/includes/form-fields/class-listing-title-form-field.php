<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_listing_title_form_field( $slug ) {
    return new AWPCP_ListingTitleFormField(
        $slug,
        awpcp_listing_renderer(),
        awpcp_payments_api(),
        awpcp_template_renderer()
    );
}

class AWPCP_ListingTitleFormField extends AWPCP_FormField {

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
        return __( 'Listing Title', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    public function extract_value( $data ) {
        if ( ! isset( $data['post_fields']['post_title'] ) ) {
            return null;
        }

        return $data['post_fields']['post_title'];
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
            'required' => true,
            'value' => $value,
            'errors' => $errors,

            'characters_allowed' => $characters_limit['characters_allowed'],
            'characters_allowed_text' => $characters_allowed_text,
            'remaining_characters' => $characters_limit['remaining_characters'],
            'remaining_characters_text' => $remaining_characters_text,

            'label' => $this->get_label(),

            'html' => array(
                'id' => 'ad-title',
                'name' => $this->get_slug(),
            ),
        );

        return $this->template_renderer->render_template( 'frontend/form-fields/listing-title-form-field.tpl.php', $params );
    }

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
            $characters_allowed = $payment_term->get_characters_allowed_in_title();
            $remaining_characters = max( 0, $characters_allowed - $characters_used );
        } else {
            $characters_allowed   = 0;
            $remaining_characters = 0;
        }

        return compact( 'characters_allowed', 'remaining_characters' );
    }
}
