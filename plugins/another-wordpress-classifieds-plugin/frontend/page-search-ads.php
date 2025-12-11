<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_search_listings_page() {
    return new AWPCP_SearchAdsPage(
        'awpcp-search-ads',
        __( 'Search Ads', 'another-wordpress-classifieds-plugin'),
        awpcp_template_renderer()
    );
}

/**
 * @since  2.1.4
 */
class AWPCP_SearchAdsPage extends AWPCP_Page {

    public function __construct( $slug, $title, $template_renderer ) {
        parent::__construct( $slug, $title, $template_renderer );

        $this->classifieds_bar_components = array( 'search_bar' => false );
    }

    public function get_current_action($default='searchads') {
        $action = awpcp_get_var( array( 'param' => 'awpcp-step' ) );

        if ( $action ) {
            return $action;
        }

        return awpcp_get_var( array( 'param' => 'a', 'default' => $default ) );
    }

    public function url($params=array()) {
        $page_url = awpcp_get_page_url( 'search-ads-page-name', true );
        return add_query_arg( urlencode_deep( $params ), $page_url );
    }

    public function dispatch() {
        wp_enqueue_style('awpcp-jquery-ui');
        wp_enqueue_style( 'select2' );
        wp_enqueue_script('awpcp-page-search-listings');
        wp_enqueue_script('awpcp-extra-fields');

        return $this->_dispatch();
    }

    protected function _dispatch($default=null) {
        $action = $this->get_current_action();

        if ( 'searchads' === $action ) {
            return $this->search_step();
        }

        return $this->do_search_step();
    }

    protected function get_posted_data() {
        $data = [
            'query'     => awpcp_get_var( array( 'param' => 'keywordphrase' ) ),
            'category'  => null,
            'name'      => awpcp_get_var( array( 'param' => 'searchname' ) ),
            'min_price' => awpcp_parse_money( awpcp_get_var( array( 'param' => 'searchpricemin' ) ) ),
            'max_price' => awpcp_parse_money( awpcp_get_var( array( 'param' => 'searchpricemax' ) ) ),
            'regions'   => awpcp_get_var( array( 'param' => 'regions' ) ),
        ];

        $category = awpcp_get_var( array( 'param' => 'searchcategory' ) );
        $category = array_filter( array_map( 'intval', (array) $category ) );

        if ( $category ) {
            $data['category'] = $category;
        }

        $data = apply_filters( 'awpcp-get-posted-data', $data, 'search', array() );

        return $data;
    }

    protected function validate_posted_data($data, &$errors=array()) {
        if (!empty($data['query']) && strlen($data['query']) < 3) {
            $errors['query'] = __("You have entered a keyword that is too short to search on. Search keywords must be at least 3 letters in length. Please try another term.", 'another-wordpress-classifieds-plugin');
        }

        if (!empty($data['min_price']) && !is_numeric($data['min_price'])) {
            $errors['min_price'] = __("You have entered an invalid minimum price. Make sure your price contains numbers only. Please do not include currency symbols.", 'another-wordpress-classifieds-plugin');
        }

        if (!empty($data['max_price']) && !is_numeric($data['max_price'])) {
            $errors['max_price'] = __("You have entered an invalid maximum price. Make sure your price contains numbers only. Please do not include currency symbols.", 'another-wordpress-classifieds-plugin');
        }

        return empty($errors);
    }

    protected function search_step() {
        $search_form = $this->search_form( $this->get_posted_data() );

        return $this->render( 'content', $search_form );
    }

    protected function search_form($form, $errors=array()) {
        global $hasregionsmodule, $hasextrafieldsmodule;

        $ui['module-extra-fields'] = $hasextrafieldsmodule;
        $ui['posted-by-field'] = get_awpcp_option('displaypostedbyfield');
        $ui['price-field'] = get_awpcp_option( 'display_price_field_on_search_form' );
        $ui['allow-user-to-search-in-multiple-regions'] = get_awpcp_option('allow-user-to-search-in-multiple-regions');

        $url_params = wp_parse_args( wp_parse_url( awpcp_current_url(), PHP_URL_QUERY ) );

        foreach ( $form as $name => $value ) {
            if ( isset( $url_params[ $name ] ) ) {
                unset( $url_params[ $name ] );
            }
        }

        // Allow selected categories to be cleared or replaced.
        unset( $url_params['searchcategory'] );

        $action_url = awpcp_current_url();
        $hidden = array_merge( $url_params, array( 'awpcp-step' => 'dosearch' ) );

        $params = compact( 'action_url', 'ui', 'form', 'hidden', 'errors' );

        $template = AWPCP_DIR . '/frontend/templates/page-search-ads.tpl.php';

        return $this->template_renderer->render_template( $template, $params );
    }

    protected function do_search_step() {
        $form = $this->get_posted_data();

        $errors = array();
        if (!$this->validate_posted_data($form, $errors)) {
            return $this->search_form($form, $errors);
        }

        $output = apply_filters( 'awpcp-search-listings-content-replacement', null, $form );

        if ( is_null( $output ) ) {
            return $this->search_listings( $form );
        } else {
            return $output;
        }
    }

    private function search_listings( $form ) {
        $query  = $this->build_search_listings_query( $form );
        $params = $this->get_display_listings_params( $form );

        $search_results = awpcp_display_listings( $query, 'search', $params );

        return $this->render( 'content', $search_results );
    }

    /**
     * @since 4.0.0
     */
    private function build_search_listings_query( $posted_data ) {
        $query = array(
            's'                 => $posted_data['query'],
            'classifieds_query' => array(
                'context'      => 'public-listings',
                'category'     => $posted_data['category'],
                'contact_name' => $posted_data['name'],
                'min_price'    => $posted_data['min_price'],
                'max_price'    => $posted_data['max_price'],
                'regions'      => $posted_data['regions'],
            ),
            'posts_per_page'    => awpcp_get_var(
                array(
                    'param'    => 'results',
                    'default'  => get_awpcp_option( 'adresultsperpage', 10 ),
                    'sanitize' => 'absint',
                )
            ),
            'offset'            => awpcp_get_var( array( 'param' => 'offset', 'default' => 0, 'sanitize' => 'absint' ) ),
            'orderby'           => get_awpcp_option( 'search-results-order' ),
        );

        return apply_filters( 'awpcp-search-listings-query', $query, $posted_data );
    }

    /**
     * @since 4.0.0
     */
    private function get_display_listings_params( $posted_data ) {
        $params = [
            'show_intro_message'         => true,
            'show_menu_items'            => false,
            'show_category_selector'     => false,
            'show_pagination'            => true,

            'classifieds_bar_components' => $this->classifieds_bar_components,
        ];

        $position_of_form_in_results = get_awpcp_option( 'search-form-in-results' );

        if ( 'above' === $position_of_form_in_results ) {
            $params['before_pagination'] = $this->search_form( $posted_data );
        }

        if ( 'below' === $position_of_form_in_results ) {
            $params['after_pagination'] = $this->search_form( $posted_data );
        }

        if ( 'none' === $position_of_form_in_results ) {
            $params['before_list'] = $this->build_return_link();
        }

        return $params;
    }

    public function build_return_link() {
        $params = $_REQUEST; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
        awpcp_sanitize_value( 'sanitize_text_field', $params );

        $params = array_merge( $params, array( 'awpcp-step' => 'searchads' ) );
        $href   = add_query_arg(urlencode_deep($params), awpcp_current_url());

        $return_link = '<div class="awpcp-return-to-search-link awpcp-clearboth"><a href="<link-url>"><link-text></a></div>';
        $return_link = str_replace( '<link-url>', esc_url( $href ), $return_link );
        $return_link = str_replace( '<link-text>', __( 'Return to Search', 'another-wordpress-classifieds-plugin' ), $return_link );

        return $return_link;
    }
}
