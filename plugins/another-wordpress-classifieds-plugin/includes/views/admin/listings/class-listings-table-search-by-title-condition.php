<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByTitleCondition {

    public function match( $search_by ) {
        return $search_by == 'title';
    }

    public function create( $search_term, $query ) {
        $query['s'] = $search_term;
        return $query;
    }
}
