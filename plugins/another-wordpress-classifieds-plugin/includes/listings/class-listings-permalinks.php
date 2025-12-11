<?php
/**
 * @package AWPCP/Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clases used to define permalink structure and create listings permalinks.
 */
class AWPCP_ListingsPermalinks {
    /**
     * @var string  Listing post type identifier.
     */
    private $post_type;

    /**
     * @var string  Name of the Listing's Category taxonomy.
     */
    private $category_taxonomy;

    /**
     * @var AWPCP_Rewrite_Rules_Helper
     */
    private $rewrite_rules_helper;

    public $settings;

    public $listing_renderer;

    /**
     * Constructor.
     *
     * @since 4.0.0
     */
    public function __construct( $post_type, $category_taxonomy, $listing_renderer, $rewrite_rules_helper, $settings ) {
        $this->post_type            = $post_type;
        $this->category_taxonomy    = $category_taxonomy;
        $this->listing_renderer     = $listing_renderer;
        $this->rewrite_rules_helper = $rewrite_rules_helper;
        $this->settings             = $settings;
    }

    /**
     * TODO: move to a Custom Post Types Rewrite Rules class.
     *
     * @param string       $post_type     A post type identifier.
     * @param WP_Post_Type $post_type_object  An instance of WP_Post_Type.
     * @since 4.0.0
     */
    public function update_post_type_permastruct( $post_type, $post_type_object ) {
        if ( $this->post_type !== $post_type ) {
            return;
        }

        $permastruct = $this->get_post_type_permastruct( $post_type_object );

        if ( is_null( $permastruct ) ) {
            return;
        }

        $permastruct_args = array(
            'with_front' => $post_type_object->rewrite['with_front'],
            // If the permalinks are disabled ep_mask, pages and feeds keys are not defined.
            'ep_mask'    => isset( $post_type_object->rewrite['ep_mask'] ) ? $post_type_object->rewrite['ep_mask'] : EP_PAGES,
            'paged'      => ! empty( $post_type_object->rewrite['pages'] ),
            'feed'       => ! empty( $post_type_object->rewrite['feeds'] ),
        );

        add_rewrite_tag( '%awpcp_listing_id%', '([0-9]+)', "post_type={$this->post_type}&p=" );
        add_rewrite_tag( '%awpcp_category%', '([^/]+)', "{$this->category_taxonomy}=" );
        add_rewrite_tag( '%awpcp_location%', '(.+?)', '_=' );

        if ( ! $this->settings->get_option( 'display-listings-as-single-posts' ) ) {
            return $this->update_post_type_permastruct_for_inline_listings( $permastruct, $permastruct_args );
        }

        return $this->update_post_type_permastruct_for_listings_as_single_posts( $permastruct, $permastruct_args );
    }

    /**
     * @param string $permastruct       Permalink structure for listing post type.
     * @param array  $permastruct_args  Additional arguments for add_permastruct().
     * @since 4.0.0
     */
    private function update_post_type_permastruct_for_inline_listings( $permastruct, $permastruct_args ) {
        $show_listing_page_id = $this->get_show_listing_page_id();

        add_rewrite_tag( '%awpcp_optional_listing_id%', '?(.*)', "page_id={$show_listing_page_id}&_=" );

        $permastruct_args['paged'] = false;
        $permastruct_args['feed']  = false;

        $this->add_permastruct( $permastruct, $permastruct_args );
    }

    /**
     * @param string $permastruct       Permalink structure for listing post type.
     * @param array  $permastruct_args  Additional arguments for add_permastruct().
     * @since 4.0.0
     */
    private function add_permastruct( $permastruct, $permastruct_args ) {
        add_permastruct( $this->post_type, $permastruct, $permastruct_args );
    }

    /**
     * @param string $permastruct       Permalink structure for listing post type.
     * @param array  $permastruct_args  Additional arguments for add_permastruct().
     * @since 4.0.0
     */
    private function update_post_type_permastruct_for_listings_as_single_posts( $permastruct, $permastruct_args ) {
        add_rewrite_tag( '%awpcp_optional_listing_id%', '?(.*)', '_=' );

        return $this->add_permastruct( $permastruct, $permastruct_args );
    }

    /**
     * TODO: Take this as a constructor argument. Define it on the container and pass it
     *       to all classes that need it.
     *
     *       Watch out for race conditions between container initialziation and defininf or storing settings.
     *
     * @since 4.0.0
     */
    private function get_show_listing_page_id() {
        $show_listing_page_id = awpcp_get_page_id_by_ref( 'show-ads-page-name' );
        return $show_listing_page_id ? $show_listing_page_id : -1;
    }

    /**
     * TODO: Allow admins to configure this setting as they configure the global
     *       permalink structure.
     *
     * Default structure: "/{$classifieds_slug}/{$post_type_slug}/%awpcp_listing_id%/%{$post_type_name}%/%awpcp_location%/%awpcp_category%/";
     *
     * @param object $post_type_object  An instance of WP_Post_Type.
     * @since 4.0.0
     */
    public function get_post_type_permastruct( $post_type_object ) {
        $permalink_structure = get_option( 'permalink_structure' );

        if ( ! $permalink_structure ) {
            return null;
        }

        $post_type_slug = $post_type_object->rewrite['slug'];

        if ( ! $this->settings->get_option( 'seofriendlyurls' ) ) {
            return "{$post_type_slug}/%awpcp_optional_listing_id%";
        }

        $parts = array( $post_type_slug, '%awpcp_listing_id%' );

        if ( $this->settings->get_option( 'include-title-in-listing-url' ) ) {
            $parts[] = "%{$this->post_type}%";
        }

        if ( $this->should_include_location_in_listing_url() ) {
            $parts[] = '%awpcp_location%';
        }

        if ( $this->settings->get_option( 'include-category-in-listing-url' ) ) {
            $parts[] = '%awpcp_category%';
        }

        return implode( '/', $parts );
    }

    /**
     * @since 4.0.0
     */
    private function should_include_location_in_listing_url() {
        if ( $this->settings->get_option( 'include-country-in-listing-url' ) ) {
            return true;
        }

        if ( $this->settings->get_option( 'include-state-in-listing-url' ) ) {
            return true;
        }

        if ( $this->settings->get_option( 'include-city-in-listing-url' ) ) {
            return true;
        }

        if ( $this->settings->get_option( 'include-county-in-listing-url' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Necessary to support non SEO friendly URLs when permalinks are enabled:
     * http://next.awpcp.test/awpcp/show-ads/?id=1
     *
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function maybe_set_current_post( $query ) {
        if ( ! $this->settings->get_option( 'display-listings-as-single-posts' ) ) {
            return;
        }

        if ( ! isset( $query->query_vars['id'] ) ) {
            return;
        }

        if ( ! $this->is_show_listings_page( $query ) ) {
            return;
        }

        if ( preg_match( '/([0-9]+)/', $query->query_vars['id'], $matches ) ) {
            $query->query_vars['p']         = intval( $matches[1] );
            $query->query_vars['post_type'] = $this->post_type;
            unset( $query->query_vars['pagename'] );
        }
    }

    /**
     * @since 4.0.0
     */
    private function is_show_listings_page( $query ) {
        $page_id = $this->get_show_listing_page_id();

        if ( -1 === $page_id ) {
            return false;
        }

        $page_uris = $this->rewrite_rules_helper->generate_page_uri_variants( get_page_uri( $page_id ) );

        if ( $query->request ) {
            foreach ( $page_uris as $page_uri ) {
                if ( 0 === strpos( $page_uri, $query->request ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $post_link     The post link generated by WordPress.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    public function filter_post_type_link( $post_link, $post ) {
        if ( $this->post_type !== $post->post_type ) {
            return $post_link;
        }

        // TODO: Make sure all handlers of this filter are still working on 4.0.
        // TODO: Rename to awpcp_listing_url.
        return apply_filters( 'awpcp-listing-url', $this->get_post_link( $post_link, $post ), $post );
    }

    /**
     * @param string $post_link     The post link generated by WordPress.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_post_link( $post_link, $post ) {
        $permalink_structure = get_option( 'permalink_structure' );

        if ( ! $permalink_structure && $this->settings->get_option( 'display-listings-as-single-posts' ) ) {
            return $post_link;
        }

        if ( ! $permalink_structure ) {
            return $this->get_plain_post_link( $post_link, $post );
        }

        if ( ! $this->settings->get_option( 'seofriendlyurls' ) ) {
            return $this->get_less_seo_friendly_post_link( $post_link, $post );
        }

        return $this->get_seo_friendly_post_link( $post_link, $post );
    }

    /**
     * @param string $post_link     The post link generated by WordPress.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_plain_post_link( $post_link, $post ) {
        $post_type_object = get_post_type_object( $this->post_type );

        if ( $post_type_object ) {
            $post_link = remove_query_arg( $post_type_object->query_var, $post_link );
        }

        $params = array(
            'page_id' => $this->get_show_listing_page_id(),
            'id'      => $post->ID,
        );

        return add_query_arg( $params, $post_link );
    }

    /**
     * @param string $post_link     The post link generated by WordPress.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_less_seo_friendly_post_link( $post_link, $post ) {
        $rewrite_tags = array(
            '%awpcp_optional_listing_id%' => '',
        );

        $post_link = $this->replace_rewrite_tags( $rewrite_tags, $post_link );
        $post_link = add_query_arg( 'id', $post->ID, $post_link );

        return $post_link;
    }

    /**
     * @param array  $rewrite_tags  An array of rewrite tags with their replacements.
     * @param string $post_link     The post link.
     * @since 4.0.0
     */
    private function replace_rewrite_tags( $rewrite_tags, $post_link ) {
        $post_link = str_replace( array_keys( $rewrite_tags ), array_values( $rewrite_tags ), $post_link );
        $post_link = str_replace( ':!!', '://', str_replace( '//', '/', str_replace( '://', ':!!', $post_link ) ) );

        return $post_link;
    }

    /**
     * @param string $post_link     The post link generated by WordPress.
     * @param object $post  An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_seo_friendly_post_link( $post_link, $post ) {
        $rewrite_tags = array(
            '%awpcp_listing_id%'          => $post->ID,
            '%awpcp_optional_listing_id%' => '',
            '%awpcp_category%'            => $this->listing_renderer->get_category_slug( $post ),
            '%awpcp_location%'            => $this->get_listing_location( $post ),
        );

        return $this->replace_rewrite_tags( $rewrite_tags, $post_link );
    }

    /**
     * TODO: This method probably belongs somewhere else.
     *
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    public function get_listing_location( $listing ) {
        $region = $this->listing_renderer->get_first_region( $listing );

        $parts = array();

        if ( $this->settings->get_option( 'include-city-in-listing-url' ) && $region ) {
            $parts[] = sanitize_title( awpcp_array_data( 'city', '', $region ) );
        }
        if ( $this->settings->get_option( 'include-state-in-listing-url' ) && $region ) {
            $parts[] = sanitize_title( awpcp_array_data( 'state', '', $region ) );
        }
        if ( $this->settings->get_option( 'include-country-in-listing-url' ) && $region ) {
            $parts[] = sanitize_title( awpcp_array_data( 'country', '', $region ) );
        }
        if ( $this->settings->get_option( 'include-county-in-listing-url' ) && $region ) {
            $parts[] = sanitize_title( awpcp_array_data( 'county', '', $region ) );
        }

        return strtolower( implode( '/', array_filter( $parts ) ) );
    }
}
