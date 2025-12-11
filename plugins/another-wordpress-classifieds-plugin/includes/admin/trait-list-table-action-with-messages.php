<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common methods to generate action messages.
 */
trait AWPCP_ListTableActionWithMessages {

    /**
     * @param array $result_codes   An array of result codes from this action.
     * @since 4.0.0
     */
    public function get_messages( $result_codes ) {
        $messages = array();

        foreach ( $result_codes as $code => $count ) {
            $messages[] = $this->get_message( $code, $count );
        }

        return $messages;
    }

    /**
     * @param string $code      Result code.
     * @param int    $count     Number of posts associated with the given result
     *                          code.
     * @since 4.0.0
     */
    abstract protected function get_message( $code, $count );
}
