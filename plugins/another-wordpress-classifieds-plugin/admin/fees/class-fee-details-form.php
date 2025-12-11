<?php
/**
 * @package AWPCP\Admin\Fess
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return AWPCP_Fee_Details_Form
 */
function awpcp_fee_details_form() {
    return new AWPCP_Fee_Details_Form( awpcp_payments_api() );
}

class AWPCP_Fee_Details_Form implements AWPCP_HTML_Element {

    private $payments_api;

    public function __construct( $payments_api ) {
        $this->payments_api = $payments_api;
    }

    public function build( $params = array() ) {
        $form_definition = array(
            '#type'       => 'form',
            '#attributes' => array(
                'class'  => array( 'awpcp-fee-details-form', 'awpcp-admin-form' ),
                'method' => 'post',
            ),
            '#content'    => array(
                'header'               => array(
                    '#type'    => 'first-level-admin-heading',
                    '#content' => $params['form_title'],
                ),
                'name-and-description' => array(
                    '#type'       => 'fieldset',
                    '#attributes' => array( 'class' => 'awpcp-admin-form-fieldset' ),
                    '#content'    => array(
                        array(
                            '#type'  => 'admin-form-textfield',
                            '#label' => __( 'Name', 'another-wordpress-classifieds-plugin' ),
                            '#name'  => 'name',
                            '#value' => awpcp_get_property( $params['fee'], 'name' ),
                        ),
                        array(
                            '#type'  => 'admin-form-textarea',
                            '#label' => __( 'Description', 'another-wordpress-classifieds-plugin' ),
                            '#name'  => 'description',
                            '#value' => awpcp_get_property( $params['fee'], 'description' ),
                            '#cols'  => 54,
                            '#rows'  => 6,
                        ),
                    ),
                ),
                'features'             => $this->get_features_fields_definition( $params ),
                'special-features'     => array(
                    '#type'       => 'fieldset',
                    '#attributes' => array( 'class' => [ 'awpcp-admin-form-fieldset' ] ),
                    '#content'    => array(
                        array(
                            '#type'        => 'admin-form-checkbox',
                            '#label'       => __( 'This is a private fee plan', 'another-wordpress-classifieds-plugin' ),
                            '#description' => __( 'The plan will be hidden from public view. It will be used for existing listings or special listings that only admins can create.', 'another-wordpress-classifieds-plugin' ),
                            '#name'        => 'is_private',
                            '#value'       => awpcp_get_property( $params['fee'], 'private', false ),
                        ),
                    ),
                ),
                'price-model'          => $this->get_price_fields_definition( $params ),
                'submit-buttons'       => array(
                    '#type'       => 'div',
                    '#attributes' => array(
                        'class' => 'awpcp-admin-form-submit-buttons',
                    ),
                    '#content'    => array(
                        array(
                            '#type'       => 'a',
                            '#attributes' => array(
                                'class' => array( 'button' ),
                                'href'  => awpcp_get_admin_fees_url(),
                            ),
                            '#content'    => __( 'Cancel', 'another-wordpress-classifieds-plugin' ),
                        ),
                        array(
                            '#type'       => 'input',
                            '#attributes' => array(
                                'class'           => array( 'button', 'button-primary' ),
                                'type'            => 'submit',
                                'name'            => 'save',
                                'value'           => __( 'Save', 'another-wordpress-classifieds-plugin' ),
                                'data-usableform' => 'show-if:price_model:flat-price',
                            ),
                        ),
                        array(
                            '#type'       => 'input',
                            '#attributes' => array(
                                'class'           => array( 'button', 'button-primary' ),
                                'type'            => 'submit',
                                'name'            => 'save_and_continue',
                                'value'           => __( 'Save & Continue', 'another-wordpress-classifieds-plugin' ),
                                'data-usableform' => 'hide-if:price_model:flat-price',
                            ),
                        ),
                    ),
                ),
            ),
        );

        return apply_filters( 'awpcp-fee-details-form-definition', $form_definition, $params );
    }

    private function get_duration_field_definition( $params ) {
        return array(
            '#type'       => 'div',
            '#attributes' => array(
                'class' => array( 'awpcp-fee-duration-field', 'awpcp-admin-form-field-with-left-label' ),
            ),
            '#content'    => array(
                array(
                    '#type'       => 'label',
                    '#attributes' => array(
                        'for' => 'awpcp-fee-duration-field',
                    ),
                    '#content'    => __( 'Duration', 'another-wordpress-classifieds-plugin' ),
                ),
                array(
                    '#type'    => 'div',
                    '#content' => array(
                        array(
                            '#type'       => 'input',
                            '#attributes' => array(
                                'id'    => 'awpcp-fee-duration-field',
                                'type'  => 'text',
                                'name'  => 'duration_amount',
                                'value' => awpcp_get_property( $params['fee'], 'duration_amount', 30 ),
                            ),
                        ),
                        array(
                            '#type'       => 'select',
                            '#attributes' => array(
                                'name' => 'duration_interval',
                            ),
                            '#options'    => $this->get_duration_interval_options(),
                            '#value'      => awpcp_get_property( $params['fee'], 'duration_interval', AWPCP_Fee::INTERVAL_DAY ),
                        ),
                    ),
                ),
            ),
        );
    }

    private function get_duration_interval_options() {
        $values = AWPCP_Fee::get_duration_intervals();
        $labels = array_map( array( 'AWPCP_Fee', 'get_duration_interval_label' ), $values );

        return array_combine( $values, $labels );
    }

    private function get_features_fields_definition( $params ) {
        $characters_allowed_in_title       = awpcp_get_property( $params['fee'], 'title_characters' );
        $characters_allowed_in_description = awpcp_get_property( $params['fee'], 'characters' );

        $limit_number_of_characters_in_title = true;
        if ( $characters_allowed_in_title === 0 ) {
            $limit_number_of_characters_in_title = false;
            $characters_allowed_in_title         = 100;
        }

        $limit_number_of_characters_in_description = true;
        if ( $characters_allowed_in_description === 0 ) {
            $limit_number_of_characters_in_description = false;
            $characters_allowed_in_description         = 750;
        }

        return array(
            '#type'       => 'fieldset',
            '#attributes' => array( 'class' => [ 'awpcp-admin-form-fieldset' ] ),
            '#content'    => array(
                'duration' => $this->get_duration_field_definition( $params ),
                array(
                    '#type'       => 'admin-form-textfield',
                    '#attributes' => array( 'class' => 'awpcp-admin-form-text-field-with-left-label' ),
                    '#label'      => __( 'Images allowed for this plan', 'another-wordpress-classifieds-plugin' ),
                    '#name'       => 'images_allowed',
                    '#value'      => awpcp_get_property( $params['fee'], 'images', 0 ),
                ),
                array(
                    '#type'            => 'admin-form-checkbox-textfield',
                    '#label'           => __( 'Limit number of characters in title', 'another-wordpress-classifieds-plugin' ),
                    '#name'            => 'characters_allowed_in_title',
                    '#checkbox_value'  => $limit_number_of_characters_in_title,
                    '#textfield_value' => $characters_allowed_in_title,
                ),
                array(
                    '#type'            => 'admin-form-checkbox-textfield',
                    '#label'           => __( 'Limit number of characters in description', 'another-wordpress-classifieds-plugin' ),
                    '#name'            => 'characters_allowed_in_description',
                    '#checkbox_value'  => $limit_number_of_characters_in_description,
                    '#textfield_value' => $characters_allowed_in_description,
                ),
            ),
        );
    }

    private function get_price_fields_definition( $params ) {
        $form_fields = array();

        $form_fields['price-model'] = array(
            '#type'       => 'input',
            '#attributes' => array(
                'type'  => 'hidden',
                'name'  => 'price_model',
                'value' => 'flat-price',
            ),
        );

        $is_currency_accepted = $this->payments_api->is_currency_accepted();
        $is_credit_accepted   = $this->payments_api->is_credit_accepted();

        if ( $is_currency_accepted ) {
            $form_fields['currency-price'] = array(
                '#type'       => 'admin-form-textfield',
                '#attributes' => array( 'class' => 'awpcp-admin-form-text-field-with-left-label' ),
                '#label'      => __( 'Price', 'another-wordpress-classifieds-plugin' ),
                '#name'       => 'price_in_currency',
                '#value'      => awpcp_format_money_without_currency_symbol( awpcp_get_property( $params['fee'], 'price', 0 ) ),
            );
        }

        if ( $is_currency_accepted && $is_credit_accepted ) {
            $form_fields['currency-price']['#label'] = __( 'Price (currency)', 'another-wordpress-classifieds-plugin' );
        }

        if ( $is_credit_accepted ) {
            $form_fields['credits-price'] = array(
                '#type'       => 'admin-form-textfield',
                '#attributes' => array( 'class' => 'awpcp-admin-form-text-field-with-left-label' ),
                '#label'      => __( 'Price (credits)', 'another-wordpress-classifieds-plugin' ),
                '#name'       => 'price_in_credits',
                '#value'      => intval( awpcp_get_property( $params['fee'], 'credits', 0 ) ),
            );
        }

        return array(
            '#type'       => 'fieldset',
            '#attributes' => [
                'class' => [ 'awpcp-admin-form-fieldset' ],
            ],
            '#content'    => $form_fields,
        );
    }
}
