<?php
/**
 * Cron Handler class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Cron_Handler {
	
	/**
	 * Scraper instance
	 *
	 * @var PA_Dockets_Scraper_Scraper
	 */
	private $scraper;
	
	/**
	 * AI Generator instance
	 *
	 * @var PA_Dockets_Scraper_AI_Generator
	 */
	private $ai_generator;
	
	/**
	 * Post Creator instance
	 *
	 * @var PA_Dockets_Scraper_Post_Creator
	 */
	private $post_creator;
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * Cron hook name
	 *
	 * @var string
	 */
	private $cron_hook = 'pa_dockets_daily_scrape';
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Scraper      $scraper      Scraper instance
	 * @param PA_Dockets_Scraper_AI_Generator $ai_generator AI Generator instance
	 * @param PA_Dockets_Scraper_Post_Creator $post_creator Post Creator instance
	 * @param PA_Dockets_Scraper_Logger       $logger       Logger instance
	 */
	public function __construct( $scraper, $ai_generator, $post_creator, $logger ) {
		$this->scraper = $scraper;
		$this->ai_generator = $ai_generator;
		$this->post_creator = $post_creator;
		$this->logger = $logger;
		
		// Register cron hook
		add_action( $this->cron_hook, array( $this, 'execute_scraping_workflow' ) );
	}
	
	/**
	 * Schedule cron job (daily at midnight)
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( $this->cron_hook ) ) {
			// Schedule for midnight (00:00) daily
			$midnight = strtotime( 'tomorrow midnight' );
			wp_schedule_event( $midnight, 'daily', $this->cron_hook );
			$this->logger->info( 'Scheduled daily scraping cron job' );
		}
	}
	
	/**
	 * Clear cron job
	 */
	public function clear_cron() {
		$timestamp = wp_next_scheduled( $this->cron_hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook );
			$this->logger->info( 'Cleared daily scraping cron job' );
		}
	}
	
	/**
	 * Execute complete scraping workflow
	 */
	public function execute_scraping_workflow() {
		// Check if scraping is enabled
		$enable_dockets = get_option( 'pa_dockets_enable_dockets', true );
		
		if ( ! $enable_dockets ) {
			$this->logger->info( 'Scraping is disabled in settings' );
			return;
		}
		
		$this->logger->info( 'Starting daily scraping workflow' );
		
		// Step 1: Scrape new dockets
		$counties = get_option( 'pa_dockets_counties', array( 'potter', 'tioga', 'mckean' ) );
		$new_count = $this->scraper->scrape_counties( $counties );
		
		$this->logger->info( sprintf( 'Found %d new dockets', $new_count ) );
		
		// Step 2: Process pending dockets (generate articles and create posts)
		$this->process_pending_dockets();
		
		$this->logger->info( 'Completed daily scraping workflow' );
	}
	
	/**
	 * Process pending dockets
	 *
	 * @param int $limit Number of dockets to process
	 */
	public function process_pending_dockets() {
		global $pa_dockets_scraper;
		
		if ( ! isset( $pa_dockets_scraper ) ) {
			$pa_dockets_scraper = pa_dockets_scraper();
		}
		
		$database = $pa_dockets_scraper->database;
		$pending_dockets = $database->get_pending_dockets( 100 ); // Process all pending dockets
		
		if ( empty( $pending_dockets ) ) {
			$this->logger->info( 'No pending dockets to process' );
			return;
		}
		
		$this->logger->info( sprintf( 'Processing %d pending dockets', count( $pending_dockets ) ) );
		
		// Track scheduled time for 10-minute intervals
		$scheduled_time = time();
		
		foreach ( $pending_dockets as $docket ) {
			$this->process_docket( $docket, $scheduled_time );
			// Increment scheduled time by 10 minutes for next post
			$scheduled_time += 600; // 10 minutes = 600 seconds
		}
	}
	
	/**
	 * Process a single docket
	 *
	 * @param object $docket Docket object
	 * @param int    $scheduled_time Unix timestamp for when to publish the post
	 */
	private function process_docket( $docket, $scheduled_time = null ) {
		$this->logger->info( sprintf( 'Processing docket: %s', $docket->docket_number ), array( 'docket_id' => $docket->id ) );
		
		// Step 1: Generate article using AI
		$article_data = $this->ai_generator->generate_article( $docket->raw_data );
		
		if ( ! $article_data ) {
			$this->logger->error( sprintf( 'Failed to generate article for docket %s', $docket->docket_number ), array( 'docket_id' => $docket->id ) );
			$this->update_docket_status( $docket->id, 'failed' );
			return;
		}
		
		// Step 2: Create WordPress post (scheduled if time provided)
		$post_id = $this->post_creator->create_post( $docket->id, $article_data, $scheduled_time );
		
		if ( ! $post_id ) {
			$this->logger->error( sprintf( 'Failed to create post for docket %s', $docket->docket_number ), array( 'docket_id' => $docket->id ) );
			$this->update_docket_status( $docket->id, 'failed' );
			return;
		}
		
		$schedule_info = $scheduled_time ? sprintf( ' (scheduled for %s)', date( 'Y-m-d H:i:s', $scheduled_time ) ) : '';
		$this->logger->success( sprintf( 'Successfully processed docket %s and created post %d%s', $docket->docket_number, $post_id, $schedule_info ), array( 'docket_id' => $docket->id, 'post_id' => $post_id, 'scheduled_time' => $scheduled_time ) );
	}
	
	/**
	 * Update docket status
	 *
	 * @param int    $docket_id Docket ID
	 * @param string $status    New status
	 */
	private function update_docket_status( $docket_id, $status ) {
		global $pa_dockets_scraper;
		
		if ( ! isset( $pa_dockets_scraper ) ) {
			$pa_dockets_scraper = pa_dockets_scraper();
		}
		
		$pa_dockets_scraper->database->update_docket_status( $docket_id, $status );
	}
	
	/**
	 * Get cron hook name
	 *
	 * @return string
	 */
	public function get_cron_hook() {
		return $this->cron_hook;
	}
}
