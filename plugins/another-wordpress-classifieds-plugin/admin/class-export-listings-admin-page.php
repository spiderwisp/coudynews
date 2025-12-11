<?php
/**
 * @package AWPCP\Admin\Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.1.0
 */
class AWPCP_ExportListingsAdminPage {

    public function enqueue_scripts() {
        wp_enqueue_style( 'awpcp-admin-export-style' );
        wp_enqueue_script( 'awpcp-admin-export' );
    }

    public function dispatch() {
        $template = AWPCP_DIR . '/templates/admin/export-listings-admin-page.tpl.php';
        return awpcp_render_template( $template, array() );
    }

    public function ajax() {
        check_ajax_referer( 'awpcp-export-csv' );
        if ( ! current_user_can( 'administrator' ) ) {
            wp_send_json_error();
        }

        $error = '';

        try {
            if ( ! isset( $_REQUEST['state'] ) ) {
                $settings = (array) awpcp_get_var( array( 'param' => 'settings', 'default' => array() ), 'post' );
                $export = new AWPCP_CSVExporter( array_merge( $settings, array() ), awpcp_settings_api() );
            } else {
                $state = awpcp_get_var( array( 'param' => 'state' ), 'post' );
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
                $state = json_decode( base64_decode( $state ), true );
                if ( ! $state || ! is_array( $state ) || empty( $state['workingdir'] ) ) {
                    $error = _x( 'Could not decode export state information.', 'admin csv-export', 'another-wordpress-classifieds-plugin' );
                }

                $export = AWPCP_CSVExporter::from_state( $state );
                $cleanup = awpcp_get_var( array( 'param' => 'cleanup' ) );

                if ( $cleanup === '1' ) {
                    $export->cleanup();
                } else {
                    $export->advance();
                }
            }
        } catch ( Exception $e ) {
            $error = $e->getMessage();
        }

        $state = ! $error ? $export->get_state() : null;

        $response          = array();
        $response['error'] = $error;
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        $response['state']    = $state ? base64_encode( wp_json_encode( $state ) ) : null;
        $response['count']    = $state ? count( $state['listings'] ) : 0;
        $response['exported'] = $state ? $state['exported'] : 0;
        $response['filesize'] = $state ? size_format( $state['filesize'] ) : 0;
        $response['isDone']   = $state ? $state['done'] : false;
        $response['fileurl']  = $state ? ( $state['done'] ? $export->get_file_url() : '' ) : '';
        $response['filename'] = $state ? ( $state['done'] ? basename( $export->get_file_url() ) : '' ) : '';

        wp_send_json( $response );
    }
}
