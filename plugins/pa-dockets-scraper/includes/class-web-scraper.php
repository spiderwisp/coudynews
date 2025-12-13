<?php
/**
 * Web Scraper class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Web_Scraper {
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * AI Generator instance
	 *
	 * @var PA_Dockets_Scraper_AI_Generator
	 */
	private $ai_generator;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Logger      $logger       Logger instance
	 * @param PA_Dockets_Scraper_AI_Generator $ai_generator AI Generator instance
	 */
	public function __construct( $logger, $ai_generator ) {
		$this->logger = $logger;
		$this->ai_generator = $ai_generator;
	}
	
	/**
	 * Scrape page and return HTML
	 *
	 * @param string $url Page URL
	 * @return string|false HTML content or false on failure
	 */
	public function scrape_page( $url ) {
		if ( empty( $url ) ) {
			return false;
		}
		
		$url = esc_url_raw( $url );
		
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Failed to scrape page', array( 'url' => $url, 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			$this->logger->warning( 'Page returned non-200 status', array( 'url' => $url, 'status' => $status_code ) );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		
		return $body;
	}
	
	/**
	 * Find article links on a page using simple HTML parsing
	 *
	 * @param string $html     HTML content
	 * @param string $base_url Base URL for resolving relative links
	 * @return array Array of article URLs
	 */
	public function find_article_links( $html, $base_url ) {
		$articles = array();
		
		if ( empty( $html ) ) {
			return $articles;
		}
		
		// Use DOMDocument to parse HTML
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Find all links
		$links = $xpath->query( '//a[@href]' );
		
		$base_url = trailingslashit( $base_url );
		
		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			$text = trim( $link->textContent );
			
			if ( empty( $href ) || empty( $text ) ) {
				continue;
			}
			
			// Skip common non-article links
			if ( preg_match( '/\b(login|register|sign|contact|about|privacy|terms|search|tag|category|author|page|#)\b/i', $href ) ) {
				continue;
			}
			
			// Resolve relative URLs
			if ( ! preg_match( '/^https?:\/\//', $href ) ) {
				$href = $base_url . ltrim( $href, '/' );
			}
			
			// Only include URLs from same domain
			$href_host = parse_url( $href, PHP_URL_HOST );
			$base_host = parse_url( $base_url, PHP_URL_HOST );
			
			if ( $href_host === $base_host ) {
				$articles[] = array(
					'url' => esc_url_raw( $href ),
					'title' => sanitize_text_field( $text ),
				);
			}
		}
		
		// Remove duplicates
		$unique_articles = array();
		$seen_urls = array();
		
		foreach ( $articles as $article ) {
			$url = $article['url'];
			if ( ! isset( $seen_urls[ $url ] ) ) {
				$seen_urls[ $url ] = true;
				$unique_articles[] = $article;
			}
		}
		
		return $unique_articles;
	}
	
	/**
	 * Use AI to parse articles from HTML
	 *
	 * @param string $html     HTML content
	 * @param string $base_url Base URL
	 * @return array|false Array of articles or false on failure
	 */
	public function use_ai_to_parse_articles( $html, $base_url ) {
		if ( empty( $this->ai_generator ) ) {
			$this->logger->error( 'AI Generator not available for parsing' );
			return false;
		}
		
		// Limit HTML size to avoid token limits (first 50000 characters)
		$html_limited = substr( $html, 0, 50000 );
		
		$prompt = "You are analyzing a webpage HTML to identify article links and extract article information.\n\n";
		$prompt .= "TASK: Parse the HTML below and identify all article/blog post links. For each article, extract:\n";
		$prompt .= "1. Article title (from link text or nearby heading)\n";
		$prompt .= "2. Article URL (full URL, resolve relative paths using base URL: {$base_url})\n";
		$prompt .= "3. Published date if available\n";
		$prompt .= "4. Excerpt/summary if available\n\n";
		$prompt .= "IGNORE: Navigation links, menu items, footer links, social media links, login/register links, category/tag pages.\n";
		$prompt .= "ONLY include actual article/blog post links.\n\n";
		$prompt .= "Return JSON format:\n";
		$prompt .= "{\n";
		$prompt .= '  "articles": [' . "\n";
		$prompt .= '    {' . "\n";
		$prompt .= '      "title": "Article Title",' . "\n";
		$prompt .= '      "url": "https://example.com/article",' . "\n";
		$prompt .= '      "published_date": "2024-01-01 12:00:00",' . "\n";
		$prompt .= '      "excerpt": "Article excerpt..."' . "\n";
		$prompt .= '    }' . "\n";
		$prompt .= '  ]' . "\n";
		$prompt .= "}\n\n";
		$prompt .= "HTML Content:\n";
		$prompt .= $html_limited;
		
		// Use AI generator's API call method (we'll need to make it accessible or create a wrapper)
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
					'content' => 'You are a web scraping assistant. Parse HTML and extract structured article information. Always return valid JSON.',
				),
				array(
					'role' => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.1,
			'max_tokens' => 4000,
		);
		
		$response = wp_remote_post( $endpoint, array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $request_body ),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'AI parsing request failed', array( 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( 200 !== $status_code ) {
			$this->logger->error( 'AI parsing returned error', array( 'status' => $status_code, 'response' => $body ) );
			return false;
		}
		
		$data = json_decode( $body, true );
		
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			$this->logger->error( 'Invalid AI response format' );
			return false;
		}
		
		$ai_response = $data['choices'][0]['message']['content'];
		
		// Extract JSON from response
		$json_data = null;
		
		// Try to find JSON in response
		if ( preg_match( '/\{[\s\S]*"articles"[\s\S]*\}/', $ai_response, $matches ) ) {
			$json_data = json_decode( $matches[0], true );
		} else {
			$json_data = json_decode( $ai_response, true );
		}
		
		if ( ! is_array( $json_data ) || ! isset( $json_data['articles'] ) ) {
			$this->logger->warning( 'AI did not return valid article data', array( 'response' => substr( $ai_response, 0, 500 ) ) );
			return array(); // Return empty array instead of false
		}
		
		$articles = array();
		foreach ( $json_data['articles'] as $article ) {
			if ( ! empty( $article['url'] ) && ! empty( $article['title'] ) ) {
				// Resolve relative URLs
				if ( ! preg_match( '/^https?:\/\//', $article['url'] ) ) {
					$article['url'] = trailingslashit( $base_url ) . ltrim( $article['url'], '/' );
				}
				
				$articles[] = array(
					'title' => sanitize_text_field( $article['title'] ),
					'url' => esc_url_raw( $article['url'] ),
					'excerpt' => ! empty( $article['excerpt'] ) ? sanitize_text_field( $article['excerpt'] ) : null,
					'published_date' => ! empty( $article['published_date'] ) ? $article['published_date'] : null,
				);
			}
		}
		
		$this->logger->info( sprintf( 'AI parsed %d articles from HTML', count( $articles ) ) );
		
		return $articles;
	}
	
	/**
	 * Extract article content from URL
	 *
	 * @param string $url Article URL
	 * @return string|false Article content or false on failure
	 */
	public function extract_article_content( $url ) {
		$html = $this->scrape_page( $url );
		
		if ( ! $html ) {
			return false;
		}
		
		// Use DOMDocument to extract main content
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Try common article content selectors
		$content_selectors = array(
			'//article',
			'//div[@class="article-content"]',
			'//div[@class="post-content"]',
			'//div[@class="entry-content"]',
			'//div[@id="content"]',
			'//main',
			'//div[@class="content"]',
		);
		
		$content = '';
		
		foreach ( $content_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				foreach ( $nodes as $node ) {
					$text = $node->textContent;
					if ( strlen( $text ) > strlen( $content ) ) {
						$content = $text;
					}
				}
			}
		}
		
		// If no content found, try to get body text
		if ( empty( $content ) ) {
			$body = $xpath->query( '//body' );
			if ( $body->length > 0 ) {
				$content = $body->item( 0 )->textContent;
			}
		}
		
		// Clean up content
		$content = wp_strip_all_tags( $content );
		$content = preg_replace( '/\s+/', ' ', $content );
		$content = trim( $content );
		
		return $content;
	}
	
	/**
	 * Crawl site to find articles
	 *
	 * @param string $start_url Starting URL
	 * @param int    $max_depth Maximum crawl depth
	 * @return array Array of article URLs
	 */
	public function crawl_for_articles( $start_url, $max_depth = 2 ) {
		$articles = array();
		$visited = array();
		$to_visit = array( array( 'url' => $start_url, 'depth' => 0 ) );
		
		while ( ! empty( $to_visit ) && count( $articles ) < 50 ) { // Limit to 50 articles
			$current = array_shift( $to_visit );
			$url = $current['url'];
			$depth = $current['depth'];
			
			if ( isset( $visited[ $url ] ) || $depth > $max_depth ) {
				continue;
			}
			
			$visited[ $url ] = true;
			
			$this->logger->info( sprintf( 'Crawling: %s (depth: %d)', $url, $depth ) );
			
			$html = $this->scrape_page( $url );
			
			if ( ! $html ) {
				continue;
			}
			
			// Find article links on this page
			$found_articles = $this->find_article_links( $html, $url );
			
			foreach ( $found_articles as $article ) {
				if ( ! isset( $visited[ $article['url'] ] ) ) {
					$articles[] = $article;
					
					// If not at max depth, add to visit queue
					if ( $depth < $max_depth ) {
						$to_visit[] = array( 'url' => $article['url'], 'depth' => $depth + 1 );
					}
				}
			}
			
			// Rate limiting - wait a bit between requests
			usleep( 500000 ); // 0.5 seconds
		}
		
		$this->logger->info( sprintf( 'Crawl completed: found %d articles', count( $articles ) ) );
		
		return $articles;
	}
}

