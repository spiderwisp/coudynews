<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * An array like object whose elements are loaded only if needed using a filter.
 */
class AWPCP_FilteredArray implements ArrayAccess, IteratorAggregate {

    /**
     * @var string
     */
    private $filter_name;

    /**
     * @var array|null
     */
    private $items;

    /**
     * @param string $filter_name   Name of the filter used to get this array's elements.
     * @since 4.0.0
     */
    public function __construct( $filter_name ) {
        $this->filter_name = $filter_name;
    }

    /**
     * @since 4.0.0
     */
    private function get_items() {
        if ( is_null( $this->items ) ) {
            $this->items = apply_filters( $this->filter_name, array() );
        }

        return $this->items;
    }

    /**
     * @param mixed $offset     Offset to check.
     * @since 4.0.0
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists( $offset ) {
        $items = $this->get_items();
        return isset( $items[ $offset ] );
    }

    /**
     * @param mixed $offset     Offset to get.
     * @since 4.0.0
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $offset ) {
        $items = $this->get_items();
        return $items[ $offset ];
    }

    /**
     * @param mixed $offset     Offset to set.
     * @param mixed $value      Value to store.
     * @since 4.0.0
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet( $offset, $value ) {
    }

    /**
     * @param mixed $offset  Offset to unset.
     * @since 4.0.0
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset( $offset ) {
    }

    /**
     * @since 4.0.0
     */
    #[\ReturnTypeWillChange]
    public function getIterator() {
        return new ArrayIterator( $this->get_items() );
    }
}
