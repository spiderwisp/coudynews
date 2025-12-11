<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_ListingsTableSearchByPayerEmailCondition {

    public function match( $search_by ) {
        return $search_by == 'payer-email';
    }

    public function create( $search_term, $query ) {
        $query['meta_query'][] = array(
            'key' => '_awpcp_payer_email',
            'value' => $search_term,
        );

        return $query;
    }
}
