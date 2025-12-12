<?php
/**
 * Database class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Database {
	
	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'pa_dockets_scraped';
	}
	
	/**
	 * Create database table
	 */
	public function create_table() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			docket_number varchar(100) NOT NULL,
			county varchar(50) NOT NULL,
			scraped_date datetime NOT NULL,
			post_id bigint(20) UNSIGNED DEFAULT NULL,
			raw_data longtext,
			status varchar(20) DEFAULT 'pending',
			PRIMARY KEY (id),
			UNIQUE KEY docket_number (docket_number),
			KEY county (county),
			KEY status (status),
			KEY post_id (post_id)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	/**
	 * Insert new docket
	 *
	 * @param array $data Docket data
	 * @return int|false Insert ID or false on failure
	 */
	public function insert_docket( $data ) {
		global $wpdb;
		
		$defaults = array(
			'docket_number' => '',
			'county' => '',
			'scraped_date' => current_time( 'mysql' ),
			'post_id' => null,
			'raw_data' => '',
			'status' => 'pending',
		);
		
		$data = wp_parse_args( $data, $defaults );
		
		// Sanitize data
		$data['docket_number'] = sanitize_text_field( $data['docket_number'] );
		$data['county'] = sanitize_text_field( $data['county'] );
		$data['status'] = sanitize_text_field( $data['status'] );
		$data['raw_data'] = wp_json_encode( $data['raw_data'] );
		
		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array( '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		
		if ( $result ) {
			return $wpdb->insert_id;
		}
		
		return false;
	}
	
	/**
	 * Check if docket exists
	 *
	 * @param string $docket_number Docket number
	 * @return bool
	 */
	public function docket_exists( $docket_number ) {
		global $wpdb;
		
		$docket_number = sanitize_text_field( $docket_number );
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE docket_number = %s",
			$docket_number
		) );
		
		return (int) $count > 0;
	}
	
	/**
	 * Get pending dockets
	 *
	 * @param int $limit Number of dockets to retrieve
	 * @return array
	 */
	public function get_pending_dockets( $limit = 10 ) {
		global $wpdb;
		
		$limit = absint( $limit );
		
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE status = 'pending' ORDER BY scraped_date ASC LIMIT %d",
			$limit
		) );
		
		foreach ( $results as $result ) {
			$result->raw_data = json_decode( $result->raw_data, true );
		}
		
		return $results;
	}
	
	/**
	 * Update docket status
	 *
	 * @param int    $id     Docket ID
	 * @param string $status New status
	 * @param int    $post_id Post ID (optional)
	 * @return bool
	 */
	public function update_docket_status( $id, $status, $post_id = null ) {
		global $wpdb;
		
		$id = absint( $id );
		$status = sanitize_text_field( $status );
		
		$data = array( 'status' => $status );
		$format = array( '%s' );
		
		if ( null !== $post_id ) {
			$data['post_id'] = absint( $post_id );
			$format[] = '%d';
		}
		
		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);
		
		return false !== $result;
	}
	
	/**
	 * Get docket by ID
	 *
	 * @param int $id Docket ID
	 * @return object|null
	 */
	public function get_docket( $id ) {
		global $wpdb;
		
		$id = absint( $id );
		
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		) );
		
		if ( $result ) {
			$result->raw_data = json_decode( $result->raw_data, true );
		}
		
		return $result;
	}
	
	/**
	 * Get dockets by status
	 *
	 * @param string $status Status
	 * @param int    $limit  Limit
	 * @return array
	 */
	public function get_dockets_by_status( $status, $limit = 100 ) {
		global $wpdb;
		
		$status = sanitize_text_field( $status );
		$limit = absint( $limit );
		
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY scraped_date DESC LIMIT %d",
			$status,
			$limit
		) );
		
		foreach ( $results as $result ) {
			$result->raw_data = json_decode( $result->raw_data, true );
		}
		
		return $results;
	}
	
	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}
}
