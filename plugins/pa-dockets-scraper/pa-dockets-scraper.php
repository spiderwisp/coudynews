<?php
/**
 * Plugin Name: Coudy AI
 * Plugin URI: https://coudynews.com
 * Description: AI-powered content generation for court dockets. Scrapes PA Web Dockets system daily and allows manual PDF uploads. Generates SEO-optimized articles using Groq Cloud AI and automatically publishes them as WordPress posts.
 * Version: 1.0.0
 * Author: Coudy News
 * Author URI: https://coudynews.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coudy-ai
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PA_DOCKETS_SCRAPER_VERSION', '1.0.0' );
define( 'PA_DOCKETS_SCRAPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PA_DOCKETS_SCRAPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PA_DOCKETS_SCRAPER_PLUGIN_FILE', __FILE__ );
define( 'PA_DOCKETS_SCRAPER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load required files
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-database.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-logger.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-simple-pdf-parser.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-pdf-processor.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-scraper.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-ai-generator.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-image-generator.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-post-creator.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/class-admin-settings.php';

/**
 * Main plugin class
 */
class PA_Dockets_Scraper {
	
	/**
	 * Instance of this class
	 *
	 * @var PA_Dockets_Scraper
	 */
	private static $instance = null;
	
	/**
	 * Database instance
	 *
	 * @var PA_Dockets_Scraper_Database
	 */
	public $database;
	
	/**
	 * Scraper instance
	 *
	 * @var PA_Dockets_Scraper_Scraper
	 */
	public $scraper;
	
	/**
	 * AI Generator instance
	 *
	 * @var PA_Dockets_Scraper_AI_Generator
	 */
	public $ai_generator;
	
	/**
	 * Image Generator instance
	 *
	 * @var PA_Dockets_Scraper_Image_Generator
	 */
	public $image_generator;
	
	/**
	 * Post Creator instance
	 *
	 * @var PA_Dockets_Scraper_Post_Creator
	 */
	public $post_creator;
	
	/**
	 * Cron Handler instance
	 *
	 * @var PA_Dockets_Scraper_Cron_Handler
	 */
	public $cron_handler;
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	public $logger;
	
	/**
	 * Admin Settings instance
	 *
	 * @var PA_Dockets_Scraper_Admin_Settings
	 */
	public $admin_settings;
	
	/**
	 * Get instance of this class
	 *
	 * @return PA_Dockets_Scraper
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
		// Initialize components
		$this->database = new PA_Dockets_Scraper_Database();
		$this->logger = new PA_Dockets_Scraper_Logger();
		$this->scraper = new PA_Dockets_Scraper_Scraper( $this->database, $this->logger );
		$this->ai_generator = new PA_Dockets_Scraper_AI_Generator( $this->logger );
		$this->image_generator = new PA_Dockets_Scraper_Image_Generator( $this->logger );
		$this->post_creator = new PA_Dockets_Scraper_Post_Creator( $this->database, $this->logger, $this->image_generator );
		$this->cron_handler = new PA_Dockets_Scraper_Cron_Handler( $this->scraper, $this->ai_generator, $this->post_creator, $this->logger );
		$this->admin_settings = new PA_Dockets_Scraper_Admin_Settings();
		
		// Register hooks
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'coudy-ai', false, dirname( PA_DOCKETS_SCRAPER_PLUGIN_BASENAME ) . '/languages' );
	}
	
}

/**
 * Plugin activation callback
 */
function pa_dockets_scraper_activate() {
	// Create database table
	$database = new PA_Dockets_Scraper_Database();
	$database->create_table();
	
	// Schedule cron job - use wp_schedule_event directly to avoid logger dependency during activation
	// Schedule for midnight (00:00) daily
	$midnight = strtotime( 'tomorrow midnight' );
	if ( ! wp_next_scheduled( 'pa_dockets_daily_scrape' ) ) {
		wp_schedule_event( $midnight, 'daily', 'pa_dockets_daily_scrape' );
	}
	
	// Set default options
	$default_options = array(
		'groq_api_key' => '',
		'groq_api_url' => 'https://api.groq.com/openai/v1',
		'groq_model' => 'llama-3.3-70b-versatile',
		'enable_dockets' => true,
		'counties' => array( 'potter', 'tioga', 'mckean' ),
		'default_category' => 0,
		'default_tags' => '',
		'article_tone' => 'professional',
	);
	
	foreach ( $default_options as $key => $value ) {
		if ( false === get_option( 'pa_dockets_' . $key ) ) {
			add_option( 'pa_dockets_' . $key, $value );
		}
	}
	
	flush_rewrite_rules();
}

/**
 * Plugin deactivation callback
 */
function pa_dockets_scraper_deactivate() {
	// Clear cron job - use wp_unschedule_event directly
	$timestamp = wp_next_scheduled( 'pa_dockets_daily_scrape' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'pa_dockets_daily_scrape' );
	}
}

// Register activation and deactivation hooks
register_activation_hook( PA_DOCKETS_SCRAPER_PLUGIN_FILE, 'pa_dockets_scraper_activate' );
register_deactivation_hook( PA_DOCKETS_SCRAPER_PLUGIN_FILE, 'pa_dockets_scraper_deactivate' );

/**
 * Initialize plugin
 */
function pa_dockets_scraper() {
	global $pa_dockets_scraper;
	$pa_dockets_scraper = PA_Dockets_Scraper::get_instance();
	return $pa_dockets_scraper;
}

// Start the plugin
pa_dockets_scraper();
