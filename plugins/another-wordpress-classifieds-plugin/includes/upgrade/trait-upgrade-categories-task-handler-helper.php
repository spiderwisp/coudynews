<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A collection of common methods used on upgrade routines created to
 * convert pre-4.0.0 categories into custom taxonomy terms.
 *
 * @since 4.0.0
 */
trait AWPCP_UpgradeCategoriesTaskHandlerHelper {

    /**
     * Returns the greatest term_id currently stored in the terms table.
     *
     * @since 4.0.0
     */
    private function get_max_term_id() {
        return intval( $this->db->get_var( "SELECT MAX(term_id) FROM {$this->db->terms}" ) );
    }

    /**
     * Insert a new term, forcing the ID of the record to be $term_id if
     * necessary.
     *
     * If the database already includes a term with ID greater or equal than
     * $term_id - 1 we let the database server set the ID of the new record.
     *
     * @since 4.0.0
     */
    private function maybe_insert_term_with_id( $term_id, $term_data ) {
        $args = [
            'slug'        => $term_data['slug'],
            'parent'      => $term_data['parent'],
            'description' => $term_data['description'],
        ];

        if ( $this->get_max_term_id() < ( $term_id - 1 ) ) {
            return $this->insert_term_with_id( $term_id, $term_data['name'], $term_data['taxonomy'], $args );
        }

        return $this->wordpress->insert_term( $term_data['name'], $term_data['taxonomy'], $args );
    }

    /**
     * Inserts a new term using wp_insert_term_data to specify the ID of the
     * term, bypassing the AUTO_INCREMENT counter.
     *
     * @since 4.0.0
     */
    public function insert_term_with_id( $term_id, $term, $taxonomy, $args ) {
        $force_term_id = function( $data ) use ( $term_id ) {
            $data['term_id'] = $term_id;

            return $data;
        };

        add_filter( 'wp_insert_term_data', $force_term_id );

        $result = $this->wordpress->insert_term( $term, $taxonomy, $args );

        remove_filter( 'wp_insert_term_data', $force_term_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // wp_insert_term() likely returned the term_id (and term_taxonomy_id) from
        // an existing term as it found out that the term we attempted to create was
        // a duplicate.
        //
        // See https://github.com/WordPress/WordPress/blob/da7a80d67fea29c2badfc538bfc01c8a585f0cbe/wp-includes/taxonomy.php#L2326.
        //
        // This is very unlikely to happen, but I only want to mess with the term_taxonomy
        // table below if a new record was added to the database. When the returned ID
        // is not what we asked it to be, we know for sure that information
        // from a different record was returned.
        //
        // Additionally, plugins will be able to control the returned IDs on 5.0.1
        // and above, but that also seems very unlikely to happen considering we plan
        // to force the ID for new terms during the 4.0.0 upgrade only. At that
        // time no other plugins should be aware of our taxonomy.
        //
        // See https://github.com/WordPress/WordPress/commit/8142df82bcfa9201f0bb48499f89e5d2e957697.
        if ( $term_id !== $result['term_id'] ) {
            return $result;
        }

        // We want both IDs to be equal, as it should be on most installations that
        // started using WordPress after the split shared terms update (WP 4.2).
        //
        // If term_id is less than term_taxonomy_id then the IDs were already different
        // before we started adding our own terms and we won't change that.
        //
        // If term_id is greater than term_taxonomy_id then we are likely to be
        // the reason those IDs are different and we want to fix it, even if some
        // IDs are lost in the process.
        if ( $result['term_id'] <= $result['term_taxonomy_id'] ) {
            return $result;
        }

        return $this->force_term_taxonomy_id( $result, $taxonomy );
    }

    /**
     * Updates a record on the term_taxonomy table to make sure the term_taxonomy_id
     * matches the term_id of the corresponding record on terms table.
     *
     * @since 4.0.0
     * @throws AWPCP_Exception  If the new term_taxonomy record can't be created.
     */
    private function force_term_taxonomy_id( $current_term_data, $taxonomy ) {
        $term_object = get_term( $current_term_data['term_id'], $taxonomy );

        // We remove the original record to avoid triggering the UNIQUE restriction
        // on term_id + taxonomy columns when we try to insert the new record.
        $term_taxonomy_deleted = $this->db->delete(
            $this->db->term_taxonomy,
            [
                'term_taxonomy_id' => $current_term_data['term_taxonomy_id'],
            ]
        );

        // The row wasn't deleted. Let's use the IDs that we received from wp_insert_term().
        if ( false === $term_taxonomy_deleted ) {
            return $current_term_data;
        }

        $term_taxonomy_inserted = $this->db->insert(
            $this->db->term_taxonomy,
            [
                'term_id'          => $term_object->term_id,
                'term_taxonomy_id' => $term_object->term_id,
                'taxonomy'         => $term_object->taxonomy,
                'description'      => $term_object->description,
                'parent'           => $term_object->parent,
                'count'            => $term_object->count,
            ]
        );

        // This is bad. Now the new term is not connected with a record on term_taxonomy.
        if ( false === $term_taxonomy_inserted ) {
            $message = 'There was an error trying to create a record on term_taxonomy table using a custom ID: {error_message}';
            $message = str_replace( '{error_message}', $this->db->last_error, $message );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        return [
            'term_id'          => $current_term_data['term_id'],
            'term_taxonomy_id' => intval( $this->db->insert_id ),
        ];
    }
}
