<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Custom Post Types class.
 */
function awpcp_custom_post_types() {
    return new AWPCP_Custom_Post_Types(
        'awpcp_listing',
        'awpcp_listing_category',
        awpcp_roles_and_capabilities(),
        awpcp_settings_api()
    );
}

/**
 * Class used to regsiter the plugin's custom post types, taxonomies and status.
 */
class AWPCP_Custom_Post_Types {

    /**
     * @var object
     */
    private $roles_and_capabilities;

    /**
     * @var object
     */
    private $settings;

    /**
     * @var string
     */
    public $listings_post_type;

    public $listings_category_taxonomy;

    /**
     * @param string $listings_post_type            The identifier for the Listings post type.
     * @param string $listings_category_taxonomy    The identifier for the Listings taxonomy.
     * @param object $roles_and_capabilities        An instance of Roles And Capabilities.
     * @param object $settings                      An instance of Settings.
     * @since 4.0.0
     */
    public function __construct( $listings_post_type, $listings_category_taxonomy, $roles_and_capabilities, $settings ) {
        $this->listings_post_type         = $listings_post_type;
        $this->listings_category_taxonomy = $listings_category_taxonomy;
        $this->roles_and_capabilities     = $roles_and_capabilities;
        $this->settings                   = $settings;
    }

    /**
     * @since 4.1.3
     */
    public function register_custom_post() {
        $this->register_custom_post_status();
        $this->register_custom_post_types();
        $this->register_custom_taxonomies();
        $this->register_custom_image_sizes();
    }

    /**
     * TODO: Do we really want to do this?
     *
     * @since 4.0.0
     */
    public function register_custom_post_status() {
        // XXX: Other possibles status are: Draft, Payment, Verification, Published, Disabled, Review.
        register_post_status(
            'disabled',
            array(
                'label'                     => __( 'Disabled', 'another-wordpress-classifieds-plugin' ),
                'public'                    => false,
                'protected'                 => true,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                // translators: %d Number of posts on this state.
                'label_count'               => _n_noop( 'Disabled <span class="count">(%d)</span>', 'Disabled <span class="count">(%d)</span>', 'another-wordpress-classifieds-plugin' ),
            )
        );
    }

    /**
     * @since 4.0.0
     */
    public function register_custom_post_types() {
        $moderator_capability = $this->roles_and_capabilities->get_moderator_capability();
        $post_type_slug       = $this->get_post_type_slug();

        register_post_type(
            $this->listings_post_type,
            array(
                'labels'               => array(
                    'name'               => __( 'Classifieds', 'another-wordpress-classifieds-plugin' ),
                    'singular_name'      => __( 'Classified Ad', 'another-wordpress-classifieds-plugin' ),
                    'all_items'          => __( 'Classifieds', 'another-wordpress-classifieds-plugin' ),
                    'add_new'            => _x( 'Add New', 'awpcp_listing', 'another-wordpress-classifieds-plugin' ),
                    'add_new_item'       => __( 'Add New Classified Ad', 'another-wordpress-classifieds-plugin' ),
                    'edit_item'          => __( 'Edit Classified Ad', 'another-wordpress-classifieds-plugin' ),
                    'new_item'           => __( 'New Classified Ad', 'another-wordpress-classifieds-plugin' ),
                    'view_item'          => __( 'View Classified Ad', 'another-wordpress-classifieds-plugin' ),
                    'search_items'       => __( 'Search Now', 'another-wordpress-classifieds-plugin' ),
                    'not_found'          => __( 'No classifieds found', 'another-wordpress-classifieds-plugin' ),
                    'not_found_in_trash' => __( 'No classifieds found in Trash', 'another-wordpress-classifieds-plugin' ),
                    'menu_name'          => __( 'Classified Ads', 'another-wordpress-classifieds-plugin' ),
                ),
                'description'          => __( 'A classified ad.', 'another-wordpress-classifieds-plugin' ),
                'public'               => true,
                'exclude_from_search'  => true,
                'show_in_menu'         => false,
                'show_in_admin_bar'    => true,
                'menu_icon'            => null,
                'supports'             => array(
                    'title',
                    'editor',
                    'author',
                    'thumbnail',
                    'excerpt',
                    'custom-fields',
                ),
                'capability_type'      => 'awpcp_classified_ad',
                'map_meta_cap'         => true,
                'capabilities'         => array(
                    'edit_posts'             => $moderator_capability,
                    'edit_published_posts'   => $moderator_capability,
                    'delete_posts'           => $moderator_capability,
                    'read_private_posts'     => $moderator_capability,
                    'edit_private_posts'     => $moderator_capability,
                    'edit_others_posts'      => $moderator_capability,
                    'publish_posts'          => $moderator_capability,
                    'delete_private_posts'   => $moderator_capability,
                    'delete_published_posts' => $moderator_capability,
                    'delete_others_posts'    => $moderator_capability,
                    'create_posts'           => $moderator_capability,
                ),
                'register_meta_box_cb' => null,
                'taxonomies'           => array(
                    $this->listings_category_taxonomy,
                ),
                'has_archive'          => false,
                'rewrite'              => array(
                    'slug'       => $post_type_slug,
                    'with_front' => false,
                ),
                'query_var'            => 'awpcp_listing',
            )
        );
    }

    /**
     * @since 4.0.0
     */
    private function get_post_type_slug() {
        $default_slug = _x( 'classifieds', 'listing post type slug', 'another-wordpress-classifieds-plugin' );

        if ( ! $this->settings->get_option( 'display-listings-as-single-posts' ) ) {
            $show_listings_page = awpcp_get_page_by_ref( 'show-ads-page-name' );

            return $show_listings_page ? get_page_uri( $show_listings_page ) : $default_slug;
        }

        $post_type_slug = $this->settings->get_option( 'listings-slug', $default_slug );

        if ( ! $this->settings->get_option( 'include-main-page-slug-in-listing-url' ) ) {
            return $post_type_slug;
        }

        $main_listings_page = awpcp_get_page_by_ref( 'main-page-name' );

        if ( ! $main_listings_page ) {
            return $post_type_slug;
        }

        return get_page_uri( $main_listings_page ) . '/' . $post_type_slug;
    }

    /**
     * @since 4.0.0
     */
    public function register_custom_taxonomies() {
        register_taxonomy(
            $this->listings_category_taxonomy,
            $this->listings_post_type,
            array(
                'labels'            => array(
                    'name'          => _x( 'Categories', 'taxonomy general name', 'another-wordpress-classifieds-plugin' ),
                    'singular_name' => _x( 'Category', 'taxonomy general name', 'another-wordpress-classifieds-plugin' ),
                ),
                'show_in_menu'      => false,
                'hierarchical'      => true,
                'query_var'         => 'awpcp_category',
                'rewrite'           => array(
                    'slug' => 'listing-category',
                ),
                'show_admin_column' => true,
            )
        );

        register_taxonomy_for_object_type( 'awpcp_listing_category', 'awpcp_listing' );

        // $terms = get_terms(
        //     'awpcp_listing_category',
        //     array(
        //         'hide_empty' => false,
        //     )
        // );
        //
        // foreach ( $terms as $term ) {
        //     wp_delete_term( $term->term_id, 'awpcp_listing_category' );
        // }
    }

    /**
     * @since 4.0.0
     */
    public function register_custom_image_sizes() {
        add_image_size(
            'awpcp-thumbnail',
            $this->settings->get_option( 'imgthumbwidth' ),
            $this->settings->get_option( 'imgthumbheight' ),
            $this->settings->get_option( 'crop-thumbnails' )
        );

        add_image_size(
            'awpcp-featured',
            $this->settings->get_option( 'primary-image-thumbnail-width' ),
            $this->settings->get_option( 'primary-image-thumbnail-height' ),
            $this->settings->get_option( 'crop-primary-image-thumbnails' )
        );

        add_image_size(
            'awpcp-featured-on-lists',
            $this->settings->get_option( 'displayadthumbwidth' ),
            $this->settings->get_option( 'featured-image-height-on-lists' ),
            $this->settings->get_option( 'crop-featured-image-on-lists' )
        );

        add_image_size(
            'awpcp-large',
            $this->settings->get_option( 'imgmaxwidth' ),
            $this->settings->get_option( 'imgmaxheight' ),
            false
        );
    }

    /**
     * TODO: This method probably belongs somewhere else. A class where we create
     * default categories and fee plans, maybe.
     */
    public function create_default_category() {
        $category_name = __( 'General', 'another-wordpress-classifieds-plugin' );
        $category_id   = null;

        try {
            $category_data = [
                'name' => $category_name,
            ];

            $category_id = awpcp_categories_logic()->create_category( $category_data );
        } catch ( AWPCP_Exception $e ) {
            $category = get_term_by( 'name', $category_name, $this->listings_category_taxonomy );

            if ( $category ) {
                $category_id = $category->term_id;
            }
        }

        if ( ! $category_id ) {
            return;
        }

        add_term_meta( $category_id, '_awpcp_order', 0, true );

        update_option( 'awpcp-main-category-id', $category_id );
    }
}
