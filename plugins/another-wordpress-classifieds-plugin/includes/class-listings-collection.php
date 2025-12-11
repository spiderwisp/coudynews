<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.3
 */
function awpcp_listings_collection() {
    return awpcp()->container['ListingsCollection'];
}

/**
 * @since 3.2.2
 */
class AWPCP_ListingsCollection {

    /**
     * @var WP_Query    A reference to the most recent WP_Query used to retrieve
     *                  or count listings.
     */
    private $last_query;

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @var AWPCP_RolesAndCapabilities $roles
     */
    private $roles;

    public function __construct( $listing_post_type, $wordpress, $roles ) {
        $this->listing_post_type = $listing_post_type;
        $this->wordpress         = $wordpress;
        $this->roles             = $roles;
    }

    /**
     * Get the listing identified by the given ID.
     *
     * If you plan to rewrite this method to use find_listings() please note
     * that doing so could cause get() query vars to be modified by modules
     * that define handlers for the {@see 'awpcp-find-listings-query'} filter.
     *
     * The Restricted Categories module, for example, modifies query vars to
     * exclude listings from all categories marked as restricted. If the same
     * restrictions are used for queries that should return a single listing,
     * listings on restricted categories may become unreachable to this class.
     *
     * @since 3.3
     * @since 4.0.0 works with custom post types.
     *
     * @param int $listing_id A listing ID.
     *
     * @return object An instance of WP_Post representing a listing.
     * @throws AWPCP_Exception If no listing is found with the specified ID.
     */
    public function get( $listing_id ) {
        if ( $listing_id <= 0 ) {
            /* translators: %d is the ID used to search. */
            $message = __( 'The listing ID must be a positive integer, %d was given.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing_id ) ) );
        }

        $listing = $this->wordpress->get_post( $listing_id );

        if ( empty( $listing ) ) {
            /* translators: %d is the ID used to search. */
            $message = __( 'No Listing was found with id: %d.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing_id ) ) );
        }

        if ( $listing->post_type !== $this->listing_post_type ) {
            /* translators: %d is the ID used to search. */
            $message = __( 'No Listing was found with id: %d.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing_id ) ) );
        }

        return $listing;
    }

    /**
     * We need to support OLD listing's IDs for a while, in order to
     * maintain old URLs working.
     *
     * @param int $listing_id   A previous listing ID.
     * @throws AWPCP_Exception  If no listing is found with the specified ID.
     * @since 4.0.0
     */
    public function get_listing_with_old_id( $listing_id ) {
        $listings = $this->find_listings(
            [
                'classifieds_query' => [
                    'previous_id' => $listing_id,
                ],
            ]
        );

        if ( empty( $listings ) ) {
            /* translators: %d is the ID used to search. */
            $message = __( 'No Listing was found with old id: %d.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing_id ) ) );
        }

        $listing = $listings[0];

        if ( $listing->post_type !== $this->listing_post_type ) {
            /* translators: %d is the ID used to search. */
            $message = __( 'No Listing was found with old id: %d.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( sprintf( $message, $listing_id ) ) );
        }

        return $listing;
    }

    /**
     * @param array $identifiers    An array of classifieds IDs.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_all_by_id( $identifiers ) {
        $identifiers = array_filter( array_map( 'intval', $identifiers ) );

        if ( empty( $identifiers ) ) {
            return array();
        }

        return $this->find_listings( array( 'post__in' => $identifiers ) );
    }

    /**
     * @param array $query  An array of query vars.
     * @since 4.0.0
     */
    public function find_listings( $query = array() ) {
        $query = $this->add_orderby_query_parameters( $query );

        $posts = $this->query_posts( apply_filters( 'awpcp-find-listings-query', $query ) );

        return apply_filters( 'awpcp-find-listings', $posts, $query );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    private function query_posts( $query_vars ) {
        if ( ! isset( $query_vars['classifieds_query'] ) ) {
            $query_vars['classifieds_query'] = array();
        }

        $this->last_query = $this->wordpress->create_posts_query( $query_vars );

        return $this->last_query->posts;
    }

    /**
     * Returns a reference to the most recent instance of WP_Query used to retrieve
     * or count listings.
     *
     * @since 4.0.0
     */
    public function get_last_query() {
        return $this->last_query;
    }

    /**
     * @param array $query_vars An array of query vars.
     * @since 3.3
     */
    public function count_listings( $query_vars = [] ) {
        $query_vars = apply_filters( 'awpcp-find-listings-query', $query_vars );

        /**
         * Filter the query vars used to count listings.
         *
         * @since 4.0.6
         */
        $query_vars = apply_filters( 'awpcp_count_listings_query', $query_vars );

        return $this->count_posts( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    private function count_posts( $query_vars ) {
        if ( ! isset( $query_vars['classifieds_query'] ) ) {
            $query_vars['classifieds_query'] = array();
        }

        if ( is_admin() && ! $this->roles->current_user_is_moderator() ) {
            $query_vars['author'] = get_current_user_id();
        }

        $this->last_query = $this->wordpress->create_posts_query( $query_vars );

        return intval( $this->last_query->found_posts );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_valid_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_valid'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_valid_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_valid'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function count_new_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_new'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function find_expired_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_expired'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function count_expired_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_expired'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_enabled_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_enabled'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_enabled_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_enabled'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_disabled_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_disabled'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_disabled_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_disabled'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_listings_about_to_expire( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_about_to_expire'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_listings_awaiting_approval( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_awaiting_approval'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_listings_awaiting_approval( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_awaiting_approval'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_listings_with_images_awaiting_approval( $query_vars = array() ) {
        $query_vars['classifieds_query']['has_images_awaiting_approval'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_successfully_paid_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_successfully_paid'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_successfully_paid_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_successfully_paid'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_listings_awaiting_verification( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_awaiting_verification'] = true;

        return $this->find_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_listings_awaiting_verification( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_awaiting_verification'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_featured_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_featured'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_flagged_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_flagged'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param array $query_vars     The full list of query vars.
     * @since 4.0.0
     */
    public function count_incomplete_listings( $query_vars = array() ) {
        $query_vars['classifieds_query']['is_incomplete'] = true;

        return $this->count_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_user_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->find_valid_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_user_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->count_valid_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_user_enabled_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->find_enabled_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_user_enabled_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->count_enabled_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function find_user_disabled_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->find_disabled_listings( $query_vars );
    }

    /**
     * @param int   $user_id        The ID of a user.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3
     * @since 4.0.0     Uses Classifieds Query Integration.
     */
    public function count_user_disabled_listings( $user_id, $query_vars = array() ) {
        $query_vars['author'] = $user_id;

        return $this->count_disabled_listings( $query_vars );
    }

    /**
     * @param int   $category_id    The ID of a category.
     * @param array $query_vars     The full list of query vars.
     * @since 3.3.2
     * @since 4.0.0     Added $query_vars parameter.
     * @since 4.0.0     Use Classifieds Query Integration.
     */
    public function count_enabled_listings_in_category( $category_id, $query_vars = array() ) {
        $query_vars['classifieds_query']['category'] = intval( $category_id );

        return $this->count_enabled_listings( $query_vars );
    }

    /**
     * -------------------------------------------------------------------------
     */

    private function add_orderby_query_parameters( $query ) {
        if ( isset( $query['orderby'] ) ) {
            $query['_orderby'] = $query['orderby'];
            $orderby           = $query['orderby'];
        } else {
            $orderby = null;
        }

        if ( ! isset( $query['order'] ) ) {
            $query['order'] = 'DESC';
        }

        switch ( $orderby ) {
            case 1:
                $query['meta_key']  = '_awpcp_most_recent_start_date';
                $query['meta_type'] = 'DATETIME';
                $query['orderby']   = array( 'meta_value' => 'DESC' );
                break;

            case 2:
                $query['orderby'] = array( 'title' => 'ASC' );
                break;

            case 3:
                $query['orderby']     = 'menu_order';
                $query['_meta_order'] = array(
                    '_awpcp_is_paid'                => 'DESC',
                    '_awpcp_most_recent_start_date' => 'DESC',
                );
                $query['_meta_type']  = array(
                    '_awpcp_is_paid'                => 'SIGNED',
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_is_paid',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 4:
                $query['meta_key']  = '_awpcp_is_paid';
                $query['meta_type'] = 'SIGNED';
                $query['orderby']   = array(
                    'meta_value' => 'DESC',
                    'title'      => 'ASC',
                );
                break;

            case 5:
                $query['meta_key']  = '_awpcp_views';
                $query['meta_type'] = 'SIGNED';
                $query['orderby']   = array(
                    'meta_value' => 'DESC',
                    'title'      => 'ASC',
                );
                break;

            case 6:
                $query['orderby']     = 'menu_order';
                $query['_meta_order'] = array(
                    '_awpcp_views'                  => 'DESC',
                    '_awpcp_most_recent_start_date' => 'DESC',
                );
                $query['_meta_type']  = array(
                    '_awpcp_views'                  => 'SIGNED',
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_views',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 7:
                $query['orderby']     = 'menu_order';
                $query['_meta_order'] = array(
                    '_awpcp_price'                  => 'DESC',
                    '_awpcp_most_recent_start_date' => 'DESC',
                );
                $query['_meta_type']  = array(
                    '_awpcp_price'                  => 'SIGNED',
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_price',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 8:
                $query['orderby']     = 'menu_order';
                $query['_meta_order'] = array(
                    '_awpcp_price'                  => 'ASC',
                    '_awpcp_most_recent_start_date' => 'DESC',
                );
                $query['_meta_type']  = array(
                    '_awpcp_price'                  => 'SIGNED',
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_price',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 9:
                $query['meta_key']  = '_awpcp_most_recent_start_date';
                $query['meta_type'] = 'DATETIME';
                $query['orderby']   = array( 'meta_value' => 'ASC' );
                break;

            case 10:
                $query['orderby'] = array( 'title' => 'DESC' );
                break;

            case 11:
                $query['meta_key']  = '_awpcp_views';
                $query['meta_type'] = 'SIGNED';
                $query['orderby']   = array(
                    'meta_value' => 'ASC',
                    'title'      => 'ASC',
                );
                break;

            case 12:
                $query['orderby']     = 'menu_order';
                $query['_meta_order'] = array(
                    '_awpcp_views'                  => 'ASC',
                    '_awpcp_most_recent_start_date' => 'ASC',
                );
                $query['_meta_type']  = array(
                    '_awpcp_views'                  => 'SIGNED',
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_views',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 'title':
                $query['orderby'] = array( 'title' => $query['order'] );
                break;

            case 'start-date':
                $query['meta_key']  = '_awpcp_start_date';
                $query['meta_type'] = 'DATETIME';
                $query['orderby']   = array( 'meta_value' => $query['order'] );
                break;

            case 'end-date':
                $query['meta_key']  = '_awpcp_start_date';
                $query['meta_type'] = 'DATETIME';
                $query['orderby']   = array( 'meta_value' => $query['order'] );
                break;

            case 'renewed-date':
                $query['orderby']     = array(
                    'menu_order' => 'DESC',
                    'ID'         => $query['order'],
                );
                $query['_meta_order'] = array(
                    '_awpcp_most_recent_start_date' => $query['order'],
                    '_awpcp_renewed_date'           => $query['order'],
                );
                $query['_meta_type']  = array(
                    '_awpcp_most_recent_start_date' => 'DATETIME',
                    '_awpcp_renewed_date'           => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_most_recent_start_date',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_renewed_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 'status':
                $query['meta_key']      = '_awpcp_start_date';
                $query['orderby']       = array(
                    'menu_order' => 'DESC',
                    'meta_value' => $query['order'],
                    'ID'         => $query['order'],
                );
                $query['_custom_order'] = array( 'post_status' => $query['order'] );
                break;

            case 'payment-term':
                $query['orderby']     = array(
                    'menu_order' => 'DESC',
                    'ID'         => $query['order'],
                );
                $query['_meta_order'] = array(
                    '_awpcp_payment_term_id' => $query['order'],
                    '_awpcp_start_date'      => $query['order'],
                );
                $query['_meta_type']  = array(
                    '_awpcp_payment_term_id' => 'SIGNED',
                    '_awpcp_start_date'      => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_payment_term_id',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 'payment-status':
                $query['orderby']     = array(
                    'menu_order' => 'DESC',
                    'ID'         => $query['order'],
                );
                $query['_meta_order'] = array(
                    '_awpcp_payment_status' => $query['order'],
                    '_awpcp_start_date'     => $query['order'],
                );
                $query['_meta_type']  = array(
                    '_awpcp_payment_status' => 'CHAR',
                    '_awpcp_start_date'     => 'DATETIME',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_payment_status',
                    'compare' => 'EXISTS',
                );

                $query['meta_query'][] = array(
                    'key'     => '_awpcp_start_date',
                    'compare' => 'EXISTS',
                );
                break;

            case 'owner':
                $query['meta_key']  = '_awpcp_start_date';
                $query['meta_type'] = 'DATETIME';
                $query['orderby']   = array(
                    'author'     => $query['order'],
                    'meta_value' => $query['order'],
                    'ID'         => $query['order'],
                );
                break;

            case 'random':
                $query['orderby'] = 'rand';
                break;

            case 'id':
            case 'ID':
                $query['orderby'] = 'ID';
                break;

            default:
                // XXX: This default case makes it impossible to use other orderby values
                // supported by WP_Query. Perhaps we should set a default orderby when
                // no value is provided only.
                $query['orderby'] = array(
                    'post_date' => 'DESC',
                    'title'     => 'ASC',
                );
                break;
        }

        // TODO: run 'awpcp-ad-order-conditions' and 'awpcp-find-listings-order-conditions' filters?
        // I think is better to remove these filters and let modules filter the query before is executed.
        return $query;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @deprecated 4.0.0    Use ListingsCollection::count_expired_listings() instead.
     */
    public function count_expired_listings_with_query( $query_vars ) {
        return $this->count_expired_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @deprecated 4.0.0    Use ListingsCollection::count_listings_awaiting_approval() instead.
     */
    public function count_listings_awaiting_approval_with_query( $query_vars ) {
        return $this->count_listings_awaiting_approval( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @deprecated 4.0.0    Use ListingsCollection::count_valid_listings() instead.
     */
    public function count_valid_listings_with_query( $query_vars ) {
        return $this->count_valid_listings( $query_vars );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @deprecated 4.0.0    Use ListingsCollection::count_listings() instead.
     */
    public function count_listings_with_query( $query_vars ) {
        return $this->count_listings( $query_vars );
    }
}
