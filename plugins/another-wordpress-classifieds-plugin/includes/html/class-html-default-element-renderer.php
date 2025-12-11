<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_default_element_renderer() {
    return new AWPCP_HTML_Default_Element_Renderer();
}

class AWPCP_HTML_Default_Element_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $element = '<<tag><attributes>><content></<tag>>';
        $element = str_replace( '<tag>', $element_definition['#type'], $element );

        if ( ! empty( $element_definition['#attributes'] ) ) {
            $element = str_replace(
                '<attributes>',
                ' ' . awpcp_html_attributes( $element_definition['#attributes'] ),
                $element
            );
        } else {
            $element = str_replace( '<attributes>', '', $element );
        }

        $element = str_replace(
            '<content>',
            $html_renderer->render_content( $element_definition, $element_definition['#content'] ),
            $element
        );

        return $element;
    }
}
