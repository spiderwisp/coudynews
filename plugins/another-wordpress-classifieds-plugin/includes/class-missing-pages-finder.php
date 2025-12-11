<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_missing_pages_finder() {
    return new AWPCP_Missing_Pages_Finder(
        awpcp_settings_api(),
        $GLOBALS['wpdb']
    );
}

class AWPCP_Missing_Pages_Finder {

    private $settings;
    private $db;

    public function __construct( $settings, $db ) {
        $this->settings = $settings;
        $this->db = $db;
    }

    public function find_page_with_shortcode( $shortcode ) {
        $pages = $this->get_pages_with_shortcode( $shortcode );

        return count( $pages ) === 1 ? $pages[0] : null;
    }

    /**
     * @since 4.0.0
     */
    public function get_pages_with_shortcode( $shortcode ) {
        $query = 'SELECT ID, post_title, post_status ';
        $query.= 'FROM ' . $this->db->posts . ' ';
        $query.= "WHERE post_content LIKE '%%%s%%' ";
        $query.= "AND post_status NOT IN ('inherit') ";

        $sql = $this->db->prepare( $query, $shortcode );

        return $this->db->get_results( $sql );
    }

    public function find_broken_page_id_references() {
        $plugin_pages = awpcp_pages();
        $plugin_pages_ids = array_filter( awpcp_get_plugin_pages_ids() );

        $registered_pages = array_keys( $plugin_pages );
        $referenced_pages = array_keys( $plugin_pages_ids );

        // pages that are registered in the code but no referenced in the DB
        $pages_not_referenced = array_diff( $registered_pages, $referenced_pages );
        $pages_not_used = array_diff( $referenced_pages, $registered_pages );
        $registered_pages_ids = awpcp_get_page_ids_by_ref( $registered_pages );

        $query = 'SELECT posts.ID post, posts.post_status status, posts.post_title name ';
        $query.= 'FROM ' . $this->db->posts . ' AS posts ';
        $query.= 'WHERE posts.ID IN (' . join( ',', $registered_pages_ids ) . ") AND posts.post_type = 'page' ";

        $existing_pages = $this->db->get_results( $query, OBJECT_K );
        $missing_pages = array( 'not-found' => array(), 'not-published' => array(), 'not-referenced' => array() );

        foreach ( $plugin_pages_ids as $page_ref => $page_id ) {
            if ( in_array( $page_ref, $pages_not_used ) ) {
                continue;
            }

            $page = isset( $existing_pages[ $page_id ] ) ? $existing_pages[ $page_id ] : null;

            if ( is_object( $page ) && isset( $page->status ) && $page->status != 'publish' ) {
                $page_array          = (array) $page;
                $page_array['page']  = $page_ref;
                $page_array['id']    = $page_id;
                $page_array['label'] = $this->settings->get_option_label( awpcp_translate_page_ref_to_setting_name( $page_ref ) );

                $missing_pages['not-published'][] = (object) $page;
            } elseif ( is_null( $page ) ) {
                $page = new stdClass();

                $page->page = $page_ref;
                $page->id = $page_id;
                $page->post = null;
                $page->status = null;
                $page->label = $this->settings->get_option_label( awpcp_translate_page_ref_to_setting_name( $page_ref ) );
                $page->default_name = $plugin_pages[ $page_ref ][0];

                $missing_pages['not-found'][] = $page;
            }
        }

        // if a page is registered in the code but there is no reference of it
        // in the database, include a dummy object to represent it.
        foreach ( $pages_not_referenced as $page ) {
            $item = new stdClass();
            $item->page = $page;
            $item->id = null;
            $item->post = null;
            $item->status = null;
            $item->label = $this->settings->get_option_label( awpcp_translate_page_ref_to_setting_name( $page ) );
            $item->default_name = $plugin_pages[ $page ][0];
            $item->candidates = $this->get_pages_with_shortcode( $plugin_pages[ $page ][1] );

            $missing_pages['not-referenced'][] = $item;
        }

        return $missing_pages;
    }
}
