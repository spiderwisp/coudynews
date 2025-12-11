<?php
/**
 * @package AWPCP/Compatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin integration for SEO Framework plugin.
 *
 * @since 4.1.0
 */
class AWPCP_SEOFrameworkIntegration {

    private $current_listing;

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var AWPCP_Attachments_Collection
     */
    private $attachments;

    public $attachment_properties;

    public $title_builder;

    public $metadata;

    public $is_singular;

    public $attachment;

    public function __construct() {
        $container = awpcp()->container;

        $this->listing_post_type     = $container['listing_post_type'];
        $this->attachments           = $container['AttachmentsCollection'];
        $this->attachment_properties = awpcp_attachment_properties();
    }

    /**
     * @since 4.1.0
     */
    public function setup() {
        if ( $this->are_required_classes_loaded() ) {
            add_action( 'awpcp_before_configure_frontend_meta', [ $this, 'before_configure_frontend_meta' ] );
        }
    }

    /**
     * @since 4.1.0
     */
    private function are_required_classes_loaded() {
        if ( ! defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
            // SEO plugin doesn't seem to be loaded. Bail.
            return false;
        }

        return function_exists( 'tsf' );
    }

    /**
     * @since 4.1.0
     */
    public function before_configure_frontend_meta( $meta ) {
        $this->current_listing = $meta->ad;
        $this->title_builder   = $meta->title_builder;
        $this->is_singular     = is_singular( $this->listing_post_type );
        $this->metadata        = [];

        if ( $this->current_listing ) {
            $this->metadata = $meta->get_listing_metadata();
        }

        add_filter( 'awpcp-should-generate-rel-canonical', [ $this, 'configure_canonical_url' ] );
        add_filter( 'awpcp-should-generate-title', [ $this, 'configure_title_generation' ] );
        add_filter( 'awpcp-should-generate-basic-meta-tags', [ $this, 'configure_description_meta_tags' ] );
        add_filter( 'awpcp-should-generate-opengraph-tags', [ $this, 'configure_opengraph_meta_tags' ] );

        add_filter( 'awpcp-should-generate-rel-canonical', '__return_false' );
        add_filter( 'awpcp-should-generate-basic-meta-tags', '__return_false' );
        add_filter( 'awpcp-should-generate-title', '__return_false' );
    }

    /**
     * @since 4.1.0
     */
    public function configure_canonical_url() {
        add_filter( 'the_seo_framework_rel_canonical_output', [ $this, 'canonical_url' ] );
    }

    /**
     * @since 4.1.0
     */
    public function configure_title_generation() {
        add_filter( 'the_seo_framework_pre_get_document_title', [ $this, 'filter_document_title' ] );
    }

    /**
     * On Show Ad page:
     * - If the listing has a SEO override, we should use the override (don't forget
     * to replace any snippet variables included).
     * - If the listing has no SEO override, generate good default.
     *
     * On an Ad own page:
     * - If the listing has a SEO override, we use the override without attempting
     * to replace any variables. Yoast must have already done that.
     * - If the listing has no SEO override, generate a good default.
     *
     * @since 4.0.0
     */
    public function configure_description_meta_tags() {
        add_filter( 'the_seo_framework_description_output', [ $this, 'filter_listing_description' ] );
    }

    /**
     * On Show Ad page:
     * - If the listing has a SEO override, we should use the override (don't forget
     * to replace any snippet variables included).
     * - If the listing has no SEO override, generate good default.
     *
     * On an Ad own page:
     * - If the listing has a SEO override, we use the override without attempting
     * to replace any variables. Yoast must have already done that.
     * - If the listing has no SEO override, generate a good default.
     *
     * @since 4.1.0
     */
    public function configure_opengraph_meta_tags() {
        add_filter( 'the_seo_framework_ogtype_output', [ $this, 'filter_opengraph_type' ] );
        add_filter( 'the_seo_framework_ogimage_output', [ $this, 'add_opengraph_images' ] );
        add_filter( 'the_seo_framework_ogtitle_output', [ $this, 'filter_opengraph_title' ] );
        add_filter( 'the_seo_framework_ogdescription_output', [ $this, 'filter_opengraph_description' ] );
        add_filter( 'the_seo_framework_ogurl_output', [ $this, 'filter_opengraph_url' ] );
        add_filter( 'the_seo_framework_available_twitter_cards', [ $this, 'twitter_cards' ] );
        add_filter( 'the_seo_framework_twitterimage_output', [ $this, 'add_opengraph_images' ] );
        add_filter( 'the_seo_framework_twitterdescription_output', [ $this, 'filter_twitter_description' ] );
        add_filter( 'the_seo_framework_twittertitle_output', [ $this, 'filter_twitter_title' ] );

        return false;
    }

    /**
     * @since 4.1.0
     */
    public function filter_document_title( $title ) {
        $override = get_post_meta( $this->current_listing->ID, '_genesis_title', true );
        if ( empty( $override ) ) {
            return $this->build_title( $title );
        }

        if ( $this->is_singular ) {
            return $title;
        }

        return $override;
    }

    private function build_title( $title ) {
        $separator = '';

        if ( isset( $GLOBALS['sep'] ) ) {
            $separator = $GLOBALS['sep'];
        }

        return $this->title_builder->build_title( $title, $separator, '' );
    }

    /**
     * @since 4.1.0
     */
    public function filter_listing_description( $description ) {
        $override = get_post_meta( $this->current_listing->ID, '_genesis_description', true );

        return $this->get_social_description( $description, $override );
    }

    /**
     * @since 4.1.0
     */
    private function get_social_description( $description, $override ) {
        if ( empty( $override ) ) {
            return $this->metadata['http://ogp.me/ns#description'];
        }

        if ( $this->is_singular ) {
            return $description;
        }

        return $override;
    }

    /**
     * @since 4.1.0
     */
    public function filter_opengraph_type( $type ) {

        if ( $this->is_singular ) {
            return $type;
        }

        return $this->metadata['http://ogp.me/ns#type'];
    }

    /**
     * @since 4.1.0
     */
    public function add_opengraph_images() {
        $override = get_post_meta( $this->current_listing->ID, '_social_image_url', true );

        if ( empty( $override ) ) {
            $featured_image = $this->attachments->get_featured_attachment_of_type(
                'image',
                [ 'post_parent' => $this->current_listing->ID ]
            );

            if ( $featured_image ) {

                return $this->attachment_properties->get_image_url( $featured_image, 'large' );
            }

            return $this->metadata['http://ogp.me/ns#image'];
        }

        return $override;
    }

    /**
     * @since 4.1.0
     */
    public function filter_opengraph_title( $title ) {
        $override = get_post_meta( $this->current_listing->ID,  '_open_graph_title' );

        return $this->get_social_title( $title, $override );
    }

    /**
     * @since 4.1.0
     */
    private function get_social_title( $title, $override ) {
        if ( empty( $override ) ) {
            return $this->metadata['http://ogp.me/ns#title'];
        }

        if ( $this->is_singular ) {
            return $title;
        }

        return $override;
    }

    /**
     * @since 4.1.0
     */
    public function filter_opengraph_url() {
        return $this->metadata['http://ogp.me/ns#url'];
    }

    /**
     * @since 4.1.0
     */
    public function filter_opengraph_description( $description ) {
        $override = get_post_meta( $this->current_listing->ID, '_open_graph_description' );

        return $this->get_social_description( $description, $override );
    }

    /**
     * @since 4.1.0
     */
    public function filter_twitter_title( $title ) {
        $override = get_post_meta( $this->current_listing->ID,  '_twitter_title' );

        return $this->get_social_title( $title, $override );
    }

    /**
     * @since 4.1.0
     */
    public function filter_twitter_description( $description ) {
        $override = get_post_meta( $this->current_listing->ID,  '_twitter_description' );

        return $this->get_social_description( $description, $override );
    }

    /**
     * TODO: move to a parent class for all SEO plugin integrations.
     */
    public function canonical_url( $url ) {
        $awpcp_canonical_url = awpcp_rel_canonical_url();

        if ( $awpcp_canonical_url ) {
            return $awpcp_canonical_url;
        }

        return $url;
    }

    /**
     * Needed to make twitter cards work On Show Ad page
     *
     * @since 4.1.0
     */
    public function twitter_cards() {
        return [ 'summary_large_image' ];
    }
}
