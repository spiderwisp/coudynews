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
		
		// Append disclaimer to content
		$content_with_disclaimer = $article_data['content'] . $this->get_ai_disclaimer();
		
		// Prepare post data
		$post_data = array(
			'post_title' => sanitize_text_field( $article_data['title'] ),
			'post_content' => wp_kses_post( $content_with_disclaimer ),
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
			'description' => ! empty( $article_data['meta_description'] ) ? $article_data['meta_description'] : $this->generate_meta_description( $article_data['content'], $docket ),
			'keywords' => ! empty( $article_data['keywords'] ) ? $article_data['keywords'] : $this->generate_keywords( $docket ),
			'focus_keyphrase' => ! empty( $article_data['focus_keyphrase'] ) ? $article_data['focus_keyphrase'] : $this->generate_focus_keyphrase( $docket ),
		);
		
		// Use AIOSEO's savePost method if available
		try {
			$aioseo_post = AIOSEO\Plugin\Common\Models\Post::getPost( $post_id );
			
			// Prepare full SEO data with Open Graph and Twitter fields
			$save_data = array(
				'title' => $seo_data['title'],
				'description' => $seo_data['description'],
				'keywords' => $seo_data['keywords'],
				// Open Graph fields (use same as title/description by default)
				'og_title' => $seo_data['title'],
				'og_description' => $seo_data['description'],
				'og_article_section' => '',
				// Twitter fields (use same as title/description by default)
				'twitter_title' => $seo_data['title'],
				'twitter_description' => $seo_data['description'],
			);
			
			// Add focus keyphrase if available
			if ( ! empty( $seo_data['focus_keyphrase'] ) ) {
				$save_data['keyphrases'] = array(
					'focus' => array(
						'keyphrase' => $seo_data['focus_keyphrase']
					)
				);
			}
			
			if ( $aioseo_post->exists() ) {
				// Update existing post
				$aioseo_post->title = $save_data['title'];
				$aioseo_post->description = $save_data['description'];
				$aioseo_post->keywords = $save_data['keywords'];
				$aioseo_post->og_title = $save_data['og_title'];
				$aioseo_post->og_description = $save_data['og_description'];
				$aioseo_post->og_article_section = $save_data['og_article_section'];
				$aioseo_post->twitter_title = $save_data['twitter_title'];
				$aioseo_post->twitter_description = $save_data['twitter_description'];
				
				// Set focus keyphrase (stored in keyphrases JSON field)
				if ( ! empty( $save_data['keyphrases'] ) ) {
					$aioseo_post->keyphrases = $save_data['keyphrases'];
				}
				
				$aioseo_post->save();
			} else {
				// Create new AIOSEO post record
				AIOSEO\Plugin\Common\Models\Post::savePost( $post_id, $save_data );
			}
		} catch ( Exception $e ) {
			$this->logger->warning( sprintf( 'Failed to save AIOSEO meta for post %d, using fallback', $post_id ), array( 'error' => $e->getMessage() ) );
			$this->set_seo_meta_fallback( $post_id, $article_data );
		}
		
		// Also update post meta for compatibility
		update_post_meta( $post_id, '_aioseo_title', $seo_data['title'] );
		update_post_meta( $post_id, '_aioseo_description', $seo_data['description'] );
		update_post_meta( $post_id, '_aioseo_keywords', $seo_data['keywords'] );
		if ( ! empty( $seo_data['focus_keyphrase'] ) ) {
			update_post_meta( $post_id, '_aioseo_focus_keyphrase', $seo_data['focus_keyphrase'] );
		}
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
		
		if ( ! empty( $article_data['focus_keyphrase'] ) ) {
			update_post_meta( $post_id, '_aioseo_focus_keyphrase', sanitize_text_field( $article_data['focus_keyphrase'] ) );
		}
	}
	
	/**
	 * Generate SEO-optimized meta description from content
	 *
	 * @param string $content Article content
	 * @param object $docket  Docket object for context
	 * @return string Meta description
	 */
	private function generate_meta_description( $content, $docket = null ) {
		$text = wp_strip_all_tags( $content );
		
		// Try to extract a compelling first sentence (up to 155 chars)
		$sentences = preg_split( '/([.!?]+)/', $text, 2, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! empty( $sentences[0] ) && strlen( $sentences[0] ) <= 155 ) {
			$description = trim( $sentences[0] );
			// Add location context if not present
			if ( $docket && isset( $docket->county ) && stripos( $description, $docket->county ) === false ) {
				$county = ucfirst( $docket->county ) . ' County';
				if ( strlen( $description ) + strlen( $county ) + 3 <= 155 ) {
					$description .= ' - ' . $county;
				}
			}
			// Ensure it's between 120-155 characters
			if ( strlen( $description ) >= 120 && strlen( $description ) <= 155 ) {
				return $description;
			}
		}
		
		// Fallback: Get first 150 characters and cut at last complete word
		$description = substr( $text, 0, 150 );
		$last_space = strrpos( $description, ' ' );
		if ( $last_space !== false ) {
			$description = substr( $description, 0, $last_space );
		}
		
		// Add location if we have space
		if ( $docket && isset( $docket->county ) && strlen( $description ) < 140 ) {
			$county = ucfirst( $docket->county ) . ' County, PA';
			if ( strlen( $description ) + strlen( $county ) + 3 <= 155 ) {
				$description .= ' - ' . $county;
			}
		}
		
		return $description;
	}
	
	/**
	 * Generate comprehensive keywords from docket
	 *
	 * @param object $docket Docket object
	 * @return string Keywords
	 */
	private function generate_keywords( $docket ) {
		$keywords = array();
		
		// Location-based keywords (high priority)
		if ( isset( $docket->county ) ) {
			$county = ucfirst( $docket->county );
			$keywords[] = $county . ' County';
			$keywords[] = $county . ' County News';
			$keywords[] = $county . ' County Court';
			$keywords[] = $county . ' County Pennsylvania';
		}
		
		// Legal/case type keywords
		if ( isset( $docket->raw_data['case_type'] ) ) {
			$keywords[] = $docket->raw_data['case_type'];
			$case_type = $docket->raw_data['case_type'];
			if ( stripos( $case_type, 'criminal' ) !== false ) {
				$keywords[] = 'Criminal Charges';
				$keywords[] = 'Criminal Court';
			}
			if ( stripos( $case_type, 'civil' ) !== false ) {
				$keywords[] = 'Civil Case';
				$keywords[] = 'Civil Court';
			}
		}
		
		// General legal news keywords
		$keywords[] = 'Court Docket';
		$keywords[] = 'Pennsylvania';
		$keywords[] = 'Pennsylvania Court News';
		$keywords[] = 'Legal News';
		$keywords[] = 'Court Proceedings';
		
		return implode( ', ', array_unique( $keywords ) );
	}
	
	/**
	 * Generate focus keyphrase for SEO
	 *
	 * @param object $docket Docket object
	 * @return string Focus keyphrase
	 */
	private function generate_focus_keyphrase( $docket ) {
		$parts = array();
		
		// Add location
		if ( isset( $docket->county ) ) {
			$parts[] = ucfirst( $docket->county ) . ' County';
		}
		
		// Add case type or main topic
		if ( isset( $docket->raw_data['case_type'] ) ) {
			$case_type = $docket->raw_data['case_type'];
			// Simplify case type for keyphrase
			if ( stripos( $case_type, 'criminal' ) !== false ) {
				$parts[] = 'Criminal';
			} elseif ( stripos( $case_type, 'civil' ) !== false ) {
				$parts[] = 'Civil';
			} else {
				$parts[] = $case_type;
			}
		} else {
			$parts[] = 'Court';
		}
		
		// Add "Docket" or "News"
		$parts[] = 'Docket';
		
		// Combine into focus keyphrase (max 4 words)
		$keyphrase = implode( ' ', array_slice( $parts, 0, 3 ) );
		
		return $keyphrase;
	}
	
	/**
	 * Get AI-generated content disclaimer
	 *
	 * @return string Disclaimer HTML
	 */
	private function get_ai_disclaimer() {
		$disclaimer = '<p style="font-size: 0.85em; color: #666; margin-top: 2em; padding-top: 1em; border-top: 1px solid #eee;"><em>This article was generated based on publicly available court docket information. While we strive for accuracy, the content may contain errors or omissions. This article is for informational purposes only and should not be considered legal advice. For official court records and legal guidance, please consult the appropriate court or legal professional.</em></p>';
		
		return $disclaimer;
	}
}
