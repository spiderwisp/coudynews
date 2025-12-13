<?php
/**
 * Article Rewriter class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Article_Rewriter {
	
	/**
	 * AI Generator instance
	 *
	 * @var PA_Dockets_Scraper_AI_Generator
	 */
	private $ai_generator;
	
	/**
	 * Content Database instance
	 *
	 * @var PA_Dockets_Scraper_Content_Database
	 */
	private $database;
	
	/**
	 * Post Creator instance
	 *
	 * @var PA_Dockets_Scraper_Post_Creator
	 */
	private $post_creator;
	
	/**
	 * Web Scraper instance
	 *
	 * @var PA_Dockets_Scraper_Web_Scraper
	 */
	private $web_scraper;
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_AI_Generator      $ai_generator AI Generator instance
	 * @param PA_Dockets_Scraper_Content_Database   $database     Content Database instance
	 * @param PA_Dockets_Scraper_Post_Creator      $post_creator Post Creator instance
	 * @param PA_Dockets_Scraper_Logger            $logger       Logger instance
	 * @param PA_Dockets_Scraper_Web_Scraper      $web_scraper  Web Scraper instance (optional)
	 */
	public function __construct( $ai_generator, $database, $post_creator, $logger, $web_scraper = null ) {
		$this->ai_generator = $ai_generator;
		$this->database = $database;
		$this->post_creator = $post_creator;
		$this->logger = $logger;
		$this->web_scraper = $web_scraper;
	}
	
	/**
	 * Rewrite article
	 *
	 * @param int    $article_id Article ID
	 * @param int    $category_id Optional category ID (0 for default)
	 * @param string $additional_prompt Optional additional prompt instructions
	 * @return int|false WordPress post ID or false on failure
	 */
	public function rewrite_article( $article_id, $category_id = 0, $additional_prompt = '' ) {
		$article = $this->database->get_article( $article_id );
		
		if ( ! $article ) {
			$this->logger->error( sprintf( 'Article not found: %d', $article_id ) );
			return false;
		}
		
		// Update status to rewriting
		$this->database->update_article_status( $article_id, 'rewriting' );
		
		// Get full article content if not already stored
		if ( empty( $article->content ) ) {
			$this->logger->info( 'Fetching full article content', array( 'article_id' => $article_id, 'url' => $article->url ) );
			
			// Use web scraper if available, otherwise use simple fetch
			if ( $this->web_scraper ) {
				$content = $this->web_scraper->extract_article_content( $article->url );
			} else {
				$content = $this->fetch_article_content( $article->url );
			}
			
			if ( $content ) {
				$this->database->update_article_content( $article_id, $content );
				$article->content = $content;
			} else {
				// Use excerpt as fallback
				$article->content = $article->excerpt;
			}
		}
		
		if ( empty( $article->content ) ) {
			$this->logger->error( 'No content available to rewrite', array( 'article_id' => $article_id ) );
			$this->database->update_article_status( $article_id, 'new' );
			return false;
		}
		
		// Build rewrite prompt
		$prompt = $this->build_rewrite_prompt( $article, $additional_prompt );
		
		// Call AI to rewrite
		$rewritten_data = $this->call_ai_rewrite( $prompt, $article );
		
		if ( ! $rewritten_data ) {
			$this->logger->error( 'AI rewrite failed', array( 'article_id' => $article_id ) );
			$this->database->update_article_status( $article_id, 'new' );
			return false;
		}
		
		// Create WordPress post as draft
		$post_id = $this->create_rewritten_post( $rewritten_data, $article, $category_id );
		
		if ( ! $post_id ) {
			$this->logger->error( 'Failed to create rewritten post', array( 'article_id' => $article_id ) );
			$this->database->update_article_status( $article_id, 'new' );
			return false;
		}
		
		// Update article status
		$this->database->update_article_status( $article_id, 'rewritten', $post_id );
		
		$this->logger->success( sprintf( 'Article rewritten successfully: Post %d', $post_id ), array(
			'article_id' => $article_id,
			'post_id' => $post_id,
		) );
		
		return $post_id;
	}
	
	/**
	 * Build rewrite prompt
	 *
	 * @param object $article Article object
	 * @param string $additional_prompt Optional additional prompt instructions
	 * @return string Prompt text
	 */
	private function build_rewrite_prompt( $article, $additional_prompt = '' ) {
		$tone = get_option( 'pa_dockets_article_tone', 'professional' );
		
		$prompt = "You are a professional content writer. Your task is to rewrite the following article in your own words while maintaining factual accuracy and key information.\n\n";
		$prompt .= "ORIGINAL ARTICLE:\n";
		$prompt .= "Title: " . $article->title . "\n\n";
		$prompt .= "Content:\n";
		$prompt .= wp_strip_all_tags( $article->content ) . "\n\n";
		$prompt .= "REWRITE REQUIREMENTS:\n";
		$prompt .= "1. Rewrite the article completely in your own words\n";
		$prompt .= "2. Maintain all factual information and key points\n";
		$prompt .= "3. Use a {$tone} tone\n";
		$prompt .= "4. Create a new, compelling headline (50-70 characters, concise and engaging)\n";
		$prompt .= "5. Structure the article with clear paragraphs and proper flow\n";
		$prompt .= "6. Add original insights or context where appropriate\n";
		$prompt .= "7. Ensure the content is SEO-friendly\n";
		$prompt .= "8. Do not copy sentences verbatim - completely rephrase everything\n";
		$prompt .= "9. Maintain the same general length and depth as the original\n";
		
		// Add additional prompt instructions if provided
		if ( ! empty( $additional_prompt ) ) {
			$prompt .= "\nADDITIONAL INSTRUCTIONS:\n";
			$prompt .= sanitize_text_field( $additional_prompt ) . "\n";
		}
		
		$prompt .= "\nSEO REQUIREMENTS:\n";
		$prompt .= "1. META DESCRIPTION: Create a compelling 150-155 character meta description\n";
		$prompt .= "2. KEYWORDS: Generate 5-8 relevant keywords/phrases\n";
		$prompt .= "3. FOCUS KEYPHRASE: Identify the primary search term (1-4 words)\n\n";
		$prompt .= "Return JSON format:\n";
		$prompt .= "{\n";
		$prompt .= '  "title": "Rewritten headline",' . "\n";
		$prompt .= '  "content": "<p>Rewritten article content with HTML paragraphs</p>",' . "\n";
		$prompt .= '  "meta_description": "SEO-optimized description (150-155 characters)",' . "\n";
		$prompt .= '  "keywords": "keyword1, keyword2, keyword3",' . "\n";
		$prompt .= '  "focus_keyphrase": "primary search term"' . "\n";
		$prompt .= "}\n";
		
		return $prompt;
	}
	
	/**
	 * Call AI to rewrite article
	 *
	 * @param string $prompt  Rewrite prompt
	 * @param object $article Original article
	 * @return array|false Rewritten article data or false on failure
	 */
	private function call_ai_rewrite( $prompt, $article ) {
		$api_url = get_option( 'pa_dockets_groq_api_url', 'https://api.groq.com/openai/v1' );
		$api_key = get_option( 'pa_dockets_groq_api_key', '' );
		
		if ( empty( $api_key ) ) {
			$this->logger->error( 'Groq API key not configured' );
			return false;
		}
		
		$endpoint = rtrim( $api_url, '/' ) . '/chat/completions';
		$model = get_option( 'pa_dockets_groq_model', 'llama-3.3-70b-versatile' );
		
		$request_body = array(
			'model' => $model,
			'messages' => array(
				array(
					'role' => 'system',
					'content' => 'You are a professional content writer who rewrites articles in your own words while maintaining factual accuracy. Always return valid JSON.',
				),
				array(
					'role' => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.3,
			'max_tokens' => 6000,
		);
		
		$response = wp_remote_post( $endpoint, array(
			'timeout' => 120,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $request_body ),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'AI rewrite request failed', array( 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( 200 !== $status_code ) {
			$this->logger->error( sprintf( 'AI rewrite returned status code: %d', $status_code ), array( 'response' => $body ) );
			return false;
		}
		
		$data = json_decode( $body, true );
		
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			$this->logger->error( 'Invalid AI rewrite response format', array( 'response' => $body ) );
			return false;
		}
		
		$ai_response = $data['choices'][0]['message']['content'];
		
		// Parse JSON response (similar to article generator)
		$rewritten_data = $this->parse_rewrite_response( $ai_response );
		
		return $rewritten_data;
	}
	
	/**
	 * Parse rewrite response from AI
	 *
	 * @param string $response AI response
	 * @return array|false Parsed data or false on failure
	 */
	private function parse_rewrite_response( $response ) {
		// Remove markdown code blocks if present
		if ( preg_match( '/```(?:json)?\s*\r?\n?(.*?)\r?\n?```/s', $response, $matches ) ) {
			$response = $matches[1];
		}
		
		$response = trim( $response );
		
		// Try to parse JSON
		$json_data = json_decode( $response, true );
		
		if ( is_array( $json_data ) && ! empty( $json_data['title'] ) && ! empty( $json_data['content'] ) ) {
			return array(
				'title' => sanitize_text_field( $json_data['title'] ),
				'content' => wp_kses_post( $json_data['content'] ),
				'meta_description' => ! empty( $json_data['meta_description'] ) ? sanitize_text_field( $json_data['meta_description'] ) : '',
				'keywords' => ! empty( $json_data['keywords'] ) ? sanitize_text_field( $json_data['keywords'] ) : '',
				'focus_keyphrase' => ! empty( $json_data['focus_keyphrase'] ) ? sanitize_text_field( $json_data['focus_keyphrase'] ) : '',
			);
		}
		
		// Fallback: try to extract fields manually
		if ( preg_match( '/"title"\s*:\s*"([^"]+)"/', $response, $matches ) ) {
			$title = $matches[1];
		} else {
			$this->logger->error( 'Could not parse rewrite response' );
			return false;
		}
		
		if ( preg_match( '/"content"\s*:\s*"([^"]+)"/s', $response, $matches ) ) {
			$content = stripcslashes( $matches[1] );
		} else {
			$this->logger->error( 'Could not parse content from rewrite response' );
			return false;
		}
		
		return array(
			'title' => sanitize_text_field( $title ),
			'content' => wp_kses_post( $content ),
			'meta_description' => '',
			'keywords' => '',
			'focus_keyphrase' => '',
		);
	}
	
	/**
	 * Create rewritten post
	 *
	 * @param array  $rewritten_data Rewritten article data
	 * @param object $original_article Original article
	 * @param int    $category_id Optional category ID (0 for default)
	 * @return int|false Post ID or false on failure
	 */
	private function create_rewritten_post( $rewritten_data, $original_article, $category_id = 0 ) {
		// Prepare post data
		$post_data = array(
			'post_title' => sanitize_text_field( $rewritten_data['title'] ),
			'post_content' => wp_kses_post( $rewritten_data['content'] ),
			'post_status' => 'draft', // Save as draft for review
			'post_type' => 'post',
			'post_author' => 1, // Default to admin user
		);
		
		// Set category - use provided category or fall back to default
		if ( $category_id > 0 ) {
			$post_data['post_category'] = array( absint( $category_id ) );
		} else {
			$default_category = get_option( 'pa_dockets_default_category', 0 );
			if ( $default_category > 0 ) {
				$post_data['post_category'] = array( absint( $default_category ) );
			}
		}
		
		// Create post
		$post_id = wp_insert_post( $post_data, true );
		
		if ( is_wp_error( $post_id ) ) {
			$this->logger->error( 'Failed to create rewritten post', array( 'error' => $post_id->get_error_message() ) );
			return false;
		}
		
		// Set SEO meta data
		$this->set_seo_meta( $post_id, $rewritten_data );
		
		// Store original article reference and content
		update_post_meta( $post_id, '_pa_content_original_article_id', $original_article->id );
		update_post_meta( $post_id, '_pa_content_original_url', $original_article->url );
		update_post_meta( $post_id, '_pa_content_source_id', $original_article->source_id );
		update_post_meta( $post_id, '_pa_content_original_title', $original_article->title );
		if ( ! empty( $original_article->excerpt ) ) {
			update_post_meta( $post_id, '_pa_content_original_excerpt', $original_article->excerpt );
		}
		if ( ! empty( $original_article->content ) ) {
			update_post_meta( $post_id, '_pa_content_original_content', $original_article->content );
		}
		
		return $post_id;
	}
	
	/**
	 * Set SEO meta data
	 *
	 * @param int   $post_id Post ID
	 * @param array $article_data Article data
	 */
	private function set_seo_meta( $post_id, $article_data ) {
		// Check if All-in-One SEO Pack is active
		if ( ! class_exists( 'AIOSEO\Plugin\Common\Models\Post' ) ) {
			// Fallback to direct post meta
			if ( ! empty( $article_data['meta_description'] ) ) {
				update_post_meta( $post_id, '_aioseo_description', sanitize_text_field( $article_data['meta_description'] ) );
			}
			if ( ! empty( $article_data['keywords'] ) ) {
				update_post_meta( $post_id, '_aioseo_keywords', sanitize_text_field( $article_data['keywords'] ) );
			}
			if ( ! empty( $article_data['focus_keyphrase'] ) ) {
				update_post_meta( $post_id, '_aioseo_focus_keyphrase', sanitize_text_field( $article_data['focus_keyphrase'] ) );
			}
			return;
		}
		
		// Use AIOSEO's savePost method
		try {
			$save_data = array(
				'title' => get_the_title( $post_id ),
				'description' => ! empty( $article_data['meta_description'] ) ? $article_data['meta_description'] : '',
				'keywords' => ! empty( $article_data['keywords'] ) ? $article_data['keywords'] : '',
				'og_title' => get_the_title( $post_id ),
				'og_description' => ! empty( $article_data['meta_description'] ) ? $article_data['meta_description'] : '',
				'og_article_section' => '',
				'twitter_title' => get_the_title( $post_id ),
				'twitter_description' => ! empty( $article_data['meta_description'] ) ? $article_data['meta_description'] : '',
			);
			
			if ( ! empty( $article_data['focus_keyphrase'] ) ) {
				$save_data['keyphrases'] = array(
					'focus' => array(
						'keyphrase' => $article_data['focus_keyphrase']
					)
				);
			}
			
			AIOSEO\Plugin\Common\Models\Post::savePost( $post_id, $save_data );
		} catch ( Exception $e ) {
			$this->logger->warning( sprintf( 'Failed to save AIOSEO meta for post %d', $post_id ), array( 'error' => $e->getMessage() ) );
		}
	}
	
	/**
	 * Fetch article content from URL
	 *
	 * @param string $url Article URL
	 * @return string|false Content or false on failure
	 */
	private function fetch_article_content( $url ) {
		// This is a simple implementation
		// In a full implementation, we'd use the web scraper
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$html = wp_remote_retrieve_body( $response );
		
		// Simple content extraction
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Try to find main content
		$content_selectors = array( '//article', '//main', '//div[@class="content"]', '//div[@id="content"]' );
		
		foreach ( $content_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				$content = $nodes->item( 0 )->textContent;
				$content = wp_strip_all_tags( $content );
				$content = preg_replace( '/\s+/', ' ', $content );
				$content = trim( $content );
				
				if ( strlen( $content ) > 200 ) { // Only return if substantial content
					return $content;
				}
			}
		}
		
		return false;
	}
}

