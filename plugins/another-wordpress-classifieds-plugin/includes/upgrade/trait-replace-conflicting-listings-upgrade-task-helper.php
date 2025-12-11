<?php
/**
 * @package AWPCP/Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common code for upgrade routines that update references to the ID
 * of conflicting AWPCP post object in custom tables or options.
 */
trait AWPCP_ReplaceConflictingListingsUpgradeTaskHelper {

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        $replacements = $this->listings_registry->get_listings_registry();
        $replacements = array_slice( $replacements, $last_item_id, null );

        return count( $replacements );
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        $replacements = $this->listings_registry->get_listings_registry();

        return array_slice( array_keys( $replacements ), $last_item_id, 20 );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        $replacements = $this->listings_registry->get_listings_registry();

        if ( ! isset( $replacements[ $item ] ) ) {
            return $last_item_id + 1;
        }

        $old_listing_id = $item;
        $new_listing_id = $replacements[ $item ];

        $this->update_records( $old_listing_id, $new_listing_id );

        return $last_item_id + 1;
    }

    /**
     * @since 4.0.0
     */
    protected function update_records( $old_listing_id, $new_listing_id ) {
    }
}
