<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for the Example CSV File admin page.
 */
class AWPCP_ExampleCSVFileAdminPage {

    /**
     * @var string
     */
    private $template = '/admin/import/example-csv-file.tpl.php';

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
            'content' => $this->convert_to_csv( $this->prepare_data() ),
            'echo'    => true,
        ];

        $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function prepare_data() {
        $data = [];

        $number_of_rows = 5;
        $current_column = 0;

        foreach ( $this->columns->get_supported_columns() as $columns ) {
            foreach ( $columns as $header => $column ) {
                $examples = array_flip( $column['examples'] );

                $data[0][ $current_column ] = $header;

                for ( $i = 0; $i < $number_of_rows; $i++ ) {
                    $data[ $i + 1 ][ $current_column ] = array_rand( $examples );
                }

                ++$current_column;
            }
        }

        return $data;
    }

    /**
     * @since 4.0.0
     */
    private function convert_to_csv( $data ) {
        $quote_string = uniqid();
        $content      = [];

        foreach ( $data as $row ) {
            $line = implode( "%{$quote_string}%,%{$quote_string}%", $row );
            $line = str_replace( '"', '\"', $line );
            $line = str_replace( "%{$quote_string}%", '"', $line );

            $content[] = '"' . $line . '"';
        }

        return implode( "\n", $content );
    }
}
