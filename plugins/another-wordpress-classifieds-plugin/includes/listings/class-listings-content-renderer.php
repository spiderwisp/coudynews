<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class that renders the information shown on individual lisitngs pages.
 */
class AWPCP_ListingsContentRenderer {

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @param object $listing_renderer  An instance of ListingRenderer.
     */
    public function __construct( $listing_renderer ) {
        $this->listing_renderer = $listing_renderer;
    }

    /**
     * @param string $content   The content of the post as passed to the
     *                          `the_content` filter.
     * @param object $post      An instance of WP_Post.
     * @since 4.0.0
     */
    public function render( $content, $post ) {
        $user_can_see_disabled_listing = $this->current_user_can_see_disabled_listing( $post );

        if ( $this->listing_renderer->is_pending_approval( $post ) && ! $user_can_see_disabled_listing ) {
            $message = __( 'This listing is currently disabled until an administrator approves it.', 'another-wordpress-classifieds-plugin' );
            return awpcp_print_error( $message );
        }

        if ( ( ! $this->listing_renderer->is_public( $post ) || $this->listing_renderer->is_expired( $post ) ) && ! $user_can_see_disabled_listing ) {
            $message = __( 'The listing you are trying to view is not available right now.', 'another-wordpress-classifieds-plugin' );
            return awpcp_print_error( $message );
        }

        $output = apply_filters( 'awpcp-show-listing-content-replacement', null, $content, $post );

        if ( ! is_null( $output ) ) {
            return $output;
        }

        if ( $user_can_see_disabled_listing ) {
            return $this->render_content_with_notices( $content, $post );
        }

        return $this->render_content_without_notices( $content, $post );
    }

    /**
     * @param object $post An instance of WP_Post.
     * @since 4.0.0
     */
    private function current_user_can_see_disabled_listing( $post ) {
        if ( awpcp_current_user_is_moderator() ) {
            return true;
        }

        if ( $this->current_user_is_listing_owner( $post ) ) {
            return true;
        }

        return false;
    }

    /**
     * @param object $post An instance of WP_Post.
     * @since 4.0.0
     */
    private function current_user_is_listing_owner( $post ) {
        if ( $post->post_author > 0 && wp_get_current_user()->ID === (int) $post->post_author ) {
            return true;
        }

        return false;
    }

    /**
     * Renders listing's content and user notices assuming current user is either
     * a moderator or the listing's owner.
     *
     * @param string $content   The content of the post as passed to the
     *                          `the_content` filter.
     * @param object $post      An instance of WP_Post.
     * @since 4.0.0
     */
    public function render_content_with_notices( $content, $post ) {
        return implode( "\n", $this->render_messages( $post ) ) . $this->render_content_without_notices( $content, $post );
    }

    /**
     * @param object $post An instance of WP_Post.
     * @since 4.0.0
     */
    private function render_messages( $post ) {
        $messages = array();

        $is_listing_verified = $this->listing_renderer->is_verified( $post );

        if ( $is_listing_verified && awpcp_get_var( array( 'param' => 'verified' ) ) ) {
            $messages[] = awpcp_print_message( __( 'Your email address was successfully verified.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( ! $is_listing_verified ) {
            $messages[] = $this->get_unverified_listing_warning();
        } elseif ( $this->listing_renderer->is_disabled( $post ) ) {
            $messages[] = $this->get_disabled_listing_warning();
        }

        return $messages;
    }

    /**
     * @since 4.0.0
     */
    private function get_unverified_listing_warning() {
        if ( awpcp_current_user_is_moderator() ) {
            $message = __( 'This listing is currently disabled until the owner verifies the email address used for the contact information. Right now only administrators users and the owner of the listing can see it. A verification email has been sent to you.', 'another-wordpress-classifieds-plugin' );
            return awpcp_print_error( $message );
        }

        $message = __( 'This listing is currently disabled until you verify the email address used for the contact information. Right now only you (the owner) and administrators users can see it.', 'another-wordpress-classifieds-plugin' );
        return awpcp_print_error( $message );
    }

    /**
     * @since 4.0.0
     */
    private function get_disabled_listing_warning() {
        if ( awpcp_current_user_is_moderator() ) {
            $message = __( 'This listing is currently disabled until an administrator approves it. As soon as an administrator approves the listing, it will become visilbe on the system. Right now only administrators users and the owner of the listing can see it.', 'another-wordpress-classifieds-plugin' );
            return awpcp_print_error( $message );
        }

        $message = __( 'This listing is currently disabled until an administrator approves it. As soon as an administrator approves the listing, it will become visilbe on the system. Right now only you (the owner) and administrators users can see it.', 'another-wordpress-classifieds-plugin' );
        return awpcp_print_error( $message );
    }

    /**
     * @since 4.3.3
     *
     * @param string $content   The content of the post.
     * @param object $post      An instance of WP_Post.
     *
     * @return void
     */
    public function show_content_without_notices( $content, $post ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_content_without_notices( $content, $post );
    }

    /**
     * @param string $content   The content of the post.
     * @param object $post      An instance of WP_Post.
     * @return string Show Ad page content.
     */
    public function render_content_without_notices( $content, $post ) {
        // Filters to provide alternative method of storing custom layouts.
        if ( has_action( 'awpcp_single_ad_template_action' ) || has_filter( 'awpcp_single_ad_template_filter' ) ) {
            do_action( 'awpcp_single_ad_template_action' );
            return apply_filters( 'awpcp_single_ad_template_filter', '' );
        }

        /* Enqueue necessary scripts. */
        awpcp_maybe_add_thickbox();
        awpcp_maybe_enqueue_font_awesome_style();
        wp_enqueue_script( 'awpcp-page-show-ad' );

        $awpcp = awpcp();

        $awpcp->js->set( 'page-show-ad-flag-ad-nonce', wp_create_nonce( 'flag_ad' ) );
        $awpcp->js->set( 'ad-id', $post->ID );

        $awpcp->js->localize(
            'page-show-ad',
            [
                'flag-confirmation-message' => __( 'Are you sure you want to flag this ad?', 'another-wordpress-classifieds-plugin' ),
                'flag-success-message'      => __( 'This Ad has been flagged.', 'another-wordpress-classifieds-plugin' ),
                'flag-error-message'        => __( 'An error occurred while trying to flag the Ad.', 'another-wordpress-classifieds-plugin' ),
            ]
        );

        $content_before_page = apply_filters( 'awpcp-content-before-listing-page', '' );
        $content_after_page  = apply_filters( 'awpcp-content-after-listing-page', '' );

        $output = '<div id="classiwrapper">%s<!--awpcp-single-ad-layout-->%s</div><!--close classiwrapper-->';
        $output = sprintf( $output, $content_before_page, $content_after_page );

        // Use the content provided by the user of this class.
        $post->post_content = $content;

        $layout = awpcp_get_listing_single_view_layout( $post );
        $layout = awpcp_do_placeholders( $post, $layout, 'single' );

        $output = str_replace( '<!--awpcp-single-ad-layout-->', $layout, $output );
        $output = apply_filters( 'awpcp-show-ad', $output, $post->ID );

        return $output;
    }
}
