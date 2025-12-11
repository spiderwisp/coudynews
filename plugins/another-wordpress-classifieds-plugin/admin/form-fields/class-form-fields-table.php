<?php
/**
 * @package AWPCP\Admin\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP_List_Table subclass used to show a list of available form fields.
 */
class AWPCP_FormFieldsTable extends WP_List_Table {

    public function __construct() {
        parent::__construct( array( 'plural' => 'awpcp-form-fields-table' ) );
    }

    public function prepare( $items, $total_items ) {
        $this->items = $items;

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $total_items,
            ]
        );

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
    }

    public function get_columns() {
        $columns = array(
            'cb'   => '<input type="checkbox" />',
            'name' => _x( 'Name', 'form field name', 'another-wordpress-classifieds-plugin' ),
            'slug' => _x( 'Slug', 'form field slug', 'another-wordpress-classifieds-plugin' ),
        );

        return $columns;
    }

    /**
     * @param object $item
     */
    public function column_cb( $item ) {
        $handle   = '<div class="awpcp-sortable-handle"><div class="spinner awpcp-spinner awpcp-form-fields-table-spinner"></div></div>';
        $checkbox = '<input type="checkbox" value="' . esc_attr( $item->get_slug() ) . '" name="selected[]" />';

        return $handle . $checkbox;
    }

    public function column_name( $item ) {
        return $item->get_name() . $this->row_actions( array( '' => '' ) );
    }

    public function column_slug( $item ) {
        return $item->get_slug();
    }

    /**
     * @param object $item The current item
     */
    public function single_row( $item ) {
        static $row_class = '';
        $row_class = $row_class === '' ? 'alternate' : '';

        // the 'field-' part in the id attribute is important. The jQuery UI Sortable plugin relies on that
        // to build a serialized string with the current order of fields.
        echo '<tr id="field-' . esc_attr( $item->get_slug() ) . '" data-id="' . esc_attr( $item->get_slug() ) . '"';
        echo ' class="' . esc_attr( $row_class ) . '"';
        echo '>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }
}
