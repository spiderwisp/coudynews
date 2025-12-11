<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
function awpcp_html_admin_form_Select_renderer() {
    return new AWPCP_HTML_Admin_Form_Select_Renderer();
}

class AWPCP_HTML_Admin_Form_Select_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $form_field_id = "awpcp-admin-form-select-{$element_definition['#name']}";

        $form_field_definition = array(
            '#type' => 'div',
            '#attributes' => $this->get_form_field_attributes( $element_definition ),
            '#content' => array(
                array(
                    '#type' => 'label',
                    '#attributes' => array( 'for' => $form_field_id ),
                    '#content' => $element_definition['#label'],
                ),
                array(
                    '#type' => 'select',
                    '#attributes' => array(
                        'id' => $form_field_id,
                        'name' => $element_definition['#name'],
                    ),
                    '#options' => $element_definition['#options'],
                    '#value' => $element_definition['#value'],
                ),
            ),
        );

        return $html_renderer->render_element( $form_field_definition );
    }

    private function get_form_field_attributes( $element_definition ) {
        $form_field_attributes = awpcp_parse_html_attributes( $element_definition['#attributes'] );
        $form_field_attributes['class'][] = 'awpcp-admin-form-select';

        return $form_field_attributes;
    }
}
