<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for the Supported CSV Headers admin page.
 */
class AWPCP_SupportedCSVHeadersAdminPage {

    /**
     * @var string
     */
    private $template = '/admin/import/supported-csv-headers.tpl.php';

    /**
     * @var AWPCP_CSVImporterColumns
     */
    private $columns;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @since 4.0.0
     */
    public function __construct( $columns, $template_renderer ) {
        $this->columns           = $columns;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     *
     * @return void
     */
    public function dispatch() {
        $params = [
            'columns' => $this->get_supported_columns(),
            'echo'    => true,
        ];

        $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_supported_columns() {
        $supported_columns = [];

        foreach ( $this->columns->get_supported_columns() as $columns ) {
            foreach ( $columns as $header => $column ) {
                $supported_columns[ $header ] = $column;
            }
        }

        return $supported_columns;
    }
}
