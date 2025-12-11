<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByLocationCondition {

    public function match( $search_by ) {
        return $search_by == 'location';
    }

    public function create( $search_term, $query ) {
        $query['region'] = $search_term;
        return $query;
    }
}
