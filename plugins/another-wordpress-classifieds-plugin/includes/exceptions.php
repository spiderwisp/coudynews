<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Exception extends Exception {

    private $errors = null;

    public function __construct( $message = '', $code = 0, $previous = null ) {
        // TODO: Make sure the second parameter is no longer being used to pass
        //       an array of errors and remove this if statement and its content.
        if ( is_array( $code ) ) {
            parent::__construct( $message );

            $this->errors = $code;

            return;
        }

        parent::__construct( $message, $code, $previous );
    }

    public function get_errors() {
        return array_filter( array_merge( array( $this->getMessage() ), (array) $this->errors ) );
    }

    public function format_errors() {
        return implode( ' ', $this->get_errors() );
    }
}

class AWPCP_IOError extends AWPCP_Exception {
}

class AWPCP_WPError extends AWPCP_Exception {

    public $wp_error;

    public function __construct( $wp_error ) {
        $this->wp_error = $wp_error;
    }
}

class AWPCP_RedirectionException extends AWPCP_Exception {

    public $step_name = null;
    public $request_method = null;

    public function __construct( $step_name, $request_method ) {
        $this->step_name = $step_name;
        $this->request_method = $request_method;
    }
}

class AWPCP_DatabaseException extends AWPCP_Exception {

    public function __construct( $exception_message, $db_error ) {
        parent::__construct( $this->prepare_exception_message( $exception_message, $db_error ) );
    }

    private function prepare_exception_message( $exception_message, $db_error ) {
        if ( ! empty( $db_error ) ) {
            return $exception_message . ' ' . $db_error;
        } else {
            return $exception_message;
        }
    }
}

class AWPCP_DBException extends AWPCP_Exception {

    public function __construct( $message, $database_error = null ) {
        if ( $database_error ) {
            /* translators: %1$s the message, %2$s the error */
            $template = _x( '%1$s The error was: %2$s.', 'DBException message template', 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $template, $message, $database_error );
        }

        parent::__construct( $message );
    }
}

class AWPCP_Easy_Digital_Downloads_Exception extends Exception {
}

class AWPCP_License_Request_Exception extends AWPCP_Easy_Digital_Downloads_Exception {
}

class AWPCP_No_Activations_Left_License_Request_Exception extends AWPCP_License_Request_Exception {
}

class AWPCP_Infinite_Loop_Detected_Exception extends AWPCP_Easy_Digital_Downloads_Exception {
}

class AWPCP_CSV_Importer_Exception extends Exception {

    private $errors;

    public function setErrors( $errors ) {
        $this->errors = $errors;
    }

    public function getErrors() {
        if ( $this->errors ) {
            return $this->errors;
        }

        return array( $this->getMessage() );
    }
}

class AWPCP_HTTP_Exception extends Exception {
}
