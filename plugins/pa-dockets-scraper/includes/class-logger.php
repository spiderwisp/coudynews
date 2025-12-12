<?php
/**
 * Logger class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Logger {
	
	/**
	 * Log option name
	 *
	 * @var string
	 */
	private $log_option = 'pa_dockets_scraper_logs';
	
	/**
	 * Max log entries
	 *
	 * @var int
	 */
	private $max_logs = 500;
	
	/**
	 * Log a message
	 *
	 * @param string $message Log message
	 * @param string $type    Log type (info, error, warning, success)
	 * @param array  $context Additional context data
	 */
	public function log( $message, $type = 'info', $context = array() ) {
		$logs = $this->get_logs();
		
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'type' => sanitize_text_field( $type ),
			'message' => sanitize_text_field( $message ),
			'context' => $context,
		);
		
		array_unshift( $logs, $log_entry );
		
		// Keep only the most recent logs
		if ( count( $logs ) > $this->max_logs ) {
			$logs = array_slice( $logs, 0, $this->max_logs );
		}
		
		update_option( $this->log_option, $logs, false );
		
		// Also log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$log_message = sprintf(
				'[PA Dockets Scraper] [%s] %s',
				strtoupper( $type ),
				$message
			);
			
			if ( ! empty( $context ) ) {
				$log_message .= ' | Context: ' . wp_json_encode( $context );
			}
			
			error_log( $log_message );
		}
	}
	
	/**
	 * Log info message
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function info( $message, $context = array() ) {
		$this->log( $message, 'info', $context );
	}
	
	/**
	 * Log error message
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function error( $message, $context = array() ) {
		$this->log( $message, 'error', $context );
	}
	
	/**
	 * Log warning message
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function warning( $message, $context = array() ) {
		$this->log( $message, 'warning', $context );
	}
	
	/**
	 * Log success message
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function success( $message, $context = array() ) {
		$this->log( $message, 'success', $context );
	}
	
	/**
	 * Get all logs
	 *
	 * @param int    $limit  Number of logs to retrieve
	 * @param string $type   Filter by type (optional)
	 * @return array
	 */
	public function get_logs( $limit = 100, $type = '' ) {
		$logs = get_option( $this->log_option, array() );
		
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		
		// Filter by type if specified
		if ( ! empty( $type ) ) {
			$logs = array_filter( $logs, function( $log ) use ( $type ) {
				return $log['type'] === $type;
			} );
			$logs = array_values( $logs );
		}
		
		// Limit results
		if ( $limit > 0 ) {
			$logs = array_slice( $logs, 0, $limit );
		}
		
		return $logs;
	}
	
	/**
	 * Clear all logs
	 */
	public function clear_logs() {
		delete_option( $this->log_option );
	}
	
	/**
	 * Get log count by type
	 *
	 * @param string $type Log type
	 * @return int
	 */
	public function get_log_count_by_type( $type ) {
		$logs = $this->get_logs( 0 );
		$count = 0;
		
		foreach ( $logs as $log ) {
			if ( isset( $log['type'] ) && $log['type'] === $type ) {
				$count++;
			}
		}
		
		return $count;
	}
}
