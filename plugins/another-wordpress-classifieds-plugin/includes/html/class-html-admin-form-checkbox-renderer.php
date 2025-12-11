<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_admin_form_checkbox_renderer() {
    return new AWPCP_HTML_Admin_Form_Checkbox_Renderer();
}

class AWPCP_HTML_Admin_Form_Checkbox_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $form_field_id = "awpcp-admin-form-checkbox-{$element_definition['#name']}";

        if ( $element_definition['#value'] ) {
            $checbox_attributes = array( 'checked' => 'checked' );
        } else {
            $checbox_attributes = array();
        }

        $form_field_definition = array(
            '#type' => 'div',
            '#attributes' => $this->get_form_field_attributes( $element_definition ),
            '#content' => array(
                array(
                    '#type' => 'label',
                    '#attributes' => array( 'for' => $form_field_id ),
                    '#content' => array(
                        array(
                            '#type' => 'input',
                            '#attributes' => array(
                                'type' => 'hidden',
                                'value' => false,
                                'name' => "{$element_definition['#name']}",
                            ),
                        ),
                        array(
                            '#type' => 'input',
                            '#attributes' => array_merge( $checbox_attributes, array(
                                'id' => $form_field_id,
                                'type' => 'checkbox',
                                'value' => true,
                                'name' => "{$element_definition['#name']}",
                            ) ),
                        ),
                        array(
                            '#type' => 'text',
                            '#content' => $element_definition['#label'],
                        ),
                    ),
                ),
            ),
        );

        if ( isset( $element_definition['#description'] ) ) {
            $form_field_definition['#content'][] = array(
                '#type' => 'span',
                '#content' => $element_definition['#description'],
            );
        }

        return $html_renderer->render_element( $form_field_definition );
    }

    private function get_form_field_attributes( $element_definition ) {
        $form_field_attributes = awpcp_parse_html_attributes( $element_definition['#attributes'] );

        $form_field_attributes['class'][] = 'awpcp-admin-form-checkbox';

        return $form_field_attributes;
    }
}
