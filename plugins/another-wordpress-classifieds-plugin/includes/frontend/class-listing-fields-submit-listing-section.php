<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Listing Fields submit listing section.
 */
class AWPCP_ListingFieldsSubmitListingSection {

    /**
     * @var string
     */
    private $template = 'frontend/listing-fields-submit-listing-section.tpl.php';

    /**
     * @var object
     */
    private $form_fields;

    /**
     * @var object
     */
    private $form_fields_data;

    /**
     * @var AWPCP_UsersCollection
     */
    private $users;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @since 4.0.0
     */
    public function __construct( $form_fields, $form_fields_data, $users, $template_renderer ) {
        $this->form_fields       = $form_fields;
        $this->form_fields_data  = $form_fields_data;
        $this->users             = $users;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function get_id() {
        return 'listing-fields';
    }

    /**
     * @since 4.0.0
     */
    public function get_position() {
        return 15;
    }

    /**
     * @since 4.0.0
     */
    public function get_state( $listing ) {
        return is_null( $listing ) ? 'disabled' : 'edit';
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
    }

    /**
     * @since 4.0.0
     */
    public function render( $listing, $transaction, $mode ) {
        if ( is_null( $listing ) ) {
            $listing = (object) [
                'ID'           => 0,
                'post_title'   => '',
                'post_content' => '',
                'post_author'  => 0,
            ];
        }

        awpcp()->js->localize( 'submit-listing-form-fields', awpcp_listing_form_fields_validation_messages() );

        $errors  = array();
        $context = array(
            'category' => null,
            'action'   => 'normal',
            'mode'     => $mode,
        );
        $data    = $this->get_form_fields_data( $listing, $transaction, $mode );

        $params = array(
            'form_fields' => $this->form_fields->render_fields( $data, $errors, $listing, $context ),
            'nonces'      => $this->maybe_generate_nonces( $listing ),
        );

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_form_fields_data( $listing, $transaction, $mode = null ) {
        $form_data = $this->form_fields_data->get_stored_data( $listing );

        $user_data = null;
        if ( !empty( $transaction->user_id )) {
            $user_data = $this->get_user_data( $transaction->user_id );
        }
        elseif ( $mode === 'create' && get_current_user_id() ) {
            $user_data       = $this->get_user_data( get_current_user_id() );
        }
        else {
            return $form_data;
        }

        foreach ( $user_data['metadata'] as $field => $value ) {
            if ( empty( $form_data['metadata'][ $field ] ) ) {
                $form_data['metadata'][ $field ] = $value;
            }
        }

        if ( empty( $form_data['regions'] ) ) {
            $form_data['regions'] = $user_data['regions'];
        }

        return $form_data;
    }

    /**
     * Gets user information from user meta and the profile fields for Classifieds
     * Contact Information.
     *
     * @since 4.0.0
     */
    private function get_user_data( $user_id ) {
        $user_properties = [
            'ID',
            'user_login',
            'user_email',
            'user_url',
            'display_name',
            'public_name',
            'first_name',
            'last_name',
            'nickname',
            'awpcp-profile',
        ];

        $user = $this->users->find_by_id( $user_id, $user_properties );

        $field_translations = [
            '_awpcp_contact_name'  => 'public_name',
            '_awpcp_contact_email' => 'user_email',
            '_awpcp_contact_phone' => 'phone',
            '_awpcp_website_url'   => 'user_url',
        ];

        $data = [
            'metadata' => [],
            'regions'  => [],
        ];

        foreach ( $field_translations as $field => $key ) {
            if ( ! empty( $user->$key ) ) {
                $data['metadata'][ $field ] = $user->$key;
            }
        }

        $user_region = array_filter(
            [
                'country' => awpcp_get_property( $user, 'country' ),
                'state'   => awpcp_get_property( $user, 'state' ),
                'city'    => awpcp_get_property( $user, 'city' ),
                'county'  => awpcp_get_property( $user, 'county' ),
            ],
            'strlen'
        );

        if ( ! empty( $user_region ) ) {
            $data['regions'][] = $user_region;
        }

        return apply_filters( 'awpcp-listing-details-user-info', $data, $user_id );
    }

    /**
     * @since 4.0.0
     */
    public function maybe_generate_nonces( $listing ) {
        $save_listing_information  = '';
        $clear_listing_information = '';

        if ( ! is_null( $listing ) ) {
            $save_listing_information  = wp_create_nonce( "awpcp-save-listing-information-{$listing->ID}" );
            $clear_listing_information = wp_create_nonce( "awpcp-clear-listing-information-{$listing->ID}" );
        }

        return compact( 'save_listing_information', 'clear_listing_information' );
    }
}
