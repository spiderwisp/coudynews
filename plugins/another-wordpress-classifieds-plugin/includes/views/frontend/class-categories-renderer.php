<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_categories_checkbox_list_renderer() {
    return new AWPCP_CategoriesRenderer( awpcp_categories_renderer_data_provider(), new AWPCP_CategoriesCheckboxListWalker() );
}

class AWPCP_CategoriesRenderer {

    private $data_provider;
    private $walker;

    public function __construct( $data_provider, $walker ) {
        $this->data_provider = $data_provider;
        $this->walker = $walker;
    }

    public function render( $params = array() ) {
        awpcp_enqueue_main_script();

        $params = $this->merge_params( $params );

        if ( $params['ignore_cache'] ) {
            return $this->render_categories( $params );
        }

        $transient_key = $this->generate_transient_key( $params );

        try {
            return $this->render_from_cache( $transient_key );
        } catch ( AWPCP_Exception $e ) {
            return $this->render_categories_and_update_cache( $params, $transient_key );
        }
    }

    private function merge_params( $params ) {
        return wp_parse_args( $params, array(
            'category_id' => null,
            'parent_category_id' => null,
            'show_empty_categories' => true,
            'show_children_categories' => true,
            'show_listings_count' => true,
            'ignore_cache' => false,
        ) );
    }

    private function generate_transient_key( $params ) {
        $params = array_merge( $params, array( 'walker' => get_class( $this->walker ) ) );
        $transient_key_params = apply_filters( 'awpcp-categories-list-transient-key-params', $params );
        $transient_key = 'awpcp-categories-list-cache-' . hash( 'crc32b', maybe_serialize( $transient_key_params ) );

        return $transient_key;
    }

    private function render_from_cache( $transient_key ) {
        $transient_keys = get_option( 'awpcp-categories-list-cache-keys', array() );

        if ( in_array( $transient_key, $transient_keys, true ) ) {
            $output = get_transient( $transient_key );

            if ( false !== $output )
                return $output;
        }

        throw new AWPCP_Exception( 'No cache entry was found.' );
    }

    private function render_categories( $params ) {
        $categories = $this->data_provider->get_categories( $params );

        if ( $this->walker->configure( $params ) ) {
            $output = $this->walker->walk( $categories, 0 );
        } else {
            $output = '';
        }

        return $output;
    }

    private function render_categories_and_update_cache( $params, $transient_key ) {
        $output = $this->render_categories( $params );

        if ( ! empty( $output ) ) {
            $this->update_cache( $transient_key, $output );
        }

        return $output;
    }

    private function update_cache( $transient_key, $output ) {
        if ( $transient_key && set_transient( $transient_key, $output, YEAR_IN_SECONDS ) ) {
            $transient_keys = get_option( 'awpcp-categories-list-cache-keys' );
            if ( $transient_keys === false ) {
                add_option( 'awpcp-categories-list-cache-keys', array( $transient_key ), '', 'no' );
            } else {
                array_push( $transient_keys, $transient_key );
                update_option( 'awpcp-categories-list-cache-keys', $transient_keys );
            }
        }
    }
}
