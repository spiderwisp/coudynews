<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generates meta tags for listings.
 */
class AWPCP_Meta {

    public $ad          = null;
    public $ad_id;
    public $properties  = array();
    public $metadata    = array();
    public $category;
    public $category_id = null;

    private $listings_collection;
    private $categories_collection;
    public $title_builder;
    private $meta_tags_genertor;
    private $query;
    private $request = null;

    private $doing_opengraph = false;
    public $doin_description_meta_tag = false;

    public function __construct( $listings_collection, $categories_collection, $title_builder, $meta_tags_genertor, $query, $request ) {
        $this->listings_collection   = $listings_collection;
        $this->categories_collection = $categories_collection;
        $this->title_builder         = $title_builder;
        $this->meta_tags_genertor    = $meta_tags_genertor;
        $this->query                 = $query;
        $this->request               = $request;

        add_action( 'wp', array( $this, 'configure' ) );
    }

    public function configure() {
        $this->find_current_listing();
        $this->find_current_category_id();

        /**
         * Runs before the plugin setups the handlers that generate the
         * default metadata.
         *
         * Used by plugin integrations to disable default behavour and configure
         * the hooks necessary to generate better metadata for listings and
         * categories.
         *
         * @since 4.0.0
         */
        do_action( 'awpcp_before_configure_frontend_meta', $this );

        $this->configure_rel_canonical();

        if ( $this->query->is_single_listing_page() ) {
            if ( $this->ad && $this->properties ) {
                $this->configure_title_generation();
                $this->configure_description_meta_tag();
                $this->configure_opengraph_meta_tags();
            }

            $this->configure_page_dates();
        }

        if ( $this->category && $this->is_browse_categories_page() ) {
            $this->configure_category_title_generator();
            $this->configure_category_description_generator();
        }

        $this->title_builder->set_current_listing( $this->ad );
        $this->title_builder->set_current_category_id( $this->category_id );
    }

    private function find_current_listing() {
        $this->ad_id = $this->request->get_current_listing_id();

        try {
            $this->ad = $this->listings_collection->get( $this->ad_id );
        } catch ( AWPCP_Exception $e ) {
            $this->ad = null;
        }

        $this->properties = awpcp_get_ad_share_info( $this->ad_id );
    }

    private function find_current_category_id() {
        $this->category_id = $this->request->get_category_id();

        try {
            $this->category = $this->categories_collection->get( $this->category_id );
        } catch ( AWPCP_Exception $e ) {
            $this->category = null;
        }
    }

    private function configure_rel_canonical() {
        if ( apply_filters( 'awpcp-should-generate-rel-canonical', true, $this ) ) {
            remove_action( 'wp_head', 'rel_canonical' );
            add_action( 'wp_head', 'awpcp_rel_canonical' );
        }
    }

    private function configure_opengraph_meta_tags() {
        if ( apply_filters( 'awpcp-should-generate-opengraph-tags', true, $this ) ) {
            add_action( 'wp_head', array( $this, 'opengraph' ) );
            $this->doing_opengraph = true;
        }
    }

    private function configure_description_meta_tag() {
        if ( apply_filters( 'awpcp-should-generate-basic-meta-tags', true, $this ) ) {
            add_action( 'wp_head', array( $this, 'generate_basic_meta_tags' ) );
            $this->doin_description_meta_tag = true;
        }
    }

    private function configure_title_generation() {
        if ( apply_filters( 'awpcp-should-generate-title', true, $this ) ) {
            add_action( 'wp_title', array( $this->title_builder, 'build_title' ), 10, 3 );
        }

        if ( apply_filters( 'awpcp-should-generate-single-post-title', true, $this ) ) {
            add_action( 'single_post_title', array( $this->title_builder, 'build_single_post_title' ) );
        }

        // SEO Ultimate.
        if ( defined( 'SU_PLUGIN_NAME' ) ) {
            $this->seo_ultimate();
        }

        // All In One SEO Pack.
        if ( class_exists( 'All_in_One_SEO_Pack' ) ) {
            $this->all_in_one_seo_pack();
        }

        // Jetpack >= 2.2.2 Integration.
        if ( function_exists( 'jetpack_og_tags' ) ) {
            $this->jetpack();
        }
    }

    private function configure_page_dates() {
        add_filter( 'get_the_date', array( $this, 'get_the_date' ), 10, 2 );
        add_filter( 'get_the_modified_date', array( $this, 'get_the_modified_date' ), 10, 2 );
    }

    private function is_browse_categories_page() {
        // We want't to use the original query but calling wp_reset_query
        // breaks things for Events Manager and maybe other plugins.
        if ( ! isset( $GLOBALS['wp_the_query'] ) ) {
            return false;
        }

        $query = $GLOBALS['wp_the_query'];
        if ( ! $query->is_page( awpcp_get_page_id_by_ref( 'browse-ads-page-name' ) ) ) {
            return false;
        }

        if ( empty( $this->category_id ) ) {
            return false;
        }

        return true;
    }

    private function remove_wp_title_filter() {
        remove_filter( 'wp_title', array( $this->title_builder, 'build_title' ) );
    }

    /**
     * The function to add the page meta and Facebook meta to the header of the index page.
     * https://www.facebook.com/sharer/sharer.php?u={url}
     */
    public function opengraph() {
        $metadata = $this->get_listing_metadata();

        $meta_tags = array_merge(
            $this->meta_tags_genertor->generate_opengraph_meta_tags( $metadata )
        );

        $this->render_meta_tags( $meta_tags, 'Open Graph' );
    }

    private function render_meta_tags( $meta_tags, $group_name ) {
        echo '<!-- START - AWP Classifieds Plugin ' . esc_html( $group_name ) . ' meta tags -->' . PHP_EOL;

        foreach ( $meta_tags as $tag ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $tag . PHP_EOL;
        }

        echo '<!-- END - AWP Classifieds Plugin ' . esc_html( $group_name ) . ' meta tags -->' . PHP_EOL;
    }

    public function get_listing_metadata() {
        if ( empty( $this->metadata ) ) {
            $this->metadata = $this->generate_listing_metadata();
        }

        return $this->metadata;
    }

    private function generate_listing_metadata() {
        $metadata = array(
            'http://ogp.me/ns#type'                   => 'article',
            'http://ogp.me/ns#url'                    => $this->properties['url'],
            'http://ogp.me/ns#title'                  => $this->properties['title'],
            'http://ogp.me/ns#description'            => htmlspecialchars( $this->properties['description'], ENT_QUOTES, get_bloginfo( 'charset' ) ),
            'http://ogp.me/ns/article#published_time' => awpcp_datetime( 'c', $this->properties['published-time'] ),
            'http://ogp.me/ns/article#modified_time'  => awpcp_datetime( 'c', $this->properties['modified-time'] ),
        );

        foreach ( $this->properties['images'] as $image ) {
            $metadata['http://ogp.me/ns#image'] = $image;
            break;
        }

        if ( empty( $this->properties['images'] ) ) {
            $metadata['http://ogp.me/ns#image'] = AWPCP_URL . '/resources/images/adhasnoimage.png';
        }

        return $metadata;
    }

    public function get_listing_metadata_property( $property, $default = '' ) {
        $metadata = $this->get_listing_metadata();

        if ( isset( $metadata[ $property ] ) ) {
            return $metadata[ $property ];
        }

        return $default;
    }

    public function generate_basic_meta_tags() {
        $metadata  = $this->get_listing_metadata();
        $meta_tags = $this->meta_tags_genertor->generate_basic_meta_tags( $metadata );

        $this->render_meta_tags( $meta_tags, 'Basic' );
    }

    public function get_the_date( $the_date, $format = '' ) {
        if ( empty( $this->properties['published-time'] ) ) {
            return $the_date;
        }

        if ( ! $format ) {
            $format = get_option( 'date_format' );
        }

        return mysql2date( $format, $this->properties['published-time'] );
    }

    public function get_the_modified_date( $the_date, $format ) {
        if ( empty( $this->properties['modified-time'] ) ) {
            return $the_date;
        }

        if ( ! $format ) {
            $format = get_option( 'date_format' );
        }

        return mysql2date( $format, $this->properties['modified-time'] );
    }

    /**
     * Integration with SEO Ultimate.
     */
    public function seo_ultimate() {
        // Overwrite title.
        add_filter( 'single_post_title', array( $this, 'seo_ultimate_title' ) );
        $this->remove_wp_title_filter();

        // Disable OpenGraph meta tags in Show Ad page.
        if ( $this->doing_opengraph ) {
            awpcp_remove_filter( 'su_head', 'SU_OpenGraph' );
        }
    }

    public function seo_ultimate_title( $title ) {
        $settings     = get_option( 'seo_ultimate_module_titles' );
        $title_format = awpcp_array_data( 'title_page', '', $settings );
        $seplocation  = 'right';

        if ( string_starts_with( $title_format, '{blog}' ) ) {
            $seplocation = 'left';
        }

        return $this->title_builder->build_title( $title, '', $seplocation );
    }

    /**
     * Integration with All In One SEO Pack
     */
    public function all_in_one_seo_pack() {
        add_filter( 'aioseop_title', array( $this, 'all_in_one_seo_pack_title' ) );
        $this->remove_wp_title_filter();
    }

    public function all_in_one_seo_pack_title( $title ) {
        global $aioseop_options;

        $title_format = awpcp_array_data( 'aiosp_page_title_format', '', $aioseop_options );
        $seplocation  = 'left';

        if ( string_starts_with( $title_format, '%page_title%' ) ) {
            $seplocation = 'right';
        }

        return $this->title_builder->build_title( $title, '', $seplocation );
    }

    /**
     * Jetpack Integration
     */
    public function jetpack() {
        if ( ! $this->doing_opengraph ) {
            return;
        }

        remove_action( 'wp_head', 'jetpack_og_tags' );
    }

    /**
     * @since 4.0.0
     */
    private function configure_category_title_generator() {
        if ( apply_filters( 'awpcp_should_generate_category_title', true, $this ) ) {
            add_action( 'wp_title', [ $this->title_builder, 'build_title' ], 10, 3 );
        }

        do_action( 'awpcp_configure_category_title_generator', $this );
    }

    /**
     * @since 4.0.0
     */
    private function configure_category_description_generator() {
        if ( apply_filters( 'awpcp_should_generate_category_description', true, $this ) ) {
            add_action( 'wp_head', [ $this, 'generate_category_description_meta_tag' ], 1 );
        }

        do_action( 'awpcp_configure_category_description_generator' );
    }

    /**
     * @since 4.0.0
     */
    public function generate_category_description_meta_tag() {
        $metadata = [
            'http://ogp.me/ns#title'       => null,
            'http://ogp.me/ns#description' => $this->category->description,
        ];

        $meta_tags = $this->meta_tags_genertor->generate_basic_meta_tags( $metadata );

        $this->render_meta_tags( [ $meta_tags['description'] ], 'Basic' );
    }
}
