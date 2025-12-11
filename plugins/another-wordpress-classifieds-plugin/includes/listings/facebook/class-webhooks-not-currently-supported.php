<?php
/**
 * @package AWPCP\Listings\Facebook
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * An exception used when some tries to send a listing to a Facebook Page
 * or Group but no Facebook Integration method has been selected on the
 * settings.
 *
 * @since 4.0.0
 */
class AWPCP_WebhooksNotCurrentlySupported extends UnexpectedValueException implements AWPCP_ExceptionInterface {
}
