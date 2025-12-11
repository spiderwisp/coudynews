<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_add_edit_fee_action_helper() {
    $page = awpcp_fees_admin_page();

    return new AWPCP_Add_Edit_Fee_Action_Helper(
        $page,
        awpcp_add_edit_table_entry_rendering_helper( $page ),
        awpcp_html_renderer(),
        awpcp_request()
    );
}

class AWPCP_Add_Edit_Fee_Action_Helper {

    private $page;
    private $table_rendering_helper;
    private $html_renderer;
    private $request;

    public function __construct( $page, $table_rendering_helper, $html_renderer, $request ) {
        $this->page = $page;
        $this->table_rendering_helper = $table_rendering_helper;
        $this->html_renderer = $html_renderer;
        $this->request = $request;
    }

    public function get_posted_data() {
        return array(
            'name' => $this->request->post( 'name' ),
            'price' => $this->request->post( 'price' ),
            'credits' => $this->request->post( 'credits' ),
            'duration_amount' => $this->request->post( 'duration_amount' ),
            'duration_interval' => $this->request->post( 'duration_interval' ),
            'images' => $this->request->post( 'images_allowed' ),
            'characters' => $this->request->post( 'characters_allowed_in_description' ),
            'title_characters' => $this->request->post( 'characters_allowed_in_title' ),
            'private' => $this->request->post( 'private', false ),
            'featured' => $this->request->post( 'featured', false ),
            'categories' => array_filter( $this->request->post( 'categories', array() ) ),
            'number_of_categories_allowed' => $this->request->post( 'number_of_categories_allowed' ),
        );
    }

    public function render_entry_row( $entry ) {
        return $this->table_rendering_helper->render_entry_row( $entry );
    }

    public function render_entry_form( $entry, $form ) {
        $params = array(
            'entry' => $entry,
            'columns' => count( $this->page->get_table()->get_columns() ),
        );

        return $this->html_renderer->render( $form->build( $params ) );
    }
}
