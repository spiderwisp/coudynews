<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class used to retrieve data posted through listing's form fields.
 */
class AWPCP_FormFieldsData {

    /**
     * @var object
     */
    private $authorization;

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @param object $authorization     An instance of Listing Authorization.
     * @param object $listing_renderer  An instance of Listing Renderer.
     * @since 4.0.0
     */
    public function __construct( $authorization, $listing_renderer ) {
        $this->authorization    = $authorization;
        $this->listing_renderer = $listing_renderer;
    }

    /**
     * A replacement for PagePlaceAd::get_ad_info().
     *
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function get_stored_data( $post ) {
        $data = array(
            'ID'          => $post->ID,
            'post_fields' => [
                'post_author'  => $post->post_author,
                'post_title'   => $this->listing_renderer->get_listing_title( $post ),
                'post_content' => $post->post_content,
                'post_status'  => isset( $post->post_status ) ? $post->post_status : '',
            ],
            'metadata'    => [
                '_awpcp_access_key'    => $this->listing_renderer->get_access_key( $post ),
                '_awpcp_start_date'    => $this->listing_renderer->get_plain_start_date( $post ),
                '_awpcp_end_date'      => $this->listing_renderer->get_plain_end_date( $post ),
                '_awpcp_contact_name'  => $this->listing_renderer->get_contact_name( $post ),
                '_awpcp_contact_phone' => $this->listing_renderer->get_contact_phone( $post ),
                '_awpcp_contact_email' => $this->listing_renderer->get_contact_email( $post ),
                '_awpcp_website_url'   => $this->listing_renderer->get_website_url( $post ),
                '_awpcp_price'         => $this->listing_renderer->get_price( $post ),
            ],
            'categories'  => array_filter(
                array_unique(
                    array_merge(
                        [ $this->listing_renderer->get_category_id( $post ) ],
                        $this->listing_renderer->get_categories_ids( $post )
                    )
                )
            ),
            'regions'     => $this->listing_renderer->get_regions( $post ),
        );

        // Remove default title for listings created on the frontend.
        if ( 'Classified Auto Draft' === $data['post_fields']['post_title'] ) {
            $data['post_fields']['post_title'] = '';
        }

        return apply_filters( 'awpcp_form_fields_stored_data', $data, 'details' );
    }

    /**
     * @since 4.0.0
     *
     * @param object $post  An instance of WP_Post.
     * @return array {
     *     @type int    $ID
     *     @type array  $post_fields
     *     @type array  $metadata
     *     @type array  $categories
     *     @type array  $regions
     *     @type string $terms_of_service
     * }
     */
    public function get_posted_data( $post ) {
        $data = [
            'ID'               => awpcp_get_var( array( 'param' => 'ad_id' ) ),
            'post_fields'      => [
                'post_title'   => awpcp_strip_all_tags_deep( awpcp_get_var( array( 'param' => 'ad_title' ) ) ),
                'post_content' => str_replace(
                    "\r",
                    '',
                    awpcp_get_var(
                        array( 'param' => 'ad_details', 'sanitize' => 'sanitize_textarea_field' )
                    )
                ),
            ],
            'metadata'         => [
                '_awpcp_start_date'    => awpcp_get_var( array( 'param' => 'start_date', 'default' => null ) ),
                '_awpcp_end_date'      => awpcp_get_var( array( 'param' => 'end_date', 'default' => null ) ),
                '_awpcp_contact_name'  => awpcp_get_var( array( 'param' => 'ad_contact_name' ) ),
                '_awpcp_contact_phone' => awpcp_get_var( array( 'param' => 'ad_contact_phone' ) ),
                '_awpcp_contact_email' => awpcp_get_var( array( 'param' => 'ad_contact_email' ) ),
                '_awpcp_website_url'   => awpcp_maybe_add_http_to_url(
                    awpcp_get_var( array( 'param' => 'websiteurl' ) )
                ),
                // Parse the value provided by the user and convert it to a float value.
                '_awpcp_price'         => 100 * awpcp_parse_money(
                    awpcp_get_var( array( 'param' => 'ad_item_price' ) )
                ),
            ],
            'categories'       => [],
            'regions'          => awpcp_get_var( array( 'param' => 'regions', 'default' => array() ) ),
            'terms_of_service' => awpcp_get_var(
                array(
                    'param'    => 'terms_of_service',
                    'sanitize' => 'sanitize_textarea_field',
                )
            ),
        ];

        if ( ! empty( $data['metadata']['_awpcp_contact_phone'] ) ) {
            $data['metadata']['_awpcp_contact_phone_number_digits'] = awpcp_get_digits_from_string( $data['metadata']['_awpcp_contact_phone'] );
        }

        $can_edit_start_date = $this->authorization->is_current_user_allowed_to_edit_listing_start_date( $post );
        $can_edit_end_date   = $this->authorization->is_current_user_allowed_to_edit_listing_end_date( $post );

        if ( ! $can_edit_start_date || empty( $data['metadata']['_awpcp_start_date'] ) ) {
            $data['metadata']['_awpcp_start_date'] = $this->listing_renderer->get_plain_start_date( $post );
        } else {
            $data['metadata']['_awpcp_start_date'] = awpcp_set_datetime_date( current_time( 'mysql' ), $data['metadata']['_awpcp_start_date'] );
        }

        if ( ! $can_edit_end_date || empty( $data['metadata']['_awpcp_end_date'] ) ) {
            $data['metadata']['_awpcp_end_date'] = $this->listing_renderer->get_plain_end_date( $post );
        } else {
            $data['metadata']['_awpcp_end_date'] = awpcp_set_datetime_date( current_time( 'mysql' ), $data['metadata']['_awpcp_end_date'] );
        }

        // TODO: We no longer pass an array that filters can use to extract data from.
        return (array) apply_filters( 'awpcp-get-posted-data', $data, 'details', [] );
    }
}
