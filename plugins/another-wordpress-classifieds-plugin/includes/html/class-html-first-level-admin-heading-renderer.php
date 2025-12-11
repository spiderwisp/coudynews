<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_first_level_admin_heading_renderer() {
    return new AWPCP_HTML_First_Level_Admin_Heading_Renderer();
}

class AWPCP_HTML_First_Level_Admin_Heading_Renderer implements AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition ) {
        $element_definition = array_merge( $element_definition, array(
            '#type' => awpcp_html_admin_first_level_heading_tag(),
        ) );

        return $html_renderer->render_element( $element_definition );
    }
}
