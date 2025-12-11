<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ListingOrderPostedData {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy, $payments, $roles, $request ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->payments                  = $payments;
        $this->roles                     = $roles;
        $this->request                   = $request;
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  When the selected payment term cannot be found.
     */
    public function get_posted_data() {
        $categories                = array_map( 'intval', $this->request->post( 'categories' ) );
        $payment_term_id           = $this->request->post( 'payment_term_id' );
        $payment_term_type         = $this->request->post( 'payment_term_type' );
        $payment_term_payment_type = $this->request->post( 'payment_term_payment_type' );
        $user_id                   = null;
        $current_url               = $this->request->post( 'current_url' );

        if ( $this->roles->current_user_is_moderator() ) {
            $user_id = intval( $this->request->post( 'user_id' ) );
        }

        if ( ! $user_id ) {
            $user_id = $this->request->get_current_user_id();
        }

        $payment_term = $this->payments->get_payment_term( $payment_term_id, $payment_term_type );

        if ( is_null( $payment_term ) ) {
            throw new AWPCP_Exception( esc_html__( "The selected payment term couldn't be found.", 'another-wordpress-classifieds-plugin' ) );
        }

        $posted_data = [
            'post_data'    => [
                'post_fields' => [
                    'post_author' => $user_id,
                ],
                'metadata'    => [
                    '_awpcp_payment_term_id'   => $payment_term->id,
                    '_awpcp_payment_term_type' => $payment_term->type,
                ],
                'terms'       => [
                    $this->listing_category_taxonomy => $categories,
                ],
            ],
            'categories'   => $categories,
            'payment_term' => $payment_term,
            'payment_type' => $payment_term_payment_type,
            'user_id'      => $user_id,
            'current_url'  => $current_url,
        ];

        return $posted_data;
    }
}
