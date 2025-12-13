<?php
/**
 * RSS Parser class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_RSS_Parser {
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Logger $logger Logger instance
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}
	
	/**
	 * Validate RSS feed URL
	 *
	 * @param string $url Feed URL
	 * @return bool|string True if valid, false if invalid, 'rate_limited' if rate limited
	 */
	public function validate_feed( $url ) {
		if ( empty( $url ) ) {
			return false;
		}
		
		$url = esc_url_raw( $url );
		
		// Try to fetch feed
		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array(
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->warning( 'RSS feed validation failed', array( 'url' => $url, 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		
		// Handle rate limiting (429)
		if ( 429 === $status_code ) {
			$this->logger->warning( 'RSS feed rate limited (429)', array( 'url' => $url ) );
			return 'rate_limited';
		}
		
		if ( 200 !== $status_code ) {
			$this->logger->warning( 'RSS feed returned non-200 status', array( 'url' => $url, 'status' => $status_code ) );
			return false;
		}
		
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type && false === strpos( $content_type, 'xml' ) && false === strpos( $content_type, 'rss' ) && false === strpos( $content_type, 'atom' ) ) {
			// Not XML, might still be valid but log it
			$this->logger->info( 'RSS feed has unexpected content type', array( 'url' => $url, 'content_type' => $content_type ) );
		}
		
		return true;
	}
	
	/**
	 * Parse RSS feed
	 *
	 * @param string $rss_url Feed URL
	 * @return array|false Array of articles or false on failure
	 */
	public function parse_feed( $rss_url ) {
		if ( empty( $rss_url ) ) {
			return false;
		}
		
		$rss_url = esc_url_raw( $rss_url );
		
		// Use WordPress built-in feed parser
		$feed = fetch_feed( $rss_url );
		
		if ( is_wp_error( $feed ) ) {
			$this->logger->error( 'Failed to fetch RSS feed', array( 'url' => $rss_url, 'error' => $feed->get_error_message() ) );
			return false;
		}
		
		// Get feed items
		$max_items = $feed->get_item_quantity( 50 ); // Limit to 50 items
		$items = $feed->get_items( 0, $max_items );
		
		if ( empty( $items ) ) {
			$this->logger->info( 'RSS feed returned no items', array( 'url' => $rss_url ) );
			return array();
		}
		
		$articles = array();
		
		foreach ( $items as $item ) {
			$article = array(
				'title' => $item->get_title(),
				'url' => $item->get_permalink(),
				'excerpt' => $item->get_description(),
				'published_date' => null,
				'metadata' => array(),
			);
			
			// Get published date
			$date = $item->get_date( 'Y-m-d H:i:s' );
			if ( $date ) {
				$article['published_date'] = $date;
			}
			
			// Get author if available
			$author = $item->get_author();
			if ( $author ) {
				$article['metadata']['author'] = $author->get_name();
			}
			
			// Get categories/tags
			$categories = $item->get_categories();
			if ( ! empty( $categories ) ) {
				$article['metadata']['categories'] = array();
				foreach ( $categories as $category ) {
					$article['metadata']['categories'][] = $category->get_term();
				}
			}
			
			// Get content if available (some feeds have full content)
			$content = $item->get_content();
			if ( $content ) {
				$article['content'] = $content;
			}
			
			$articles[] = $article;
		}
		
		$this->logger->info( sprintf( 'Parsed %d articles from RSS feed', count( $articles ) ), array( 'url' => $rss_url ) );
		
		return $articles;
	}
	
	/**
	 * Extract articles from feed data
	 *
	 * @param object $feed_data Feed object
	 * @return array
	 */
	public function extract_articles( $feed_data ) {
		// This is a wrapper method for consistency
		// The actual extraction is done in parse_feed()
		return $this->parse_feed( $feed_data );
	}
	
	/**
	 * Auto-detect RSS feed URL from website URL
	 *
	 * @param string $website_url Website URL
	 * @return string|false RSS feed URL or false if not found
	 */
	public function auto_detect_rss( $website_url ) {
		if ( empty( $website_url ) ) {
			return false;
		}
		
		$website_url = esc_url_raw( $website_url );
		$base_url = trailingslashit( $website_url );
		
		// Strategy 1: Try to find feed link in HTML first (single request, less likely to rate limit)
		$response = wp_remote_get( $website_url, array(
			'timeout' => 15,
			'headers' => array(
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			),
		) );
		
		if ( ! is_wp_error( $response ) ) {
			$status_code = wp_remote_retrieve_response_code( $response );
			
			// If we get rate limited on HTML fetch, try minimal path checking
			if ( 429 === $status_code ) {
				$this->logger->warning( 'Rate limited when fetching HTML, trying minimal path detection', array( 'website' => $website_url ) );
				return $this->try_minimal_paths( $base_url, $website_url );
			}
			
			$body = wp_remote_retrieve_body( $response );
			
			// Look for RSS feed links in HTML (most reliable method)
			if ( preg_match_all( '/<link[^>]+type=["\']application\/(rss|atom)\+xml["\'][^>]+href=["\']([^"\']+)["\']/', $body, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$feed_url = $match[2];
					if ( ! preg_match( '/^https?:\/\//', $feed_url ) ) {
						$feed_url = $base_url . ltrim( $feed_url, '/' );
					}
					
					// If we found it in HTML, it's likely valid - try to validate but don't fail on rate limits
					$result = $this->validate_feed( $feed_url );
					if ( true === $result ) {
						$feed_type = 'rss' === $match[1] ? 'RSS' : 'Atom';
						$this->logger->info( sprintf( 'Found and validated %s feed in HTML', $feed_type ), array( 'website' => $website_url, 'feed' => $feed_url ) );
						return $feed_url;
					}
					
					// If rate limited but we found it in HTML, trust it and return it anyway
					if ( 'rate_limited' === $result ) {
						$feed_type = 'rss' === $match[1] ? 'RSS' : 'Atom';
						$this->logger->warning( sprintf( 'Rate limited when validating %s feed from HTML, but trusting it since it was found in page HTML', $feed_type ), array( 'website' => $website_url, 'feed' => $feed_url ) );
						return $feed_url; // Return it anyway since we found it in HTML
					}
				}
			}
			
			// Also check for feed URLs in meta tags or comments
			if ( preg_match( '/<!--[^>]*feed[^>]*:([^\s]+)/i', $body, $matches ) ) {
				$feed_url = trim( $matches[1] );
				if ( ! preg_match( '/^https?:\/\//', $feed_url ) ) {
					$feed_url = $base_url . ltrim( $feed_url, '/' );
				}
				
				$result = $this->validate_feed( $feed_url );
				if ( true === $result ) {
					$this->logger->info( 'Found RSS feed in HTML comment', array( 'website' => $website_url, 'feed' => $feed_url ) );
					return $feed_url;
				}
				
				// If rate limited but we found it in HTML, trust it
				if ( 'rate_limited' === $result ) {
					$this->logger->warning( 'Rate limited when validating feed from HTML comment, but trusting it', array( 'website' => $website_url, 'feed' => $feed_url ) );
					return $feed_url;
				}
			}
		}
		
		// Strategy 2: Try only the most common paths (reduced set to avoid rate limiting)
		return $this->try_minimal_paths( $base_url, $website_url );
	}
	
	/**
	 * Try minimal set of common RSS feed paths
	 *
	 * @param string $base_url Base URL with trailing slash
	 * @param string $website_url Original website URL for logging
	 * @return string|false RSS feed URL or false if not found
	 */
	private function try_minimal_paths( $base_url, $website_url ) {
		// Only try the most common paths to minimize requests
		$common_paths = array(
			'feed',
			'rss',
			'feed.xml',
			'rss.xml',
		);
		
		foreach ( $common_paths as $path ) {
			$feed_url = $base_url . $path;
			
			// Add delay between requests
			usleep( 1000000 ); // 1 second delay
			
			$result = $this->validate_feed( $feed_url );
			
			if ( true === $result ) {
				$this->logger->info( 'Auto-detected RSS feed via path', array( 'website' => $website_url, 'feed' => $feed_url ) );
				return $feed_url;
			}
			
			// If we get rate limited, stop trying more paths
			if ( 'rate_limited' === $result ) {
				$this->logger->warning( 'Rate limited during RSS auto-detection, stopping attempts', array( 'website' => $website_url ) );
				return false;
			}
		}
		
		$this->logger->info( 'Could not auto-detect RSS feed', array( 'website' => $website_url ) );
		return false;
	}
}

