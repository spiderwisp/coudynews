<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Image File Processor.
 */
function awpcp_image_file_processor() {
    return new AWPCP_ImageFileProcessor();
}

/**
 * Process uploaded image files before they are associated with listings.
 */
class AWPCP_ImageFileProcessor {

    /**
     * Entry point.
     */
    public function process_file( $listing, $file ) {
        $this->try_to_fix_image_rotation( $file );
    }

    /**
     * Attemps to fix image rotation.
     */
    private function try_to_fix_image_rotation( $file ) {
        if ( ! function_exists( 'exif_read_data' ) ) {
            return;
        }

        $exif_data = exif_read_data( $file->get_path() );

        $orientation = isset( $exif_data['Orientation'] ) ? $exif_data['Orientation'] : 0;
        $mime_type   = isset( $exif_data['MimeType'] ) ? $exif_data['MimeType'] : '';

        $rotation_angle = 0;
        if ( 6 === $orientation ) {
            $rotation_angle = 90;
        } elseif ( 3 === $orientation ) {
            $rotation_angle = 180;
        } elseif ( 8 === $orientation ) {
            $rotation_angle = 270;
        }

        if ( $rotation_angle > 0 ) {
            awpcp_rotate_image( $file->get_path(), $mime_type, $rotation_angle );
        }
    }
}
