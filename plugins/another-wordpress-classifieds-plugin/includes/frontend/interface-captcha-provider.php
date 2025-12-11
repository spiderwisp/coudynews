<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface for CAPTCHA providers.
 */
interface AWPCP_CAPTCHAProviderInterface {

    /**
     * @since 4.0.0
     */
    public function render();

    /**
     * @since 4.0.0
     */
    public function validate();
}
