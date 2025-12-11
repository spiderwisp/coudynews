<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to force the ID of the next Post to be greater than the
 * maximum ID of ads stored in the custom table.
 *
 * @since 4.0.0beta8
 */
class AWPCP_MaybeForcePostIDUpgradeTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var AWPCP_WordPress
     */
    private $wordpress;

    /**
     * @var wpdb
     */
    private $db;

    use AWPCP_UpgradeListingsTaskHandlerHelper;

    /**
     * @since 4.0.0
     */
    public function __construct( $wordpress, $db ) {
        $this->wordpress = $wordpress;
        $this->db        = $db;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        if ( $this->wordpress->get_option( 'awpcp_mfpi_maybe_force_post_id' ) ) {
            return 1;
        }

        return 0;
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        if ( $this->wordpress->get_option( 'awpcp_mfpi_maybe_force_post_id' ) ) {
            return [ (object) [] ];
        }

        return [];
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception If a post with a greater ID cannot be created.
     */
    public function process_item( $item, $last_item_id ) {
        $max_legacy_post_id = $this->get_max_legacy_post_id();
        $max_post_id        = $this->get_max_post_id();

        if ( $max_post_id >= $max_legacy_post_id ) {
            $this->wordpress->delete_option( 'awpcp_mfpi_maybe_force_post_id' );

            return;
        }

        $post_data = [
            'post_title' => 'AWPCP Test Post',
        ];

        $post_id = $this->insert_post_with_id( $max_legacy_post_id, $post_data );

        if ( is_wp_error( $post_id ) ) {
            $message = __( 'There was an error trying to force the next post_id to be greater than {post_id}. {error_message}', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{post_id}', $max_legacy_post_id, $message );
            $message = str_replace( '{error_message}', $post_id->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        if ( $this->wordpress->delete_post( $post_id ) ) {
            $this->wordpress->delete_option( 'awpcp_mfpi_maybe_force_post_id' );
        }
    }
}
