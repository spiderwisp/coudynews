<?php
/**
 * @package AWPCP\Admim\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Metabox to collect additional deatils for an ad.
 */
class AWPCP_ListingFieldsMetabox {

    /**
     * @var object
     */
    private $listings_logic;

    /**
     * @var object
     */
    private $form_fields_data;

    /**
     * @var object
     */
    private $form_fields_validator;

    /**
     * @var object
     */
    private $form_fields;

    /**
     * @var object
     */
    private $date_form_fields;

    /**
     * @var object
     */
    private $media_center;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @var object
     */
    private $wordpress;

    /**
     * @var object
     */
    private $listing_authorization;

    /**
     * @param object $listings_logic            An instance of Listings API.
     * @param object $form_fields_data          An instance of Form Fields Data.
     * @param object $form_fields_validator     An instance of Form Fields Validator.
     * @param object $form_fields               An instance of Details Form Fields.
     * @param object $date_form_fields          An instance of Date Form Fields.
     * @param object $media_center              An instance of Media Center.
     * @param object $template_renderer         An instance of Template Renderer.
     * @param object $wordpress                 An instance of WordPress.
     * @since 4.0.0
     */
    public function __construct( $listings_logic, $form_fields_data, $form_fields_validator, $form_fields, $date_form_fields, $media_center, $template_renderer, $wordpress, $listing_authorization ) {
        $this->listings_logic        = $listings_logic;
        $this->form_fields_data      = $form_fields_data;
        $this->form_fields_validator = $form_fields_validator;
        $this->form_fields           = $form_fields;
        $this->date_form_fields      = $date_form_fields;
        $this->media_center          = $media_center;
        $this->template_renderer     = $template_renderer;
        $this->wordpress             = $wordpress;
        $this->listing_authorization = $listing_authorization;
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'awpcp-jquery-ui' );
        wp_enqueue_style( 'awpcp-admin-style' );

        wp_enqueue_script( 'awpcp-admin-edit-post' );
        wp_enqueue_script( 'awpcp-extra-fields' );

        awpcp()->js->localize( 'edit-post-form-fields', awpcp_listing_form_fields_validation_messages() );
    }

    /**
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function render( $post ) {
        $errors = $this->get_errors( $post );
        $data   = $this->get_stored_data( $post, $errors );

        $context = array(
            'category' => null,
            'action'   => 'normal',
        );

        $params = array(
            'details_form_fields' => $this->form_fields->render_fields( $data, $errors, $post, $context ),
            'date_form_fields'    => '',
            'media_manager'       => $this->media_center->render( $post ),
            'nonce'               => wp_create_nonce( 'save-listing-fields-metabox' ),
            'errors'              => $errors,
        );

        if ( $this->listing_authorization->is_current_user_allowed_to_edit_listing_start_date( $post ) ) {
            $params['date_form_fields'] = $this->date_form_fields->render_fields( $data, $errors, $post, $context );
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->template_renderer->render_template( 'admin/listings/listing-fields-metabox.tpl.php', $params );
    }

    /**
     * Get validation and save errors stored in the post's metadata.
     *
     * @since 4.0.0
     */
    private function get_errors( $post ) {
        $validation_errors = get_post_meta( $post->ID, '__awpcp_admin_editor_validation_errors', true );
        $save_errors       = get_post_meta( $post->ID, '__awpcp_admin_editor_save_errors', true );
        $errors            = [];

        if ( $validation_errors ) {
            $errors = $validation_errors;
        }

        if ( $save_errors ) {
            $errors = array_merge( $errors, $save_errors );
        }

        return $errors;
    }

    /**
     * Get listing data necessary to populate form fields.
     *
     * Returns pending data stored in the post's metadata if available, to allow
     * users to recover from errors.
     *
     * @since 4.0.0
     */
    private function get_stored_data( $post, $errors ) {
        $data = [];

        if ( $errors ) {
            $data = get_post_meta( $post->ID, '__awpcp_admin_editor_pending_data', true );
        }

        if ( is_array( $data ) && $data ) {
            return $data;
        }

        return $this->form_fields_data->get_stored_data( $post );
    }

    /**
     * TODO: Use redirect_post_location filter to add feedback messages on redirect_post().
     *
     * @param int    $post_id   The ID of the post being saved.
     * @param object $post      The post being saved.
     * @since 4.0.0
     */
    public function save( $post_id, $post ) {
        if (
            empty( $_POST['awpcp_listing_fields_nonce'] )
            || ! wp_verify_nonce( sanitize_key( $_POST['awpcp_listing_fields_nonce'] ), 'save-listing-fields-metabox' )
        ) {
            return;
        }

        $data   = $this->form_fields_data->get_posted_data( $post );
        $errors = $this->form_fields_validator->get_validation_errors( $data, $post );

        // Post Title and Content are handled by WordPress.
        unset( $data['post_fields']['post_title'] );
        unset( $data['post_fields']['post_content'] );
        unset( $errors['ad_title'] );
        unset( $errors['ad_details'] );

        if ( empty( $data['metadata']['_awpcp_start_date'] ) && empty( $data['metadata']['_awpcp_end_date'] ) ) {
            unset( $data['metadata']['_awpcp_start_date'] );
            unset( $data['metadata']['_awpcp_end_date'] );

            unset( $errors['start_date'] );
            unset( $errors['end_date'] );
        }

        if ( ! $this->wordpress->get_post_meta( $post->ID, '_awpcp_access_key', true ) ) {
            $data['metadata'] = $this->listings_logic->fill_default_listing_metadata(
                $post,
                $data['metadata']
            );
        }

        $this->save_or_store_errors( $post, $data, $errors );
    }

    /**
     * @param object $post      An instance of WP Post.
     * @param array  $data      An array of data.
     * @param array  $errors    An array of errors.
     */
    private function save_or_store_errors( $post, $data, $errors ) {
        if ( ! empty( $errors ) ) {
            $this->wordpress->update_post_meta( $post->ID, '__awpcp_admin_editor_pending_data', $data );
            $this->wordpress->update_post_meta( $post->ID, '__awpcp_admin_editor_validation_errors', $errors );
            return;
        }

        // Delete old validation errors.
        $this->wordpress->delete_post_meta( $post->ID, '__awpcp_admin_editor_validation_errors' );

        try {
            $this->save_listing_information( $post, $data );
        } catch ( AWPCP_Exception $e ) {
            $this->wordpress->update_post_meta( $post->ID, '__awpcp_admin_editor_pending_data', $data );
            $this->wordpress->update_post_meta( $post->ID, '__awpcp_admin_editor_save_errors', [ $e->getMessage() ] );
            return;
        }

        // Delete old save errors and no longer necessary temporary data.
        $this->wordpress->delete_post_meta( $post->ID, '__awpcp_admin_editor_pending_data' );
        $this->wordpress->delete_post_meta( $post->ID, '__awpcp_admin_editor_save_errors' );
    }

    /**
     * XXX: This is a copy of AWPCP_SaveListingInformationAjaxHandler::save_listing_information.
     *
     * TODO: trigger awpcp-place-listing-listing-data filter
     * TODO: trigger awpcp_before_edit_ad action.
     *
     * @since 4.0.0
     */
    private function save_listing_information( $listing, $post_data ) {
        do_action( 'awpcp-before-save-listing', $listing, $post_data );

        $this->listings_logic->update_listing( $listing, $post_data );

        /**
         * Fires once the information for a classified ad has been saved.
         *
         * @since 4.0.0     A transaction object is no longer passsed as the second argument.
         * @deprecated 4.0.0    Use awpcp_listing_information_saved instead.
         */
        do_action( 'awpcp-save-ad-details', $listing, null );

        /**
         * Fires once the information for a classified ad has been saved.
         *
         * @since 4.0.0
         */
        do_action( 'awpcp_listing_information_saved', $listing, $post_data );
    }
}
