<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to replace listing category taxonomy terms that have an ID
 * matching the ID from one of the pre-4.0.0 categories, causing that listing
 * category taxonomy term to be inaccessible because, in order to maintain
 * backwards compatiblity, the plugin always assumes the user is trying to see
 * the pre-4.0.0 category.
 *
 * @since 4.0.0beta2
 */
class AWPCP_FixIDCollisionForListingCategoriesUpgradeTaskHandler implements AWPCP_Upgrade_Task_Runner {

    use AWPCP_UpgradeCategoriesTaskHandlerHelper;

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var AWPCP_Categories_Registry
     */
    private $categories_registry;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @var object
     */
    private $db;

    /**
     * Constructor.
     */
    public function __construct( $listing_category_taxonomy, $categories_registry, $wordpress, $db ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->categories_registry       = $categories_registry;
        $this->wordpress                 = $wordpress;
        $this->db                        = $db;
    }

    /**
     * Count number items that need to be processed.
     *
     * @param int $last_item_id     The ID of the last item processed by the routine.
     */
    public function count_pending_items( $last_item_id ) {
        $collisions = $this->categories_registry->get_id_collisions();

        return count( $collisions );
    }

    /**
     * Get items that need to be processed.
     *
     * @param int $last_item_id     The ID of the last item processed by the routine.
     */
    public function get_pending_items( $last_item_id ) {
        $collisions = $this->categories_registry->get_id_collisions();

        return array_slice( $collisions, 0, 50 );
    }

    /**
     * @param object $item          An item to process.
     * @param int    $last_item_id  The ID of the last item processed by the routine.
     * @throws AWPCP_Exception  If the necessary terms cannot be created.
     */
    public function process_item( $item, $last_item_id ) {
        $categories_registry = $this->categories_registry->get_categories_registry();
        $current_category_id = intval( $item );
        $legacy_category_id  = array_search( $current_category_id, $categories_registry, true );
        $current_category    = get_term_by( 'id', $current_category_id, $this->listing_category_taxonomy );

        if ( ! $current_category ) {
            $this->categories_registry->delete_category_from_registry( $legacy_category_id );

            return $last_item_id;
        }

        $new_term = $this->create_replacement_term( $current_category, $categories_registry );

        if ( is_wp_error( $new_term ) ) {
            $message = 'An error occurred trying to create additional terms to replace category with ID = {category_id}: {error_message}';
            $message = str_replace( '{category_id}', $current_category_id, $message );
            $message = str_replace( '{error_message}', $new_term->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $this->replace_term( $current_category, $new_term['term_id'] );

        $this->categories_registry->update_categories_registry( $legacy_category_id, $new_term['term_id'] );
        $this->categories_registry->update_categories_replacements( $current_category_id, $new_term['term_id'] );

        return $last_item_id;
    }

    /**
     * Creates a new category term making sure its ID is greater than any of the IDs
     * used by the categories that were stored on a custom table before 4.0.0.
     *
     * @since 4.0.0
     */
    private function create_replacement_term( $current_category, $categories_registry ) {
        $max_legacy_category_id = max( array_keys( $categories_registry ) );
        $wanted_term_id         = $max_legacy_category_id + 1;

        $term_data = [
            'slug'        => null,
            'name'        => $current_category->name . ' (' . wp_rand() . ')',
            'parent'      => $current_category->parent,
            'description' => $current_category->term_id,
            'taxonomy'    => $this->listing_category_taxonomy,
        ];

        return $this->maybe_insert_term_with_id( $wanted_term_id, $term_data );
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If the necessary terms cannot be created.
     */
    private function replace_term( $current_category, $new_category_id ) {
        // When the existing term is deleted, WordPress will set the parent of all
        // the children terms to the parent of the deleted term.
        //
        // We make the existing term a child of the new term so that after the existing
        // term is deleted and the new term is modified the hierarchy is not changed.
        $result = $this->wordpress->update_term(
            $current_category->term_id,
            $this->listing_category_taxonomy,
            [
                'parent' => $new_category_id,
            ]
        );

        if ( is_wp_error( $result ) ) {
            $message = 'An error occurred trying to update the parent of existing category with ID = {category_id}: {error_message}';
            $message = str_replace( '{category_id}', $current_category->term_id, $message );
            $message = str_replace( '{error_message}', $result->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $result = $this->wordpress->delete_term(
            $current_category->term_id,
            $this->listing_category_taxonomy,
            [
                'default'       => $new_category_id,
                'force_default' => true,
            ]
        );

        if ( is_wp_error( $result ) ) {
            $message = 'An error occurred trying to delete current category with ID = {category_id}: {error_message}';
            $message = str_replace( '{category_id}', $current_category->term_id, $message );
            $message = str_replace( '{error_message}', $result->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $result = $this->wordpress->update_term(
            $new_category_id,
            $this->listing_category_taxonomy,
            [
                'name' => $current_category->name,
                'slug' => $current_category->slug,
            ]
        );

        if ( is_wp_error( $result ) ) {
            $message = 'An error occurred trying to update the replacement term with ID {category_id}: {error_message}';
            $message = str_replace( '{category_id}', $new_category_id, $message );
            $message = str_replace( '{error_message}', $result->get_error_message(), $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }
    }
}
