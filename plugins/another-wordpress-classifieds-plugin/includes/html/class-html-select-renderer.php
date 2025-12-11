<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_select_renderer() {
    return new AWPCP_HTML_Select_Renderer();
}

class AWPCP_HTML_Select_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $form_field_definition = array_merge( $element_definition, array(
            '#type' => 'a' . uniqid(),
            '#content' => $this->get_field_options_defintion( $element_definition ),
        ) );

        return str_replace(
            $form_field_definition['#type'],
            'select',
            $html_renderer->render_element( $form_field_definition )
        );
    }

    private function get_field_options_defintion( $element_definition ) {
        $options = array();

        foreach ( $element_definition['#options'] as $option_value => $option_label ) {
            if ( $element_definition['#value'] == $option_value ) {
                $attributes = array( 'value' => $option_value, 'selected' => 'selected' );
            } else {
                $attributes = array( 'value' => $option_value );
            }

            $options[] = array(
                '#type' => 'option',
                '#attributes' => $attributes,
                '#content' => $option_label,
            );
        }

        return $options;
    }
}
