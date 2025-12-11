<?php
/**
 * @package AWPCP\HTML
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for HTML Admin Form Textarea Renderer class.
 */
function awpcp_html_admin_form_textarea_renderer() {
    return new AWPCP_HTML_Admin_Form_Textarea_Renderer();
}

class AWPCP_HTML_Admin_Form_Textarea_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $element_definition = wp_parse_args(
            $element_definition,
            [
                '#cols' => null,
                '#rows' => null,
            ]
        );

        $form_field_id = "awpcp-admin-form-textarea-{$element_definition['#name']}";

        $form_field_definition = array(
            '#type'       => 'div',
            '#attributes' => $this->get_form_field_attributes( $element_definition ),
            '#content'    => array(
                array(
                    '#type'       => 'label',
                    '#attributes' => array( 'for' => $form_field_id ),
                    '#content'    => $element_definition['#label'],
                ),
                array(
                    '#type'       => 'textarea',
                    '#attributes' => array(
                        'id'   => $form_field_id,
                        'name' => $element_definition['#name'],
                        'cols' => $element_definition['#cols'],
                        'rows' => $element_definition['#rows'],
                    ),
                    '#content'    => esc_html( $element_definition['#value'] ),
                ),
            ),
        );

        return $html_renderer->render_element( $form_field_definition );
    }

    private function get_form_field_attributes( $element_definition ) {
        $form_field_attributes            = awpcp_parse_html_attributes( $element_definition['#attributes'] );
        $form_field_attributes['class'][] = 'awpcp-admin-form-textarea';

        return $form_field_attributes;
    }
}
