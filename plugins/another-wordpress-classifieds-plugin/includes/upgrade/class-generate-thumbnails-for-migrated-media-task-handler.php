<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to genrate thumbnails for migrated media.
 */
class AWPCP_GenerateThumbnailsForMigratedMediaTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var AWPCP_WordPress
     */
    private $wordpress;

    public function __construct( $wordpress ) {
        $this->wordpress = $wordpress;
    }

    /**
     * @since 4.0.0
     */
    public function before_step() {
        // See https://10up.github.io/Engineering-Best-Practices/migrations/#requirements-for-a-successful-migration.
        if ( ! defined( 'WP_IMPORTING' ) ) {
            define( 'WP_IMPORTING', true );
        }
    }

    public function count_pending_items( $last_item_id ) {
        $query = $this->wordpress->create_posts_query(
            [
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'meta_query'  => [
                    [
                        'key'     => '_awpcp_generate_intermediate_image_sizes',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );

        return $query->found_posts;
    }

    public function get_pending_items( $last_item_id ) {
        $query = $this->wordpress->create_posts_query(
            [
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'meta_query'     => [
                    [
                        'key'     => '_awpcp_generate_intermediate_image_sizes',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );

        return $query->posts;
    }

    public function process_item( $item, $last_item_id ) {
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/admin.php';
        }

        $pending_sizes = $this->wordpress->get_post_meta( $item->ID, '_awpcp_generate_intermediate_image_sizes', true );

        // There is no information about what image sizes to generate. Skip this attachment.
        if ( ! is_array( $pending_sizes ) || 0 === count( $pending_sizes ) ) {
            $this->delete_pending_image_sizes( $item );

            return $item->ID;
        }

        $item_metadata = wp_get_attachment_metadata( $item->ID );

        foreach ( $pending_sizes as $index => $pending_size ) {
            // Generate AWPCP image sizes only. See https://github.com/drodenbaugh/awpcp/issues/2370#issuecomment-490937711.
            if ( 'awpcp' === substr( $pending_size, 0, 5 ) ) {
                $new_metadata = $this->generate_intermediate_image_sizes( $item, [ $pending_size ] );

                $item_metadata['sizes'] = array_merge( $item_metadata['sizes'], $new_metadata['sizes'] );

                // We save the modified version of the item's metadata in case
                // PHP dies trying to generate the next image size.
                wp_update_attachment_metadata( $item->ID, $item_metadata );
            }

            $this->wordpress->update_post_meta( $item->ID, '_awpcp_generate_intermediate_image_sizes', array_slice( $pending_sizes, $index + 1 ) );
        }

        $this->delete_pending_image_sizes( $item );

        return $item->ID;
    }

    private function generate_intermediate_image_sizes( $item, $wanted_sizes ) {
        /**
         * Handler for the intermediate_image_sizes_advanced filter used to
         * force WordPress to generate the intermediate image sizes we want only.
         */
        $callback = function( $sizes ) use ( $wanted_sizes ) {
            $new_sizes = [];

            foreach ( $wanted_sizes as $size ) {
                if ( isset( $sizes[ $size ] ) ) {
                    $new_sizes[ $size ] = $sizes[ $size ];
                }
            }

            return $new_sizes;
        };

        // Force WordPress to generate the intermediate image sizes we want only.
        add_filter( 'intermediate_image_sizes_advanced', $callback, 10, 2 );

        // Prevent exif_read_data from being called. Image metadata was already
        // retrieved when the image was imported.
        add_filter( 'wp_read_image_metadata_types', '__return_empty_array', 1000 );

        $new_metadata = wp_generate_attachment_metadata( $item->ID, get_attached_file( $item->ID ) );

        remove_filter( 'wp_read_image_metadata_types', '__return_empty_array', 1000 );
        remove_filter( 'intermediate_image_sizes_advanced', $callback );

        return $new_metadata;
    }

    /**
     * @since 4.0.0
     */
    private function delete_pending_image_sizes( $item ) {
        return $this->wordpress->delete_post_meta( $item->ID, '_awpcp_generate_intermediate_image_sizes' );
    }
}
