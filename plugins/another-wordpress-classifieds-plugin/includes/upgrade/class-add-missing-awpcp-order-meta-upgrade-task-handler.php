<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to add the `_awpcp_is_paid` meta to ads that don't have it.
 */
class AWPCP_AddMissingCategoriesOrder implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var string
     */
    private $taxonomy;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @param string          $taxonomy taxonomy name.
     * @param AWPCP_WordPress $wordpress AWPCP_WordPress.
     *
     * @since 4.0.0
     */
    public function __construct( $taxonomy, $wordpress ) {
        $this->taxonomy  = $taxonomy;
        $this->wordpress = $wordpress;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        $query_vars = $this->prepare_query_vars();

        $terms = get_terms( $query_vars );

        return count( $terms );
    }

    /**
     * Add common query vars for counting and finding items that need to be
     * processed by this routine.
     *
     * @since 4.0.0
     */
    private function prepare_query_vars( $query_vars = null ) {
        $query_vars['taxonomy']     = $this->taxonomy;
        $query_vars['hide_empty']   = false;
        $query_vars['meta_key']     = '_awpcp_order';
        $query_vars['meta_compare'] = 'NOT EXISTS';

        return $query_vars;
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        $query_vars = $this->prepare_query_vars(
            [ 'number' => 50 ]
        );

        $terms = get_terms( $query_vars );
        return $terms;
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        $this->wordpress->update_term_meta( $item->term_id, '_awpcp_order', 0 );
        return $item;
    }
}
