<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for Upgrade Task Handlers used to update categories associations
 * with objects from premium modules.
 */
abstract class AWPCP_Update_Categories_Task_Runner implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var object
     */
    protected $delegate;

    /**
     * @var AWPCP_Categories_Registry
     */
    protected $categories;

    /**
     * @var AWPCP_WordPress
     */
    protected $wordpress;

    /**
     * @var wpdb
     */
    protected $db;

    public function __construct( $delegate, $categories, $wordpress, $db ) {
        $this->delegate   = $delegate;
        $this->categories = $categories;
        $this->wordpress  = $wordpress;
        $this->db         = $db;
    }

    public function count_pending_items( $last_item_id ) {
        $sql = $this->delegate->get_count_pending_items_sql();
        return intval( $this->db->get_var( $this->db->prepare( $sql, $last_item_id ) ) );
    }

    public function get_pending_items( $last_item_id ) {
        $sql = $this->delegate->get_pending_items_sql();
        return $this->db->get_results( $this->db->prepare( $sql, $last_item_id ) );
    }

    public function process_item( $item, $last_item_id ) {
        $categories_translations = $this->get_categories_translations();

        $old_categories = $this->delegate->get_item_categories( $item );
        $new_categories = array();

        foreach ( $old_categories as $category ) {
            if ( isset( $categories_translations[ $category ] ) ) {
                $new_categories[] = $categories_translations[ $category ];
            } else {
                $new_categories[] = $category;
            }
        }

        if ( ! empty( $new_categories ) ) {
            $this->delegate->update_item_categories( $item, $new_categories );
        }

        return $this->delegate->get_item_id( $item );
    }

    /**
     * Subclasses should use this method to return an array with outdated
     * categories IDs as keys (pre-4.0.0 or conflciting IDs for example) and
     * the new term IDs as the corresponding values.
     *
     * @since 4.0.0
     */
    abstract protected function get_categories_translations();
}
