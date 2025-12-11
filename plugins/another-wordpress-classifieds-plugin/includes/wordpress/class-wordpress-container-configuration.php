<?php
/**
 * @package AWPCP\WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register constructor for classes that wrap or extend WordPress classes and
 * functions.
 */
class AWPCP_WordPressContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @param AWPCP_Container $container An instance of Container.
     * @since 4.0.0
     */
    public function modify( $container ) {
        $container['WordPress'] = $container->service(
            function( $container ) {
                return new AWPCP_WordPress();
            }
        );

        $container['ArrayOptions'] = $container->service(
            function( $container ) {
                return new AWPCP_ArrayOptions(
                    $container['WordPress']
                );
            }
        );
    }
}
