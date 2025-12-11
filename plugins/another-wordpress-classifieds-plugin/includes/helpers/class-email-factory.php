<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Factory of Email.
 */
class AWPCP_EmailFactory {

    /**
     * Return a new instance of Email.
     *
     * @since 4.0.0
     */
    public function get_email() {
        return new AWPCP_Email();
    }
}
