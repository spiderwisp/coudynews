<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validates, process and associates an uploaded file with a listing.
 */
class AWPCP_ListingFileHandler {

    /**
     * @var object
     */
    private $validator;

    /**
     * @var object
     */
    private $processor;

    /**
     * @var object
     */
    private $creator;

    /**
     * @param object $validator     An instance of Listing File Validator.
     * @param object $processor     An instance of Listing File Processor.
     * @param object $creator       An instance of Listing Attachment Creator.
     */
    public function __construct( $validator, $processor, $creator ) {
        $this->validator = $validator;
        $this->processor = $processor;
        $this->creator   = $creator;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @param object $file      An instance of Uploaded File Logic.
     * @since 4.0.0
     */
    public function validate_file( $listing, $file ) {
        $this->validator->validate_file( $listing, $file );
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @param object $file      An instance of Uploaded File Logic.
     */
    public function handle_file( $listing, $file ) {
        $this->validator->validate_file( $listing, $file );
        $this->processor->process_file( $listing, $file );

        return $this->creator->create_attachment( $listing, $file );
    }
}
