<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register constructors for classes in the AWPCP\Media package.
 *
 * @since 4.0.0
 */
class AWPCP_MediaContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * @param AWPCP_Container $container     An instance of the plugin's container.
     * @since 4.0.0
     */
    public function modify( $container ) {
        $container['AttachmentsLogic'] = $container->service(
            function ( $container ) {
                return new AWPCP_Attachments_Logic(
                    awpcp_file_types(),
                    $container['AttachmentsCollection'],
                    $container['WordPress']
                );
            }
        );

        $container['AttachmentsCollection'] = $container->service(
            function ( $container ) {
                return new AWPCP_Attachments_Collection(
                    $container['FileTypes'],
                    $container['WordPress']
                );
            }
        );

        $container['FileHandlersManager'] = $container->service(
            function ( $container ) {
                return new AWPCP_File_Handlers_Manager( $container );
            }
        );

        $this->register_file_handlers( $container );
    }

    /**
     * @since 4.0.0
     */
    private function register_file_handlers( $container ) {
        $container['ImageFileHandler'] = $container->service(
            function ( $container ) {
                return new AWPCP_ListingFileHandler(
                    awpcp_image_file_validator(),
                    awpcp_image_file_processor(),
                    awpcp_image_attachment_creator()
                );
            }
        );
    }
}
