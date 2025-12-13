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
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-content-database.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-rss-parser.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-web-scraper.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-content-discovery.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-article-rewriter.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/class-admin-settings.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/class-admin-content.php';
require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/class-admin-post-meta.php';

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
	 * Content Database instance
	 *
	 * @var PA_Dockets_Scraper_Content_Database
	 */
	public $content_database;
	
	/**
	 * Content Discovery instance
	 *
	 * @var PA_Dockets_Scraper_Content_Discovery
	 */
	public $content_discovery;
	
	/**
	 * Article Rewriter instance
	 *
	 * @var PA_Dockets_Scraper_Article_Rewriter
	 */
	public $article_rewriter;
	
	/**
	 * Admin Content instance
	 *
	 * @var PA_Dockets_Scraper_Admin_Content
	 */
	public $admin_content;
	
	/**
	 * Admin Post Meta instance
	 *
	 * @var PA_Dockets_Scraper_Admin_Post_Meta
	 */
	public $admin_post_meta;
	
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
		
		// Initialize content tracking components
		$this->content_database = new PA_Dockets_Scraper_Content_Database();
		$rss_parser = new PA_Dockets_Scraper_RSS_Parser( $this->logger );
		$web_scraper = new PA_Dockets_Scraper_Web_Scraper( $this->logger, $this->ai_generator );
		$this->content_discovery = new PA_Dockets_Scraper_Content_Discovery( $this->content_database, $rss_parser, $web_scraper, $this->logger );
		$this->article_rewriter = new PA_Dockets_Scraper_Article_Rewriter( $this->ai_generator, $this->content_database, $this->post_creator, $this->logger, $web_scraper );
		$this->admin_content = new PA_Dockets_Scraper_Admin_Content( $this->content_database, $this->content_discovery, $this->article_rewriter, $rss_parser, $this->logger );
		$this->admin_post_meta = new PA_Dockets_Scraper_Admin_Post_Meta( $this->content_database, $this->article_rewriter );
		$this->admin_post_meta->init();
		
		$this->admin_settings = new PA_Dockets_Scraper_Admin_Settings();
		
		// Register hooks
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_ajax_pa_get_article_preview', array( $this, 'ajax_get_article_preview' ) );
		add_action( 'pa_content_discovery_check', array( $this, 'run_content_discovery' ) );
		add_action( 'pa_content_discovery_check_single', array( $this, 'run_single_source_discovery' ) );
	}
	
	/**
	 * Run content discovery cron job
	 */
	public function run_content_discovery() {
		if ( $this->content_discovery ) {
			$this->content_discovery->check_all_sources();
		}
	}
	
	/**
	 * Run discovery for a single source (scheduled)
	 *
	 * @param int $source_id Source ID
	 */
	public function run_single_source_discovery( $source_id ) {
		if ( $this->content_discovery && $source_id ) {
			$this->content_discovery->discover_articles( absint( $source_id ) );
		}
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'coudy-ai', false, dirname( PA_DOCKETS_SCRAPER_PLUGIN_BASENAME ) . '/languages' );
	}
	
	/**
	 * AJAX handler for article preview
	 */
	public function ajax_get_article_preview() {
		check_ajax_referer( 'pa_article_preview', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		
		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		
		if ( ! $article_id ) {
			wp_send_json_error( array( 'message' => 'Invalid article ID' ) );
		}
		
		$article = $this->content_database->get_article( $article_id );
		
		if ( ! $article ) {
			wp_send_json_error( array( 'message' => 'Article not found' ) );
		}
		
		ob_start();
		// Make article available to the view (variable is already set above)
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/article-detail-modal.php';
		$html = ob_get_clean();
		
		wp_send_json_success( array( 'data' => $html ) );
	}
	
}

/**
 * Plugin activation callback
 */
function pa_dockets_scraper_activate() {
	// Create database tables
	$database = new PA_Dockets_Scraper_Database();
	$database->create_table();
	
	// Create content tracking tables
	$content_database = new PA_Dockets_Scraper_Content_Database();
	$content_database->create_content_tables();
	
	// Schedule cron job - use wp_schedule_event directly to avoid logger dependency during activation
	// Schedule for midnight (00:00) daily
	$midnight = strtotime( 'tomorrow midnight' );
	if ( ! wp_next_scheduled( 'pa_dockets_daily_scrape' ) ) {
		wp_schedule_event( $midnight, 'daily', 'pa_dockets_daily_scrape' );
	}
	
	// Schedule content discovery cron job (hourly)
	if ( ! wp_next_scheduled( 'pa_content_discovery_check' ) ) {
		wp_schedule_event( time(), 'hourly', 'pa_content_discovery_check' );
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
	// Clear cron jobs - use wp_unschedule_event directly
	$timestamp = wp_next_scheduled( 'pa_dockets_daily_scrape' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'pa_dockets_daily_scrape' );
	}
	
	$timestamp = wp_next_scheduled( 'pa_content_discovery_check' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'pa_content_discovery_check' );
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
