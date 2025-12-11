<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Math CAPTCHA provider.
 */
class AWPCP_DefaultCAPTCHAProvider implements AWPCP_CAPTCHAProviderInterface {

    /**
     * @var int
     */
    private $max_number;

    /**
     * Constructor.
     */
    public function __construct( $max_number ) {
        $this->max_number = $max_number;
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    public function show() {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render();
    }

    /**
     * Renders the form field to enter the answer to the challenge.
     */
    public function render() {
        $left  = wp_rand( 1, $this->max_number );
        $right = wp_rand( 1, $this->max_number );

        $hash   = $this->hash( $left + $right );
        $answer = awpcp_get_var( array( 'param' => 'captcha' ), 'post' );

        $label = sprintf(
            /* translators: the numbers that need to be added up for the math challenge. */
            _x( 'Enter the value of the following sum: %1$d + %2$d', 'CAPTCHA', 'another-wordpress-classifieds-plugin' ),
            $left,
            $right
        );

        $html  = '<label for="captcha"><span>' .
            esc_html( $label ) .
            '<span class="required">*</span>' .
            '</span></label>';
        $html .= '<input type="hidden" name="captcha-hash" value="' . esc_attr( $hash ) . '" />';
        $html .= '<input id="captcha" class="awpcp-textfield inputbox required" type="text" ' .
            'name="captcha" value="' . esc_attr( $answer ) . '" size="5" autocomplete="off"/>';

        return $html;
    }

    /**
     * Creates a nonce based on the expected answer to the challenge.
     */
    private function hash( $number ) {
        return wp_create_nonce( "captcha-answer-$number" );
    }

    /**
     * @throws AWPCP_Exception  If the answer to the challenge is not valid.
     */
    public function validate() {
        $answer   = awpcp_get_var( array( 'param' => 'captcha' ), 'post' );
        $expected = awpcp_get_var( array( 'param' => 'captcha-hash' ), 'post' );

        if ( empty( $answer ) ) {
            $error = __( 'You did not solve the math problem. Please solve the math problem to proceed.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $error ) );
        }

        if ( ! wp_verify_nonce( $expected, "captcha-answer-$answer" ) ) {
            $error = __( 'Your solution to the math problem was incorrect. Please try again.', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $error ) );
        }

        return true;
    }
}
