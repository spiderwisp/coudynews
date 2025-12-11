<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exporter and eraser for Listings personal data.
 */
class AWPCP_ListingsPersonalDataProvider implements AWPCP_PersonalDataProviderInterface {

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_Attachments_Collection
     */
    private $attachments;

    /**
     * @var object
     */
    private $regions;

    /**
     * @var AWPCP_DataFormatter
     */
    private $data_formatter;

    protected $listing_renderer;

    /**
     * @since 3.8.6
     */
    public function __construct( $listings, $listing_renderer, $listings_logic, $attachments, $data_formatter ) {
        $this->listings         = $listings;
        $this->listing_renderer = $listing_renderer;
        $this->listings_logic   = $listings_logic;
        $this->attachments      = $attachments;
        $this->regions          = awpcp_basic_regions_api();
        $this->data_formatter   = $data_formatter;
    }

    /**
     * @since 3.8.6
     */
    public function get_page_size() {
        return 10;
    }

    /**
     * @since 3.8.6
     */
    public function get_objects( $user, $email_address, $page ) {
        $items_per_page = $this->get_page_size();

        if ( $user ) {
            $user_listings = $this->listings->find_listings( [
                'author'         => $user->ID,
                'posts_per_page' => $items_per_page,
                'paged'          => $page,
                'fields'         => 'ids',
            ] );

            $email_listings = $this->listings->find_listings( [
                'classifieds_query' => [
                    'contact_email' => $email_address,
                ],
                'posts_per_page'    => $items_per_page,
                'paged'             => $page,
                'fields'            => 'ids',
            ] );

            $posts_ids = array_unique( array_merge( $user_listings, $email_listings ) );

            if ( empty( $posts_ids ) ) {
                return [];
            }

            return $this->listings->find_listings( [
                'post__in' => $posts_ids,
            ] );
        }

        return $this->listings->find_listings( [
            'classifieds_query' => [
                'contact_email' => $email_address,
            ],
            'posts_per_page'    => $items_per_page,
            'paged'             => $page,
        ] );
    }

    /**
     * @since 3.8.6
     */
    public function export_objects( $listings ) {
        // TODO: Let premium modules define additional properties.
        $items = apply_filters( 'awpcp_listings_personal_data_items_descriptions', array(
            'ID'                          => __( 'Classified ID', 'another-wordpress-classifieds-plugin' ),
            'contact_name'                => __( 'Contact Name', 'another-wordpress-classifieds-plugin' ),
            'contact_phone'               => __( 'Contact Phone Number', 'another-wordpress-classifieds-plugin' ),
            'contact_phone_number_digits' => __( 'Contact Phone Number Digits', 'another-wordpress-classifieds-plugin' ),
            'contact_email'               => __( 'Contact Email', 'another-wordpress-classifieds-plugin' ),
            'ad_country'                  => __( 'Country', 'another-wordpress-classifieds-plugin' ),
            'ad_state'                    => __( 'State', 'another-wordpress-classifieds-plugin' ),
            'ad_city'                     => __( 'City', 'another-wordpress-classifieds-plugin' ),
            'ad_county_village'           => __( 'County', 'another-wordpress-classifieds-plugin' ),
            'website_url'                 => __( 'Website URL', 'another-wordpress-classifieds-plugin' ),
            'payer_email'                 => __( 'Payer Email', 'another-wordpress-classifieds-plugin' ),
            'ip_address'                  => __( 'Author IP', 'another-wordpress-classifieds-plugin' ),
        ) );

        $region_items = array(
            'country' => __( 'Country', 'another-wordpress-classifieds-plugin' ),
            'state'   => __( 'State', 'another-wordpress-classifieds-plugin' ),
            'city'    => __( 'City', 'another-wordpress-classifieds-plugin' ),
            'county'  => __( 'County', 'another-wordpress-classifieds-plugin' ),
        );

        $media_items = array(
            'URL' => __( 'URL', 'another-wordpress-classifieds-plugin' ),
        );

        $export_items = array();

        foreach ( $listings as $listing ) {
            $data = $this->data_formatter->format_data( $items, $this->get_listing_properties( $listing ) );

            foreach ( $this->regions->find_by_ad_id( $listing->ID ) as $region ) {
                $data = array_merge( $data, $this->data_formatter->format_data( $region_items, (array) $region ) );
            }

            $export_items[] = array(
                'group_id'    => 'awpcp-classifieds',
                'group_label' => __( 'Classifieds Listings', 'another-wordpress-classifieds-plugin' ),
                'item_id'     => "awpcp-classified-{$listing->ID}",
                'data'        => $data,
            );
        }

        foreach ( $this->get_listings_media( $listings ) as $media_record ) {
            $data = $this->data_formatter->format_data( $media_items, $this->get_media_properties( $media_record ) );

            $export_items[] = array(
                'group_id'    => 'awpcp-media',
                'group_label' => __( 'Classifieds Media', 'another-wordpress-classifieds-plugin' ),
                'item_id'     => "awpcp-media-{$media_record->ID}",
                'data'        => $data,
            );
        }

        return $export_items;
    }

    /**
     * @since 3.8.6
     */
    private function get_listing_properties( $listing ) {
        $properties = array(
            'ID'                          => $listing->ID,
            'contact_name'                => $this->listing_renderer->get_contact_name( $listing ),
            'contact_phone'               => $this->listing_renderer->get_contact_phone( $listing ),
            'contact_phone_number_digits' => $this->listing_renderer->get_contact_phone_digits( $listing ),
            'contact_email'               => $this->listing_renderer->get_contact_email( $listing ),
            'website_url'                 => $this->listing_renderer->get_website_url( $listing ),
            'payer_email'                 => $this->listing_renderer->get_payment_email( $listing ),
            'ip_address'                  => $this->listing_renderer->get_ip_address( $listing ),
        );

        return apply_filters( 'awpcp_listing_personal_data_properties', $properties, $listing );
    }

    /**
     * @since 3.8.6
     */
    private function get_listings_media( $listings ) {
        return $this->attachments->find_attachments( [
            'post_parent__in' => wp_list_pluck( $listings, 'ID' ),
        ] );
    }

    /**
     * @since 3.8.6
     */
    private function get_media_properties( $media_record ) {
        return array(
            'URL' => wp_get_attachment_url( $media_record->ID ),
        );
    }

    /**
     * @since 3.8.6
     */
    public function erase_objects( $listings ) {
        $items_removed  = false;
        $items_retained = false;
        $messages       = array();

        foreach ( $listings as $listing ) {
            if ( $this->listings_logic->delete_listing( $listing ) ) {
                $items_removed = true;
                continue;
            }

            $items_retained = true;

            $message = __( 'An unknown error occurred while trying to delete information for classified {listing_id}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{listing_id}', $listing->ID, $message );

            $messages[] = $message;
        }

        return compact( 'items_removed', 'items_retained', 'messages' );
    }
}
