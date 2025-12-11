<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_FeesTable extends WP_List_Table {

    private $page;
    private $items_per_page;

    public function __construct($page, $args=array()) {
        parent::__construct(wp_parse_args($args, array('plural' => 'awpcp-fees')));
        $this->page = $page;
    }

    private function parse_query() {
        $user = wp_get_current_user();
        $ipp = (int) get_user_meta($user->ID, 'fees-items-per-page', true);

        $this->items_per_page = awpcp_get_var(
            array(
                'param' => 'items-per-page',
                'default' => $ipp === 0 ? 10 : $ipp,
            )
        );
        update_user_meta($user->ID, 'fees-items-per-page', $this->items_per_page);

        $params = array(
            'orderby' => awpcp_get_var( array( 'param' => 'orderby' ) ),
            'order'   => awpcp_get_var( array( 'param' => 'order', 'default' => 'desc' ) ),
            'paged'   => awpcp_get_var( array( 'param' => 'paged', 'default' => 1 ) ),
        );

        $params['order'] = strcasecmp($params['order'], 'DESC') === 0 ? 'DESC' : 'ASC';
        $params['pages'] = (int) $params['paged'];

        switch($params['orderby']) {
            case 'duration':
                $orderby = sprintf('rec_period %1$s, adterm_name', $params['order']);
                break;

            case 'interval':
                $orderby = sprintf('rec_increment %1$s, adterm_name', $params['order']);
                break;

            case 'images':
                $orderby = sprintf('imagesallowed %1$s, adterm_name', $params['order']);
                break;

            case 'regions':
                $orderby = sprintf( 'regions %1$s, adterm_name', $params['order'] );
                break;

            case 'title-characters':
                $orderby = sprintf( 'title_characters %1$s, adterm_name', $params['order'] );
                break;

            case 'characters':
                $orderby = sprintf('characters_allowed %1$s, adterm_name', $params['order']);
                break;

            case 'price':
                $orderby = sprintf('amount %1$s, adterm_name', $params['order']);
                break;

            case 'credits':
                $orderby = sprintf('credits %1$s, adterm_name', $params['order']);
                break;

            case 'categories':
                $orderby = sprintf('categories %1$s, adterm_name', $params['order']);
                break;

            case 'featured':
                $orderby = sprintf('is_featured_ad_pricing %1$s, adterm_name', $params['order']);
                break;

            case 'private':
                $orderby = sprintf( 'private %1$s, adterm_name', $params['order'] );
                break;

            case 'name':
            default:
                $orderby = 'adterm_name';
                break;
        }

        return array(
            'orderby' => $orderby,
            'order' => $params['order'],
            'offset' => $this->items_per_page * ($params['paged'] - 1),
            'limit'   => $this->items_per_page,
        );
    }

    public function prepare_items() {
        $query = $this->parse_query();

        $total_items = AWPCP_Fee::query(array_merge(array('fields' => 'count'), $query));
        $this->items = AWPCP_Fee::query(array_merge(array('fields' => '*'), $query));

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $this->items_per_page,
        ));

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }

    public function has_items() {
        return count($this->items) > 0;
    }

    public function get_columns() {
        $columns = array();

        $columns['cb'] = '<input type="checkbox" />';
        $columns['name'] = __( 'Name', 'another-wordpress-classifieds-plugin');
        $columns['attributes']  = __( 'Attributes', 'another-wordpress-classifieds-plugin' );
        $columns['price'] = __( 'Price', 'another-wordpress-classifieds-plugin');
        $columns['credits'] = __( 'Credits', 'another-wordpress-classifieds-plugin');

        if (function_exists('awpcp_price_cats'))
            $columns['categories'] = __( 'Categories', 'another-wordpress-classifieds-plugin');

        if (function_exists('awpcp_featured_ads'))
            $columns['featured'] = __( 'Featured Ads', 'another-wordpress-classifieds-plugin');

        $columns['private'] = __( 'Private', 'another-wordpress-classifieds-plugin' );

        return $columns;
    }

    public function get_sortable_columns() {
        $columns = array(
            'name' => array('name', true),
            'duration' => array('duration', true),
            'interval' => array('interval', true),
            'images' => array('images', true),
            'regions' => array( 'regions', true ),
            'title_characters' => array('title-characters', true),
            'characters' => array('characters', true),
            'price' => array('price', true),
            'credits' => array('credits', true),
            'private' => array( 'private', true ),
        );

        if (function_exists('awpcp_price_cats'))
            $columns['categories'] = array('categories', true);

        if (function_exists('awpcp_featured_ads'))
            $columns['featured'] = array('featured', true);

        return $columns;
    }

    private function get_row_actions($item) {
        $actions = $this->page->actions($item);
        return $this->page->links($actions);
    }

    /**
     * @param object $item
     */
    public function column_cb($item) {
        return '<input type="checkbox" value="' . esc_attr( $item->id ) . '" name="selected[]" />';
    }

    public function column_name($item) {
        return $item->get_name() . $this->row_actions($this->get_row_actions($item), true);
    }

    public function column_description( $item ) {
        return $item->description;
    }

    /**
     * @since 4.0.0
     */
    public function column_attributes( $item ) {
        $features = [
            'duration'                  => $this->get_payment_term_duration( $item ),
            'images'                    => $this->get_number_of_images( $item ),
            'characters-in-title'       => $this->get_characters_limit_for_title( $item ),
            'characters-in-description' => $this->get_characters_limit_for_description( $item ),
        ];

        return implode( '<br/>', $features );
    }

    /**
     * @since 4.0.0
     */
    private function get_payment_term_duration( $payment_term ) {
        $duration = __( '<duration-amount> <duration-interval>', 'another-wordpress-classifieds-plugin' );
        $duration = str_replace( '<duration-amount>', $payment_term->duration_amount, $duration );
        $duration = str_replace( '<duration-interval>', $payment_term->get_duration_interval(), $duration );

        return sprintf(
            // translators: %s is the duration
            esc_html__( 'Duration: %s', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $duration ) . '</strong>'
        );
    }

    /**
     * @since 4.0.0
     */
    private function get_number_of_images( $payment_term ) {
        return sprintf(
            // translators: %s is the number of images
            esc_html__( '# of images: %s', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $payment_term->images ) . '</strong>'
        );
    }

    /**
     * @since 4.0.0
     */
    private function get_characters_limit_for_title( $payment_term ) {
        $characters_limit = $payment_term->get_characters_allowed_in_title();

        if ( 0 === $characters_limit ) {
            $characters_limit = __( 'unlimited', 'another-wordpress-classifieds-plugin' );
        }

        return sprintf(
            // translators: %s is the characters limit
            esc_html__( 'Chars in title: %s', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $characters_limit ) . '</strong>'
        );
    }

    /**
     * @since 4.0.0
     */
    private function get_characters_limit_for_description( $payment_term ) {
        $characters_limit = $payment_term->get_characters_allowed();

        if ( 0 === $characters_limit ) {
            $characters_limit = __( 'unlimited', 'another-wordpress-classifieds-plugin' );
        }

        return sprintf(
            // translators: %s is the characters limit
            esc_html__( 'Chars in description: %s', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $characters_limit ) . '</strong>'
        );
    }

    public function column_price($item) {
        return awpcp_format_money( $item->price );
    }

    public function column_credits($item) {
        return number_format($item->credits, 0);
    }

    public function column_categories($item) {
        if ( empty( $item->categories ) ) {
            return _x( 'All', 'all categories', 'another-wordpress-classifieds-plugin' );
        }

        $categories = awpcp_categories_collection()->find_categories(array(
            'include' => $item->categories,
        ));

        return awpcp_get_comma_separated_categories_list( $categories );
    }

    public function column_featured($item) {
        return $item->featured ? __( 'Yes', 'another-wordpress-classifieds-plugin') : __( 'No', 'another-wordpress-classifieds-plugin');
    }

    public function column_private($item) {
        return $item->private ? __( 'Yes', 'another-wordpress-classifieds-plugin' ) : __( 'No', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $item
     */
    public function single_row($item) {
        static $row_class = '';
        $row_class = $row_class === '' ? 'alternate' : '';

        echo '<tr id="fee-' . esc_attr( $item->id ) . '" data-id="' . esc_attr( $item->id ) . '"';
        echo ' class="' . esc_attr( $row_class ) . '"';
        echo '>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }
}
