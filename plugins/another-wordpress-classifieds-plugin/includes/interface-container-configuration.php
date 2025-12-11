<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface for a Container Configuration object.
 */
interface AWPCP_ContainerConfigurationInterface {

    /**
     * Modifies the given dependency injection container.
     *
     * @param AWPCP_Container $container    An instance of Container.
     */
    public function modify( $container );
}
