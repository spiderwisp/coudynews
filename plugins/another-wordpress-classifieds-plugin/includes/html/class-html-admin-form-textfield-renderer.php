<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_admin_form_textfield_renderer() {
    return new AWPCP_HTML_Admin_Form_Textfield_Renderer();
}

class AWPCP_HTML_Admin_Form_Textfield_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $form_field_id = "awpcp-admin-form-textfield-{$element_definition['#name']}";

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
                    '#type' => 'input',
                    '#attributes' => array(
                        'id' => $form_field_id,
                        'type' => 'text',
                        'value' => $element_definition['#value'],
                        'name' => $element_definition['#name'],
                    ),
                ),
            ),
        );

        return $html_renderer->render_element( $form_field_definition );
    }

    private function get_form_field_attributes( $element_definition ) {
        $form_field_attributes = awpcp_parse_html_attributes( $element_definition['#attributes'] );

        $form_field_attributes['class'][] = 'awpcp-admin-form-field';
        $form_field_attributes['class'][] = 'awpcp-admin-form-textfield';

        return $form_field_attributes;
    }
}
