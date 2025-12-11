<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clases used to handle custom views for WP List Table.
 */
class AWPCP_ListTableViewsHandler {

    /**
     * @var array
     */
    private $views;

    /**
     * @param array $views      A list of views handlers.
     * @since 4.0.0
     */
    public function __construct( $views ) {
        $this->views   = $views;
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        $current_view = $this->get_current_view();

        if ( ! $current_view || ! isset( $this->views[ $current_view ] ) ) {
            return;
        }

        $this->views[ $current_view ]->pre_get_posts( $query );
    }

    /**
     * @since 4.0.0
     */
    private function get_current_view() {
        return awpcp_get_var( array( 'param' => 'awpcp_filter', 'default' => false ) );
    }

    /**
     * @param array $views  An array of already defined views for the table.
     */
    public function views( $views ) {
        /**
         * Fired before the plugin creates the view links for the table of ads
         * in the Classifieds Ads admin page.
         *
         * @since 4.0.6
         */
        do_action( 'awpcp_before_admin_listings_views' );

        $current_view = $this->get_current_view();
        $post_type    = awpcp_get_var( array( 'param' => 'post_type' ) );
        $current_url  = add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) );

        foreach ( $this->views as $name => $view ) {
            $count = $view->get_count();

            if ( 0 === $count ) {
                continue;
            }

            $views[ $name ] = $this->create_view_link(
                $view->get_label(),
                $view->get_url( $current_url ),
                $count,
                $current_view === $name ? 'current' : ''
            );
        }

        /**
         * Fired after the plugin creates the view links for the table of ads
         * in the Classifieds Ads admin page.
         *
         * @since 4.0.6
         */
        do_action( 'awpcp_after_admin_listings_views' );

        return $views;
    }

    /**
     * @param string $label     The label for the action.
     * @param mixed  $url       The URL for the action.
     * @param int    $count     The number of posts on this view.
     * @param string $class     The CSS class for the A tag.
     * @since 4.0.0
     */
    private function create_view_link( $label, $url, $count, $class ) {
        return sprintf(
            '<a class="%s" href="%s">%s <span class="count">(%s)</span></a>',
            $class,
            esc_url( $url ),
            esc_html( $label ),
            esc_html( $count )
        );
    }
}
