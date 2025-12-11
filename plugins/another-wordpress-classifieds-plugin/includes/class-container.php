<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A container class greatly inspired by https://carlalexander.ca/dependency-injection-wordpress/
 *
 * @since 4.0.0
 */
class AWPCP_Container implements ArrayAccess {

    /**
     * Values stored inside the container.
     *
     * @var array
     */
    private $values = array();

    /**
     * Constructor
     *
     * @param array $values     Initial set of values to store in the container.
     */
    public function __construct( array $values = array() ) {
        $this->values = $values;
    }

    /**
     * Checks if there's a value in the container for the given key.
     *
     * @param mixed $key    The identifier of a value in the container.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists( $key ) {
        return (bool) array_key_exists( $key, $this->values );
    }

    /**
     * Returns a value stored in the container for the given key.
     *
     * @param mixed $key    The identifier of a value in the container.
     * @throws Exception    If the container doesn't have a value assocaited with
     *                      the given $key.
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $key ) {
        if ( ! array_key_exists( $key, $this->values ) ) {
            throw new Exception( esc_html( sprintf( "Container doesn't have a value stored for key: %s", $key ) ) );
        }

        if ( $this->values[ $key ] instanceof Closure ) {
            return $this->values[ $key ]( $this );
        }

        return $this->values[ $key ];
    }

    /**
     * Sets a value inside the container.
     *
     * @param mixed $key    The identifier for the new value.
     * @param mixed $value  The value to store in the container.
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet( $key, $value ) {
        $this->values[ $key ] = $value;
    }

    /**
     * Unsets the value in the container for the given key.
     *
     * @param mixed $key    The identifier of a value in the container.
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset( $key ) {
        unset( $this->values[ $key ] );
    }

    /**
     * Configure the container using the given configuration objects.
     *
     * @param array $configurations     An array of instances of Container Configurations Interface.
     */
    public function configure( array $configurations ) {
        foreach ( $configurations as $configuration ) {
            $configuration->modify( $this );
        }
    }

    /**
     * Returns a closure that creates a service object using the given constructor
     * function.
     *
     * @param Closure $closure   A constructor function for the object.
     *
     * @return Closure   A constructor function for the service object.
     */
    public function service( Closure $closure ) {
        return function ( $container ) use ( $closure ) {
            static $object;

            if ( null === $object ) {
                $object = $closure( $container );
            }

            return $object;
        };
    }
}
