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
class AWPCP_AddMissingPhoneDigits implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @param AWPCP_WordPress $wordpress AWPCP_WordPress.
     *
     * @since 4.0.0
     */
    public function __construct( $wordpress ) {
        $this->wordpress = $wordpress;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        $query_vars = $this->prepare_query_vars();

        $terms = get_posts( $query_vars );

        return count( $terms );
    }

    /**
     * Add common query vars for counting and finding items that need to be
     * processed by this routine.
     *
     * @since 4.0.0
     */
    private function prepare_query_vars( $query_vars = null ) {
        $query_vars['hide_empty']   = false;
        $query_vars['post_type']    = AWPCP_LISTING_POST_TYPE;
        $query_vars['meta_key']     = '_awpcp_contact_phone_number_digits';
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

        $posts = get_posts( $query_vars );
        return $posts;
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        $contact_phone = $this->wordpress->get_post_meta( $item->ID, '_awpcp_contact_phone', true );
        $contact_phone = awpcp_get_digits_from_string( $contact_phone );
        $this->wordpress->update_post_meta( $item->ID, '_awpcp_contact_phone_number_digits', $contact_phone );
        return $item;
    }
}
