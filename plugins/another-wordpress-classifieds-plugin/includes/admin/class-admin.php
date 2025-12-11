<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Entry point for all plugin features available from the Classified Ads admin menu.
 */
class AWPCP_Admin {

    /**
     * @var string
     */
    private $post_type;

    /**
     * @var array
     */
    private $container;

    /**
     * @var object
     */
    private $table_views;

    /**
     * @var object
     */
    private $table_actions;

    /**
     * @var object
     */
    private $table_nav;

    /**
     * @var object
     */
    private $table_search;

    /**
     * @var object
     */
    private $table_columns;

    /**
     * @var object
     */
    private $table_restrictions;

    /**
     * @since 4.0.0
     *
     * @param string $post_type             A post type identifier.
     * @param array  $container             An instance of Container.
     * @param object $table_views           An instance of List Table Views Handler.
     * @param object $table_actions         An instance of List Table Actions Handler.
     * @param object $table_nav             An instance of List Table Nav Handler.
     * @param object $table_search          An instance of List Table Search Handler.
     * @param object $table_columns         An instance of List Table Columns Handler.
     * @param object $table_restrictions    An instance of List Table Restrictions.
     */
    public function __construct(
        $post_type,
        $container,
        $table_views,
        $table_actions,
        $table_nav,
        $table_search,
        $table_columns,
        $table_restrictions
    ) {
        $this->post_type          = $post_type;
        $this->container          = $container;
        $this->table_views        = $table_views;
        $this->table_actions      = $table_actions;
        $this->table_nav          = $table_nav;
        $this->table_search       = $table_search;
        $this->table_columns      = $table_columns;
        $this->table_restrictions = $table_restrictions;
    }

    /**
     * @since 4.0.0
     */
    public function admin_init() {
        global $typenow;

        if ( $this->post_type === $typenow ) {
            add_filter( "get_user_option_edit_{$this->post_type}_per_page", [ $this, 'filter_items_per_page_user_option' ], 10, 3 );

            add_action( 'pre_get_posts', array( $this->table_restrictions, 'pre_get_posts' ) );
            add_filter( 'awpcp_before_admin_listings_views', [ $this->table_restrictions, 'maybe_add_count_listings_query_filter' ] );
            add_filter( 'awpcp_after_admin_listings_views', [ $this->table_restrictions, 'maybe_remove_count_listings_query_filter' ] );
            add_filter( 'wp_count_posts', [ $this->table_restrictions, 'filter_posts_count' ], 10, 2 );

            add_action( 'pre_get_posts', array( $this->table_views, 'pre_get_posts' ) );
            add_filter( 'views_edit-' . $this->post_type, array( $this->table_views, 'views' ) );

            add_action( 'admin_head-edit.php', array( $this->table_actions, 'admin_head' ), 10, 2 );
            add_filter( "{$this->post_type}_row_actions", [ $this->table_actions, 'row_actions_buttons' ], 10, 2 );
            add_filter( "bulk_actions-edit-{$this->post_type}", [ $this->table_actions, 'get_bulk_actions' ] );
            add_filter( 'handle_bulk_actions-edit-' . $this->post_type, array( $this->table_actions, 'handle_action' ), 10, 3 );

            add_filter( 'disable_months_dropdown', '__return_true' );
            add_action( 'pre_get_posts', array( $this->table_nav, 'pre_get_posts' ) );
            add_action( 'restrict_manage_posts', array( $this->table_nav, 'restrict_listings' ), 10, 2 );

            add_action( 'admin_enqueue_scripts', array( $this->table_search, 'enqueue_scripts' ) );
            add_action( 'pre_get_posts', array( $this->table_search, 'pre_get_posts' ) );
            add_filter( 'get_search_query', array( $this->table_search, 'get_search_query' ) );
            add_action( 'manage_posts_extra_tablenav', array( $this->table_search, 'render_search_mode_dropdown' ) );

            add_action( 'pre_get_posts', array( $this->table_columns, 'pre_get_posts' ) );
            add_action( 'posts_orderby', array( $this->table_columns, 'posts_orderby' ), 10, 2 );
            add_filter( "manage_{$this->post_type}_posts_columns", array( $this->table_columns, 'manage_posts_columns' ) );
            add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this->table_columns, 'manage_sortable_columns' ) );
            add_action( "manage_{$this->post_type}_posts_custom_column", array( $this->table_columns, 'manage_posts_custom_column' ), 10, 2 );
        }

        add_filter( 'awpcp_list_table_views_listings', array( $this, 'register_listings_table_views' ) );
        add_filter( 'awpcp_list_table_actions_listings', array( $this, 'register_listings_table_actions' ) );
        add_filter( 'awpcp_list_table_actions_listings', [ $this, 'filter_listings_table_actions' ], 99, 1 );
        add_filter( 'awpcp_list_table_search_listings', array( $this, 'register_listings_table_search_modes' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_meta_boxes_scripts' ] );
        add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'add_classifieds_meta_boxes' ) );
        add_action( 'save_post_' . $this->post_type, [ $this, 'save_classifieds_meta_boxes' ], 10, 2 );
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
        if ( ! $this->is_awpcp_post_page() ) {
            return;
        }
        wp_enqueue_style( 'select2' );
        wp_enqueue_style( 'daterangepicker' );
        wp_enqueue_style( 'awpcp-admin-style' );
    }

    /**
     * @since 4.1.2
     */
    public function is_awpcp_post_page() {
        global $pagenow;

        if ( $pagenow !== 'post.php' && $pagenow !== 'post-new.php' && $pagenow !== 'edit.php' ) {
            return false;
        }

        $post_type = isset( $_GET['post_type'] ) ? sanitize_title( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ( empty( $post_type ) ) {
            $post_id   = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
            $post      = get_post( $post_id );
            $post_type = $post ? $post->post_type : '';
        }

        return $post_type === $this->post_type;
    }

    /**
     * Determine if the current page is a specific AWP Classifieds admin page.
     *
     * @since 4.3.5
     * @param string $page The name of the page to check.
     * @return bool
     */
    public function is_admin_page( $page = 'awpcp.php' ) {
        global $pagenow;
        $get_page = awpcp_get_var( array( 'param' => 'page' ) );

        if ( $pagenow ) {
            // Allow this to be true during ajax load i.e. ajax form builder loading.
            $is_page = ( $pagenow === 'admin.php' || $pagenow === 'admin-ajax.php' ) && $get_page === $page;
            if ( $is_page ) {
                return true;
            }
        }

        return is_admin() && $get_page === $page;
    }

    /**
     * @since 4.0.0
     */
    public function filter_items_per_page_user_option( $items_per_page, $option, $user ) {
        return apply_filters( 'awpcp_listings_table_items_per_page', $items_per_page, $user );
    }

    /**
     * @param array $views  An array of views for the Listings table.
     * @since 4.0.0
     */
    public function register_listings_table_views( $views ) {
        $views['new']                      = $this->container['NewListingTableView'];
        $views['expired']                  = $this->container['ExpiredListingTableView'];
        $views['awaiting-approval']        = $this->container['AwaitingApprovalListingTableView'];
        $views['images-awaiting-approval'] = $this->container['ImagesAwaitingApprovalListingTableView'];
        $views['flagged']                  = $this->container['FlaggedListingTableView'];
        $views['incomplete']               = $this->container['IncompleteListingTableView'];
        $views['unverified']               = $this->container['UnverifiedListingTableView'];
        $views['complete']                 = $this->container['CompleteListingTableView'];

        return $views;
    }

    /**
     * @param array $actions    An array of actions for the Listings table.
     * @since 4.0.0
     */
    public function register_listings_table_actions( $actions ) {
        $actions['enable']                  = $this->container['EnableListingTableAction'];
        $actions['approve-images']          = $this->container['ApproveImagesTableAction'];
        $actions['disable']                 = $this->container['DisableListingTableAction'];
        $actions['send-access-key']         = $this->container['SendAccessKeyListingTableAction'];
        $actions['spam']                    = $this->container['MarkAsSPAMListingTableAction'];
        $actions['unflag']                  = $this->container['UnflagListingTableAction'];
        $actions['renew']                   = $this->container['ModeratorRenewListingTableAction'];
        $actions['renew-for-subscribers']   = $this->container['SubscriberRenewListingTableAction'];
        $actions['make-featured']           = $this->container['MakeFeaturedListingTableAction'];
        $actions['make-standard']           = $this->container['MakeStandardListingTableAction'];
        $actions['mark-reviewed']           = $this->container['MarkReviewedListingTableAction'];
        $actions['mark-paid']               = $this->container['MarkPaidListingTableAction'];
        $actions['mark-verified']           = $this->container['MarkVerifiedListingTableAction'];
        $actions['send-verification-email'] = $this->container['SendVerificationEmailTableAction'];
        $actions['send-to-facebook-page']   = $this->container['SendToFacebookPageListingTableAction'];
        $actions['send-to-facebook-group']  = $this->container['SendToFacebookGroupListingTableAction'];

        return $actions;
    }

    /**
     * Remove listing table actions that are not needed on this request.
     *
     * @since 4.0.0
     */
    public function filter_listings_table_actions( $available_actions ) {
        $actions = [];

        foreach ( $available_actions as $name => $action ) {
            // We assume the action should be used on every request if the object
            // is not an instance of the AWPCP_ConditionalListTableActionInterface.
            if (
                ! is_a( $action, 'AWPCP_ConditionalListTableActionInterface' )
                || $action->is_needed()
            ) {
                $actions[ $name ] = $action;
            }
        }

        return $actions;
    }

    /**
     * @param array $search_modes   An array of available search modes.
     * @since 4.0.0
     */
    public function register_listings_table_search_modes( $search_modes ) {
        $search_modes['keyword']       = $this->container['KeywordListingsTableSearchMode'];
        $search_modes['title']         = $this->container['TitleListingsTableSearchMode'];
        $search_modes['user']          = $this->container['UserListingsTableSearchMode'];
        $search_modes['contact-name']  = $this->container['ContactNameListingsTableSearchMode'];
        $search_modes['contact-phone'] = $this->container['ContactPhoneListingsTableSearchMode'];
        $search_modes['contact-email'] = $this->container['ContactEmailListingsTableSearchMode'];
        $search_modes['payer-email']   = $this->container['PayerEmailListingsTableSearchMode'];
        $search_modes['location']      = $this->container['LocationListingsTableSearchMode'];
        $search_modes['id']            = $this->container['IDListingsTableSearchMode'];

        return $search_modes;
    }

    /**
     * @since 4.0.0
     */
    public function maybe_enqueue_meta_boxes_scripts( $hook_suffix ) {
        if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
            return;
        }

        $post = get_post();

        if ( $this->post_type !== $post->post_type ) {
            return;
        }

        $this->container['ListingFieldsMetabox']->enqueue_scripts();
    }

    /**
     * @since 4.0.0
     */
    public function add_classifieds_meta_boxes() {
        // The Author meta box is replaced with the Classified Owner meta box below.
        remove_meta_box( 'authordiv', $this->post_type, 'normal' );

        add_meta_box(
            'awpcp-classifeds-information-metabox',
            __( 'Classified Information', 'another-wordpress-classifieds-plugin' ),
            [ $this->container['ListingInformationMetabox'], 'render' ],
            $this->post_type,
            'side'
        );

        add_meta_box(
            'awpcp-classifieds-owner-metabox',
            __( 'Classified Owner', 'another-wordpress-classifieds-plugin' ),
            [ $this->container['ListingOwnerMetabox'], 'render' ],
            $this->post_type,
            'advanced'
        );

        add_meta_box(
            'awpcp-classifieds-fields-metabox',
            __( 'Classified Fields', 'another-wordpress-classifieds-plugin' ),
            array( $this->container['ListingFieldsMetabox'], 'render' ),
            $this->post_type,
            'advanced'
        );
    }

    /**
     * Each metabox save() method is manually executed here to ensure that
     * the execution of one method is complete before the next one is called.
     *
     * This prevents information being accidentally overwritten, for example
     * when the save() method for the Listing Information Metabox was called
     * after wp_update_post() was used in Listings_API::update_listing(), but
     * before the terms and metadata had been stored.
     *
     * @since 4.0.0
     */
    public function save_classifieds_meta_boxes( $post_id, $post ) {
        static $save_in_progress = false;

        if ( $save_in_progress ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $this->post_type !== $post->post_type ) {
            return;
        }

        if ( 'auto-draft' === $post->post_status ) {
            return;
        }

        $save_in_progress = true;

        $this->container['ListingFieldsMetabox']->save( $post_id, $post );
        $this->container['ListingInformationMetabox']->save( $post_id, $post );

        $save_in_progress = false;
    }
}
