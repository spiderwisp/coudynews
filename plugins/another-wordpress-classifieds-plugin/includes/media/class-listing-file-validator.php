<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for the objects used to validate files added to listings as attachments.
 */
abstract class AWPCP_ListingFileValidator {

    /**
     * @var object
     */
    protected $upload_limits;

    /**
     * @var object
     */
    protected $validation_errors;

    /**
     * @param object $upload_limits         An instance of Listing Upload Limits.
     * @param object $validation_errors     An instance of File Validation Errors.
     */
    public function __construct( $upload_limits, $validation_errors ) {
        $this->upload_limits     = $upload_limits;
        $this->validation_errors = $validation_errors;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @param object $file      An instance of Uploaded File Logic.
     */
    public function validate_file( $listing, $file ) {
        $upload_limits = $this->get_listing_upload_limits( $listing );

        if ( ! in_array( $file->get_mime_type(), $upload_limits['mime_types'], true ) ) {
            $message = __( 'The type of the uploaded file <filename> is not allowed.', 'another-wordpress-classifieds-plugin' );
            $this->throw_file_validation_exception( $file, $message );
        }

        if ( ! $this->upload_limits->can_add_file_to_listing( $listing, $file ) ) {
            $message = $this->validation_errors->get_cannot_add_more_files_of_type_error_message();
            $this->throw_file_validation_exception( $file, $message );
        }

        if ( ! file_exists( $file->get_path() ) ) {
            $message = __( 'The file <filename> was not found in the temporary uploads directory.', 'another-wordpress-classifieds-plugin' );
            $this->throw_file_validation_exception( $file, $message );
        }

        $this->validate_file_size( $file, $upload_limits );
        $this->additional_verifications( $file, $upload_limits );
    }

    /**
     * @param object $listing   An instance of WP_Post.
     */
    abstract protected function get_listing_upload_limits( $listing );

    /**
     * @param object $file      An instance of Upload File Logic.
     * @param string $message   An error message.
     * @throws AWPCP_Exception  If the file does not meet any of the requirements.
     */
    private function throw_file_validation_exception( $file, $message ) {
        $message = str_replace( '<filename>', '<strong>' . $file->get_real_name() . '</strong>', $message );
        throw new AWPCP_Exception( wp_kses_post( $message ) );
    }

    /**
     * @param object $file              An instance of Upload File Logic.
     * @param object $upload_limits     The upload limits for this kind of file.
     */
    private function validate_file_size( $file, $upload_limits ) {
        $filesize = filesize( $file->get_path() );

        if ( empty( $filesize ) ) {
            $message = __( 'There was an error trying to find out the file size of the file <filename>.', 'another-wordpress-classifieds-plugin' );
            $this->throw_file_validation_exception( $file, $message );
        }

        if ( $filesize > $upload_limits['max_file_size'] ) {
            $message = $this->validation_errors->get_file_is_too_large_error_message();
            $message = str_replace( '<bytes-count>', $upload_limits['max_file_size'], $message );
            $this->throw_file_validation_exception( $file, $message );
        }

        if ( $filesize < $upload_limits['min_file_size'] ) {
            $message = __( 'The file <filename> is smaller than the minimum allowed file size of <bytes-count> bytes. The file was not uploaded.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<bytes-count>', $upload_limits['min_file_size'], $message );
            $this->throw_file_validation_exception( $file, $message );
        }
    }

    /**
     * @param object $file              An instance of Upload File Logic.
     * @param object $upload_limits     The upload limits for this kind of file.
     */
    protected function additional_verifications( $file, $upload_limits ) {
        // nothing here!
    }
}
