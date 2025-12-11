<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_listing_spam_filter() {
    return new AWPCP_SpamFilter( awpcp_akismet_wrapper_factory()->get_akismet_wrapper(), awpcp_listing_akismet_data_source() );
}

function awpcp_listing_akismet_data_source() {
    return new AWPCP_ListingAkismetDataSource(
        awpcp_listing_renderer()
    );
}

class AWPCP_ListingAkismetDataSource {

    private $listing_renderer;

    public function __construct( $listing_renderer ) {
        $this->listing_renderer = $listing_renderer;
    }

    public function get_request_data( $listing ) {
        $subject_data = array(
            'comment_type' => 'comment',
            'comment_author' => $this->listing_renderer->get_contact_name( $listing ),
            'comment_author_email' => $this->listing_renderer->get_contact_email( $listing ),
            'comment_author_url' => $this->listing_renderer->get_website_url( $listing ),
            'comment_content' => $listing->post_content,
        );

        if ( isset( $listing->ID ) ) {
            $subject_data['permalink'] = url_showad( intval( $listing->ID ) );
        }

        return $subject_data;
    }
}
