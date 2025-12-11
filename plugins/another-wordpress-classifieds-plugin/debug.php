<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class WP_Skeleton_Logger {
    public static $instance = null;

    protected $html;
    protected $from;
    protected $context;
    protected $root;
    protected $log;
    private $wp_filesystem;

    private function __construct() {
        $this->html    = true;
        $this->from    = true;
        $this->context = 3;

        $this->root          = realpath(getenv('DOCUMENT_ROOT'));
        $this->wp_filesystem = awpcp_get_wp_filesystem();

        $this->log = array();

        if ( is_admin() ) {
            add_action('admin_print_footer_scripts', array($this, 'show'), 100000);
        } else {
            add_action('print_footer_scripts', array($this, 'show'), 100000);
        }
    }

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($var, $type='debug', $print=false, $file=false) {
        $entry       = array('backtrace' => debug_backtrace(), 'var' => $var, 'type' => $type);
        $this->log[] = $entry;

        if ($print) {
            return $this->render($entry);
        }

        if ($file) {
            return $this->write($entry);
        }

        return true;
    }

    public function debug($vars, $print=false, $file=false) {
        if (count($vars) > 1) {
            return $this->log($vars, 'debug', $print, $file);
        } else {
            return $this->log($vars[0], 'debug', $print, $file);
        }
    }

    public function render($entry) {
        $var       = $entry['var'];
        $backtrace = $entry['backtrace'];

        $start = 2;
        $limit = $this->context + $start;

        $html = '<div class="' . esc_attr( $entry['type'] ) . '">';
        if ($this->from) {
            $items = array();
            for ($k = $start; $k < $limit; $k++) {
                if (!isset($backtrace[$k]) || !isset($backtrace[$k]['file'])) {
                    break;
                }

                $item  = '<strong>';
                $item .= esc_html( substr(str_replace($this->root, '', $backtrace[$k]['file']), 1) );
                $item .= ':' . esc_html( $backtrace[$k]['line'] );
                $item .= ' - function <strong>' . esc_html( $backtrace[$k+1]['function'] ) . '</strong>()';
                $item .= '</strong>';

                $items[] = $item;
            }
            $html .= join('<br/>', $items);
        }

        $var = print_r($var, true);
        if ($this->html && !empty($var)) {
            $html .= "\n<pre class=\"cake-debug\" style=\"color:#000; background: #FFF\">\n";
            $var   = $var;
            $html .= esc_html( $var ) . "\n</pre>\n";
        } else {
            $html .= '<br/>';
        }

        $html .= '</div>';

        return $html;
    }

    private function write($entry) {
        $log_file = AWPCP_DIR . '/debug.log';
        $content  = sprintf(
            "[%s] %s",
            gmdate( 'Y-m-d H:i:s' ),
            print_r( $entry['var'], true ) . "\n"
        );

        // Use WordPress filesystem API
        $existing_content = '';
        if ( $this->wp_filesystem->exists( $log_file ) ) {
            $existing_content = $this->wp_filesystem->get_contents( $log_file );
        }
        $this->wp_filesystem->put_contents( $log_file, $existing_content . $content, FS_CHMOD_FILE );
    }

    public function show() {
        if ( ! get_option( 'awpcp-debug', false ) ) {
            return;
        }

        if (empty($this->log)) {
            return;
        }

        $html = '';
        foreach($this->log as $entry) {
            $html .= $this->render($entry);
        }

        echo '<div style="background:#000; color: #FFF; padding-bottom: 40px">' . wp_kses_post( $html ) . '</div>';
    }
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
if (!function_exists('debug')) {
    function debugp($var = false) {
        $args = func_get_args();
        echo esc_html( WP_Skeleton_Logger::instance()->debug( $args, true ) );
    }

    function debugf($var = false) {
        $args = func_get_args();
        return WP_Skeleton_Logger::instance()->debug($args, false, true);
    }

    function debug($var = false) {
        $args = func_get_args();
        return WP_Skeleton_Logger::instance()->debug($args, false);
    }
}

if (!function_exists('kaboom')) {
    function kaboom($message='', $title='', $args=array()) {
        _deprecated_function( __FUNCTION__, '4.3.3', 'wp_die' );
        // phpcs:ignore WordPress.Security.NonceVerification
        if (!isset($_REQUEST['c66d946bb'])) {
            wp_die( esc_html( $message ), esc_html( $title ) );
        }
    }
}

// how to find debug calls
// ^[^/\n]+debugp?\(
