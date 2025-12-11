<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to replace listing terms that have an ID matching the ID
 * from one of the pre-4.0.0 listings, causing that listing to be inaccessible
 * because, in order to maintain backwards compatiblity, the plugin always
 * assumes the user is trying to see the pre-4.0.0 listing.
 *
 * @since 4.0.0beta2
 */
class AWPCP_FixIDCollisionForListingsUpgradeTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var AWPCP_ListingsRegistry
     */
    private $listings_registry;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

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
    public function __construct( $listings_registry, $listings, $wordpress, $db ) {
        $this->listings_registry = $listings_registry;
        $this->listings          = $listings;
        $this->wordpress         = $wordpress;
        $this->db                = $db;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        return $this->execute_query_for_posts_with_id_greater_than(
            $last_item_id,
            function () {
                return $this->listings->count_listings( $this->get_pending_items_query_vars() );
            }
        );
    }

    /**
     * This method should probably be renamed to ....with_id_less_than(), since
     * it tries to run a query over existing listings without counting new
     * listings that are added to the database by this routine.
     *
     * @since 4.0.0
     */
    private function execute_query_for_posts_with_id_greater_than( $last_item_id, $query ) {
        if ( 0 === $last_item_id ) {
            $last_item_id = $this->get_max_legacy_post_id();
        }

        $filter_by_id = function( $where ) use ( $last_item_id ) {
            return "$where AND {$this->db->posts}.ID < $last_item_id";
        };

        add_filter( 'posts_where', $filter_by_id );

        $result = $query();

        remove_filter( 'posts_where', $filter_by_id );

        return $result;
    }

    /**
     * @since 4.0.0
     */
    private function get_pending_items_query_vars() {
        return [
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'posts_per_page' => 10,
        ];
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        return $this->execute_query_for_posts_with_id_greater_than(
            $last_item_id,
            function() {
                return $this->listings->find_listings( $this->get_pending_items_query_vars() );
            }
        );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        if ( $this->post_has_conflicting_id( $item ) ) {
            $this->replace_post( $item );
        }

        return $item->ID;
    }

    /**
     * @since 4.0.0
     */
    private function post_has_conflicting_id( $item ) {
        $sql = 'SELECT * FROM ' . AWPCP_TABLE_ADS . ' WHERE ad_id = %d';

        $listing = $this->db->get_row( $this->db->prepare( $sql, $item->ID ) );

        return is_object( $listing );
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception If a replacement post cannot be created or modified.
     */
    private function replace_post( $post ) {
        $max_legacy_post_id = $this->get_max_legacy_post_id();
        $wanted_post_id     = $max_legacy_post_id + 1;
        $post_data          = [
            'post_title' => $post->post_title,
        ];

        $old_post_id = $post->ID;
        $new_post_id = $this->maybe_insert_post_with_id( $wanted_post_id, $post_data );

        if ( is_wp_error( $new_post_id ) ) {
            $message = __( 'There was an error trying to create a replacement post for post with ID equal to {post_id}. {error_message}', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{post_id}', $old_post_id, $message );
            $message = str_replace( '{error_message}', $new_post_id->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        // Cast the old WP_Post to stdClass and set the ID property to the ID
        // of the newly created post.
        $post_data = (object) array_merge( (array) $post, [ 'ID' => $new_post_id ] );

        $new_post_id = $this->wordpress->update_post( $post_data, true );

        if ( is_wp_error( $new_post_id ) ) {
            $message = __( 'There was an error trying to update the replacement post ({replacement_post_id}) for post with ID equal to {post_id}. {error_message}', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{replacement_post_id}', $post_data->ID, $message );
            $message = str_replace( '{post_id}', $old_post_id, $message );
            $message = str_replace( '{error_message}', $new_post_id->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $sql  = "UPDATE {$this->db->term_relationships} ";
        $sql .= 'SET object_id = %d ';
        $sql .= 'WHERE object_id = %d ';

        $this->db->query( $this->db->prepare( $sql, $new_post_id, $old_post_id ) );

        $sql  = "UPDATE {$this->db->postmeta} ";
        $sql .= 'SET post_id = %d ';
        $sql .= 'WHERE post_id = %d ';

        $this->db->query( $this->db->prepare( $sql, $new_post_id, $old_post_id ) );

        $old_post = $this->wordpress->delete_post( $old_post_id, true );

        if ( $old_post ) {
            $this->listings_registry->update_listings_registry( $old_post_id, $new_post_id );
        }

        return $old_post;
    }
}
