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
function awpcp_listing_renderer() {
    return new AWPCP_ListingRenderer(
        awpcp_categories_collection(),
        '',
        awpcp_payments_api(),
        awpcp_wordpress()
    );
}

/**
 * @since 3.3
 */
class AWPCP_ListingRenderer {

    private $disabled_status = 'disabled';
    private $categories;
    private $regions;
    private $payments;
    private $wordpress;

    public function __construct( $categories, $null, $payments, $wordpress ) {
        $this->categories = $categories;
        $this->regions    = awpcp_basic_regions_api();
        $this->payments = $payments;
        $this->wordpress = $wordpress;
    }

    public function get_listing_title( $listing ) {
        return stripslashes( $listing->post_title );
    }

    public function get_categories( $listing ) {
        return $this->categories->find_by_listing_id( $listing->ID );
    }

    public function get_categories_ids( $listing ) {
        return awpcp_get_properties(
            $this->get_categories( $listing ),
            'term_id'
        );
    }

    public function get_category( $listing ) {
        $categories = $this->get_categories( $listing );

        if ( empty( $categories ) ) {
            return null;
        }

        $category = $categories[0];
        if ( count( $categories ) > 1 ) {
            foreach ( $categories as $cat ) {
                if ( $cat->parent > 0 ) {
                    $category = $cat;
                    break;
                }
            }
        }

        return $category;
    }

    public function get_category_name( $listing ) {
        $category = $this->get_category( $listing );
        return is_object( $category ) ? $category->name : null;
    }

    public function get_category_description( $listing ) {
        $category = $this->get_category( $listing );
        return is_object( $category ) ? $category->description : null;
    }

    public function get_category_id( $listing ) {
        $category = $this->get_category( $listing );
        return is_object( $category ) ? $category->term_id : null;
    }

    /**
     * @since 4.0.0
     */
    public function get_category_slug( $listing ) {
        $category = $this->get_category( $listing );

        return is_object( $category ) ? $category->slug : null;
    }

    public function get_contact_name( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_contact_name', true );
    }

    public function get_contact_email( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_contact_email', true );
    }

    public function get_contact_phone( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_contact_phone', true );
    }

    /**
     * TODO: Make sure phone number digits are being stored.
     *
     * @since 4.0.0
     */
    public function get_contact_phone_digits( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_contact_phone_number_digits', true );
    }

    /**
     * @since 4.0.0
     */
    public function get_payment_email( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_payer_email', true );
    }

    public function get_access_key( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_access_key', true );
    }

    /**
     * TODO: Rename to get_formatted_end_date
     * @since 4.0.0
     */
    public function get_end_date( $listing ) {
        return $this->get_end_date_formatted( $listing );
    }

    /**
     * TODO: Rename to get_end_date
     * @since 4.0.0
     */
    public function get_plain_end_date( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_end_date', true );
    }

    /**
     * @since 4.0.0
     */
    public function get_end_date_formatted( $listing ) {
        $end_date = $this->get_plain_end_date( $listing );

        return $this->get_formatted_date( $end_date );
    }

    /**
     * @param string $mysql_date    A date string.
     * @param string $format        A format string as supported by date().
     * @since 4.0.0
     */
    private function get_formatted_date( $mysql_date, $format = 'awpcp-date' ) {
        if ( empty( $mysql_date ) ) {
            return '';
        }

        return awpcp_datetime( $format, strtotime( $mysql_date ) );
    }

    /**
     * @since 4.0.0
     */
    public function get_start_date( $listing ) {
        $start_date = $this->wordpress->get_post_meta( $listing->ID, '_awpcp_start_date', true );
        return $this->get_formatted_date( $start_date );
    }

    /**
     * @since 4.0.0
     */
    public function get_plain_start_date( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_start_date', true );
    }

    /**
     * @since 4.0.0
     */
    public function get_renewed_date( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_renewed_date', true );
    }

    /**
     * @since 4.0.0
     */
    public function get_renewed_date_formatted( $listing ) {
        return $this->get_formatted_date( $this->get_renewed_date( $listing ) );
    }

    /**
     * @since 4.0.0
     */
    public function get_posted_date_formatted( $listing ) {
        return $this->get_formatted_date( $listing->post_date );
    }

    /**
     * @since 4.0.0
     */
    public function get_posted_date_and_time_formatted( $listing ) {
        return $this->get_formatted_date( $listing->post_date, 'awpcp' );
    }

    /**
     * @since 4.0.0
     */
    public function get_last_updated_date_formatted( $listing ) {
        return $this->get_formatted_date( $listing->post_modified );
    }

    public function get_verification_date( $listing ) {
        $verification_date = $this->wordpress->get_post_meta( $listing->ID, '_awpcp_verification_date', true );
        return $this->get_formatted_date( $verification_date );
    }

    public function get_disabled_date( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_disabled_date', true );
    }

    /**
     * @return array
     */
    public function get_regions( $listing ) {
        $regions = array();

        foreach ( $this->regions->find_by_ad_id( $listing->ID ) as $region ) {
            $regions[] = array(
                'country' => $region->country,
                'county' => $region->county,
                'state' => $region->state,
                'city'    => $region->city,
            );
        }

        return $regions;
    }

    public function get_first_region( $listing ) {
        $regions = $this->get_regions( $listing );
        return count( $regions ) > 0 ? $regions[0] : null;
    }

    /**
     * Calculate the number of regions that can be associated with the given listing.
     *
     * Returns zero if the listing is not already assocaited with a region or payment
     * term. No new information should be be added until a payment term is assigned.
     *
     * @since 4.0.0
     *
     * @return int
     */
    public function get_number_of_regions_allowed( $listing ) {
        $payment_term = $this->get_payment_term( $listing );

        $regions_allowed = 0;

        if ( $payment_term ) {
            $regions_allowed = 1;
        }

        $existing_regions = $this->get_regions( $listing );

        if ( $existing_regions ) {
            $regions_allowed = max( $regions_allowed, count( $existing_regions ) );
        }

        return $regions_allowed;
    }

    /**
     * TODO: Store User's IP address during listing creation.
     *
     * @since 4.0.0
     */
    public function get_ip_address( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_user_ip_address', true );
    }

    public function has_payment( $listing ) {
        return 'Unpaid' !== $this->get_payment_status( $listing );
    }

    public function is_verified( $listing ) {
        $verification_needed = $this->wordpress->get_post_meta( $listing->ID, '_awpcp_verification_needed', true );

        if ( $verification_needed ) {
            return false;
        }

        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_verified', true );
    }

    public function is_disabled( $listing ) {
        if ( $this->disabled_status !== $listing->post_status ) {
            return false;
        }

        if ( $this->wordpress->get_post_meta( $listing->ID, '_awpcp_expired', true ) ) {
            return false;
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function is_public( $listing ) {
        return 'publish' === $listing->post_status;
    }

    /**
     * Before AWPCP 4.0.0, both disabled listings and listings pending approval
     * had a disabled property set to true. In 4.0.0 and newer versions,
     * disabled listings have post_status = disabled and listings pending
     * approval have post_status = pending.
     *
     * @since 4.0.0
     */
    public function is_pending_approval( $listing ) {
        return 'pending' === $listing->post_status;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    public function is_featured( $listing ) {
        return (bool) $this->wordpress->get_post_meta( $listing->ID, '_awpcp_is_featured', true );
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    public function needs_review( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_content_needs_review', true );
    }

    /**
     * @param object $listing  An instance of WP_Post.
     * @since 4.0.0
     * @return bool
     */
    public function is_flagged( $listing ) {
        return (bool) $this->wordpress->get_post_meta( $listing->ID, '_awpcp_flagged', true );
    }

    /**
     * @since 4.0.0
     */
    public function is_expired( $listing ) {
        $expired     = $this->has_expired( $listing );
        $is_disabled = in_array( $listing->post_status, array( $this->disabled_status, 'trash' ), true );

        if ( $expired && ! $is_disabled ) {
            // This listing has expired, but isn't marked as expired.
            awpcp_listings_api()->expire_listing_with_notice( $listing );
        }

        return $expired;
    }

    public function has_expired( $listing ) {
        return $this->has_expired_on_date( $listing, current_time( 'timestamp' ) );
    }

    private function has_expired_on_date( $listing, $timestamp ) {
        $end_date = $this->get_plain_end_date( $listing );

        if ( ! empty( $end_date ) ) {
            $end_date = strtotime( $end_date );
        } else {
            $end_date = 0;
        }

        return $end_date < $timestamp;
    }

    /**
     * @since 4.1.7
     *
     * @return float
     */
    public function days_until_expired( $listing ) {
        if ( $this->has_expired( $listing ) ) {
            return 0;
        }

        $end_date          = strtotime( $this->get_plain_end_date( $listing ) );
        $extended_end_date = awpcp_extend_date_to_end_of_the_day( $end_date );
        $time_left         = $extended_end_date - current_time( 'timestamp' );

        return $time_left / ( 24 * 60 * 60 );
    }

    /**
     * @param object $listing   An instance of WP_Post.
     */
    public function is_about_to_expire( $listing ) {
        if ( $this->has_expired( $listing ) ) {
            return false;
        }

        $end_of_date_range = awpcp_calculate_end_of_renew_email_date_range_from_now();

        // Has the listing expired one second after current time + renew email threshold?
        return $this->has_expired_on_date( $listing, $end_of_date_range + 1 );
    }

    /**
     * Determine whether the listing already expired or will expire within the
     * renew email threshold.
     *
     * @since 4.0.4
     *
     * @param WP_Post $listing An instance of WP_Post representing an ad.
     */
    public function has_expired_or_is_about_to_expire( $listing ) {
        return $this->has_expired( $listing ) || $this->is_about_to_expire( $listing );
    }

    public function get_payment_status( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_payment_status', true );
    }

    public function get_payment_term( $listing ) {
        $payment_term_id = $this->wordpress->get_post_meta( $listing->ID, '_awpcp_payment_term_id', true );
        $payment_term_type = $this->wordpress->get_post_meta( $listing->ID, '_awpcp_payment_term_type', true );

        return $this->payments->get_payment_term( $payment_term_id, $payment_term_type );
    }

    public function get_price( $listing ) {
        return absint( $this->wordpress->get_post_meta( $listing->ID, '_awpcp_price', true ) );
    }

    public function get_website_url( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_website_url', true );
    }

    public function get_views_count( $listing ) {
        return $this->wordpress->get_post_meta( $listing->ID, '_awpcp_views', true );
    }

    public function get_user( $listing ) {
        return $this->wordpress->get_user_by( 'id', $listing->post_author );
    }

    public function get_view_listing_link( $listing ) {
        $url = $this->get_view_listing_url( $listing );
        $title = $this->get_listing_title( $listing );

        return sprintf( '<a href="%s" title="%s">%s</a>', $url, esc_attr( $title ), $title );
    }

    public function get_view_listing_url( $listing ) {
        return get_permalink( $listing );
    }

    public function get_edit_listing_url( $listing ) {
        return awpcp_get_edit_listing_url( $listing );
    }

    public function get_delete_listing_url( $listing ) {
        $url = $this->get_edit_listing_url( $listing );
        return apply_filters( 'awpcp-delete-listing-url', $url, $listing );
    }
}
