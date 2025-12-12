<?php
/**
 * Post Creator class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Post_Creator {
	
	/**
	 * Database instance
	 *
	 * @var PA_Dockets_Scraper_Database
	 */
	private $database;
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Database $database Database instance
	 * @param PA_Dockets_Scraper_Logger   $logger   Logger instance
	 */
	public function __construct( $database, $logger ) {
		$this->database = $database;
		$this->logger = $logger;
	}
	
	/**
	 * Create WordPress post from article data
	 *
	 * @param int   $docket_id  Docket ID
	 * @param array $article_data Article data (title, content, meta_description, keywords)
	 * @param int   $scheduled_time Optional Unix timestamp for scheduled publication (10-minute intervals)
	 * @return int|false Post ID or false on failure
	 */
	public function create_post( $docket_id, $article_data, $scheduled_time = null ) {
		$docket = $this->database->get_docket( $docket_id );
		
		if ( ! $docket ) {
			$this->logger->error( sprintf( 'Docket not found: %d', $docket_id ) );
			return false;
		}
		
		// Determine post status and date
		$post_status = 'publish';
		$post_date = null;
		
		if ( $scheduled_time && $scheduled_time > time() ) {
			// Schedule the post for future publication
			$post_status = 'future';
			$post_date = date( 'Y-m-d H:i:s', $scheduled_time );
		}
		
		// Prepare post data
		$post_data = array(
			'post_title' => sanitize_text_field( $article_data['title'] ),
			'post_content' => wp_kses_post( $article_data['content'] ),
			'post_status' => $post_status,
			'post_type' => 'post',
			'post_author' => 1, // Default to admin user
		);
		
		// Set scheduled date if provided
		if ( $post_date ) {
			$post_data['post_date'] = $post_date;
			$post_data['post_date_gmt'] = get_gmt_from_date( $post_date );
		}
		
		// Set category
		$default_category = get_option( 'pa_dockets_default_category', 0 );
		if ( $default_category > 0 ) {
			$post_data['post_category'] = array( absint( $default_category ) );
		}
		
		// Create post
		$post_id = wp_insert_post( $post_data, true );
		
		if ( is_wp_error( $post_id ) ) {
			$this->logger->error( sprintf( 'Failed to create post for docket %d', $docket_id ), array( 'error' => $post_id->get_error_message() ) );
			return false;
		}
		
		// Set tags
		$default_tags = get_option( 'pa_dockets_default_tags', '' );
		if ( ! empty( $default_tags ) ) {
			$tags = array_map( 'trim', explode( ',', $default_tags ) );
			wp_set_post_tags( $post_id, $tags );
		}
		
		// Add docket-specific tags
		$docket_tags = array();
		if ( isset( $docket->county ) ) {
			$docket_tags[] = ucfirst( $docket->county ) . ' County';
		}
		if ( isset( $docket->raw_data['case_type'] ) ) {
			$docket_tags[] = $docket->raw_data['case_type'];
		}
		$docket_tags[] = 'Court Docket';
		
		if ( ! empty( $docket_tags ) ) {
			wp_set_post_tags( $post_id, $docket_tags, true );
		}
		
		// Set SEO meta data (All-in-One SEO Pack)
		$this->set_seo_meta( $post_id, $article_data, $docket );
		
		// Store docket number as post meta for reference
		update_post_meta( $post_id, '_pa_dockets_docket_number', $docket->docket_number );
		update_post_meta( $post_id, '_pa_dockets_county', $docket->county );
		update_post_meta( $post_id, '_pa_dockets_scraped_date', $docket->scraped_date );
		
		// Update docket record with post ID
		$this->database->update_docket_status( $docket_id, 'processed', $post_id );
		
		$this->logger->success( sprintf( 'Created post %d for docket %s', $post_id, $docket->docket_number ), array( 'post_id' => $post_id, 'docket_id' => $docket_id ) );
		
		return $post_id;
	}
	
	/**
	 * Set SEO meta data using All-in-One SEO Pack
	 *
	 * @param int    $post_id      Post ID
	 * @param array  $article_data Article data
	 * @param object $docket       Docket object
	 */
	private function set_seo_meta( $post_id, $article_data, $docket ) {
		// Check if All-in-One SEO Pack is active
		if ( ! class_exists( 'AIOSEO\Plugin\Common\Models\Post' ) ) {
			// Fallback to direct post meta if AIOSEO is not available
			$this->set_seo_meta_fallback( $post_id, $article_data );
			return;
		}
		
		// Prepare SEO data
		$seo_data = array(
			'title' => ! empty( $article_data['title'] ) ? $article_data['title'] : get_the_title( $post_id ),
			'description' => ! empty( $article_data['meta_description'] ) ? $article_data['meta_description'] : $this->generate_meta_description( $article_data['content'] ),
			'keywords' => ! empty( $article_data['keywords'] ) ? $article_data['keywords'] : $this->generate_keywords( $docket ),
		);
		
		// Use AIOSEO's savePost method if available
		try {
			$aioseo_post = AIOSEO\Plugin\Common\Models\Post::getPost( $post_id );
			
			if ( $aioseo_post->exists() ) {
				$aioseo_post->title = $seo_data['title'];
				$aioseo_post->description = $seo_data['description'];
				$aioseo_post->keywords = $seo_data['keywords'];
				$aioseo_post->save();
			} else {
				// Create new AIOSEO post record
				AIOSEO\Plugin\Common\Models\Post::savePost( $post_id, array(
					'title' => $seo_data['title'],
					'description' => $seo_data['description'],
					'keywords' => $seo_data['keywords'],
				) );
			}
		} catch ( Exception $e ) {
			$this->logger->warning( sprintf( 'Failed to save AIOSEO meta for post %d, using fallback', $post_id ), array( 'error' => $e->getMessage() ) );
			$this->set_seo_meta_fallback( $post_id, $article_data );
		}
		
		// Also update post meta for compatibility
		update_post_meta( $post_id, '_aioseo_title', $seo_data['title'] );
		update_post_meta( $post_id, '_aioseo_description', $seo_data['description'] );
		update_post_meta( $post_id, '_aioseo_keywords', $seo_data['keywords'] );
	}
	
	/**
	 * Set SEO meta data using post meta (fallback method)
	 *
	 * @param int   $post_id      Post ID
	 * @param array $article_data Article data
	 */
	private function set_seo_meta_fallback( $post_id, $article_data ) {
		if ( ! empty( $article_data['title'] ) ) {
			update_post_meta( $post_id, '_aioseo_title', sanitize_text_field( $article_data['title'] ) );
		}
		
		if ( ! empty( $article_data['meta_description'] ) ) {
			update_post_meta( $post_id, '_aioseo_description', sanitize_text_field( $article_data['meta_description'] ) );
		}
		
		if ( ! empty( $article_data['keywords'] ) ) {
			update_post_meta( $post_id, '_aioseo_keywords', sanitize_text_field( $article_data['keywords'] ) );
		}
	}
	
	/**
	 * Generate meta description from content
	 *
	 * @param string $content Article content
	 * @return string Meta description
	 */
	private function generate_meta_description( $content ) {
		$text = wp_strip_all_tags( $content );
		$description = substr( $text, 0, 155 );
		$description = substr( $description, 0, strrpos( $description, ' ' ) );
		return $description . '...';
	}
	
	/**
	 * Generate keywords from docket
	 *
	 * @param object $docket Docket object
	 * @return string Keywords
	 */
	private function generate_keywords( $docket ) {
		$keywords = array();
		
		if ( isset( $docket->county ) ) {
			$keywords[] = ucfirst( $docket->county ) . ' County';
		}
		
		if ( isset( $docket->raw_data['case_type'] ) ) {
			$keywords[] = $docket->raw_data['case_type'];
		}
		
		$keywords[] = 'Court Docket';
		$keywords[] = 'Pennsylvania';
		
		return implode( ', ', array_unique( $keywords ) );
	}
}
