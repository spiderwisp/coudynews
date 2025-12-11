<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_html_renderer() {
    return new AWPCP_HTML_Renderer();
}

class AWPCP_HTML_Renderer {

    public $element_renderers = array();

    public function render( $element ) {
        return $this->render_element( $element );
    }

    public function render_elements( $element_definitions ) {
        $output = array();

        foreach ( $element_definitions as $element_definition ) {
            $output[] = $this->render_element( $element_definition );
        }

        return implode( '', $output );
    }

    public function render_element( $element_definition ) {
        $element_definition = $this->normalize_element_definition( $element_definition );
        $element_renderer = $this->get_element_renderer( $element_definition );
        return $element_renderer->render_element( $this, $element_definition );
    }

    private function normalize_element_definition( $element_definition ) {
        return wp_parse_args( $element_definition, array(
            '#content_prefix' => '',
            '#content' => '',
            '#content_suffix' => '',
            '#attributes' => array(),
            '#escape' => false,
        ) );
    }

    private function get_element_renderer( $element_definition ) {
        $this->load_renderers();

        $type_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $element_definition['#type'] ) ) );
        $class_name = 'AWPCP_HTML_' . $type_name . '_Renderer';
        $constructor_function = strtolower( $class_name );

        if ( isset( $this->element_renderers[ $class_name ] ) ) {
            return $this->element_renderers[ $class_name ];
        } elseif ( function_exists( $constructor_function ) ) {
            $this->element_renderers[ $class_name ] = call_user_func( $constructor_function );
        } else {
            $this->element_renderers[ $class_name ] = awpcp_html_default_element_renderer();
        }

        return $this->element_renderers[ $class_name ];
    }

    private function load_renderers() {
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-autocomplete-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-checkbox-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-checkbox-textfield-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-radio-buttons-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-select-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-textarea-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-admin-form-textfield-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-first-level-admin-heading-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-select-renderer.php' );
        include_once( AWPCP_DIR . '/includes/html/class-html-text-renderer.php' );

        // include_once( AWPCP_DIR . '/includes/html/class-html-inline-form-renderers.php' );
    }

    public function render_content( $element_definition, $content ) {
        $output = array();

        if ( $element_definition['#content_prefix'] ) {
            $output[] = $element_definition['#content_prefix'];
        }

        if ( is_array( $content ) ) {
            $output[] = $this->render_elements( $content );
        } else {
            $output[] = $content;
        }

        if ( $element_definition['#content_suffix'] ) {
            $output[] = $element_definition['#content_suffix'];
        }

        return implode( '', $output );
    }
}
