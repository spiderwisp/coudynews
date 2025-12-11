<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for legacy frontend pages.
 */
class AWPCP_Page {

    /**
     * @var boolean
     */
    public $show_menu_items = true;

    /**
     * @var array
     */
    public $classifieds_bar_components = array();

    /**
     * @var string
     */
    protected $template = 'frontend/templates/page.tpl.php';

    /**
     * @var string|bool
     */
    protected $action = false; // I was tempted to change this to '', but don't want to break anything.

    /**
     * @var string
     */
    public $page;

    /**
     * @var string
     */
    public $title;

    /**
     * @var AWPCP_Template_Renderer
     */
    protected $template_renderer;

    /**
     * Constructor.
     */
    public function __construct( $page, $title, $template_renderer ) {
        $this->page              = $page;
        $this->title             = $title;
        $this->template_renderer = $template_renderer;
    }

    /**
     * Return the current step or action being processed.
     */
    public function get_current_action( $default = null ) {
        return $this->action ? $this->action : $default;
    }

    /**
     * Returns the URL of the page after adding the given parameters.
     */
    public function url( $params = array() ) {
        $url = add_query_arg( urlencode_deep( $params ), awpcp_current_url() );
        return $url;
    }

    /**
     * Process the current request.
     */
    public function dispatch() {
        return '';
    }

    /**
     * Simulates a redirection to a different step on this page.
     */
    public function redirect( $action ) {
        $this->action = $action;
        return $this->dispatch();
    }

    /**
     * Return the title of this page.
     */
    public function title() {
        return $this->title;
    }

    /**
     * @since 4.0.0 Updated to use an instance of Template Renderer.
     */
    public function render( $content_template, $content_params = array() ) {
        $page_template = AWPCP_DIR . '/' . $this->template;

        return $this->template_renderer->render_page_template(
            $this, $page_template, $content_template, $content_params
        );
    }

    public function transaction_error() {
        return __( 'There was an error processing your request. Please try again or contact an Administrator.', 'another-wordpress-classifieds-plugin' );
    }
}
