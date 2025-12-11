<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class that integrates with WP_Query.
 */
class AWPCP_QueryIntegration {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var string
     */
    private $categories_taxonomy;

    /**
     * @var object
     */
    private $settings;

    /**
     * @var object
     */
    private $db;

    /**
     * @param string $listing_post_type     The identifier for the Listings post type.
     * @param string $categories_taxonomy   The identifier for the Listing Category taxonomy.
     * @param object $settings              An instance of Settings API.
     * @param object $db                    An instance of wpdb.
     * @since 4.0.0
     */
    public function __construct( $listing_post_type, $categories_taxonomy, $settings, $db ) {
        $this->listing_post_type   = $listing_post_type;
        $this->categories_taxonomy = $categories_taxonomy;
        $this->settings            = $settings;
        $this->db                  = $db;
    }

    /**
     * Set the post_type query var early if the query is a Classifieds Query.
     *
     * Polylang will try to load translated ads, even when translations are not
     * enabled for ads, if the post_type query var is not properly configured
     * before PLL_Frontend::parse_query() is executed.
     *
     * @since 4.0.4
     *
     * @param WP_Query $query A new posts query.
     */
    public function parse_query( $query ) {
        if ( isset( $query->query_vars['classifieds_query'] ) ) {
            $query->query_vars['post_type'] = $this->listing_post_type;
        }
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( isset( $query->query_vars['classifieds_query'] ) ) {
            $query->query_vars = $this->process_query_vars( $query->query_vars );

            /**
             * A pre_get_posts action for queries that return classified ads only.
             *
             * @since 4.0.0
             */
            do_action( 'awpcp_pre_get_posts', $query );
        }
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    private function process_query_vars( $query_vars ) {
        $query_vars = $this->normalize_query_vars( $query_vars );
        $query_vars = $this->process_query_parameters( $query_vars );

        return $query_vars;
    }

    /**
     * TODO: Do we need to set a context for the query? Listings Collection defined
     *       context = 'default'.
     *
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function normalize_query_vars( $query_vars ) {
        $query_vars = $this->set_default_query_paramaters( $query_vars );
        $query_vars = $this->normalize_region_query_parameters( $query_vars );

        // These groups of listings must be valid listings as well.
        $must_be_valid = array(
            'is_new',
            'is_expired',
            'is_about_to_expire',
            'is_enabled',
            'is_disabled',
            'is_awaiting_approval',
            'has_images_awaiting_approval',
            'is_featured',
            'is_flagged',
        );

        if ( array_intersect( $must_be_valid, array_keys( $query_vars['classifieds_query'] ) ) ) {
            $query_vars['classifieds_query']['is_valid'] = true;
        }

        // Valid listings are listings that have been verified and paid for.
        if ( isset( $query_vars['classifieds_query']['is_valid'] ) ) {
            $query_vars['classifieds_query']['is_verified']          = true;
            $query_vars['classifieds_query']['is_successfully_paid'] = true;
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    private function set_default_query_paramaters( $query_vars ) {
        if ( ! isset( $query_vars['post_status'] ) ) {
            $query_vars['post_status'] = array( 'disabled', 'draft', 'pending', 'publish' );
        }

        if ( ! isset( $query_vars['order'] ) ) {
            $query_vars['order'] = 'DESC';
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    private function normalize_region_query_parameters( $query_vars ) {
        $regions_query = [];

        if ( isset( $query_vars['classifieds_query']['regions'] ) && is_array( $query_vars['classifieds_query']['regions'] ) ) {
            $regions_query = $this->remove_empty_regions( $query_vars['classifieds_query']['regions'] );
        }

        // The 'region' parameter can be used to find listings that are associated
        // with a region of that name, regardless of the type of the region.
        if ( ! empty( $query_vars['classifieds_query']['region'] ) ) {
            $regions_query[] = array(
                'country' => $query_vars['classifieds_query']['region'],
            );

            $regions_query[] = array(
                'state' => $query_vars['classifieds_query']['region'],
            );

            $regions_query[] = array(
                'city' => $query_vars['classifieds_query']['region'],
            );

            $regions_query[] = array(
                'county' => $query_vars['classifieds_query']['region'],
            );
        }

        $single_region = array();

        // Search for a listing associated with region hierarchy that matches
        // the given search values.
        if ( ! empty( $query_vars['classifieds_query']['country'] ) ) {
            $single_region['country'] = $query_vars['classifieds_query']['country'];
        }

        if ( ! empty( $query_vars['classifieds_query']['state'] ) ) {
            $single_region['state'] = $query_vars['classifieds_query']['state'];
        }

        if ( ! empty( $query_vars['classifieds_query']['city'] ) ) {
            $single_region['city'] = $query_vars['classifieds_query']['city'];
        }

        if ( ! empty( $query_vars['classifieds_query']['county'] ) ) {
            $single_region['county'] = $query_vars['classifieds_query']['county'];
        }

        if ( ! empty( $single_region ) ) {
            $regions_query[] = $single_region;
        }

        $query_vars['classifieds_query']['regions'] = $regions_query;

        // Remove other region parameters.
        unset( $query_vars['classifieds_query']['region'] );
        unset( $query_vars['classifieds_query']['country'] );
        unset( $query_vars['classifieds_query']['state'] );
        unset( $query_vars['classifieds_query']['city'] );
        unset( $query_vars['classifieds_query']['county'] );

        return $query_vars;
    }

    /**
     * @since 4.0.0
     */
    private function remove_empty_regions( $regions ) {
        $filtered_regions = [];

        foreach ( $regions as $region ) {
            if ( ! is_array( $region ) ) {
                continue;
            }

            $region = array_filter(
                $region,
                function( $value ) {
                    return is_array( $value ) || strlen( $value );
                }
            );

            if ( empty( $region ) ) {
                continue;
            }

            $filtered_regions[] = $region;
        }

        return $filtered_regions;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_query_parameters( $query_vars ) {
        $query_vars = $this->process_is_verified_query_parameter( $query_vars );
        $query_vars = $this->process_is_successfully_paid_query_parameter( $query_vars );
        $query_vars = $this->process_is_new_query_parameter( $query_vars );
        $query_vars = $this->process_is_disabled_query_parameter( $query_vars );
        $query_vars = $this->process_is_enabled_query_parameter( $query_vars );
        $query_vars = $this->process_is_about_to_expire_query_parameter( $query_vars );
        $query_vars = $this->process_is_expired_query_parameter( $query_vars );
        $query_vars = $this->process_is_awaiting_approval_query_parameter( $query_vars );
        $query_vars = $this->process_has_images_awaiting_approval_query_parameter( $query_vars );
        $query_vars = $this->process_is_awaiting_verification_query_parameter( $query_vars );
        $query_vars = $this->process_is_flagged_query_parameter( $query_vars );
        $query_vars = $this->process_is_incomplete_query_parameter( $query_vars );

        $query_vars = $this->process_previous_id_query_parameter( $query_vars );

        $query_vars = $this->process_category_query_parameter( $query_vars );
        $query_vars = $this->process_category__not_in_query_parameter( $query_vars );
        $query_vars = $this->process_category__exclude_children_query_parameter( $query_vars );

        // TODO: Add support for verified, have_media_awaiting_approval.
        // TODO: What other parameters are missing?
        // TODO: Remove unused methods.
        $query_vars = $this->process_contact_name_query_parameter( $query_vars );
        $query_vars = $this->process_contact_phone_query_parameter( $query_vars );
        $query_vars = $this->process_contact_email_query_parameter( $query_vars );
        $query_vars = $this->process_price_query_parameter( $query_vars );
        $query_vars = $this->process_min_price_query_parameter( $query_vars );
        $query_vars = $this->process_max_price_query_parameter( $query_vars );
        $query_vars = $this->process_payment_status_query_parameter( $query_vars );
        $query_vars = $this->process_payment_status__not_in_query_parameter( $query_vars );
        $query_vars = $this->process_payer_email_query_parameter( $query_vars );

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_verified_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_verified'] ) ) {
            // TODO: Can this be done with an EXISTS comparator? I think so.
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_verified',
                'value'   => true,
                'compare' => '=',
                'type'    => 'UNSIGNED',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_successfully_paid_query_parameter( $query_vars ) {
        if ( ! isset( $query_vars['classifieds_query']['is_successfully_paid'] ) ) {
            return $query_vars;
        }

        $payments_are_enabled = $this->settings->get_option( 'freepay' ) === 1;

        if ( ! $this->settings->get_option( 'enable-ads-pending-payment' ) && $payments_are_enabled ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_payment_status',
                'value'   => array( 'Pending', 'Unpaid' ),
                'compare' => 'NOT IN',
                'type'    => 'char',
            );

            return $query_vars;
        }

        $query_vars['meta_query'][] = array(
            'key'     => '_awpcp_payment_status',
            'value'   => 'Unpaid',
            'compare' => '!=',
            'type'    => 'char',
        );

        return $query_vars;
    }

    /**
     * TODO: Use EXISTS comparator instead.
     *
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_new_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_new'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_content_needs_review',
                'value'   => true,
                'compare' => '=',
                'type'    => 'UNSIGNED',
            );
        }

        return $query_vars;
    }

    /**
     * TODO: Is it really a good idea to use a custom post status?
     *
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_disabled_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_disabled'] ) ) {
            $query_vars['post_status'] = array( 'disabled', 'pending' );
        }

        return $query_vars;
    }

    /**
     * TODO: Consdier order conditions (See Ad::get_order_conditions,
     *       Ad::get_enabled_ads (origin/master) and groupbrowseadsby option).
     *
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_enabled_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_enabled'] ) ) {
            $query_vars['post_status'] = 'publish';

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_start_date',
                'value'   => current_time( 'mysql' ),
                'compare' => '<',
                'type'    => 'DATE',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_about_to_expire_query_parameter( $query_vars ) {
        if ( ! isset( $query_vars['classifieds_query']['is_about_to_expire'] ) ) {
            return $query_vars;
        }

        $threshold   = intval( $this->settings->get_option( 'ad-renew-email-threshold' ) );
        $target_date = strtotime( "+ $threshold days", current_time( 'timestamp' ) );

        $query_vars['meta_query'][] = array(
            'key'     => '_awpcp_end_date',
            'value'   => awpcp_datetime( 'mysql', $target_date ),
            'compare' => '<=',
            'type'    => 'DATE',
        );

        $query_vars['meta_query'][] = array(
            'key'     => '_awpcp_renew_email_sent',
            'compare' => 'NOT EXISTS',
        );

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_expired_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_expired'] ) ) {
            $query_vars['post_status'] = 'disabled';

            $query_vars['meta_query'][] = [
                'key'     => '_awpcp_expired',
                'compare' => 'EXISTS',
            ];
        }

        return $query_vars;
    }

    /**
     * TODO: Should we handle this with a single meta parameter that is removed
     *       when the classified ad no longer needs to be approved?
     *
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_awaiting_approval_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_awaiting_approval'] ) ) {
            $query_vars['post_status'] = 'disabled';

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_disabled_date',
                'compare' => 'NOT EXISTS',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_has_images_awaiting_approval_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['has_images_awaiting_approval'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_has_images_awaiting_approval',
                'compare' => 'EXISTS',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_awaiting_verification_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_awaiting_verification'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_verification_needed',
                'compare' => 'EXISTS',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_flagged_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_flagged'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_flagged',
                'compare' => 'EXISTS',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_is_incomplete_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['is_incomplete'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_payment_status',
                'value'   => 'Unpaid',
                'compare' => '=',
                'type'    => 'CHAR',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_previous_id_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['previous_id'] ) ) {
            $previous_id = intval( $query_vars['classifieds_query']['previous_id'] );

            $query_vars['meta_query'][] = array(
                'key'     => "_awpcp_old_id_{$previous_id}",
                'compare' => 'EXISTS',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_category_query_parameter( $query_vars ) {
        $include_children = true;

        if ( isset( $query_vars['classifieds_query']['include_listings_in_children_categories'] ) ) {
            $include_children = $query_vars['classifieds_query']['include_listings_in_children_categories'];
        }

        if ( isset( $query_vars['classifieds_query']['category'] ) ) {
            $terms = $this->sanitize_terms( $query_vars['classifieds_query']['category'] );

            $query_vars['tax_query'][] = array(
                'taxonomy'         => $this->categories_taxonomy,
                'field'            => 'term_id',
                'terms'            => $terms,
                'include_children' => $include_children,
            );
        }

        return $query_vars;
    }

    /**
     * @param mixed $terms  An integer or array of terms IDs.
     * @since 4.0.0
     */
    private function sanitize_terms( $terms ) {
        if ( ! is_array( $terms ) ) {
            $terms = array( $terms );
        }

        return array_filter( array_map( 'intval', $terms ) );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_category__not_in_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['category__not_in'] ) ) {
            $terms = $this->sanitize_terms( $query_vars['classifieds_query']['category__not_in'] );

            $query_vars['tax_query'][] = array(
                'taxonomy'         => $this->categories_taxonomy,
                'field'            => 'term_id',
                'terms'            => $terms,
                'include_children' => true,
                'operator'         => 'NOT IN',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_category__exclude_children_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['category__exclude_children'] ) ) {
            $terms = $this->sanitize_terms( $query_vars['classifieds_query']['category__exclude_children'] );

            $query_vars['tax_query'][] = array(
                'taxonomy'         => $this->categories_taxonomy,
                'field'            => 'term_id',
                'terms'            => $terms,
                'include_children' => false,
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_contact_name_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['contact_name'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_contact_name',
                'value'   => $query_vars['classifieds_query']['contact_name'],
                'compare' => 'LIKE',
                'type'    => 'CHAR',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_contact_phone_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['contact_phone'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_contact_phone_number_digits',
                'value'   => awpcp_get_digits_from_string( $query_vars['classifieds_query']['contact_phone'] ),
                'compare' => 'LIKE',
            );
        }

        return $query_vars;
    }

    /**
     * @since 4.0.0
     */
    public function process_contact_email_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['contact_email'] ) ) {
            $query_vars['meta_query'][] = [
                'key'     => '_awpcp_contact_email',
                'value'   => $query_vars['classifieds_query']['contact_email'],
                'compare' => 'LIKE',
            ];
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_price_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['price'] ) && strlen( $query_vars['classifieds_query']['price'] ) ) {
            $price = $this->sanitize_price( $query_vars['classifieds_query']['price'] );

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_price',
                'value'   => $price,
                'compare' => '=',
                'type'    => 'SIGNED',
            );
        }

        return $query_vars;
    }

    /**
     * @param mixed $price  The price provided for the query.
     * @since 4.0.0
     */
    private function sanitize_price( $price ) {
        return round( floatval( $price ) * 100 );
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_min_price_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['min_price'] ) && strlen( $query_vars['classifieds_query']['min_price'] ) ) {
            $price = $this->sanitize_price( $query_vars['classifieds_query']['min_price'] );

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_price',
                'value'   => $price,
                'compare' => '>=',
                'type'    => 'SIGNED',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_max_price_query_parameter( $query_vars ) {
        if ( isset( $query_vars['classifieds_query']['max_price'] ) && strlen( $query_vars['classifieds_query']['max_price'] ) ) {
            $price = $this->sanitize_price( $query_vars['classifieds_query']['max_price'] );

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_price',
                'value'   => $price,
                'compare' => '<=',
                'type'    => 'SIGNED',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_payment_status_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['payment_status'] ) ) {
            $payment_status = $query_vars['classifieds_query']['payment_status'];

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_payment_status',
                'value'   => $payment_status,
                'compare' => is_array( $payment_status ) ? 'IN' : '=',
                'type'    => 'CHAR',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_payment_status__not_in_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['payment_status__not_in'] ) ) {
            $payment_status = (array) $query_vars['classifieds_query']['payment_status__not_in'];

            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_payment_status',
                'value'   => $payment_status,
                'compare' => 'NOT IN',
                'type'    => 'CHAR',
            );
        }

        return $query_vars;
    }

    /**
     * @param array $query_vars     An array of query vars.
     * @since 4.0.0
     */
    public function process_payer_email_query_parameter( $query_vars ) {
        if ( ! empty( $query_vars['classifieds_query']['payer_email'] ) ) {
            $query_vars['meta_query'][] = array(
                'key'     => '_awpcp_payer_email',
                'value'   => $query_vars['classifieds_query']['payer_email'],
                'compare' => '=',
                'type'    => 'CHAR',
            );
        }

        return $query_vars;
    }

    /**
     * @param string $where     SQL WHERE cluase for the currrent query.
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function posts_where( $where, $query ) {
        if ( ! isset( $query->query_vars['classifieds_query']['title'] ) ) {
            return $where;
        }

        $search_term = $this->db->esc_like( $query->query_vars['classifieds_query']['title'] );
        $search_term = '%' . $search_term . '%';

        return $where . $this->db->prepare( " AND {$this->db->posts}.post_title LIKE %s", $search_term );
    }

    /**
     * @param array  $clauses   An array of SQL clauses.
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function posts_clauses( $clauses, $query ) {
        if ( isset( $query->query_vars['_meta_order'] ) ) {
            $clauses = $this->add_clauses_to_order_by_multiple_meta_keys( $clauses, $query );
        }

        if ( isset( $query->query_vars['_custom_order'] ) ) {
            $clauses = $this->add_clauses_to_order_by_unsupported_properties( $clauses, $query );
        }

        if ( ! empty( $query->query_vars['classifieds_query']['regions'] ) ) {
            $clauses = $this->add_regions_clauses( $clauses, $query );
        }

        if ( isset( $query->query_vars['classifieds_query'] ) ) {
            $clauses = apply_filters( 'awpcp_listings_query_clauses', $clauses, $query );
        }

        return $clauses;
    }

    /**
     * Based on code found in http://wordpress.stackexchange.com/a/67391
     *
     * See http://www.billerickson.net/wp-query-sort-by-meta/.
     *
     * TODO: This function won't be necessary when WP 4.2 becomes the
     * minimum supported version.
     *
     * @param array  $clauses   An array of SQL clauses.
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    private function add_clauses_to_order_by_multiple_meta_keys( $clauses, $query ) {
        $orderby = array();

        foreach ( $query->query_vars['_meta_order'] as $meta_key => $order ) {
            $regexp = "/([\w_]+)\.meta_key = '" . preg_quote( $meta_key, '/' ) . "'/";

            if ( preg_match( $regexp, $clauses['join'], $matches ) ) {
                $table_name = $matches[1];
            } elseif ( preg_match( $regexp, $clauses['where'], $matches ) ) {
                $table_name = $matches[1];
            } else {
                continue;
            }

            $meta_type = $query->query_vars['_meta_type'][ $meta_key ];

            $orderby[] = "CAST({$table_name}.meta_value AS {$meta_type}) $order";
        }

        if ( ! empty( $orderby ) ) {
            $clauses['orderby'] = preg_replace( '/[\w_]+\.menu_order DESC/', implode( ', ', $orderby ), $clauses['orderby'] );
        }

        return $clauses;
    }

    /**
     * @since 4.0.0
     */
    private function add_clauses_to_order_by_unsupported_properties( $clauses, $query ) {
        if ( ! preg_match( '/(\w+)\.menu_order DESC/', $clauses['orderby'], $matches ) ) {
            return $clauses;
        }

        $orderby     = array();
        $posts_table = $matches[1];

        foreach ( $query->query_vars['_custom_order'] as $property => $order ) {
            switch ( $property ) {
                case 'post_status':
                    $orderby[] = "$posts_table.post_status $order";
            }
        }

        if ( ! empty( $orderby ) ) {
            $clauses['orderby'] = str_replace( "$posts_table.menu_order DESC", implode( ', ', $orderby ), $clauses['orderby'] );
        }

        return $clauses;
    }

    /**
     * @since 4.0.0
     */
    private function add_regions_clauses( $clauses, $query ) {
        $regions_conditions = array();

        foreach ( $query->query_vars['classifieds_query']['regions'] as $region ) {
            $region_conditions = array();

            foreach ( $region as $field => $search ) {
                // add support for exact search, passing a search values defined as array( '=', <region-name> ).
                if ( is_array( $search ) && count( $search ) === 2 && '=' === $search[0] ) {
                    $region_conditions[] = $this->db->prepare( "listing_regions.`{$field}` = %s", trim( $search[1] ) );
                } elseif ( ! is_array( $search ) ) {
                    $search_term = $this->db->esc_like( trim( $search ) );
                    $search_term = '%' . $search_term . '%';

                    $region_conditions[] = $this->db->prepare( "listing_regions.`{$field}` LIKE %s", $search_term );
                }
            }

            $regions_conditions[] = '( ' . implode( ' AND ', $region_conditions ) . ' )';
        }

        $clauses['join']  .= ' INNER JOIN ' . AWPCP_TABLE_AD_REGIONS . ' AS listing_regions ON (listing_regions.ad_id = ' . $this->db->posts . '.ID)';
        $clauses['where'] .= ' AND ( ' . implode( ' OR ', $regions_conditions ) . ' )';

        return $clauses;
    }
}
