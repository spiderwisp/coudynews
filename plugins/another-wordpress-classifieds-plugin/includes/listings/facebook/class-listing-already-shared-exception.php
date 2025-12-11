<?php
/**
 * @package AWPCP\Listings\Facebook
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exception thrown when a listing when someone tries to send an already shared
 * listing to Facebook.
 */
class AWPCP_ListingAlreadySharedException extends AWPCP_Exception {
}
