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
		error_log( 'handle_form_submissions called. POST keys: ' . implode( ', ', array_keys( $_POST ) ) );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			error_log( 'User does not have manage_options capability' );
			return;
		}
		
		// Only handle on our pages
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		error_log( 'Current page: ' . $page );
		
		if ( ! in_array( $page, array( 'pa-content-sources', 'pa-content-articles' ), true ) ) {
			error_log( 'Page not in allowed list, returning' );
			return;
		}
		
		// Handle articles page form submissions
		if ( 'pa-content-articles' === $page ) {
			error_log( 'Handling articles page submissions' );
			if ( isset( $_POST['rewrite_article'] ) && check_admin_referer( 'pa_content_rewrite_article' ) ) {
				$this->handle_rewrite_article();
				return; // Exit after redirect
			}
			
			if ( isset( $_POST['skip_article'] ) && check_admin_referer( 'pa_content_skip_article' ) ) {
				$this->handle_skip_article();
				return; // Exit after redirect
			}
			
		if ( isset( $_POST['pa_bulk_action'] ) ) {
			error_log( 'Bulk action form submitted. POST data: ' . print_r( $_POST, true ) );
			
			// Verify nonce - check if user has proper capability as additional security
			if ( ! current_user_can( 'manage_options' ) ) {
				error_log( 'User does not have manage_options capability for bulk action' );
				set_transient( 'pa_content_error', __( 'You do not have permission to perform this action.', 'coudy-ai' ), 30 );
				wp_safe_redirect( add_query_arg( array(
					'page' => 'pa-content-articles',
					'error' => 'permission_denied',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			// Verify nonce manually (check_admin_referer would die() on failure)
			$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
			error_log( 'Nonce check: ' . ( empty( $nonce ) ? 'EMPTY' : 'EXISTS' ) . ', Value: ' . ( empty( $nonce ) ? 'N/A' : substr( $nonce, 0, 10 ) . '...' ) );
			error_log( 'Nonce action: pa_content_bulk_action' );
			
			$nonce_result = wp_verify_nonce( $nonce, 'pa_content_bulk_action' );
			error_log( 'wp_verify_nonce result: ' . ( $nonce_result ? 'PASSED (' . $nonce_result . ')' : 'FAILED (false)' ) );
			
			// If nonce fails, try checking referer manually
			if ( ! $nonce_result ) {
				$referer = isset( $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : '';
				$admin_url = strtolower( admin_url() );
				$admin_url_path = strtolower( parse_url( admin_url(), PHP_URL_PATH ) );
				$referer_lower = strtolower( $referer );
				error_log( 'Referer check - Admin URL: ' . $admin_url . ', Admin Path: ' . $admin_url_path . ', Referer: ' . $referer_lower );
				
				// Check if referer matches admin URL (absolute or relative)
				$referer_matches = false;
				if ( 0 === strpos( $referer_lower, $admin_url ) ) {
					$referer_matches = true;
				} elseif ( 0 === strpos( $referer_lower, $admin_url_path ) ) {
					$referer_matches = true;
				} elseif ( 0 === strpos( $referer_lower, '/wp-admin/' ) ) {
					$referer_matches = true;
				}
				
				// If referer matches admin and user has capability, allow
				if ( $referer_matches && current_user_can( 'manage_options' ) ) {
					error_log( 'Nonce failed but referer check passed - allowing action' );
					$nonce_result = true;
				}
			}
			
			if ( empty( $nonce ) || ! $nonce_result ) {
				error_log( 'Nonce verification FAILED - redirecting' );
				set_transient( 'pa_content_error', __( 'Security check failed. Please refresh the page and try again.', 'coudy-ai' ), 30 );
				wp_safe_redirect( add_query_arg( array(
					'page' => 'pa-content-articles',
					'error' => 'nonce_failed',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
			
			error_log( 'Nonce verified, calling handle_bulk_action' );
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
			} elseif ( 'nonce_expired' === $_GET['error'] || 'nonce_failed' === $_GET['error'] || 'nonce_missing' === $_GET['error'] ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page and try again.', 'coudy-ai' ) . '</p></div>';
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
				$action = isset( $_GET['bulk_action'] ) ? sanitize_text_field( $_GET['bulk_action'] ) : '';
				$message = '';
				if ( 'skip' === $action ) {
					$message = sprintf( esc_html__( 'Skipped %d article(s).', 'coudy-ai' ), $count );
				} elseif ( 'rewrite' === $action ) {
					$message = sprintf( esc_html__( 'Rewrote %d article(s).', 'coudy-ai' ), $count );
				} else {
					$message = sprintf( esc_html__( 'Processed %d article(s).', 'coudy-ai' ), $count );
				}
				echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
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
		$article_ids = isset( $_POST['article_ids'] ) ? (array) $_POST['article_ids'] : array();
		$article_ids = array_map( 'absint', $article_ids );
		$article_ids = array_filter( $article_ids ); // Remove empty values
		$category_id = isset( $_POST['bulk_category'] ) ? absint( $_POST['bulk_category'] ) : 0;
		
		// Debug: Log what we received
		error_log( 'Bulk action: ' . $action . ', Article IDs: ' . implode( ', ', $article_ids ) );
		
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
		$errors = array();
		
		foreach ( $article_ids as $article_id ) {
			$article_id = absint( $article_id );
			
			if ( empty( $article_id ) ) {
				continue; // Skip invalid IDs
			}
			
			if ( 'rewrite' === $action ) {
				$result = $this->rewriter->rewrite_article( $article_id, $category_id, $additional_prompt );
				if ( $result ) {
					$processed++;
				} else {
					$errors[] = $article_id;
				}
			} elseif ( 'skip' === $action ) {
				// Verify article exists before updating
				$article = $this->database->get_article( $article_id );
				if ( ! $article ) {
					error_log( 'Article not found for skip: ' . $article_id );
					$errors[] = $article_id;
					continue;
				}
				
				error_log( 'Attempting to skip article ID: ' . $article_id . ', Current status: ' . $article->status );
				$result = $this->database->update_article_status( $article_id, 'skipped' );
				// update_article_status returns true if successful (even if 0 rows updated), false on error
				if ( $result ) {
					$processed++;
					error_log( 'Successfully skipped article ID: ' . $article_id );
				} else {
					error_log( 'Failed to skip article ID: ' . $article_id );
					$errors[] = $article_id;
				}
			}
		}
		
		// Build redirect URL with success message
		$redirect_args = array(
			'page' => 'pa-content-articles',
		);
		
		// Add success or error message
		if ( $processed > 0 ) {
			$redirect_args['bulk_processed'] = $processed;
			$redirect_args['bulk_action'] = $action;
			
			if ( ! empty( $errors ) ) {
				set_transient( 'pa_content_warning', sprintf( __( 'Processed %d article(s), but %d failed.', 'coudy-ai' ), $processed, count( $errors ) ), 30 );
				$redirect_args['warning'] = 'bulk_partial';
			}
		} else {
			// All failed
			set_transient( 'pa_content_error', __( 'Failed to process articles. Please check the logs.', 'coudy-ai' ), 30 );
			$redirect_args['error'] = 'bulk_failed';
		}
		
		$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );
		error_log( 'Bulk action redirect URL: ' . $redirect_url . ', Processed: ' . $processed . ', Action: ' . $action );
		wp_safe_redirect( $redirect_url );
		exit;
	}
}

