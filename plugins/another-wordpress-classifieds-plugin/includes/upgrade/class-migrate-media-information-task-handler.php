<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_Migrate_Media_Information_Task_Handler {

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var wpdb
     */
    private $db;

    public function __construct( $settings, $db ) {
        $this->settings = $settings;
        $this->db       = $db;
    }

    /**
     * TODO: do this in the next version upgrade
     * $this->db->query( 'DROP TABLE ' . AWPCP_TABLE_ADPHOTOS );
     */
    public function run_task() {
        $mime_types = awpcp_mime_types();

        if ( ! $this->photos_table_exists() ) {
            return array( 0, 0 );
        }

        $cursor = get_option( 'awpcp-migrate-media-information-cursor', 0 );
        $total = $this->count_pending_images( $cursor );

        $sql = 'SELECT * FROM ' . AWPCP_TABLE_ADPHOTOS . ' ';
        $sql.= 'WHERE ad_id > %d ORDER BY key_id LIMIT 0, 100';

        $results = $this->db->get_results( $this->db->prepare( $sql, $cursor ) );

        $uploads_dir = trailingslashit( $this->settings->get_option( 'awpcp-uploads-dir' ) );

        foreach ( $results as $image ) {
            $cursor = $image->ad_id;

            if ( file_exists( $uploads_dir . $image->image_name ) ) {
                $relative_path = $image->image_name;
            } elseif ( file_exists( $uploads_dir . 'images/' . $image->image_name ) ) {
                $relative_path = 'images/' . $image->image_name;
            } else {
                continue;
            }

            $mime_type = $mime_types->get_file_mime_type( $uploads_dir . $relative_path );

            $entry = array(
                'ad_id' => $image->ad_id,
                'path' => $relative_path,
                'name' => $image->image_name,
                'mime_type' => strtolower( $mime_type ),
                'enabled' => ! $image->disabled,
                'is_primary' => $image->is_primary,
                'created' => awpcp_datetime(),
            );

            $this->db->insert( AWPCP_TABLE_MEDIA, $entry );
        }

        update_option( 'awpcp-migrate-media-information-cursor', $cursor );
        $remaining = $this->count_pending_images( $cursor );

        return array( $total, $remaining );
    }

    protected function photos_table_exists() {
        return awpcp_table_exists( AWPCP_TABLE_ADPHOTOS );
    }

    private function count_pending_images($cursor) {
        $sql = 'SELECT count(key_id) FROM ' . AWPCP_TABLE_ADPHOTOS . '  ';
        $sql.= 'WHERE ad_id > %d ORDER BY key_id LIMIT 0, 100';

        return intval( $this->db->get_var( $this->db->prepare( $sql, $cursor ) ) );
    }
}
