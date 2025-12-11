<?php
/**
 * @package AWPCP\Listings\Facebook
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exception thrown when someone attempts to sent a disabled listing to Facebook.
 */
class AWPCP_ListingDisabledException extends AWPCP_Exception {
}
