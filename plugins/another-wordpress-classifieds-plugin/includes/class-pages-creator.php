<?php
/**
 * @since 3.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_pages_creator() {
    return new AWPCP_Pages_Creator();
}

/**
 * @since 3.5.3
 */
class AWPCP_Pages_Creator {

    private $missing_pages_finder;

    public function __construct() {
        $this->missing_pages_finder = awpcp_missing_pages_finder();
    }

    public function restore_missing_pages() {
        $shortcodes = awpcp_pages();
        $multiple_pages = array();

        // Attempt to find existing plugin pages that haven't been assigned in the Page Settings.
        foreach( $shortcodes as $refname => $properties ) {
            if ( awpcp_get_page_id_by_ref( $refname ) ) {
                continue;
            }

            $pages = $this->missing_pages_finder->get_pages_with_shortcode( $properties[1] );

            if ( empty( $pages ) ) {
                continue;
            }

            if ( count( $pages ) > 1 ) {
                $multiple_pages[ $refname ] = $pages;
            }

            awpcp_update_plugin_page_id( $refname, $pages[0]->ID );
        }

        // Find out which pages we need to create.
        $missing_pages = $this->missing_pages_finder->find_broken_page_id_references();
        $pages_to_restore = array_merge( $missing_pages['not-found'], $missing_pages['not-referenced'] );
        $page_refs = awpcp_get_properties( $pages_to_restore, 'page' );

        // If we are restoring the main page, let's do it first!
        $p = array_search( 'main-page-name', $page_refs );
        if ( $p !== false ) {
            // put the main page as the first page to restore
            array_splice( $pages_to_restore, 0, 0, array( $pages_to_restore[ $p ] ) );
            array_splice( $pages_to_restore, $p + 1, 1 );
        }

        $restored_pages = array();

        foreach( $pages_to_restore as $page ) {
            $refname = $page->page;
            $name = $shortcodes[ $refname ][0];

            if ( isset( $multiple_pages[ $refname ] ) ) {
                continue;
            }

            if (strcmp($refname, 'main-page-name') == 0) {
                awpcp_create_pages($name, $subpages=false);
            } else {
                awpcp_create_subpage($refname, $name, $shortcodes[$refname][1]);
            }

            $restored_pages[] = $page;
        }

        update_option( 'awpcp-flush-rewrite-rules', true );

        return $restored_pages;
    }
}
