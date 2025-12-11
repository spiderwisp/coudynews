<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



interface AWPCP_HTML_Element_Renderer {

    public function render_element( $html_renderer, $element_definition );
}
