<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_categories_renderer_factory() {
    static $instance = null;

    if ( $instance == null ) {
        $instance = new AWPCP_Categories_Renderer_Factory(
            awpcp_categories_renderer_data_provider()
        );
    }

    return $instance;
}

class AWPCP_Categories_Renderer_Factory {

    private $data_provider;

    public function __construct( $data_provider ) {
        $this->data_provider = $data_provider;
    }

    public function create_list_renderer() {
        return new AWPCP_CategoriesRenderer( $this->data_provider, new AWPCP_CategoriesListWalker() );
    }
}
