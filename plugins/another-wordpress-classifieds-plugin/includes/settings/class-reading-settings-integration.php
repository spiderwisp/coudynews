<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ReadingSettingsIntegration {

    /**
     * @since 4.0.0
     */
    public function filter_plugin_pages( $html, $params, $pages ) {
        if ( 'page_for_posts' === $params['id'] ) {
            return $this->exclude_from_pages_for_posts( $html, $params );
        }

        if ( 'page_on_front' === $params['id'] ) {
            return $this->exclude_from_pages_on_front( $html, $params );
        }

        return $html;
    }

    /**
     * @since 4.0.0
     */
    private function exclude_from_pages_for_posts( $html, $params ) {
        $excluded_pages = $this->get_excluded_pages_from_pages_for_posts();

        return $this->remove_excluded_pages( $html, $excluded_pages, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_excluded_pages_from_pages_for_posts() {
        return array_map( 'intval', ( awpcp_get_plugin_pages_ids() ) );
    }

    /**
     * @since 4.0.0
     */
    private function remove_excluded_pages( $html, $excluded_pages, $params ) {
        foreach ( $excluded_pages as $page_id ) {
            if ( intval( $params['selected'] ) === $page_id ) {
                continue;
            }

            $pattern = '/<option[^>]*value="' . $page_id . '"[^>]*>.*<\/option>/';

            $html = preg_replace( $pattern, '', $html );
        }

        return $html;
    }

    /**
     * @since 4.0.0
     */
    private function exclude_from_pages_on_front( $html, $params ) {
        $excluded_pages = $this->get_excluded_pages_on_front_page();

        return $this->remove_excluded_pages( $html, $excluded_pages, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_excluded_pages_on_front_page() {
        return awpcp_get_page_ids_by_ref( [
            'edit-ad-page-name',
            'renew-ad-page-name',
            'reply-to-ad-page-name',
            'show-ads-page-name',
        ] );
    }
}
