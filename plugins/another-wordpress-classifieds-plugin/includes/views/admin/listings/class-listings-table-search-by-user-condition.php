<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByUserCondition {

    public function match( $search_by ) {
        return $search_by == 'user';
    }

    public function create( $search_term, $query ) {
        $query['author'] = $search_term;
        return $query;
    }
}
