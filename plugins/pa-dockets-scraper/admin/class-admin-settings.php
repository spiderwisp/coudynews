<?php
/**
 * Admin Settings class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Admin_Settings {
	
	/**
	 * Admin Upload instance
	 *
	 * @var PA_Dockets_Scraper_Admin_Upload
	 */
	private $admin_upload;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// Register upload handler action hook early to ensure it's available when form submits
		add_action( 'admin_init', array( $this, 'register_upload_handler' ) );
	}
	
	/**
	 * Register upload handler action hook
	 */
	public function register_upload_handler() {
		// Ensure upload handler is initialized so the action hook is registered
		$this->get_admin_upload();
	}
	
	/**
	 * Get admin upload instance (lazy-loaded to avoid circular dependency)
	 *
	 * @return PA_Dockets_Scraper_Admin_Upload
	 */
	private function get_admin_upload() {
		if ( ! $this->admin_upload ) {
			require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/class-admin-upload.php';
			$this->admin_upload = new PA_Dockets_Scraper_Admin_Upload();
		}
		return $this->admin_upload;
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Coudy AI', 'coudy-ai' ),
			__( 'Coudy AI', 'coudy-ai' ),
			'manage_options',
			'pa-dockets-scraper',
			array( $this, 'render_settings_page' ),
			'dashicons-clipboard',
			30
		);
		
		add_submenu_page(
			'pa-dockets-scraper',
			__( 'Settings', 'coudy-ai' ),
			__( 'Settings', 'coudy-ai' ),
			'manage_options',
			'pa-dockets-scraper',
			array( $this, 'render_settings_page' )
		);
		
		add_submenu_page(
			'pa-dockets-scraper',
			__( 'Upload Docket', 'coudy-ai' ),
			__( 'Upload Docket', 'coudy-ai' ),
			'manage_options',
			'coudy-ai-upload',
			array( $this, 'render_upload_page' )
		);
		
		add_submenu_page(
			'pa-dockets-scraper',
			__( 'Content Sources', 'coudy-ai' ),
			__( 'Content Sources', 'coudy-ai' ),
			'manage_options',
			'pa-content-sources',
			array( $this, 'render_content_sources_page' )
		);
		
		add_submenu_page(
			'pa-dockets-scraper',
			__( 'Articles', 'coudy-ai' ),
			__( 'Articles', 'coudy-ai' ),
			'manage_options',
			'pa-content-articles',
			array( $this, 'render_articles_page' )
		);
		
		add_submenu_page(
			'pa-dockets-scraper',
			__( 'Logs', 'coudy-ai' ),
			__( 'Logs', 'coudy-ai' ),
			'manage_options',
			'pa-dockets-scraper-logs',
			array( $this, 'render_logs_page' )
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		// AI API Credentials
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_groq_api_key' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_groq_api_url' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_groq_model' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_openai_api_key' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_enable_image_generation' );
		
		// Source Configuration
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_enable_dockets' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_counties' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_search_url' );
		
		// Article Settings
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_default_category' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_default_tags' );
		register_setting( 'pa_dockets_scraper_settings', 'pa_dockets_article_tone' );
	}
	
	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'pa-dockets-scraper' ) === false && 
		     strpos( $hook, 'pa-content' ) === false ) {
			return;
		}
		
		wp_enqueue_style(
			'pa-dockets-scraper-admin',
			PA_DOCKETS_SCRAPER_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			PA_DOCKETS_SCRAPER_VERSION
		);
		
		// Enqueue content admin styles and scripts
		if ( strpos( $hook, 'pa-content' ) !== false ) {
			wp_enqueue_style(
				'pa-content-admin',
				PA_DOCKETS_SCRAPER_PLUGIN_URL . 'admin/css/content-admin.css',
				array(),
				PA_DOCKETS_SCRAPER_VERSION
			);
			
			wp_enqueue_script(
				'pa-content-admin',
				PA_DOCKETS_SCRAPER_PLUGIN_URL . 'admin/js/content-admin.js',
				array( 'jquery' ),
				PA_DOCKETS_SCRAPER_VERSION,
				true
			);
			
			wp_localize_script( 'pa-content-admin', 'paContentAdmin', array(
				'nonce' => wp_create_nonce( 'pa_article_preview' ),
			) );
		}
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Handle form submission
		if ( isset( $_POST['pa_dockets_scraper_save_settings'] ) && check_admin_referer( 'pa_dockets_scraper_settings' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'coudy-ai' ) . '</p></div>';
		}
		
		// Handle manual scrape trigger
		if ( isset( $_POST['pa_dockets_scraper_trigger_scrape'] ) && check_admin_referer( 'pa_dockets_scraper_trigger_scrape' ) ) {
			$this->trigger_manual_scrape();
		}
		
		// Get current settings
		$groq_api_key = get_option( 'pa_dockets_groq_api_key', '' );
		$groq_api_url = get_option( 'pa_dockets_groq_api_url', 'https://api.groq.com/openai/v1' );
		$groq_model = get_option( 'pa_dockets_groq_model', 'llama-3.3-70b-versatile' );
		$openai_api_key = get_option( 'pa_dockets_openai_api_key', '' );
		$enable_image_generation = get_option( 'pa_dockets_enable_image_generation', true );
		$enable_dockets = get_option( 'pa_dockets_enable_dockets', true );
		$counties = get_option( 'pa_dockets_counties', array( 'potter', 'tioga', 'mckean' ) );
		$search_url = get_option( 'pa_dockets_search_url', '' );
		$default_category = get_option( 'pa_dockets_default_category', 0 );
		$default_tags = get_option( 'pa_dockets_default_tags', '' );
		$article_tone = get_option( 'pa_dockets_article_tone', 'professional' );
		
		// Get categories
		$categories = get_categories( array( 'hide_empty' => false ) );
		
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/settings-page.php';
	}
	
	/**
	 * Render upload page
	 */
	public function render_upload_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$this->get_admin_upload()->render_upload_page();
	}
	
	/**
	 * Render content sources page
	 */
	public function render_content_sources_page() {
		global $pa_dockets_scraper;
		$pa_dockets_scraper->admin_content->render_content_sources_page();
	}
	
	/**
	 * Render articles page
	 */
	public function render_articles_page() {
		global $pa_dockets_scraper;
		$pa_dockets_scraper->admin_content->render_articles_page();
	}
	
	/**
	 * Render logs page
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		global $pa_dockets_scraper;
		$logger = $pa_dockets_scraper->logger;
		
		// Handle clear logs
		if ( isset( $_POST['pa_dockets_scraper_clear_logs'] ) && check_admin_referer( 'pa_dockets_scraper_clear_logs' ) ) {
			$logger->clear_logs();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Logs cleared successfully!', 'coudy-ai' ) . '</p></div>';
		}
		
		// Get filter
		$log_type = isset( $_GET['log_type'] ) ? sanitize_text_field( $_GET['log_type'] ) : '';
		$limit = isset( $_GET['limit'] ) ? absint( $_GET['log_type'] ) : 100;
		
		$logs = $logger->get_logs( $limit, $log_type );
		
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/logs-page.php';
	}
	
	/**
	 * Save settings
	 */
	private function save_settings() {
		// AI API Credentials
		if ( isset( $_POST['pa_dockets_groq_api_key'] ) ) {
			update_option( 'pa_dockets_groq_api_key', sanitize_text_field( $_POST['pa_dockets_groq_api_key'] ) );
		}
		
		if ( isset( $_POST['pa_dockets_groq_api_url'] ) ) {
			update_option( 'pa_dockets_groq_api_url', esc_url_raw( $_POST['pa_dockets_groq_api_url'] ) );
		}
		
		if ( isset( $_POST['pa_dockets_groq_model'] ) ) {
			update_option( 'pa_dockets_groq_model', sanitize_text_field( $_POST['pa_dockets_groq_model'] ) );
		}
		
		if ( isset( $_POST['pa_dockets_openai_api_key'] ) ) {
			update_option( 'pa_dockets_openai_api_key', sanitize_text_field( $_POST['pa_dockets_openai_api_key'] ) );
		}
		
		$enable_image_generation = isset( $_POST['pa_dockets_enable_image_generation'] ) ? true : false;
		update_option( 'pa_dockets_enable_image_generation', $enable_image_generation );
		
		// Source Configuration
		$enable_dockets = isset( $_POST['pa_dockets_enable_dockets'] ) ? true : false;
		update_option( 'pa_dockets_enable_dockets', $enable_dockets );
		
		if ( isset( $_POST['pa_dockets_counties'] ) && is_array( $_POST['pa_dockets_counties'] ) ) {
			$counties = array_map( 'sanitize_text_field', $_POST['pa_dockets_counties'] );
			update_option( 'pa_dockets_counties', $counties );
		}
		
		if ( isset( $_POST['pa_dockets_search_url'] ) ) {
			update_option( 'pa_dockets_search_url', esc_url_raw( $_POST['pa_dockets_search_url'] ) );
		}
		
		// Article Settings
		if ( isset( $_POST['pa_dockets_default_category'] ) ) {
			update_option( 'pa_dockets_default_category', absint( $_POST['pa_dockets_default_category'] ) );
		}
		
		if ( isset( $_POST['pa_dockets_default_tags'] ) ) {
			update_option( 'pa_dockets_default_tags', sanitize_text_field( $_POST['pa_dockets_default_tags'] ) );
		}
		
		if ( isset( $_POST['pa_dockets_article_tone'] ) ) {
			update_option( 'pa_dockets_article_tone', sanitize_text_field( $_POST['pa_dockets_article_tone'] ) );
		}
	}
	
	/**
	 * Trigger manual scrape
	 */
	private function trigger_manual_scrape() {
		global $pa_dockets_scraper;
		
		$cron_handler = $pa_dockets_scraper->cron_handler;
		$cron_handler->execute_scraping_workflow();
		
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Manual scrape triggered! Check the logs for results.', 'coudy-ai' ) . '</p></div>';
	}
}
