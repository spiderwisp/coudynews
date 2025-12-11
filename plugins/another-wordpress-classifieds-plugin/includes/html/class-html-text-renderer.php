<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_text_renderer() {
    return new AWPCP_HTML_Text_Renderer();
}

class AWPCP_HTML_Text_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        if ( $element_definition['#escape'] ) {
            $content = esc_html( $element_definition['#content'] );
        } else {
            $content = $element_definition['#content'];
        }

        return $html_renderer->render_content( $element_definition, $content );
    }
}
