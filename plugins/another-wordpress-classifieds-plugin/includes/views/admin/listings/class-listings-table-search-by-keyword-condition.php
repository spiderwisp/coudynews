<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByKeywordCondition {

    public function match( $search_by ) {
        return $search_by == 'keyword';
    }

    public function create( $search_term, $query ) {
        $query['s'] = $search_term;
        return $query;
    }
}
