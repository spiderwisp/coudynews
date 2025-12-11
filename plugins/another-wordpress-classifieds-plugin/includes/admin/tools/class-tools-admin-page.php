<?php
/**
 * @package AWPCP\Admin\Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ToolsAdminPage {

    /**
     * @var string
     */
    private $template = '/admin/tools/tools-admin-page.tpl.php';

    /**
     * @var object
     */
    protected $template_renderer;

    /**
     * @var array
     */
    private $views;

    /**
     * @since 4.0.0
     */
    public function __construct( $template_renderer ) {
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function dispatch() {
        // Tool page views.
        $views       = array(
            array(
                'title'       => __( 'Import and Export Settings', 'another-wordpress-classifieds-plugin' ),
                'url'         => add_query_arg( 'awpcp-view', 'import-settings' ),
                'description' => __( 'Import and export your settings for re-use on another site.', 'another-wordpress-classifieds-plugin' ),
            ),
            array(
                'title' => __( 'Import Listings', 'another-wordpress-classifieds-plugin' ),
                'url'   => add_query_arg( 'awpcp-view', 'import-listings' ),
            ),
            array(
                'title' => __( 'Export Listings', 'another-wordpress-classifieds-plugin' ),
                'url'   => add_query_arg( 'awpcp-view', 'export-listings' ),
            ),
        );
        $views       = apply_filters( 'awpcp_tool_page_views', $views );
        $this->views = $views;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->template_renderer->render_template( $this->template, $this->views );
    }
}
