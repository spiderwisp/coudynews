<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_edit_listing_link_placeholder() {
    return new AWPCP_EditListingLinkPlaceholder(
        awpcp_listing_renderer(),
        awpcp_listing_authorization()
    );
}

class AWPCP_EditListingLinkPlaceholder {

    private $listing_renderer;
    private $authorization;

    public function __construct( $listing_renderer, $authorization ) {
        $this->listing_renderer = $listing_renderer;
        $this->authorization = $authorization;
    }

    public function do_placeholder( $listing, $placeholder, $context ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        if ( ! $this->authorization->is_current_user_allowed_to_edit_listing( $listing ) ) {
            return '';
        }

        return $this->generate_edit_listing_link( $listing );
    }

    private function generate_edit_listing_link( $listing ) {
        $template = '<a href="<edit-listing-url>" title="<link-title>"><link-text></a>';

        $template = str_replace( '<edit-listing-url>', esc_url( awpcp_get_edit_listing_url( $listing ) ), $template );
        $template = str_replace( '<link-title>', esc_attr( $this->generate_link_title( $listing ) ), $template );
        $template = str_replace( '<link-text>', esc_html( _x( 'Edit Ad', 'text for edit listing link', 'another-wordpress-classifieds-plugin' ) ), $template );

        return $template;
    }

    private function generate_link_title( $listing ) {
        $template = _x( 'Edit <listing-title>', 'title attribute for edit listing link', 'another-wordpress-classifieds-plugin' );
        $template = str_replace( '<listing-title>', $this->listing_renderer->get_listing_title( $listing ), $template );

        return $template;
    }
}
