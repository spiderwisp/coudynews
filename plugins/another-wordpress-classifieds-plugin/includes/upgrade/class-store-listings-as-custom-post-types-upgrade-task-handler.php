<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This upgrade routine converts records from the ads table into posts with the
 * awpcp_listing post type.
 *
 * @since 4.0.0
 */
class AWPCP_Store_Listings_As_Custom_Post_Types_Upgrade_Task_Handler implements AWPCP_Upgrade_Task_Runner {

    private $categories;
    private $legacy_listing_metadata;
    private $wordpress;
    private $db;

    use AWPCP_UpgradeListingsTaskHandlerHelper;

    use AWPCP_UpgradeAssociatedListingsTaskHandlerHelper;

    public function __construct( $categories, $legacy_listing_metadata, $wordpress, $db ) {
        $this->categories              = $categories;
        $this->legacy_listing_metadata = $legacy_listing_metadata;
        $this->wordpress               = $wordpress;
        $this->db                      = $db;
    }

    /**
     * @since 4.0.0
     */
    public function before_step() {
        // See https://10up.github.io/Engineering-Best-Practices/migrations/#requirements-for-a-successful-migration.
        if ( ! defined( 'WP_IMPORTING' ) ) {
            define( 'WP_IMPORTING', true );
        }

        wp_defer_term_counting( true );
    }

    public function count_pending_items( $last_item_id ) {
        $query = 'SELECT COUNT(ad_id) FROM ' . AWPCP_TABLE_ADS . ' WHERE ad_id > %d';
        return intval( $this->db->get_var( $this->db->prepare( $query, $last_item_id ) ) );
    }

    public function get_pending_items( $last_item_id ) {
        $query = 'SELECT * FROM ' . AWPCP_TABLE_ADS . ' WHERE ad_id > %d LIMIT 0, 50';
        return $this->db->get_results( $this->db->prepare( $query, $last_item_id ) );
    }

    /**
     * @throws AWPCP_Exception  If a custom post can't be created.
     */
    public function process_item( $item, $last_item_id ) {
        // Ignore incomplete ad records.
        if ( ! $item->ad_details && ! $item->ad_title ) {
            return $item->ad_id;
        }

        $post_date_gmt = get_gmt_from_date( $item->ad_postdate, 'Y-m-d' );
        $post_time_gmt = get_gmt_from_date( $item->ad_startdate, 'H:i:s' );

        $data = [
            'post_data' => [
                'post_content'      => $item->ad_details, // TODO: do I need to strip slashes?
                'post_title'        => $item->ad_title,
                'post_name'         => sanitize_title( $item->ad_title ),
                'post_status'       => 'draft',
                'post_type'         => 'awpcp_listing',
                'post_date'         => get_date_from_gmt( $post_date_gmt . ' ' . $post_time_gmt ),
                'post_date_gmt'     => $post_date_gmt . ' ' . $post_time_gmt,
                'post_modified'     => $item->ad_last_updated,
                'post_modified_gmt' => get_gmt_from_date( $item->ad_last_updated ),
                'comment_status'    => 'closed',
                'tax_input'         => [],
            ],
            'post_meta' => [
                // Store old listing's ad_id in custom field so premium modules can rebuild relationships.
                "_awpcp_old_id_{$item->ad_id}" => $item->ad_id,
            ],
        ];

        // Update post status and status meta information.
        $data = $this->update_post_status_with_item_properties( $data, $item );

        // Store listing properties as custom fields.
        $data = $this->update_post_metadata_with_item_properties( $data, $item );

        // Import information from ad_meta table.
        $data = $this->update_post_metadata_with_item_metadata( $data, $item );

        $data = $this->update_post_terms_with_item_properties( $data, $item );

        $data = $this->update_post_author_with_item_properties( $data, $item );

        // Create post and import standard fields as custom fields.
        $existing_listing_id = $this->get_id_of_associated_listing( $item->ad_id );

        // Insert a new post or update an existing one with the ad's information.
        //
        // We will check if a new post already exists for the ad being migrated, in
        // case the user had already installed 4.0.0 and decided to downgrade for
        // some time before attempting to upgrade again.
        if ( $existing_listing_id ) {
            $post_id = wp_update_post( [ 'ID' => $existing_listing_id ] + $data['post_data'], true );
        } else {
            $post_id = $this->insert_post( $data['post_data'] );
        }

        if ( is_wp_error( $post_id ) ) {
            throw new AWPCP_Exception( esc_html( sprintf( "A custom post entry couldn't be created for listing %d. %s", $item->ad_id, $post_id->get_error_message() ) ) );
        }

        if ( $existing_listing_id ) {
            $data = $this->update_post_metadata_with_attachments_data( $data, $existing_listing_id );

            foreach ( $data['post_meta'] as $meta_key => $meta_value ) {
                update_post_meta( $post_id, $meta_key, $meta_value );
            }
        } else {
            // We can safely use add_post_meta() to add meta data because this post
            // was just created, so there are no existing keys that we need to
            // worry about.
            foreach ( $data['post_meta'] as $meta_key => $meta_value ) {
                add_post_meta( $post_id, $meta_key, $meta_value );
            }
        }

        // Update references to listing's id in ad_regions table.
        $sql = 'UPDATE ' . AWPCP_TABLE_AD_REGIONS . ' SET ad_id = %d WHERE ad_id = %d';
        $this->db->query( $this->db->prepare( $sql, $post_id, $item->ad_id ) );

        return $item->ad_id;
    }

    /**
     * @since 4.0.0
     */
    private function insert_post( $post_data ) {
        $max_legacy_post_id = $this->get_max_legacy_post_id();
        $wanted_post_id     = $max_legacy_post_id + 1;

        return $this->maybe_insert_post_with_id( $wanted_post_id, $post_data );
    }

    private function update_post_status_with_item_properties( $data, $item ) {
        $listing_expired = strtotime( $item->ad_enddate ) < current_time( 'timestamp' );

        if ( 'Unpaid' === $item->payment_status || ! $item->verified ) {
            $data['post_data']['post_status'] = 'draft';
        } elseif ( $item->disabled || $listing_expired ) {
            $data['post_data']['post_status'] = 'disabled';
        } else {
            $data['post_data']['post_status'] = 'publish';
        }

        // Update verified status.
        if ( intval( $item->verified ) !== 1 ) {
            $data['post_meta']['_awpcp_verification_needed'] = true;
        } else {
            $data['post_meta']['_awpcp_verified'] = true;
        }

        // Update reviewed status.
        $reviewed = $this->legacy_listing_metadata->get( $item->ad_id, 'reviewed' );

        if ( is_null( $reviewed ) || $reviewed ) {
            $data['post_meta']['_awpcp_reviewed'] = true;
        } else {
            $data['post_meta']['_awpcp_content_needs_review'] = true;
        }

        // Update expired status.
        if ( $listing_expired ) {
            $data['post_meta']['_awpcp_expired'] = true;
        }

        return $data;
    }

    public function update_post_metadata_with_item_properties( $data, $item ) {
        $data['post_meta']['_awpcp_payment_term_id']             = $item->adterm_id;
        $data['post_meta']['_awpcp_payment_term_type']           = $item->payment_term_type;
        $data['post_meta']['_awpcp_payment_gateway']             = $item->payment_gateway;
        $data['post_meta']['_awpcp_payment_amount']              = $item->ad_fee_paid;
        $data['post_meta']['_awpcp_payment_status']              = $item->payment_status;
        $data['post_meta']['_awpcp_payer_email']                 = $item->payer_email;
        $data['post_meta']['_awpcp_contact_name']                = $item->ad_contact_name;
        $data['post_meta']['_awpcp_contact_phone']               = $item->ad_contact_phone;
        $data['post_meta']['_awpcp_contact_phone_number_digits'] = $item->phone_number_digits;
        $data['post_meta']['_awpcp_contact_email']               = $item->ad_contact_email;
        $data['post_meta']['_awpcp_website_url']                 = $item->websiteurl;
        $data['post_meta']['_awpcp_price']                       = $item->ad_item_price;
        $data['post_meta']['_awpcp_views']                       = $item->ad_views;
        $data['post_meta']['_awpcp_start_date']                  = $item->ad_startdate;
        $data['post_meta']['_awpcp_end_date']                    = $item->ad_enddate;
        $data['post_meta']['_awpcp_most_recent_start_date']      = $item->ad_startdate;
        $data['post_meta']['_awpcp_disabled_date']               = $item->disabled_date;
        $data['post_meta']['_awpcp_flagged']                     = $item->flagged;
        $data['post_meta']['_awpcp_verification_date']           = $item->verified_at;
        $data['post_meta']['_awpcp_access_key']                  = $item->ad_key;
        $data['post_meta']['_awpcp_transaction_id']              = $item->ad_transaction_id;
        $data['post_meta']['_awpcp_poster_ip']                   = $item->posterip;
        $data['post_meta']['_awpcp_renew_email_sent']            = $item->renew_email_sent;
        $data['post_meta']['_awpcp_renewed_date']                = $item->renewed_date;
        $data['post_meta']['_awpcp_is_paid']                     = $item->ad_fee_paid > 0;

        if ( $item->renewed_date && strtotime( $item->renewed_date ) > strtotime( $item->ad_startdate ) ) {
            $data['post_meta']['_awpcp_most_recent_start_date'] = $item->renewed_date;
        }

        return $data;
    }

    private function update_post_metadata_with_item_metadata( $data, $item ) {
        // 'reviewed' was handled in update_post_status_with_item_properties()
        $meta_keys = array(
            'sent-to-facebook'           => '_awpcp_sent_to_facebook_page',
            'sent-to-facebook-group'     => '_awpcp_sent_to_facebook_group',
            'verification_email_sent_at' => '_awpcp_verification_email_sent_at',
            'verification_emails_sent'   => '_awpcp_verification_emails_sent',
        );

        foreach ( $meta_keys as $old_key => $new_key ) {
            if ( $this->legacy_listing_metadata->get( $item->ad_id, $old_key ) ) {
                $data['post_meta'][ $new_key ] = true;
            }
        }

        return $data;
    }

    private function update_post_terms_with_item_properties( $data, $item ) {
        if ( empty( $item->ad_category_id ) ) {
            return $data;
        }

        $categories_registry = $this->categories->get_categories_registry();

        if ( ! isset( $categories_registry[ $item->ad_category_id ] ) ) {
            return $data;
        }

        $data['post_data']['tax_input']['awpcp_listing_category'][] = intval( $categories_registry[ $item->ad_category_id ] );

        return $data;
    }

    private function update_post_author_with_item_properties( $data, $item ) {
        $user    = null;
        $user_id = 0;

        if ( ! empty( $item->user_id ) ) {
            $user = $this->wordpress->get_user_by( 'id', $item->user_id );
        }

        if ( is_a( $user, 'WP_User' ) ) {
            $user_id = $user->ID;
        }

        $data['post_data']['post_author'] = $user_id;

        return $data;
    }

    /**
     * Update the post metadata with a list of the filenames of existing attachments.
     *
     * If we are running this upgrade routine after the user downgraded from
     * 4.0.0, we need to preload information that the routine that migrate
     * media records is going to need to decide whether a record needs to be
     * migrated again or not.
     *
     * @since 4.0.1
     *
     * @param array $data       An array with information for the new listing.
     * @param int   $listing_id The ID of a WP_Post object associated with the
     *                          ad being migrated.
     *
     * @return array A modified version of $data.
     */
    private function update_post_metadata_with_attachments_data( $data, $listing_id ) {
        $meta_key = '__awpcp_migrated_attachments_filenames';

        $data['post_meta'][ $meta_key ] = $this->get_attachments_filenames( $listing_id );

        return $data;
    }

    /**
     * Get the filenames of attachments associated with the given post.
     *
     * @since 4.0.1
     *
     * @see AWPCP_Store_Media_As_Attachments_Upgrade_Task_Handler
     *
     * @param int $listing_id The ID of a WP_Post object associated with the ad
     *                        being migrated.
     */
    private function get_attachments_filenames( $listing_id ) {
        $query = new WP_Query(
            [
                'post_type'              => 'attachment',
                'post_status'            => 'any',
                'post_parent'            => $listing_id,
                // I can't know what's the maximum number of media records
                // associated with a single ad out there, but I hope is
                // significantly less than 100.
                'posts_per_page'         => 100,
                'meta_query'             => [
                    [
                        'key'     => '_awpcp_allowed_status',
                        'compare' => 'EXISTS',
                    ],
                ],
                // See https://10up.github.io/Engineering-Best-Practices/php/#performance.
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );

        $filenames = [];

        foreach ( $query->posts as $attachment ) {
            $filenames[ $attachment->ID ] = awpcp_utf8_basename( $attachment->guid );
        }

        return $filenames;
    }
}
