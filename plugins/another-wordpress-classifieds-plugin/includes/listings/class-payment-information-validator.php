<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validator for categories, user and payment term properties selection for
 * listings.
 */
class AWPCP_PaymentInformationValidator {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var AWPCP_Categories_Collection
     */
    private $categories;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy, $categories, $payments, $roles ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->categories                = $categories;
        $this->payments                  = $payments;
        $this->roles                     = $roles;
    }

    /**
     * @since 4.0.0
     */
    public function get_validation_errors( $data ) {
        $categories_errors   = $this->validate_selected_categories( $data );
        $payment_term_errors = $this->validate_selected_payment_term( $data );
        $user_errors         = $this->validate_selected_user( $data );

        $errors = array_merge( $categories_errors, $payment_term_errors, $user_errors );
        $errors = apply_filters( 'awpcp-validate-post-listing-order', $errors, $data );

        return $errors;
    }

    /**
     * @since 4.0.0
     */
    private function validate_selected_payment_term( $data ) {
        $errors = [];

        // Can't use empty for term ID, because 0 is the ID of the Free Fee.
        if ( 0 === strlen( $data['metadata']['_awpcp_payment_term_id'] ) || empty( $data['metadata']['_awpcp_payment_term_type'] ) ) {
            $errors['payment-term'] = __( 'You should choose one of the available Payment Terms.', 'another-wordpress-classifieds-plugin' );
        }

        $payment_term_id   = $data['metadata']['_awpcp_payment_term_id'];
        $payment_term_type = $data['metadata']['_awpcp_payment_term_type'];

        $payment_term = $this->payments->get_payment_term( $payment_term_id, $payment_term_type );

        if ( is_null( $payment_term ) ) {
            $errors['payment-term'] = __( 'You should choose one of the available Payment Terms.', 'another-wordpress-classifieds-plugin' );
        } elseif ( ! $this->roles->current_user_is_administrator() && $payment_term->private ) {
            $errors['payment-term'] = __( 'The Payment Term you selected is not available for non-administrator users.', 'another-wordpress-classifieds-plugin' );
        }

        return $errors;
    }

    /**
     * @since 4.0.0
     */
    private function validate_selected_user( $data ) {
        $errors = [];

        if ( $this->roles->current_user_is_moderator() && empty( $data['post_fields']['post_author'] ) ) {
            $errors['user'] = __( 'You should select an owner for this Ad.', 'another-wordpress-classifieds-plugin' );
        }

        return $errors;
    }

    /**
     * Migrated from Place Ad page.
     *
     * @since 4.0.0
     */
    private function validate_selected_categories( $data ) {
        $categories = [];
        $errors     = [];

        if ( isset( $data['terms'][ $this->listing_category_taxonomy ] ) ) {
            $categories = $data['terms'][ $this->listing_category_taxonomy ];
        }

        if ( empty( $categories ) ) {
            $errors['categories'] = __( 'Ad Category field is required', 'another-wordpress-classifieds-plugin' );
        }

        if ( ! get_awpcp_option( 'noadsinparentcat' ) ) {
            return $errors;
        }

        $hierarchy = $this->categories->get_hierarchy();

        foreach ( $categories as $category_id ) {
            if ( isset( $hierarchy[ $category_id ] ) ) {
                $errors['categories'] = __( 'You cannot list your Ad in top level categories.', 'another-wordpress-classifieds-plugin' );

                return $errors;
            }
        }

        return $errors;
    }
}
