<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * During the upgrade to AWPCP 4.0.0, some posts were created with an ID equal to the ID
 * of one of the records on the custom ads table. In order to disambiguate those IDs,
 * some posts were replaced by new posts having an ID greater than the greatest ID
 * in the custom ads table.
 *
 * The listings registry is a translation table between AWPCP posts created by
 * the first upgrade routine, and replacement posts created later to fix the
 * collision of IDs.
 */
class AWPCP_ListingsRegistry {

    /**
     * @var AWPCP_ArrayOptions
     */
    protected $array_options;

    /**
     * @since 4.0.0
     */
    public function __construct( $array_options ) {
        $this->array_options = $array_options;
    }

    /**
     * Returns a translation array between AWPCP posts (keys) and the ID of
     * their replacements (values).
     *
     * @since 4.0.0
     */
    public function get_listings_registry() {
        return $this->array_options->get_array_option( 'awpcp_listing_replacements_for_id_collision_fix' );
    }

    /**
     * @since 4.0.0
     */
    public function update_listings_registry( $old_post_id, $new_post_id ) {
        $this->array_options->update_array_option(
            'awpcp_listing_replacements_for_id_collision_fix',
            $old_post_id,
            $new_post_id
        );
    }
}
