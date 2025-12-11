<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_image_file_validator() {
    return new AWPCP_Image_File_Validator( awpcp_listing_upload_limits(), awpcp_file_validation_errors() );
}

class AWPCP_Image_File_Validator extends AWPCP_ListingFileValidator {

    protected function get_listing_upload_limits( $listing ) {
        return $this->upload_limits->get_listing_upload_limits_by_file_type( $listing, 'images' );
    }

    protected function additional_verifications( $file, $upload_limits ) {
        $this->validate_image_dimensions( $file, $upload_limits );
    }

    private function validate_image_dimensions( $file, $image_upload_limits ) {
        $img_info = getimagesize( $file->get_path() );

        if ( ! isset( $img_info[ 0 ] ) && ! isset( $img_info[ 1 ] ) ) {
            $message = _x( 'There was an error trying to find out the dimension of <filename>. The file was not uploaded.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<filename>', $file->get_real_name(), $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( $img_info[ 0 ] < $image_upload_limits['min_image_width'] ) {
            // translators: %1$s is the file name, %2$s is the minimum width
            $message = __( 'The image %1$s did not meet the minimum width of %2$s pixels. The file was not uploaded.', 'another-wordpress-classifieds-plugin');
            $message = sprintf(
                $message,
                $file->get_real_name(),
                $image_upload_limits['min_image_width']
            );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( $img_info[ 1 ] < $image_upload_limits['min_image_height'] ) {
            // translators: %1$s is the file name, %2$s is the minimum height
            $message = __( 'The image %1$s did not meet the minimum height of %2$s pixels. The file was not uploaded.', 'another-wordpress-classifieds-plugin');
            $message = sprintf(
                $message,
                $file->get_real_name(),
                $image_upload_limits['min_image_height']
            );
            throw new AWPCP_Exception( esc_html( $message ) );
        }
    }
}
