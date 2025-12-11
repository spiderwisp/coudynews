<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Search listings by their user.
 */
class AWPCP_UserListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'User', 'listings table search', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $users_query = new WP_User_Query( array(
            'fields' => 'ID',
            'search' => '*' . $query->query_vars['s'] . '*',
        ) );

        unset( $query->query_vars['s'] );

        if ( empty( $users_query->results ) ) {
            $query->query_vars['author'] = array( 0 );
            return;
        }

        $query->query_vars['author__in'] = $users_query->results;
    }
}
