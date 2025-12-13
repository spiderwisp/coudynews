<?php
/**
 * Content Database class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Content_Database {
	
	/**
	 * Sources table name
	 *
	 * @var string
	 */
	private $sources_table;
	
	/**
	 * Articles table name
	 *
	 * @var string
	 */
	private $articles_table;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->sources_table = $wpdb->prefix . 'pa_content_sources';
		$this->articles_table = $wpdb->prefix . 'pa_content_articles';
	}
	
	/**
	 * Create content tracking tables
	 */
	public function create_content_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// Sources table
		$sources_sql = "CREATE TABLE IF NOT EXISTS {$this->sources_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			url varchar(500) NOT NULL,
			rss_url varchar(500) DEFAULT NULL,
			scraping_method enum('rss','scrape','both') DEFAULT 'rss',
			last_checked datetime DEFAULT NULL,
			check_interval int(11) DEFAULT 24,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY is_active (is_active),
			KEY last_checked (last_checked)
		) $charset_collate;";
		
		// Articles table
		$articles_sql = "CREATE TABLE IF NOT EXISTS {$this->articles_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			source_id bigint(20) UNSIGNED NOT NULL,
			title varchar(500) NOT NULL,
			url varchar(1000) NOT NULL,
			excerpt text DEFAULT NULL,
			content longtext DEFAULT NULL,
			published_date datetime DEFAULT NULL,
			discovered_at datetime NOT NULL,
			status enum('new','rewriting','rewritten','skipped') DEFAULT 'new',
			rewritten_post_id bigint(20) UNSIGNED DEFAULT NULL,
			metadata text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY url (url(255)),
			KEY source_id (source_id),
			KEY status (status),
			KEY discovered_at (discovered_at),
			KEY rewritten_post_id (rewritten_post_id)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sources_sql );
		dbDelta( $articles_sql );
	}
	
	/**
	 * Add content source
	 *
	 * @param array $data Source data
	 * @return int|false Insert ID or false on failure
	 */
	public function add_content_source( $data ) {
		global $wpdb;
		
		$defaults = array(
			'name' => '',
			'url' => '',
			'rss_url' => null,
			'scraping_method' => 'rss',
			'check_interval' => 24,
			'is_active' => 1,
		);
		
		$data = wp_parse_args( $data, $defaults );
		$now = current_time( 'mysql' );
		
		$insert_data = array(
			'name' => sanitize_text_field( $data['name'] ),
			'url' => esc_url_raw( $data['url'] ),
			'rss_url' => ! empty( $data['rss_url'] ) ? esc_url_raw( $data['rss_url'] ) : null,
			'scraping_method' => in_array( $data['scraping_method'], array( 'rss', 'scrape', 'both' ) ) ? $data['scraping_method'] : 'rss',
			'check_interval' => absint( $data['check_interval'] ),
			'is_active' => $data['is_active'] ? 1 : 0,
			'created_at' => $now,
			'updated_at' => $now,
		);
		
		$result = $wpdb->insert(
			$this->sources_table,
			$insert_data,
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
		
		if ( $result ) {
			return $wpdb->insert_id;
		}
		
		return false;
	}
	
	/**
	 * Get content sources
	 *
	 * @param bool $active_only Only get active sources
	 * @return array
	 */
	public function get_content_sources( $active_only = false ) {
		global $wpdb;
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s",
			$this->sources_table
		) );
		
		if ( ! $table_exists ) {
			// Table doesn't exist, create it
			$this->create_content_tables();
		}
		
		$where = '';
		if ( $active_only ) {
			$where = "WHERE is_active = 1";
		}
		
		$results = $wpdb->get_results( "SELECT * FROM {$this->sources_table} {$where} ORDER BY name ASC" );
		
		if ( ! is_array( $results ) ) {
			return array();
		}
		
		// Get article counts for each source
		foreach ( $results as $source ) {
			$source->article_count = $this->get_article_count( $source->id );
		}
		
		return $results;
	}
	
	/**
	 * Get content source by ID
	 *
	 * @param int $id Source ID
	 * @return object|null
	 */
	public function get_content_source( $id ) {
		global $wpdb;
		
		$id = absint( $id );
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->sources_table} WHERE id = %d",
			$id
		) );
	}
	
	/**
	 * Update content source
	 *
	 * @param int   $id   Source ID
	 * @param array $data Update data
	 * @return bool
	 */
	public function update_content_source( $id, $data ) {
		global $wpdb;
		
		$id = absint( $id );
		$update_data = array( 'updated_at' => current_time( 'mysql' ) );
		
		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['url'] ) ) {
			$update_data['url'] = esc_url_raw( $data['url'] );
		}
		if ( isset( $data['rss_url'] ) ) {
			$update_data['rss_url'] = ! empty( $data['rss_url'] ) ? esc_url_raw( $data['rss_url'] ) : null;
		}
		if ( isset( $data['scraping_method'] ) ) {
			$update_data['scraping_method'] = in_array( $data['scraping_method'], array( 'rss', 'scrape', 'both' ) ) ? $data['scraping_method'] : 'rss';
		}
		if ( isset( $data['check_interval'] ) ) {
			$update_data['check_interval'] = absint( $data['check_interval'] );
		}
		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = $data['is_active'] ? 1 : 0;
		}
		if ( isset( $data['last_checked'] ) ) {
			$update_data['last_checked'] = $data['last_checked'];
		}
		
		$result = $wpdb->update(
			$this->sources_table,
			$update_data,
			array( 'id' => $id ),
			null,
			array( '%d' )
		);
		
		return false !== $result;
	}
	
	/**
	 * Delete content source
	 *
	 * @param int $id Source ID
	 * @return bool
	 */
	public function delete_content_source( $id ) {
		global $wpdb;
		
		$id = absint( $id );
		
		// Also delete associated articles
		$wpdb->delete( $this->articles_table, array( 'source_id' => $id ), array( '%d' ) );
		
		// Delete source
		$result = $wpdb->delete( $this->sources_table, array( 'id' => $id ), array( '%d' ) );
		
		return false !== $result;
	}
	
	/**
	 * Add article
	 *
	 * @param array $data Article data
	 * @return int|false Insert ID or false on failure
	 */
	public function add_article( $data ) {
		global $wpdb;
		
		$defaults = array(
			'source_id' => 0,
			'title' => '',
			'url' => '',
			'excerpt' => null,
			'content' => null,
			'published_date' => null,
			'status' => 'new',
			'metadata' => null,
		);
		
		$data = wp_parse_args( $data, $defaults );
		$now = current_time( 'mysql' );
		
		$insert_data = array(
			'source_id' => absint( $data['source_id'] ),
			'title' => sanitize_text_field( $data['title'] ),
			'url' => esc_url_raw( $data['url'] ),
			'excerpt' => ! empty( $data['excerpt'] ) ? wp_kses_post( $data['excerpt'] ) : null,
			'content' => ! empty( $data['content'] ) ? wp_kses_post( $data['content'] ) : null,
			'published_date' => ! empty( $data['published_date'] ) ? $data['published_date'] : null,
			'discovered_at' => $now,
			'status' => in_array( $data['status'], array( 'new', 'rewriting', 'rewritten', 'skipped' ) ) ? $data['status'] : 'new',
			'metadata' => ! empty( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null,
			'created_at' => $now,
			'updated_at' => $now,
		);
		
		$result = $wpdb->insert(
			$this->articles_table,
			$insert_data,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		
		if ( $result ) {
			return $wpdb->insert_id;
		}
		
		return false;
	}
	
	/**
	 * Get articles
	 *
	 * @param int    $source_id Optional source ID filter
	 * @param string $status    Optional status filter
	 * @param int    $limit     Limit results
	 * @param int    $offset    Offset for pagination
	 * @return array
	 */
	public function get_articles( $source_id = null, $status = null, $limit = 50, $offset = 0 ) {
		global $wpdb;
		
		$where = array( '1=1' );
		$where_values = array();
		
		if ( $source_id ) {
			$where[] = 'source_id = %d';
			$where_values[] = absint( $source_id );
		}
		
		if ( $status ) {
			$where[] = 'status = %s';
			$where_values[] = sanitize_text_field( $status );
		}
		
		$where_clause = implode( ' AND ', $where );
		
		$query = "SELECT a.*, s.name as source_name, s.url as source_url 
			FROM {$this->articles_table} a 
			LEFT JOIN {$this->sources_table} s ON a.source_id = s.id 
			WHERE {$where_clause} 
			ORDER BY a.discovered_at DESC 
			LIMIT %d OFFSET %d";
		
		$where_values[] = absint( $limit );
		$where_values[] = absint( $offset );
		
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		
		$results = $wpdb->get_results( $query );
		
		// Decode metadata
		foreach ( $results as $article ) {
			if ( ! empty( $article->metadata ) ) {
				$article->metadata = json_decode( $article->metadata, true );
			}
		}
		
		return $results;
	}
	
	/**
	 * Get article by ID
	 *
	 * @param int $id Article ID
	 * @return object|null
	 */
	public function get_article( $id ) {
		global $wpdb;
		
		$id = absint( $id );
		
		$article = $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*, s.name as source_name, s.url as source_url 
			FROM {$this->articles_table} a 
			LEFT JOIN {$this->sources_table} s ON a.source_id = s.id 
			WHERE a.id = %d",
			$id
		) );
		
		if ( $article && ! empty( $article->metadata ) ) {
			$article->metadata = json_decode( $article->metadata, true );
		}
		
		return $article;
	}
	
	/**
	 * Update article status
	 *
	 * @param int    $id      Article ID
	 * @param string $status  New status
	 * @param int    $post_id Optional rewritten post ID
	 * @return bool
	 */
	public function update_article_status( $id, $status, $post_id = null ) {
		global $wpdb;
		
		$id = absint( $id );
		$status = sanitize_text_field( $status );
		
		$update_data = array(
			'status' => $status,
			'updated_at' => current_time( 'mysql' ),
		);
		
		if ( null !== $post_id ) {
			$update_data['rewritten_post_id'] = absint( $post_id );
		}
		
		$result = $wpdb->update(
			$this->articles_table,
			$update_data,
			array( 'id' => $id ),
			null,
			array( '%d' )
		);
		
		return false !== $result;
	}
	
	/**
	 * Check if article exists by URL
	 *
	 * @param string $url Article URL
	 * @return bool
	 */
	public function article_exists( $url ) {
		global $wpdb;
		
		$url = esc_url_raw( $url );
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->articles_table} WHERE url = %s",
			$url
		) );
		
		return (int) $count > 0;
	}
	
	/**
	 * Get article count for source
	 *
	 * @param int $source_id Source ID
	 * @return int
	 */
	public function get_article_count( $source_id ) {
		global $wpdb;
		
		$source_id = absint( $source_id );
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->articles_table} WHERE source_id = %d",
			$source_id
		) );
		
		return (int) $count;
	}
	
	/**
	 * Get articles count with filters
	 *
	 * @param int    $source_id Optional source ID filter
	 * @param string $status    Optional status filter
	 * @return int
	 */
	public function get_articles_count( $source_id = null, $status = null ) {
		global $wpdb;
		
		$where = array( '1=1' );
		$where_values = array();
		
		if ( $source_id ) {
			$where[] = 'source_id = %d';
			$where_values[] = absint( $source_id );
		}
		
		if ( $status ) {
			$where[] = 'status = %s';
			$where_values[] = sanitize_text_field( $status );
		}
		
		$where_clause = implode( ' AND ', $where );
		
		$query = "SELECT COUNT(*) FROM {$this->articles_table} WHERE {$where_clause}";
		
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		
		$count = $wpdb->get_var( $query );
		
		return (int) $count;
	}
	
	/**
	 * Update article content
	 *
	 * @param int    $id      Article ID
	 * @param string $content Article content
	 * @return bool
	 */
	public function update_article_content( $id, $content ) {
		global $wpdb;
		
		$id = absint( $id );
		
		$result = $wpdb->update(
			$this->articles_table,
			array(
				'content' => wp_kses_post( $content ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		
		return false !== $result;
	}
}

