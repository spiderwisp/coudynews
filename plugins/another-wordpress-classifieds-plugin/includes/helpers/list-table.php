<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AWPCP_List_Table extends WP_List_Table {
    public $_screen;
    public $_columns;
    public $_sortable;

    public function __construct( $screen, $columns = array(), $sortable = array()) {
        if ( is_string( $screen ) )
            $screen = convert_to_screen( $screen );

        $this->_screen = $screen;
        $this->_sortable = $sortable;

        if ( !empty( $columns ) ) {
            $this->_columns = $columns;
            add_filter( 'manage_' . $screen->id . '_columns', array( &$this, 'get_columns' ), 0 );
        }
    }

    protected function get_column_info() {
        $columns = get_column_headers( $this->_screen );
        $hidden = get_hidden_columns( $this->_screen );
        $sortable = $this->_sortable;

        $column_slugs = array_keys( $columns );
        $primary_column = $column_slugs[1];

        return array( $columns, $hidden, $sortable, $primary_column );
    }

    public function get_columns() {
        return $this->_columns;
    }
}
