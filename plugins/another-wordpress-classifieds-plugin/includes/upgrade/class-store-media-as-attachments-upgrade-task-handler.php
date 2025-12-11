<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to store media records as attachments.
 */
class AWPCP_Store_Media_As_Attachments_Upgrade_Task_Handler implements AWPCP_Upgrade_Task_Runner {

    use AWPCP_UpgradeAssociatedListingsTaskHandlerHelper;

    /**
     * @var object
     */
    private $settings;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @var object
     */
    private $db;

    /**
     * @param object $settings  An instance of SettingsAPI.
     * @param object $wordpress An instance of WordPress.
     * @param object $db        An instance of wpdb.
     */
    public function __construct( $settings, $wordpress, $db ) {
        $this->settings  = $settings;
        $this->wordpress = $wordpress;
        $this->db        = $db;
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

    /**
     * Count number of items that need to be processed.
     *
     * @param int $last_item_id     The ID of the last item processed by the routine.
     */
    public function count_pending_items( $last_item_id ) {
        $query = 'SELECT COUNT(id) FROM ' . AWPCP_TABLE_MEDIA . ' WHERE id > %d';
        return intval( $this->db->get_var( $this->db->prepare( $query, $last_item_id ) ) );
    }

    /**
     * Get items that need to be processed.
     *
     * @param int $last_item_id     The ID of the last item processed by the routine.
     */
    public function get_pending_items( $last_item_id ) {
        $query = 'SELECT * FROM ' . AWPCP_TABLE_MEDIA . ' WHERE id > %d LIMIT 0, 50';
        return $this->db->get_results( $this->db->prepare( $query, $last_item_id ) );
    }

    /**
     * @param object $item          An item to process.
     * @param int    $last_item_id  The ID of the last item processed by the routine.
     *
     * @throws AWPCP_Exception  If the associated file cannot be copied or stored.
     */
    public function process_item( $item, $last_item_id ) {
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $parent_listing_id = $this->get_id_of_associated_listing( $item->ad_id );

        if ( 0 === $parent_listing_id ) {
            // The file has no associated listing. We assume this is an orphan file
            // left behind by broken delete operation on previous versions of the
            // plugin.
            return $item->id;
        }

        // Some users are downgrading to version 3.9.5 after upgrading to 4.0.0.
        // Those users will eventually upgrade again, but we don't want to
        // migrate media records that were already migrated.
        if ( $this->is_item_already_processed( $item, $parent_listing_id ) ) {
            return $item->id;
        }

        $file_path = trailingslashit( $this->settings->get_runtime_option( 'awpcp-uploads-dir' ) ) . $item->path;
        $file_name = awpcp_utf8_pathinfo( $file_path, PATHINFO_BASENAME );

        if ( ! file_exists( $file_path ) ) {
            $error_message = __( "The file {filepath} doesn't exist.", 'another-wordpress-classifieds-plugin' );
            $error_message = str_replace( '{filepath}', $file_path, $error_message );

            add_post_meta(
                $parent_listing_id,
                '_awpcp_failed_media_migration',
                [
                    'errors' => [ $error_message ],
                    'media'  => (array) $item,
                ]
            );

            return $item->id;
        }

        $tmp_dir  = get_temp_dir();
        $new_name = wp_unique_filename( $tmp_dir, $file_name );
        $tmp_name = "{$tmp_dir}{$new_name}";

        $file_was_copied = @copy( $file_path, $tmp_name );

        if ( ! $file_was_copied ) {
            throw new AWPCP_Exception( esc_html( sprintf( "The file %s couldn't be copied to the temporary location %s", $file_path, $tmp_name ) ) );
        }

        $file_array  = array(
            'name'     => awpcp_sanitize_file_name( $file_name ),
            'tmp_name' => $tmp_name,
        );
        $description = '';

        // Add attachment, but don't create intermediate image sizes.
        add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 20181224 );
        $attachment_id = $this->wordpress->handle_media_sideload( $file_array, $parent_listing_id, $description );
        remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 20181224 );

        if ( file_exists( $tmp_name ) ) {
            wp_delete_file( $tmp_name );
        }

        // If error storing permanently, unlink.
        if ( is_wp_error( $attachment_id ) ) {
            $error_message = __( "An attachment couldn't be created for media item with id {media_id}.", 'another-wordpress-classifieds-plugin' );
            $error_message = str_replace( '{media_id}', $item->id, $error_message );

            add_post_meta(
                $parent_listing_id,
                '_awpcp_failed_media_migration',
                [
                    'errors' => [
                        $error_message,
                        $attachment_id->get_error_message(),
                    ],
                    'media'  => (array) $item,
                ]
            );

            return $item->id;
        }

        if ( $item->enabled ) {
            add_post_meta( $attachment_id, '_awpcp_enabled', true );
        }

        if ( $item->is_primary ) {
            add_post_meta( $parent_listing_id, '_thumbnail_id', $attachment_id );
            add_post_meta( $attachment_id, '_awpcp_featured', true );
        }

        add_post_meta( $attachment_id, '_awpcp_allowed_status', $item->status );
        add_post_meta( $attachment_id, '_awpcp_generate_intermediate_image_sizes', $this->get_intermediate_image_sizes() );

        return $item->id;
    }

    /**
     * Check whether the current media record was already processed on a previous
     * upgrade operation.
     *
     * @since 4.0.1
     *
     * @param object $item       The current media record.
     * @param int    $listing_id The ID of the WP_Post associated with
     *                           this media record.
     *
     * @return bool
     */
    private function is_item_already_processed( $item, $listing_id ) {
        $migrated_attachments_filenames = get_post_meta( $listing_id, '__awpcp_migrated_attachments_filenames', true );

        // It seems the asasociated listing was recently created during this
        // upgrade operation. Process items normally.
        if ( ! is_array( $migrated_attachments_filenames ) ) {
            return false;
        }

        // Media items keep their original name except, maybe, for a numeric
        // suffix used when another file with the same name already exists
        // in the uploads directory.
        $info    = awpcp_utf8_pathinfo( $item->name );
        $pattern = preg_quote( $info['filename'], '/' ) . '(?:-\d+)?\.' . preg_quote( $info['extension'], '/' );

        foreach ( $migrated_attachments_filenames as $filename ) {
            if ( preg_match( '/^' . $pattern . '$/', $filename ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a list of AWPCP's intermediate image sizes.
     *
     * See https://github.com/drodenbaugh/awpcp/issues/2370#issuecomment-490937711.
     *
     * @since 4.0.0
     */
    private function get_intermediate_image_sizes() {
        return array_filter(
            get_intermediate_image_sizes(),
            function( $size ) {
                return 'awpcp' === substr( $size, 0, 5 );
            }
        );
    }
}
