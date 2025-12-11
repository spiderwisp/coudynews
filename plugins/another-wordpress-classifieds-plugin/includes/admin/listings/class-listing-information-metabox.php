<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classified Information metabox.
 */
class AWPCP_ListingInfromationMetabox {

    /**
     * @var AWPCP_ListingsPayments
     */
    private $listings_payments;

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @var AWPCP_Request
     */
    private $request;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings_payments, $listing_renderer, $payments, $template_renderer, $request ) {
        $this->listings_payments = $listings_payments;
        $this->listing_renderer  = $listing_renderer;
        $this->payments          = $payments;
        $this->template_renderer = $template_renderer;
        $this->request           = $request;
    }

    /**
     * @since 4.0.0
     */
    public function render( $post ) {
        $params = [
            'echo'                         => true,
            'user_can_change_payment_term' => awpcp_current_user_is_moderator(),
            'renewed_date'                 => $this->listing_renderer->get_renewed_date_formatted( $post ),
        ];
        $params['end_date']     = $this->listing_renderer->get_end_date_formatted( $post );
        $params['access_key']   = $this->listing_renderer->get_access_key( $post );

        $payment_term = $this->listing_renderer->get_payment_term( $post );

        $params['payment_term'] = [
            'id'                        => '',
            'name'                      => '',
            'number_of_images'          => '',
            'number_of_regions'         => '',
            'characters_in_title'       => '',
            'characters_in_description' => '',
            'url'                       => '',
        ];

        if ( $payment_term ) {
            $params['payment_term'] = $this->get_payment_term_properties( $payment_term );
        }

        $params['payment_terms'] = $this->get_available_payment_terms( $post->post_author, $payment_term );

        $this->template_renderer->render_template( 'admin/listings/listing-information-metabox.tpl.php', $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_available_payment_terms( $post_author, $current_payment_term ) {
        $current_payment_term_included = false;
        $payment_terms                 = [];

        foreach ( $this->payments->get_user_payment_terms( $post_author ) as $type => $terms ) {
            foreach ( $terms as $term ) {
                $payment_terms[] = $this->get_payment_term_properties( $term );

                if ( ! $current_payment_term ) {
                    continue;
                }

                if ( $type === $current_payment_term->type && $term->id === $current_payment_term->id ) {
                    $current_payment_term_included = true;
                }
            }
        }

        if ( $current_payment_term && ! $current_payment_term_included ) {
            array_unshift( $payment_terms, $this->get_payment_term_properties( $current_payment_term ) );
        }

        return $payment_terms;
    }

    /**
     * @since 4.0.0
     */
    private function get_payment_term_properties( $payment_term ) {
        $properties = [
            'id'                        => "{$payment_term->type}-{$payment_term->id}",
            'name'                      => $payment_term->get_name(),
            'number_of_images'          => $payment_term->images,
            'number_of_regions'         => $payment_term->get_regions_allowed(),
            'characters_in_title'       => $payment_term->get_characters_allowed_in_title(),
            'characters_in_description' => $payment_term->get_characters_allowed(),
            'url'                       => $payment_term->get_dashboard_url(),
        ];

        if ( 0 === $properties['characters_in_title'] ) {
            $properties['characters_in_title'] = _x( 'unlimited', 'listing information metabox', 'another-wordpress-classifieds-plugin' );
        }

        if ( 0 === $properties['characters_in_description'] ) {
            $properties['characters_in_description'] = _x( 'unlimited', 'listing information metabox', 'another-wordpress-classifieds-plugin' );
        }

        return $properties;
    }

    /**
     * TODO: What happens when update_listing throws an exception?
     *
     * @since 4.0.0
     */
    public function save( $post_id, $post ) {
        if ( ! awpcp_current_user_is_moderator() ) {
            return;
        }

        $this->maybe_update_payment_term( $post );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_update_payment_term( $post ) {
        $new_payment_term = $this->get_selected_payment_term();

        if ( is_null( $new_payment_term ) ) {
            return;
        }

        $this->listings_payments->update_listing_payment_term( $post, $new_payment_term );
    }

    /**
     * @since 4.0.0
     */
    private function get_selected_payment_term() {
        $selected_payment_term = $this->request->post( 'payment_term' );
        $separator_pos         = strrpos( $selected_payment_term, '-' );
        $payment_term_type     = substr( $selected_payment_term, 0, $separator_pos );
        $payment_term_id       = intval( substr( $selected_payment_term, $separator_pos + 1 ) );

        return $this->payments->get_payment_term( $payment_term_id, $payment_term_type );
    }
}
