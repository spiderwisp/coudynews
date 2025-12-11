<?php
/**
 * @package AWPCP\Listings\Facebook
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exception thrown when an operation fails because the user hasn't configured a
 * Facebook Page or Group.
 */
class AWPCP_NoFacebookObjectSelectedException extends AWPCP_Exception {
}
