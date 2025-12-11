<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByIdCondition {

    public function match( $search_by ) {
        return $search_by == 'id';
    }

    public function create( $search_term, $query ) {
        $query['p'] = absint( $search_term );
        return $query;
    }
}
