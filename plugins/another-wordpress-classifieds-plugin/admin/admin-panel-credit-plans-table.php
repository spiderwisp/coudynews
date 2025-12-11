<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_CreditPlansTable extends WP_List_Table {

    private $page;
    private $items_per_page;
    private $total_items;

    public function __construct( $page, $args = array() ) {
        $args = array_merge( array( 'plural' => 'awpcp-credit-plans' ), $args );
        parent::__construct( $args );
        $this->page = $page;
    }

    private function parse_query() {
        $user = wp_get_current_user();
        $ipp = (int) get_user_meta($user->ID, 'credit-plans-items-per-page', true);

        $this->items_per_page = awpcp_get_var(
            array(
                'param' => 'items-per-page',
                'default' => $ipp === 0 ? 10 : $ipp,
            )
        );
        update_user_meta($user->ID, 'credit-plans-items-per-page', $this->items_per_page);

        $params = array(
            'orderby' => awpcp_get_var( array( 'param' => 'orderby' ) ),
            'order'   => awpcp_get_var( array( 'param' => 'order', 'default' => 'DESC' ) ),
            'paged'   => awpcp_get_var( array( 'param' => 'paged', 'default' => 1 ) ),
        );

        $params['order'] = strtoupper( $params['order'] ) == 'ASC' ? 'ASC' : 'DESC';

        switch($params['orderby']) {
            case 'price':
                $orderby = sprintf('price %1$s, name %1$s, id', $params['order']);
                break;

            case 'credits':
                $orderby = sprintf('credits %1$s, name %1$s, id', $params['order']);
                break;

            case 'name':
            default:
                $orderby = 'name';
                break;
        }

        return array(
            'orderby' => $orderby,
            'order' => $params['order'],
            'offset' => $this->items_per_page * ( absint( $params['paged'] ) - 1),
            'limit'   => $this->items_per_page,
        );
    }

    public function prepare_items() {
        $query = $this->parse_query();
        $this->total_items = AWPCP_CreditPlan::query(array_merge(array('fields' => 'count'), $query));
        $this->items = AWPCP_CreditPlan::query(array_merge(array('fields' => '*'), $query));

        $this->set_pagination_args(array(
            'total_items' => $this->total_items,
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
        $columns['description'] = __( 'Description', 'another-wordpress-classifieds-plugin');
        $columns['credits'] = __( 'Credits', 'another-wordpress-classifieds-plugin');
        $columns['price'] = __( 'Price', 'another-wordpress-classifieds-plugin');

        return $columns;
    }

    public function get_sortable_columns() {
        return array(
            'name' => array('name', true),
            'credits' => array('credits', true),
            'price' => array('price', true),
        );
    }

    private function get_row_actions($item) {
        $actions = $this->page->actions($item);
        return $this->page->links($actions);
    }

    public function column_default($item, $column_name) {
        return '...';
    }

    /**
     * @param object $item
     */
    public function column_cb($item) {
        return '<input type="checkbox" value="' . esc_attr( $item->id ) . '" name="selected[]" />';
    }

    public function column_name($item) {
        return $item->name . $this->row_actions($this->get_row_actions($item), true);
    }

    public function column_description($item) {
        return $item->description;
    }

    public function column_credits($item) {
        return $item->get_formatted_credits();
    }

    public function column_price($item) {
        return $item->get_formatted_price();
    }

    /**
     * @param object $item
     */
    public function single_row($item) {
        static $row_class = '';
        $row_class = $row_class === '' ? 'alternate' : '';

        echo '<tr id="credit-plan-' . esc_attr( $item->id ) . '" data-id="' . esc_attr( $item->id ) . '"';
        echo ' class="' . esc_attr( $row_class ) . '"';
        echo '>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }
}
