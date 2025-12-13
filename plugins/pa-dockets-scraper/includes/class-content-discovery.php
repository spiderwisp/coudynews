<?php
/**
 * Content Discovery class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Content_Discovery {
	
	/**
	 * Database instance
	 *
	 * @var PA_Dockets_Scraper_Content_Database
	 */
	private $database;
	
	/**
	 * RSS Parser instance
	 *
	 * @var PA_Dockets_Scraper_RSS_Parser
	 */
	private $rss_parser;
	
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
	 * @param PA_Dockets_Scraper_Content_Database $database   Database instance
	 * @param PA_Dockets_Scraper_RSS_Parser        $rss_parser RSS Parser instance
	 * @param PA_Dockets_Scraper_Web_Scraper       $web_scraper Web Scraper instance
	 * @param PA_Dockets_Scraper_Logger           $logger     Logger instance
	 */
	public function __construct( $database, $rss_parser, $web_scraper, $logger ) {
		$this->database = $database;
		$this->rss_parser = $rss_parser;
		$this->web_scraper = $web_scraper;
		$this->logger = $logger;
	}
	
	/**
	 * Discover articles for a source
	 *
	 * @param int $source_id Source ID
	 * @return int Number of new articles discovered
	 */
	public function discover_articles( $source_id ) {
		$source = $this->database->get_content_source( $source_id );
		
		if ( ! $source ) {
			$this->logger->error( sprintf( 'Source not found: %d', $source_id ) );
			return 0;
		}
		
		$this->logger->info( sprintf( 'Starting article discovery for source: %s', $source->name ), array( 'source_id' => $source_id ) );
		
		$articles = array();
		
		// Determine which method to use
		if ( 'rss' === $source->scraping_method || 'both' === $source->scraping_method ) {
			if ( ! empty( $source->rss_url ) ) {
				$rss_articles = $this->process_rss_source( $source );
				if ( is_array( $rss_articles ) ) {
					$articles = array_merge( $articles, $rss_articles );
				}
			}
		}
		
		if ( 'scrape' === $source->scraping_method || 'both' === $source->scraping_method ) {
			$scrape_articles = $this->process_scrape_source( $source );
			if ( is_array( $scrape_articles ) ) {
				$articles = array_merge( $articles, $scrape_articles );
			}
		}
		
		// Store discovered articles
		$new_count = $this->store_discovered_articles( $articles, $source_id );
		
		// Update last checked time
		$this->database->update_content_source( $source_id, array( 'last_checked' => current_time( 'mysql' ) ) );
		
		$this->logger->success( sprintf( 'Discovery completed for %s: %d new articles', $source->name, $new_count ), array( 'source_id' => $source_id ) );
		
		return $new_count;
	}
	
	/**
	 * Check all active sources
	 *
	 * @return array Results for each source
	 */
	public function check_all_sources() {
		$sources = $this->database->get_content_sources( true ); // Only active sources
		
		$results = array();
		
		foreach ( $sources as $source ) {
			// Check if it's time to check this source
			$should_check = true;
			
			if ( ! empty( $source->last_checked ) ) {
				$last_checked = strtotime( $source->last_checked );
				$check_interval = $source->check_interval * HOUR_IN_SECONDS;
				$next_check = $last_checked + $check_interval;
				
				if ( time() < $next_check ) {
					$should_check = false;
				}
			}
			
			if ( $should_check ) {
				$new_count = $this->discover_articles( $source->id );
				$results[ $source->id ] = array(
					'name' => $source->name,
					'new_articles' => $new_count,
				);
			}
		}
		
		return $results;
	}
	
	/**
	 * Process RSS source
	 *
	 * @param object $source Source object
	 * @return array|false Array of articles or false on failure
	 */
	private function process_rss_source( $source ) {
		if ( empty( $source->rss_url ) ) {
			$this->logger->warning( 'RSS URL not set for source', array( 'source_id' => $source->id ) );
			return false;
		}
		
		$articles = $this->rss_parser->parse_feed( $source->rss_url );
		
		if ( false === $articles ) {
			$this->logger->error( 'Failed to parse RSS feed', array( 'source_id' => $source->id, 'rss_url' => $source->rss_url ) );
			return false;
		}
		
		return $articles;
	}
	
	/**
	 * Process scrape source
	 *
	 * @param object $source Source object
	 * @return array|false Array of articles or false on failure
	 */
	private function process_scrape_source( $source ) {
		$articles = array();
		
		// First, try to scrape the main page and use AI to parse articles
		$html = $this->web_scraper->scrape_page( $source->url );
		
		if ( ! $html ) {
			$this->logger->error( 'Failed to scrape main page', array( 'source_id' => $source->id, 'url' => $source->url ) );
			return false;
		}
		
		// Use AI to parse articles from HTML
		$ai_articles = $this->web_scraper->use_ai_to_parse_articles( $html, $source->url );
		
		if ( is_array( $ai_articles ) && ! empty( $ai_articles ) ) {
			$articles = array_merge( $articles, $ai_articles );
		} else {
			// Fallback to simple link finding
			$found_links = $this->web_scraper->find_article_links( $html, $source->url );
			$articles = array_merge( $articles, $found_links );
		}
		
		// For each article, try to extract full content
		foreach ( $articles as &$article ) {
			if ( empty( $article['content'] ) ) {
				$content = $this->web_scraper->extract_article_content( $article['url'] );
				if ( $content ) {
					$article['content'] = $content;
				}
			}
		}
		
		return $articles;
	}
	
	/**
	 * Store discovered articles
	 *
	 * @param array $articles  Array of article data
	 * @param int   $source_id Source ID
	 * @return int Number of new articles stored
	 */
	private function store_discovered_articles( $articles, $source_id ) {
		$new_count = 0;
		
		foreach ( $articles as $article ) {
			// Check if article already exists
			if ( $this->database->article_exists( $article['url'] ) ) {
				continue;
			}
			
			// Add source_id
			$article['source_id'] = $source_id;
			
			// Store article
			$article_id = $this->database->add_article( $article );
			
			if ( $article_id ) {
				$new_count++;
				$this->logger->info( sprintf( 'Stored new article: %s', $article['title'] ), array( 'article_id' => $article_id, 'url' => $article['url'] ) );
			} else {
				$this->logger->warning( 'Failed to store article', array( 'url' => $article['url'] ) );
			}
		}
		
		return $new_count;
	}
}

