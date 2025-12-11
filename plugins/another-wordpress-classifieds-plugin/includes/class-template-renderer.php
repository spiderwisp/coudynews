<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_template_renderer() {
    return new AWPCP_Template_Renderer();
}

class AWPCP_Template_Renderer {

    public function render_page_template( $page, $page_template, $content_template, $content_params = array() ) {
        if ( $content_template === 'content' ) {
            $content = $content_params;
        } else {
            $content = $this->render_template( $content_template, $content_params );
        }

        if ( method_exists( $page, 'should_show_title' ) ) {
            $should_show_title = $page->should_show_title();
        } else {
            $should_show_title = true;
        }

        if ( method_exists( $page, 'show_sidebar' ) ) {
            $show_sidebar = $page->show_sidebar();
        } else {
            $show_sidebar = false;
        }

        $params = array(
            'page' => $page,
            'page_slug' => $page->page,
            'page_title' => $page->title(),
            'should_show_title' => $should_show_title,
            'show_sidebar' => $show_sidebar,
            'content' => $content,
        );

        return $this->render_template( $page_template, $params );
    }

    /**
     * Render the given template using the provided parameters.
     *
     * The view parameters can be accessed from the template through the $params
     * array or using each key in that array as variable names.
     *
     * The recommended method is to use the $params array because that makes it
     * more clear that you are using a value that the user of the template
     * should provide.
     *
     * @param string $template  The path to a template file. It can be an
     *                          absolute path or relative to the plugin's
     *                          templates directory.
     * @param array $params     An array of parameters that will be extracted
     *                          into the function's symbol table and made
     *                          available in the template.
     * @return string The rendered template.
     */
    public function render_template( $template, $params = array() ) {
        if ( file_exists( $template ) ) {
            $template_file = $template;
        } elseif ( file_exists( AWPCP_DIR . '/templates/' . $template ) ) {
            $template_file = AWPCP_DIR . '/templates/' . $template;
        } else {
            $template_file = null;
        }

        if ( ! is_null( $template_file ) ) {
            extract( $params );

            if ( ! empty( $params['echo'] ) ) {
                include $template_file;
                return '';
            }

            ob_start();
            include( $template_file );
            $output = ob_get_contents();
            ob_end_clean();
        } else {
            $output = sprintf( 'Template %s not found!', str_replace( AWPCP_DIR, '', $template ) );

            if ( ! empty( $params['echo'] ) ) {
                echo esc_html( $output );
                return '';
            }
        }

        return $output;
    }
}
