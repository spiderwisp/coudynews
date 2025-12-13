<?php
/**
 * Admin Upload class for Coudy AI
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Admin_Upload {
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * PDF Processor instance
	 *
	 * @var PA_Dockets_Scraper_PDF_Processor
	 */
	private $pdf_processor;
	
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
	 * Database instance
	 *
	 * @var PA_Dockets_Scraper_Database
	 */
	private $database;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $pa_dockets_scraper;
		
		if ( ! isset( $pa_dockets_scraper ) ) {
			$pa_dockets_scraper = pa_dockets_scraper();
		}
		
		$this->logger = $pa_dockets_scraper->logger;
		$this->database = $pa_dockets_scraper->database;
		$this->ai_generator = $pa_dockets_scraper->ai_generator;
		$this->post_creator = $pa_dockets_scraper->post_creator;
		
		// Initialize PDF processor (class is already loaded in main plugin file)
		$this->pdf_processor = new PA_Dockets_Scraper_PDF_Processor( $this->logger );
		
		// Handle file upload - register early to ensure it's available
		add_action( 'admin_post_coudy_ai_upload_pdf', array( $this, 'handle_upload' ) );
		add_action( 'admin_post_nopriv_coudy_ai_upload_pdf', array( $this, 'handle_upload' ) ); // Not needed but ensures hook exists
	}
	
	/**
	 * Render upload page
	 */
	public function render_upload_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'coudy-ai' ) );
		}
		
		// Get recent uploads
		$recent_uploads = $this->get_recent_uploads();
		
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/upload-page.php';
	}
	
	/**
	 * Handle PDF file upload
	 */
	public function handle_upload() {
		try {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to perform this action.', 'coudy-ai' ) );
			}
			
			// Verify nonce
			if ( ! isset( $_POST['coudy_ai_upload_nonce'] ) || ! wp_verify_nonce( $_POST['coudy_ai_upload_nonce'], 'coudy_ai_upload_pdf' ) ) {
				wp_die( __( 'Security check failed.', 'coudy-ai' ) );
			}
		
			// Check if file was uploaded
			if ( ! isset( $_FILES['docket_pdf'] ) || $_FILES['docket_pdf']['error'] !== UPLOAD_ERR_OK ) {
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'upload_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			$file = $_FILES['docket_pdf'];
			
			// Validate file type
			$file_type = wp_check_filetype( $file['name'] );
			if ( $file_type['ext'] !== 'pdf' ) {
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'invalid_file_type',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Create upload directory
			$upload_dir = $this->get_upload_directory();
			if ( ! wp_mkdir_p( $upload_dir ) ) {
				$this->logger->error( sprintf( 'Failed to create upload directory: %s', $upload_dir ) );
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'directory_creation_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Generate unique filename
			$filename = 'docket-' . time() . '-' . wp_unique_filename( $upload_dir, $file['name'] );
			$file_path = $upload_dir . '/' . $filename;
			
			// Move uploaded file
			if ( ! move_uploaded_file( $file['tmp_name'], $file_path ) ) {
				$this->logger->error( sprintf( 'Failed to move uploaded file to: %s', $file_path ) );
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'file_move_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			$this->logger->info( sprintf( 'PDF file uploaded successfully: %s', $filename ) );
			
			// Check for browser-extracted text
			$browser_extracted_text = '';
			$extraction_method = 'php';
			
			if ( isset( $_POST['extracted_text'] ) && ! empty( $_POST['extracted_text'] ) ) {
				$browser_extracted_text = sanitize_textarea_field( $_POST['extracted_text'] );
				
				// Validate browser-extracted text
				if ( strlen( trim( $browser_extracted_text ) ) > 50 ) {
					$printable_count = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $browser_extracted_text ) );
					$printable_ratio = $printable_count / max( strlen( $browser_extracted_text ), 1 );
					
					if ( $printable_ratio >= 0.4 ) {
						$extraction_method = 'browser';
						$this->logger->info( sprintf( 'Using browser-extracted text (%d characters)', strlen( $browser_extracted_text ) ) );
					} else {
						$this->logger->warning( 'Browser-extracted text failed validation, falling back to PHP extraction' );
						$browser_extracted_text = '';
					}
				} else {
					$this->logger->warning( 'Browser-extracted text too short, falling back to PHP extraction' );
					$browser_extracted_text = '';
				}
			}
			
			// Process the PDF (with optional pre-extracted text)
			$docket_data = $this->pdf_processor->process_uploaded_pdf( $file_path, $browser_extracted_text );
			
			if ( ! $docket_data ) {
				$methods_tried = array();
				if ( ! empty( $browser_extracted_text ) ) {
					$methods_tried[] = 'browser (PDF.js)';
				}
				$methods_tried[] = 'PHP parser';
				
				$this->logger->error( sprintf( 'PDF processing failed after trying: %s', implode( ', ', $methods_tried ) ) );
				
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'pdf_processing_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Log successful extraction method
			if ( isset( $docket_data['extraction_method'] ) ) {
				$this->logger->info( sprintf( 'PDF text extracted using %s method', $docket_data['extraction_method'] ) );
			}
			
			// Store docket in database (optional, for tracking)
			$insert_data = array(
				'docket_number' => $docket_data['docket_number'],
				'county' => 'manual',
				'raw_data' => $docket_data,
				'status' => 'pending',
			);
			
			$docket_id = $this->database->insert_docket( $insert_data );
			
			// Generate article using AI
			$article_data = $this->ai_generator->generate_article( $docket_data );
			
			if ( ! $article_data ) {
				$this->logger->error( sprintf( 'Failed to generate article for uploaded docket: %s', $docket_data['docket_number'] ) );
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'ai_generation_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Determine scheduled time (10-minute intervals if multiple uploads)
			$scheduled_time = null;
			if ( isset( $_POST['schedule_post'] ) && $_POST['schedule_post'] === 'yes' ) {
				// Get the next available scheduled time (10 minutes after last scheduled post)
				$scheduled_time = $this->get_next_scheduled_time();
			}
			
			// Check if image generation should be skipped for this upload
			$skip_image_generation = ! isset( $_POST['generate_image'] ) || $_POST['generate_image'] !== 'yes';
			
			// Create WordPress post
			$post_id = $this->post_creator->create_post( $docket_id, $article_data, $scheduled_time, $skip_image_generation );
			
			if ( ! $post_id ) {
				$this->logger->error( sprintf( 'Failed to create post for uploaded docket: %s', $docket_data['docket_number'] ) );
				wp_redirect( add_query_arg( array(
					'page' => 'coudy-ai-upload',
					'error' => 'post_creation_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Success - redirect with success message
			wp_redirect( add_query_arg( array(
				'page' => 'coudy-ai-upload',
				'success' => '1',
				'post_id' => $post_id,
			), admin_url( 'admin.php' ) ) );
			exit;
		} catch ( Exception $e ) {
			// Log the error
			if ( isset( $this->logger ) ) {
				$this->logger->error( sprintf( 'Upload handler error: %s', $e->getMessage() ), array( 'trace' => $e->getTraceAsString() ) );
			}
			
			// Redirect with error message
			wp_redirect( add_query_arg( array(
				'page' => 'coudy-ai-upload',
				'error' => 'processing_error',
				'message' => urlencode( $e->getMessage() ),
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
	
	/**
	 * Get upload directory path
	 *
	 * @return string Upload directory path
	 */
	private function get_upload_directory() {
		$upload_dir = wp_upload_dir();
		$coudy_ai_dir = $upload_dir['basedir'] . '/coudy-ai/' . date( 'Y/m' );
		return $coudy_ai_dir;
	}
	
	/**
	 * Get next scheduled time for post (10-minute intervals)
	 *
	 * @return int Unix timestamp
	 */
	private function get_next_scheduled_time() {
		// Get the most recent scheduled post time
		$args = array(
			'post_status' => 'future',
			'posts_per_page' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_query' => array(
				array(
					'key' => '_pa_dockets_docket_number',
					'compare' => 'EXISTS',
				),
			),
		);
		
		$query = new WP_Query( $args );
		
		if ( $query->have_posts() ) {
			$query->the_post();
			$last_scheduled = get_post_time( 'U', true );
			wp_reset_postdata();
			
			// Add 10 minutes to the last scheduled post
			$next_time = $last_scheduled + 600; // 600 seconds = 10 minutes
			
			// If the next time is in the past, use current time + 10 minutes
			if ( $next_time <= time() ) {
				$next_time = time() + 600;
			}
			
			return $next_time;
		}
		
		// No scheduled posts yet, schedule for 10 minutes from now
		return time() + 600;
	}
	
	/**
	 * Get recent uploads
	 *
	 * @param int $limit Number of uploads to retrieve
	 * @return array Recent uploads
	 */
	private function get_recent_uploads( $limit = 10 ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pa_dockets_scraped';
		
		$uploads = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table_name} 
			WHERE county = 'manual' 
			ORDER BY scraped_date DESC 
			LIMIT %d",
			$limit
		) );
		
		return $uploads;
	}
}
