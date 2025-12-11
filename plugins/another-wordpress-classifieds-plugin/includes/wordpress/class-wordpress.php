<?php
/**
 * @package AWPCP\WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_wordpress() {
    return new AWPCP_WordPress();
}

class AWPCP_WordPress {

    /* Users */

    public function get_user_by( $field, $value ) {
        return get_user_by( $field, $value );
    }

    /* Options */

    public function get_option( $option, $key = false ) {
        return get_option( $option, $key );
    }

    public function update_option( $option, $new_value, $autoload = null ) {
        return update_option( $option, $new_value, $autoload );
    }

    public function delete_option( $option ) {
        return delete_option( $option );
    }

    /* Custom Post Types */

    public function insert_post( $post, $return_wp_error_on_failure = false ) {
        return wp_insert_post( $post, $return_wp_error_on_failure );
    }

    public function update_post( $post, $return_wp_error_on_failure = false ) {
        return wp_update_post( $post, $return_wp_error_on_failure );
    }

    public function delete_post( $post_id, $force_delete = false ) {
        return wp_delete_post( $post_id, $force_delete );
    }

    public function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
        return get_post( $post, $output, $filter );
    }

    public function get_posts( $args = array() ) {
        return get_posts( $args );
    }

    public function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
        return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
    }

    public function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
        return update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
    }

    public function get_post_meta( $post_id, $meta_key = '', $single = false ) {
        return get_post_meta( $post_id, $meta_key, $single );
    }

    public function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {
        return delete_post_meta( $post_id, $meta_key, $meta_value );
    }

    public function get_edit_post_link( $post, $context = 'display' ) {
        return get_edit_post_link( $post, $context );
    }

    /* Taxonomies */

    public function insert_term( $term, $taxonomy, $args = array() ) {
        return wp_insert_term( $term, $taxonomy, $args );
    }

    public function update_term( $temr_id, $taxonomy, $args = array() ) {
        return wp_update_term( $temr_id, $taxonomy, $args );
    }

    public function delete_term( $term_id, $taxonomy, $args = array() ) {
        return wp_delete_term( $term_id, $taxonomy, $args );
    }

    public function get_term_by( $field, $value, $taxonomy, $output = OBJECT, $filter = 'raw' ) {
        return get_term_by( $field, $value, $taxonomy, $output, $filter );
    }

    public function get_terms( $taxonomies, $args = array() ) {
        if ( is_array( $taxonomies ) && ! empty( $taxonomies['taxonomy'] ) ) {
            $args = $taxonomies;
        } else {
            $args['taxonomy'] = (array) $taxonomies;
        }
        return get_terms( $args );
    }

    public function get_term_hierarchy( $taxonomy ) {
        return _get_term_hierarchy( $taxonomy );
    }

    public function set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
        return wp_set_object_terms( $object_id, $terms, $taxonomy, $append );
    }

    public function add_object_terms( $object_id, $terms, $taxonomy ) {
        return wp_add_object_terms( $object_id, $terms, $taxonomy );
    }

    public function get_object_terms( $objects_ids, $taxonomies, $args = array() ) {
        return wp_get_object_terms( $objects_ids, $taxonomies, $args );
    }

    /**
     * @since 4.0.0
     */
    public function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
        return update_term_meta( $term_id, $meta_key, $meta_value, $prev_value );
    }

    /* Attachments */

    public function handle_media_sideload( $file_array, $parent_post_id, $description ) {
        return media_handle_sideload( $file_array, $parent_post_id, $description );
    }

    /**
     * @since 4.0.0
     */
    public function get_attachment_url( $attachment_id ) {
        return wp_get_attachment_url( $attachment_id );
    }

    public function get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
        return wp_get_attachment_image_url( $attachment_id, $size, $icon );
    }

    public function get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
        return wp_get_attachment_image( $attachment_id, $size, $icon, $attr );
    }

    public function get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) {
        return wp_get_attachment_image_src( $attachment_id, $size, $icon );
    }

    public function delete_attachment( $attachment_id, $force_delete = false ) {
        return wp_delete_attachment( $attachment_id, $force_delete );
    }

    /**
     * @since 4.0.0
     */
    public function set_post_thumbnail( $post_id, $attachment_id ) {
        return set_post_thumbnail( $post_id, $attachment_id );
    }

    /* Others */

    public function schedule_single_event( $timestamp, $hook, $args ) {
        return wp_schedule_single_event( $timestamp, $hook, $args );
    }

    public function current_time( $time, $gmt = 0 ) {
        return current_time( $time, $gmt );
    }

    /* Misc */

    public function create_posts_query( $query = array() ) {
        return new WP_Query( $query );
    }
}
