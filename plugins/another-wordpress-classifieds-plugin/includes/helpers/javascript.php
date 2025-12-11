<?php
/**
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_JavaScript {

    private static $instance = null;

    private $data = array();
    private $l10n = array();

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new AWPCP_JavaScript();
        }
        return self::$instance;
    }

    public function set($key, $value, $replace=true) {
        if ( $replace || !isset( $this->data[ $key ] ) ) {
            $this->data[ $key ] = $value;
        }
    }

    public function localize($context, $key, $value=null) {
        if ( is_array( $key ) && isset( $this->l10n[ $context ] ) ) {
            $this->l10n[ $context ] = array_merge( $this->l10n[ $context ], $key );
        } elseif ( is_array( $key ) ) {
            $this->l10n[ $context ] = $key;
        } else {
            $this->l10n[ $context ][ $key ] = $value;
        }
    }

    /**
     * @since 3.4
     */
    public function print_data() {
        echo "\n";
        echo "<script>\n";
        echo "/* <![CDATA[ */\n";
        echo "(function($, window){\n";

        $this->print_variable( 'options', '__awpcp_js_data', $this->data );
        $this->print_variable( 'localization', '__awpcp_js_l10n', $this->l10n );

        echo '})(jQuery, window);';
        echo "/* ]]> */\n";
        echo "</script>\n";
    }

    private function print_variable( $property_name, $variable_name, $content ) {
        ?>
window.<?php echo esc_attr( $variable_name ); ?> = <?php echo wp_json_encode( $this->encode_scalar_values( $content ) ); ?>;
if ( typeof $.AWPCP !== 'undefined' ) {
    $.extend( $.AWPCP.<?php echo esc_attr( $property_name ); ?>, <?php echo esc_attr( $variable_name ); ?> );
}
        <?php
    }

    private function encode_scalar_values( $values ) {
        foreach ( $values as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $values[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
            }
        }

        return $values;
    }
}
