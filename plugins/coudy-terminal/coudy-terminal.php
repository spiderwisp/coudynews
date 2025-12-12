<?php
/**
 * Plugin Name: Coudy Terminal
 * Plugin URI: https://coudynews.com
 * Description: Popup terminal interface for wp-admin allowing administrators to execute PHP code, shell commands, and WP-CLI commands.
 * Version: 1.0.0
 * Author: Coudy News
 * Author URI: https://coudynews.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coudy-terminal
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'COUDY_TERMINAL_VERSION', '1.0.0' );
define( 'COUDY_TERMINAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COUDY_TERMINAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'COUDY_TERMINAL_PLUGIN_FILE', __FILE__ );
define( 'COUDY_TERMINAL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load required files
require_once COUDY_TERMINAL_PLUGIN_DIR . 'includes/class-terminal-admin.php';
require_once COUDY_TERMINAL_PLUGIN_DIR . 'includes/class-terminal-handler.php';

/**
 * Main plugin class
 */
class Coudy_Terminal {
	
	/**
	 * Instance of this class
	 *
	 * @var Coudy_Terminal
	 */
	private static $instance = null;
	
	/**
	 * Admin instance
	 *
	 * @var Coudy_Terminal_Admin
	 */
	public $admin;
	
	/**
	 * Handler instance
	 *
	 * @var Coudy_Terminal_Handler
	 */
	public $handler;
	
	/**
	 * Get instance of this class
	 *
	 * @return Coudy_Terminal
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}
	
	/**
	 * Initialize plugin
	 */
	private function init() {
		// Initialize admin
		$this->admin = new Coudy_Terminal_Admin();
		
		// Initialize handler
		$this->handler = new Coudy_Terminal_Handler();
	}
}

/**
 * Initialize the plugin
 */
function coudy_terminal_init() {
	return Coudy_Terminal::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'coudy_terminal_init' );

