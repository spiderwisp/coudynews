<?php
/**
 * Admin Content class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Admin_Content {
	
	/**
	 * Content Database instance
	 *
	 * @var PA_Dockets_Scraper_Content_Database
	 */
	private $database;
	
	/**
	 * Content Discovery instance
	 *
	 * @var PA_Dockets_Scraper_Content_Discovery
	 */
	private $discovery;
	
	/**
	 * Article Rewriter instance
	 *
	 * @var PA_Dockets_Scraper_Article_Rewriter
	 */
	private $rewriter;
	
	/**
	 * RSS Parser instance
	 *
	 * @var PA_Dockets_Scraper_RSS_Parser
	 */
	private $rss_parser;
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Content_Database   $database  Database instance
	 * @param PA_Dockets_Scraper_Content_Discovery  $discovery Discovery instance
	 * @param PA_Dockets_Scraper_Article_Rewriter   $rewriter  Rewriter instance
	 * @param PA_Dockets_Scraper_RSS_Parser         $rss_parser RSS Parser instance
	 * @param PA_Dockets_Scraper_Logger             $logger    Logger instance
	 */
	public function __construct( $database, $discovery, $rewriter, $rss_parser, $logger ) {
		$this->database = $database;
		$this->discovery = $discovery;
		$this->rewriter = $rewriter;
		$this->rss_parser = $rss_parser;
		$this->logger = $logger;
		
		// Handle form submissions early, before any output
		add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
	}
	
	/**
	 * Handle form submissions early (before output)
	 */
	public function handle_form_submissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Only handle on our pages
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( ! in_array( $page, array( 'pa-content-sources', 'pa-content-articles' ), true ) ) {
			return;
		}
		
		// Handle articles page form submissions
		if ( 'pa-content-articles' === $page ) {
			if ( isset( $_POST['rewrite_article'] ) && check_admin_referer( 'pa_content_rewrite_article' ) ) {
				$this->handle_rewrite_article();
				return; // Exit after redirect
			}
			
			if ( isset( $_POST['skip_article'] ) && check_admin_referer( 'pa_content_skip_article' ) ) {
				$this->handle_skip_article();
				return; // Exit after redirect
			}
			
			if ( isset( $_POST['pa_bulk_action'] ) && check_admin_referer( 'pa_content_bulk_action' ) ) {
				$this->handle_bulk_action();
				return; // Exit after redirect
			}
		}
		
		// Handle sources page form submissions
		if ( 'pa-content-sources' === $page ) {
			if ( isset( $_POST['add_source'] ) && check_admin_referer( 'pa_content_add_source' ) ) {
				$this->handle_add_source();
				return; // Exit after redirect
			}
			
			if ( isset( $_POST['edit_source'] ) && check_admin_referer( 'pa_content_edit_source' ) ) {
				$this->handle_edit_source();
				// Don't redirect on edit, just show message
			}
		}
	}
	
	/**
	 * Render content sources page
	 */
	public function render_content_sources_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Ensure database tables exist
		$this->database->create_content_tables();
		
		// Form submissions are handled in admin_init hook
		// Handle GET requests (delete, check) - these don't redirect
		if ( isset( $_GET['delete'] ) && check_admin_referer( 'pa_content_delete_source_' . $_GET['delete'] ) ) {
			$this->handle_delete_source( absint( $_GET['delete'] ) );
		}
		
		if ( isset( $_GET['check'] ) && check_admin_referer( 'pa_content_check_source_' . $_GET['check'] ) ) {
			$this->handle_manual_check( absint( $_GET['check'] ) );
		}
		
		// Show success message if source was just added
		if ( isset( $_GET['added'] ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Source added successfully!', 'coudy-ai' ) . '</p></div>';
			} );
		}
		
		// Show error message if there was an error
		if ( isset( $_GET['error'] ) && 'add_failed' === $_GET['error'] ) {
			$error_message = get_transient( 'pa_content_source_error' );
			if ( $error_message ) {
				delete_transient( 'pa_content_source_error' );
				add_action( 'admin_notices', function() use ( $error_message ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
				} );
			}
		}
		
		// Get sources
		$sources = $this->database->get_content_sources();
		
		// Get source to edit if specified
		$edit_source = null;
		if ( isset( $_GET['edit'] ) ) {
			$edit_source = $this->database->get_content_source( absint( $_GET['edit'] ) );
		}
		
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/content-sources-page.php';
	}
	
	/**
	 * Render articles page
	 */
	public function render_articles_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Form submissions are handled in admin_init hook
		// Show success/error messages
		if ( isset( $_GET['rewritten'] ) ) {
			add_action( 'admin_notices', function() {
				$post_id = absint( $_GET['rewritten'] );
				echo '<div class="notice notice-success is-dismissible"><p>';
				esc_html_e( 'Article rewritten successfully!', 'coudy-ai' );
				echo ' <a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $post_id ) ) . '">';
				esc_html_e( 'View Draft Post', 'coudy-ai' );
				echo '</a></p></div>';
			} );
		}
		
		if ( isset( $_GET['error'] ) ) {
			$error_message = get_transient( 'pa_content_error' );
			if ( $error_message ) {
				delete_transient( 'pa_content_error' );
				add_action( 'admin_notices', function() use ( $error_message ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
				} );
			} elseif ( 'rewrite_failed' === $_GET['error'] ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to rewrite article. Please check the logs for details.', 'coudy-ai' ) . '</p></div>';
				} );
			}
		}
		
		if ( isset( $_GET['skipped'] ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Article skipped.', 'coudy-ai' ) . '</p></div>';
			} );
		}
		
		if ( isset( $_GET['bulk_processed'] ) ) {
			add_action( 'admin_notices', function() {
				$count = absint( $_GET['bulk_processed'] );
				echo '<div class="notice notice-success is-dismissible"><p>' . 
				     sprintf( esc_html__( 'Processed %d article(s).', 'coudy-ai' ), $count ) . 
				     '</p></div>';
			} );
		}
		
		// Get filters
		$source_id = isset( $_GET['source'] ) ? absint( $_GET['source'] ) : null;
		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : null;
		$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 20;
		$offset = ( $paged - 1 ) * $per_page;
		
		// Get articles
		$articles = $this->database->get_articles( $source_id, $status, $per_page, $offset );
		
		// Apply search filter if provided
		if ( ! empty( $search ) ) {
			$articles = array_filter( $articles, function( $article ) use ( $search ) {
				return stripos( $article->title, $search ) !== false || 
				       stripos( $article->excerpt, $search ) !== false;
			} );
		}
		
		// Get total count for pagination
		$total_count = $this->database->get_articles_count( $source_id, $status );
		$total_pages = ceil( $total_count / $per_page );
		
		// Get sources for filter dropdown
		$sources = $this->database->get_content_sources();
		
		include PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'admin/views/articles-page.php';
	}
	
	/**
	 * Handle add source
	 */
	private function handle_add_source() {
		$name = isset( $_POST['source_name'] ) ? sanitize_text_field( $_POST['source_name'] ) : '';
		$url = isset( $_POST['source_url'] ) ? esc_url_raw( $_POST['source_url'] ) : '';
		$rss_url = isset( $_POST['rss_url'] ) ? esc_url_raw( $_POST['rss_url'] ) : '';
		$scraping_method = isset( $_POST['scraping_method'] ) ? sanitize_text_field( $_POST['scraping_method'] ) : 'rss';
		$check_interval = isset( $_POST['check_interval'] ) ? absint( $_POST['check_interval'] ) : 24;
		$is_active = isset( $_POST['is_active'] ) ? 1 : 0;
		
		if ( empty( $name ) || empty( $url ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Name and URL are required.', 'coudy-ai' ) . '</p></div>';
			} );
			return;
		}
		
		// Auto-detect RSS if not provided and method is RSS or both
		if ( empty( $rss_url ) && ( 'rss' === $scraping_method || 'both' === $scraping_method ) ) {
			$rss_url = $this->rss_parser->auto_detect_rss( $url );
		}
		
		$data = array(
			'name' => $name,
			'url' => $url,
			'rss_url' => $rss_url,
			'scraping_method' => $scraping_method,
			'check_interval' => $check_interval,
			'is_active' => $is_active,
		);
		
		$source_id = $this->database->add_content_source( $data );
		
		if ( $source_id ) {
			// Optionally trigger immediate check (but don't wait for it to complete)
			if ( isset( $_POST['check_immediately'] ) ) {
				// Run discovery in background to avoid timeout
				wp_schedule_single_event( time() + 1, 'pa_content_discovery_check_single', array( $source_id ) );
			}
			
			// Redirect to prevent duplicate submissions and refresh the list
			// Use wp_safe_redirect and ensure no output before this
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-sources',
				'added' => $source_id,
			), admin_url( 'admin.php' ) ) );
			exit;
		} else {
			// Store error in transient to show after redirect
			set_transient( 'pa_content_source_error', __( 'Failed to add source. Please check the logs for details.', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-sources',
				'error' => 'add_failed',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
	
	/**
	 * Handle edit source
	 */
	private function handle_edit_source() {
		$id = isset( $_POST['source_id'] ) ? absint( $_POST['source_id'] ) : 0;
		
		if ( ! $id ) {
			return;
		}
		
		$name = isset( $_POST['source_name'] ) ? sanitize_text_field( $_POST['source_name'] ) : '';
		$url = isset( $_POST['source_url'] ) ? esc_url_raw( $_POST['source_url'] ) : '';
		$rss_url = isset( $_POST['rss_url'] ) ? esc_url_raw( $_POST['rss_url'] ) : '';
		$scraping_method = isset( $_POST['scraping_method'] ) ? sanitize_text_field( $_POST['scraping_method'] ) : 'rss';
		$check_interval = isset( $_POST['check_interval'] ) ? absint( $_POST['check_interval'] ) : 24;
		$is_active = isset( $_POST['is_active'] ) ? 1 : 0;
		
		$data = array(
			'name' => $name,
			'url' => $url,
			'rss_url' => $rss_url,
			'scraping_method' => $scraping_method,
			'check_interval' => $check_interval,
			'is_active' => $is_active,
		);
		
		$result = $this->database->update_content_source( $id, $data );
		
		if ( $result ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Source updated successfully!', 'coudy-ai' ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to update source.', 'coudy-ai' ) . '</p></div>';
			} );
		}
	}
	
	/**
	 * Handle delete source
	 *
	 * @param int $id Source ID
	 */
	private function handle_delete_source( $id ) {
		$result = $this->database->delete_content_source( $id );
		
		if ( $result ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Source deleted successfully!', 'coudy-ai' ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to delete source.', 'coudy-ai' ) . '</p></div>';
			} );
		}
	}
	
	/**
	 * Handle manual check
	 *
	 * @param int $id Source ID
	 */
	private function handle_manual_check( $id ) {
		$new_count = $this->discovery->discover_articles( $id );
		
		add_action( 'admin_notices', function() use ( $new_count ) {
			echo '<div class="notice notice-success"><p>' . 
			     sprintf( esc_html__( 'Discovery completed! Found %d new articles.', 'coudy-ai' ), $new_count ) . 
			     '</p></div>';
		} );
	}
	
	/**
	 * Handle rewrite article
	 */
	private function handle_rewrite_article() {
		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		$category_id = isset( $_POST['article_category'] ) ? absint( $_POST['article_category'] ) : 0;
		$additional_prompt = isset( $_POST['additional_prompt'] ) ? sanitize_textarea_field( $_POST['additional_prompt'] ) : '';
		
		if ( ! $article_id ) {
			set_transient( 'pa_content_error', __( 'Invalid article ID', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'error' => 'invalid_id',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
		
		$post_id = $this->rewriter->rewrite_article( $article_id, $category_id, $additional_prompt );
		
		if ( $post_id ) {
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'rewritten' => $post_id,
			), admin_url( 'admin.php' ) ) );
			exit;
		} else {
			set_transient( 'pa_content_error', __( 'Failed to rewrite article. Please check the logs.', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'error' => 'rewrite_failed',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
	
	/**
	 * Handle skip article
	 */
	private function handle_skip_article() {
		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		
		if ( ! $article_id ) {
			set_transient( 'pa_content_error', __( 'Invalid article ID', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'error' => 'invalid_id',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
		
		$this->database->update_article_status( $article_id, 'skipped' );
		
		wp_safe_redirect( add_query_arg( array(
			'page' => 'pa-content-articles',
			'skipped' => 1,
		), admin_url( 'admin.php' ) ) );
		exit;
	}
	
	/**
	 * Handle bulk action
	 */
	private function handle_bulk_action() {
		$action = isset( $_POST['bulk_action_type'] ) ? sanitize_text_field( $_POST['bulk_action_type'] ) : '';
		$article_ids = isset( $_POST['article_ids'] ) ? array_map( 'absint', $_POST['article_ids'] ) : array();
		$category_id = isset( $_POST['bulk_category'] ) ? absint( $_POST['bulk_category'] ) : 0;
		
		// Validate action and article IDs
		if ( empty( $action ) ) {
			set_transient( 'pa_content_error', __( 'Please select a bulk action.', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'error' => 'no_action',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
		
		if ( empty( $article_ids ) ) {
			set_transient( 'pa_content_error', __( 'Please select at least one article to perform this action.', 'coudy-ai' ), 30 );
			wp_safe_redirect( add_query_arg( array(
				'page' => 'pa-content-articles',
				'error' => 'no_articles',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
		
		$additional_prompt = isset( $_POST['bulk_additional_prompt'] ) ? sanitize_textarea_field( $_POST['bulk_additional_prompt'] ) : '';
		
		$processed = 0;
		
		foreach ( $article_ids as $article_id ) {
			if ( 'rewrite' === $action ) {
				$this->rewriter->rewrite_article( $article_id, $category_id, $additional_prompt );
				$processed++;
			} elseif ( 'skip' === $action ) {
				$this->database->update_article_status( $article_id, 'skipped' );
				$processed++;
			}
		}
		
		wp_safe_redirect( add_query_arg( array(
			'page' => 'pa-content-articles',
			'bulk_processed' => $processed,
		), admin_url( 'admin.php' ) ) );
		exit;
	}
}

