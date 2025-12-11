<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



if ( ! class_exists( 'AWPCP_AjaxHandler' ) ) {

function awpcp_ajax_response() {
    return new AWPCP_AjaxResponse();
}

/**
 * @since 3.2.2
 */
class AWPCP_AjaxResponse {

    /**
     * @since 3.2.2
     */
    public function set_content_type( $content_type ) {
        header( sprintf( 'Content-Type: %s', $content_type ) );
    }

    /**
     * @since 3.2.2
     * @deprecated 4.3.3
     */
    public function write( $content ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $content;
    }

    /**
     * @since 3.2.2
     * @deprecated 4.3.3
     */
    public function close() {
        die();
    }
}

/**
 * @since 3.2.2
 */
abstract class AWPCP_AjaxHandler {

    private $response;

    public function __construct( $response ) {
        $this->response = $response;
    }

    /**
     * @since 3.2.2
     */
    abstract public function ajax();

    /**
     * @since 3.2.2
     * @since 4.0.0 access changed to public.
     */
    public function success( $params = array() ) {
        return $this->flush( array_merge( array( 'status' => 'ok' ), $params ) );
    }

    /**
     * @since 3.2.2
     * @since 4.0.0 access changed to public.
     */
    public function error( $params = array() ) {
        return $this->flush( array_merge( array( 'status' => 'error' ), $params ) );
    }

    /**
     * @since 3.2.2
     * @since 4.0.0 access changed to public.
     */
    public function progress_response( $records_count, $records_left ) {
        return $this->success( array( 'recordsCount' => $records_count, 'recordsLeft' => $records_left ) );
    }

    /**
     * @since 3.2.2
     * @since 4.0.0 access changed to public.
     */
    public function error_response( $error_message ) {
        return $this->error( array( 'error' => $error_message ) );
    }

    /**
     * @since 3.2.2
     * @since 4.0.0 access changed to public.
     */
    public function multiple_errors_response( $errors ) {
        return $this->error( array( 'errors' => (array) $errors ) );
    }

    /**
     * @since 3.2.2
     */
    protected function flush( $array_response ) {
        $this->response->set_content_type( 'application/json' );
        echo wp_json_encode( $array_response );
        die();
    }
}

}
