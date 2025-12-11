<?php
/**
 * @package AWPCP/Compatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Yoast WordPress SEO Plugin Integration.
 */
function awpcp_yoast_wordpress_seo_plugin_integration() {
    _deprecated_function( __FUNCTION__, '4.2' );
}

class AWPCP_YoastWordPressSEOPluginIntegration {

    protected $metadata;

    public function __construct() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function setup() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function before_configure_frontend_meta() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function configure_canonical_url() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function configure_title_generation() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function configure_description_meta_tags() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function configure_opengraph_meta_tags() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function filter_listing_title( $title ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $title;
    }

    /**
     * @since 4.0.0
     */
    public function filter_listing_description( $description ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $description;
    }

    /**
     * TODO: move to a parent class for all SEO plugin integrations.
     */
    public function canonical_url( $url ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $url;
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_type( $type ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $type;
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_title( $title ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $title;
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_description( $description ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $description;
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_url() {
        _deprecated_function( __METHOD__, '4.2' );
        return $this->metadata['http://ogp.me/ns#url'];
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_published_time() {
        _deprecated_function( __METHOD__, '4.2' );
        return $this->metadata['http://ogp.me/ns/article#published_time'];
    }

    /**
     * @since 4.0.0
     */
    public function filter_opengraph_modified_time() {
        _deprecated_function( __METHOD__, '4.2' );
        return $this->metadata['http://ogp.me/ns/article#modified_time'];
    }

    /**
     * @since 4.0.0
     */
    public function add_opengraph_images() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function filter_twitter_title( $title ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $title;
    }

    /**
     * @since 4.0.0
     */
    public function filter_twitter_description( $description ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $description;
    }

    /**
     * @since 4.0.0
     */
    public function configure_category_title_generator() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function filter_category_title( $title ) {
        return $title;
    }

    /**
     * @since 4.0.0
     */
    public function configure_category_description_generator() {
        _deprecated_function( __METHOD__, '4.2' );
    }

    /**
     * @since 4.0.0
     */
    public function filter_category_description( $description ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $description;
    }
}
