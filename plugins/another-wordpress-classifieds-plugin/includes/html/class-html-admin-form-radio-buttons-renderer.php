<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_admin_form_radio_buttons_renderer() {
    return new AWPCP_HTML_Admin_Form_Radio_Buttons_Renderer();
}

class AWPCP_HTML_Admin_Form_Radio_Buttons_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $form_field_definition = array(
            '#type' => 'div',
            '#attributes' => array(
                'class' => 'awpcp-admin-form-radio-buttons',
            ),
            '#content' => array_merge( array(
                array(
                    '#type' => 'label',
                    '#attributes' => array( 'class' => array( 'awpcp-admin-form-radio-buttons-label' ) ),
                    '#content' => $element_definition['#label'],
                ),
            ), $this->get_radio_buttons_defintion( $element_definition ) ),
        );

        return $html_renderer->render_element( $form_field_definition );
    }

    private function get_radio_buttons_defintion( $element_definition ) {
        $radio_buttons = array();

        foreach ( $element_definition['#options'] as $option_value => $option_label ) {
            $attributes = array(
                'type' => 'radio',
                'name' => empty( $element_definition['#name'] ) ? 'price_model' : $element_definition['#name'],
                'value' => $option_value,
            );

            if ( $element_definition['#value'] == $option_value ) {
                $attributes['checked'] = 'checked';
            }

            $radio_buttons[] = array(
                '#type' => 'label',
                '#attributes' => array( 'class' => array( 'awpcp-admin-form-radio-buttons-option' ) ),
                '#content' => array(
                    array(
                        '#type' => 'input',
                        '#attributes' => $attributes,
                    ),
                    array(
                        '#type' => 'text',
                        '#content' => $option_label,
                    ),
                ),
            );
        }

        return $radio_buttons;
    }
}
