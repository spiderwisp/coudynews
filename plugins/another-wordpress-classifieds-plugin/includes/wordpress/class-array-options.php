<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides utility methods to get, save and delete entries from arrays
 * stored using the Options API.
 */
class AWPCP_ArrayOptions {

    /**
     * @var AWPCP_WordPress
     */
    private $wordpress;

    /**
     * @since 4.0.0
     */
    public function __construct( $wordpress ) {
        $this->wordpress = $wordpress;
    }

    /**
     * Gets the value of a WordPress option always returning an array.
     *
     * If the option does not exists or the current value is not an array, the
     * function returns an empty array.
     *
     * @since 4.0.0
     */
    public function get_array_option( $option_name ) {
        $data = $this->wordpress->get_option( $option_name );

        if ( ! is_array( $data ) ) {
            return [];
        }

        return $data;
    }

    /**
     * @since 4.0.0
     */
    public function update_array_option( $option_name, $key, $value ) {
        $data = $this->get_array_option( $option_name );

        $data[ $key ] = $value;

        $this->wordpress->update_option( $option_name, $data, false );
    }

    /**
     * @since 4.0.0
     */
    public function delete_entry_from_array_option( $option_name, $key ) {
        $data = $this->get_array_option( $option_name );

        unset( $data[ $key ] );

        $this->wordpress->update_option( $option_name, $data, false );
    }
}
