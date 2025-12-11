<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_add_edit_table_entry_rendering_helper( $page ) {
    return new AWPCP_Add_Edit_Table_Entry_Rendering_Helper(
        $page,
        awpcp_template_renderer()
    );
}

class AWPCP_Add_Edit_Table_Entry_Rendering_Helper {

    private $page;
    private $template_renderer;

    public function __construct( $page, $template_renderer ) {
        $this->page = $page;
        $this->template_renderer = $template_renderer;
    }

    public function render_entry_row( $plan ) {
        ob_start();
            $this->page->get_table()->single_row( $plan );
            $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function render_entry_form( $template, $entry ) {
        $params = array(
            'entry' => $entry,
            'columns' => count( $this->page->get_table()->get_columns() ),
        );

        return $this->template_renderer->render_template( $template, $params );
    }
}
