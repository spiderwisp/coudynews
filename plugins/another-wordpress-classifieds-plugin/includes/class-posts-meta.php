<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Posts_Meta {

    private $configuration;
    private $db;

    public function __construct( $configuration, $db ) {
        $this->configuration = $configuration;
        $this->db = $db;
    }

    public function get_meta_values( $meta_key ) {
        if( empty( $meta_key ) ) {
            return array();
        }

        $sql = "SELECT pm.meta_value FROM {$this->db->postmeta} pm ";
        $sql.= "LEFT JOIN {$this->db->posts} p ON p.ID = pm.post_id ";
        $sql.= "WHERE p.post_type = '%s' ";
        $sql.= "AND p.post_status = 'publish' ";
        $sql.= "AND pm.meta_key = '%s' ";
        $sql.= 'ORDER BY pm.meta_value ASC';

        $post_type = $this->configuration->get_post_type();
        $meta_key = $this->configuration->prepare_meta_key( $meta_key );

        $sql = $this->db->prepare( $sql, $post_type, $meta_key );

        return $this->db->get_col( $sql );
    }
}
