<?php
/**
 * Scraper class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Scraper {
	
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
	 * Base URL for PA Web Dockets
	 *
	 * @var string
	 */
	private $base_url = 'https://ujsportal.pacourts.us';
	
	/**
	 * Search URL (configurable)
	 *
	 * @var string
	 */
	private $search_url_override = '';
	
	/**
	 * County codes mapping
	 *
	 * @var array
	 */
	private $county_codes;
	
	/**
	 * Initialize county codes
	 */
	private function init_county_codes() {
		// County names (not codes) as used by the form
		$this->county_codes = array(
			'potter' => 'Potter',
			'tioga' => 'Tioga',
			'mckean' => 'McKean',
		);
	}
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Database $database Database instance
	 * @param PA_Dockets_Scraper_Logger   $logger   Logger instance
	 */
	public function __construct( $database, $logger ) {
		$this->database = $database;
		$this->logger = $logger;
		$this->init_county_codes();
		$this->search_url_override = get_option( 'pa_dockets_search_url', '' );
	}
	
	/**
	 * Scrape dockets for specified counties
	 *
	 * @param array $counties Array of county names to scrape
	 * @return int Number of new dockets found
	 */
	public function scrape_counties( $counties = array() ) {
		if ( empty( $counties ) ) {
			$counties = get_option( 'pa_dockets_counties', array( 'potter', 'tioga', 'mckean' ) );
		}
		
		if ( ! is_array( $counties ) ) {
			$counties = array( $counties );
		}
		
		$total_new = 0;
		
		foreach ( $counties as $county ) {
			$county = strtolower( sanitize_text_field( $county ) );
			
			if ( ! isset( $this->county_codes[ $county ] ) ) {
				$this->logger->warning( sprintf( 'Unknown county: %s', $county ) );
				continue;
			}
			
			$new_count = $this->scrape_county( $county );
			$total_new += $new_count;
			
			$this->logger->info( sprintf( 'Scraped %d new dockets for %s county', $new_count, ucfirst( $county ) ), array( 'county' => $county ) );
		}
		
		return $total_new;
	}
	
	/**
	 * Scrape dockets for a specific county
	 *
	 * @param string $county County name
	 * @return int Number of new dockets found
	 */
	public function scrape_county( $county ) {
		$county_name = $this->county_codes[ $county ];
		$new_count = 0;
		
		// Get recent dockets - search by date range (past day only)
		// Use YYYY-MM-DD format as the form expects
		$date_from = date( 'Y-m-d', strtotime( '-1 day' ) );
		$date_to = date( 'Y-m-d' );
		
		// Try using Puppeteer first (executes JavaScript)
		$dockets = $this->search_dockets_with_puppeteer( $county_name, $date_from, $date_to );
		
		// Fallback to regular search if Puppeteer fails
		if ( empty( $dockets ) ) {
			$this->logger->info( 'Puppeteer search returned no results, falling back to regular search' );
			$dockets = $this->search_dockets( $county_name, $date_from, $date_to );
		}
		
		if ( empty( $dockets ) ) {
			$this->logger->info( sprintf( 'No dockets found for %s county', ucfirst( $county ) ), array( 'county' => $county ) );
			return 0;
		}
		
		foreach ( $dockets as $docket_data ) {
			// Check if docket already exists
			if ( $this->database->docket_exists( $docket_data['docket_number'] ) ) {
				continue;
			}
			
			// Get full docket details from PDF
			$pdf_url = isset( $docket_data['pdf_url'] ) ? $docket_data['pdf_url'] : '';
			$full_docket = $this->get_docket_details( $docket_data['docket_number'], $pdf_url );
			
			if ( ! $full_docket ) {
				$this->logger->warning( sprintf( 'Could not retrieve details for docket %s', $docket_data['docket_number'] ) );
				continue;
			}
			
			// Store docket in database
			$insert_data = array(
				'docket_number' => $full_docket['docket_number'],
				'county' => $county,
				'raw_data' => $full_docket,
				'status' => 'pending',
			);
			
			$insert_id = $this->database->insert_docket( $insert_data );
			
			if ( $insert_id ) {
				$new_count++;
				$this->logger->success( sprintf( 'Stored new docket: %s', $full_docket['docket_number'] ), array( 'docket_id' => $insert_id ) );
			} else {
				$this->logger->error( sprintf( 'Failed to store docket: %s', $full_docket['docket_number'] ) );
			}
		}
		
		return $new_count;
	}
	
	/**
	 * Search for dockets using Puppeteer (executes JavaScript)
	 *
	 * @param string $county_name County name
	 * @param string $date_from   Start date (YYYY-MM-DD)
	 * @param string $date_to     End date (YYYY-MM-DD)
	 * @return array Array of docket data
	 */
	private function search_dockets_with_puppeteer( $county_name, $date_from, $date_to ) {
		$dockets = array();
		
		$script_path = PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'scraper.js';
		
		if ( ! file_exists( $script_path ) ) {
			$this->logger->warning( 'Puppeteer script not found', array( 'path' => $script_path ) );
			return $dockets;
		}
		
		// Check if node is available - try multiple methods
		$node_path = null;
		
		// Method 1: Try which/where command
		$node_cmd = 'which node 2>/dev/null || where node 2>nul';
		$result = shell_exec( $node_cmd );
		if ( ! empty( $result ) ) {
			$node_path = trim( $result );
		}
		
		// Method 2: Try common Windows paths (both Windows and WSL/Git Bash format)
		if ( empty( $node_path ) ) {
			$common_paths = array(
				'C:\\Program Files\\nodejs\\node.exe',
				'C:\\Program Files (x86)\\nodejs\\node.exe',
				'C:\\nodejs\\node.exe',
				'/c/Program Files/nodejs/node',
				'/c/Program Files/nodejs/node.exe',
				'/usr/bin/node',
				'/usr/local/bin/node',
			);
			foreach ( $common_paths as $path ) {
				if ( file_exists( $path ) ) {
					$node_path = $path;
					break;
				}
			}
		}
		
		// Method 3: Try just 'node' (might be in PATH)
		if ( empty( $node_path ) ) {
			$test_output = shell_exec( 'node --version 2>&1' );
			if ( ! empty( $test_output ) && strpos( $test_output, 'v' ) === 0 ) {
				$node_path = 'node';
			}
		}
		
		if ( empty( $node_path ) ) {
			$this->logger->warning( 'Node.js not found - cannot use Puppeteer. Tried: which/where, common Windows paths, and PATH lookup.' );
			return $dockets;
		}
		
		$this->logger->info( 'Found Node.js', array( 'path' => $node_path ) );
		
		// Escape arguments for shell - especially important on Windows with spaces in paths
		// On Windows, we need to quote paths with spaces
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			// Windows: use double quotes and escape any existing quotes
			$node_path_escaped = '"' . str_replace( '"', '\\"', $node_path ) . '"';
			$script_path_escaped = '"' . str_replace( '"', '\\"', $script_path ) . '"';
		} else {
			// Unix/Linux: use escapeshellarg
			$node_path_escaped = escapeshellarg( $node_path );
			$script_path_escaped = escapeshellarg( $script_path );
		}
		
		$county_escaped = escapeshellarg( $county_name );
		$date_from_escaped = escapeshellarg( $date_from );
		$date_to_escaped = escapeshellarg( $date_to );
		
		// Normalize script path for Windows (use backslashes)
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			$script_path = str_replace( '/', '\\', $script_path );
			$script_path_escaped = '"' . str_replace( '"', '\\"', $script_path ) . '"';
		}
		
		// Run Puppeteer script - make sure node path is properly quoted
		$command = sprintf( '%s %s %s %s %s', $node_path_escaped, $script_path_escaped, $county_escaped, $date_from_escaped, $date_to_escaped );
		
		$this->logger->info( 'Running Puppeteer scraper', array( 
			'command' => $command,
			'script_path' => $script_path,
		) );
		
		// Capture both stdout and stderr
		$output = shell_exec( $command . ' 2>&1' );
		
		if ( empty( $output ) ) {
			$this->logger->error( 'Puppeteer script returned no output', array( 'command' => $command ) );
			return $dockets;
		}
		
		// Log raw output for debugging (first 1000 chars)
		$this->logger->info( 'Puppeteer raw output', array( 
			'output_length' => strlen( $output ),
			'output_preview' => substr( $output, 0, 1000 ),
		) );
		
		// Try to extract JSON from output (might have error messages before/after)
		$json_start = strpos( $output, '[' );
		$json_end = strrpos( $output, ']' );
		
		if ( $json_start !== false && $json_end !== false && $json_end > $json_start ) {
			$json_string = substr( $output, $json_start, $json_end - $json_start + 1 );
		} else {
			// Try to find JSON object
			$json_start = strpos( $output, '{' );
			$json_end = strrpos( $output, '}' );
			if ( $json_start !== false && $json_end !== false && $json_end > $json_start ) {
				$json_string = substr( $output, $json_start, $json_end - $json_start + 1 );
			} else {
				$json_string = $output;
			}
		}
		
		// Parse JSON output
		$json_data = json_decode( $json_string, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->logger->error( 'Failed to parse Puppeteer output as JSON', array(
				'error' => json_last_error_msg(),
				'json_string' => substr( $json_string, 0, 500 ),
				'full_output' => substr( $output, 0, 1000 ),
			) );
			return $dockets;
		}
		
		if ( is_array( $json_data ) ) {
			// Ensure dockets have the correct structure
			foreach ( $json_data as $docket ) {
				if ( isset( $docket['docket_number'] ) && isset( $docket['pdf_url'] ) ) {
					$dockets[] = array(
						'docket_number' => $docket['docket_number'],
						'docket_url' => $docket['pdf_url'],
						'pdf_url' => $docket['pdf_url'],
					);
				}
			}
			$this->logger->info( sprintf( 'Puppeteer found %d dockets', count( $dockets ) ) );
		}
		
		return $dockets;
	}
	
	/**
	 * Search for dockets by county and date range (fallback method)
	 *
	 * @param string $county_name County name (e.g., "Potter", "Tioga", "McKean")
	 * @param string $date_from   Start date (YYYY-MM-DD)
	 * @param string $date_to     End date (YYYY-MM-DD)
	 * @return array Array of docket data
	 */
	private function search_dockets( $county_name, $date_from, $date_to ) {
		$dockets = array();
		
		// PA Web Dockets search endpoint - use correct URL
		// The correct URL is /CaseSearch (not /DocketSheets/MDJ.aspx)
		$search_url = $this->base_url . '/CaseSearch';
		
		// If custom search URL is configured, use it instead
		if ( ! empty( $this->search_url_override ) ) {
			$search_url = $this->search_url_override;
		}
		
		// First, get the page to retrieve CSRF token and cookies
		$page_response = wp_remote_get( $search_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language' => 'en-US,en;q=0.5',
			),
			'redirection' => 5,
		) );
		
		if ( is_wp_error( $page_response ) ) {
			$this->logger->error( 'Failed to fetch search page', array( 'error' => $page_response->get_error_message() ) );
			return $dockets;
		}
		
		$page_body = wp_remote_retrieve_body( $page_response );
		$page_status = wp_remote_retrieve_response_code( $page_response );
		
		if ( 200 !== $page_status ) {
			$this->logger->error( sprintf( 'Failed to fetch search page, status: %d, URL: %s', $page_status, $search_url ) );
			return $dockets;
		}
		
		// Extract CSRF token (RequestVerificationToken)
		$csrf_token = '';
		if ( preg_match( '/name="__RequestVerificationToken"[^>]*value="([^"]+)"/i', $page_body, $matches ) ) {
			$csrf_token = $matches[1];
		}
		
		if ( empty( $csrf_token ) ) {
			$this->logger->warning( 'Could not extract CSRF token from search page' );
		}
		
		// Build form data for Date Filed search
		// Based on actual form structure from curl: include all fields, even empty ones
		$form_data = array(
			'SearchBy' => 'DateFiled',
			'AdvanceSearch' => 'true',
			'ParticipantSID' => '',
			'ParticipantSSN' => '',
			'FiledStartDate' => $date_from, // YYYY-MM-DD format
			'FiledEndDate' => $date_to, // YYYY-MM-DD format
			'County' => $county_name, // County name (e.g., "Potter", "Tioga", "McKean")
			'JudicialDistrict' => '',
			'MDJSCourtOffice' => '',
			'DocketType' => '',
			'CaseCategory' => '',
			'CaseStatus' => '',
			'DriversLicenseState' => '',
			'PADriversLicenseNumber' => '',
			'ArrestingAgency' => '',
			'ORI' => '',
			'JudgeNameID' => '',
			'AppellateCourtName' => '',
			'AppellateDistrict' => '',
			'AppellateDocketType' => '',
			'AppellateCaseCategory' => '',
			'AppellateCaseType' => '',
			'AppellateAgency' => '',
			'AppellateTrialCourt' => '',
			'AppellateTrialCourtJudge' => '',
			'AppellateCaseStatus' => '',
			'ParticipantRole' => '',
			'ParcelState' => '',
			'ParcelCounty' => '',
			'ParcelMunicipality' => '',
			'CourtOffice' => '',
			'CourtRoomID' => '',
			'CalendarEventStartDate' => '',
			'CalendarEventEndDate' => '',
			'CalendarEventType' => '',
		);
		
		if ( ! empty( $csrf_token ) ) {
			$form_data['__RequestVerificationToken'] = $csrf_token;
		}
		
		// Get cookies from first request - WordPress will handle them automatically
		$cookies = wp_remote_retrieve_cookies( $page_response );
		
		// Build headers - match browser request as closely as possible
		$post_headers = array(
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Accept-Language' => 'en-US,en;q=0.9',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Referer' => $search_url,
			'Origin' => $this->base_url,
			'Cache-Control' => 'no-cache',
			'Pragma' => 'no-cache',
		);
		
		// Build request args
		$request_args = array(
			'timeout' => 30,
			'headers' => $post_headers,
			'body' => http_build_query( $form_data ),
			'redirection' => 5, // Follow redirects in case results are on a different page
		);
		
		// Add cookies if available - WordPress will handle cookie jar automatically
		if ( is_array( $cookies ) && ! empty( $cookies ) ) {
			$request_args['cookies'] = $cookies;
		}
		
		// Perform search with proper form encoding
		$response = wp_remote_post( $search_url, $request_args );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Failed to search dockets', array( 'error' => $response->get_error_message() ) );
			return $dockets;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$status_code = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		
		// Check for redirects
		$location = isset( $response_headers['location'] ) ? $response_headers['location'] : '';
		if ( ! empty( $location ) ) {
			$this->logger->info( 'Response includes redirect', array( 'location' => $location ) );
		}
		
		if ( 200 !== $status_code ) {
			// Log more details about the error
			$error_details = array(
				'status_code' => $status_code,
				'response_body_preview' => substr( $body, 0, 500 ),
			);
			$this->logger->error( sprintf( 'Search returned status code: %d', $status_code ), $error_details );
			return $dockets;
		}
		
		// DEBUG: Save full response to file for inspection (only if no links found)
		// This will help us see what's actually in the response
		$debug_file = WP_CONTENT_DIR . '/pa-dockets-debug-response.html';
		if ( ! file_exists( $debug_file ) || filesize( $debug_file ) < 1000 ) {
			// Only save if file doesn't exist or is small (to avoid filling disk)
			file_put_contents( $debug_file, $body );
			$this->logger->info( sprintf( 'Saved full response to %s for debugging (%d bytes)', $debug_file, strlen( $body ) ) );
		}
		
		// The table is loaded dynamically, but let's check if maybe the HTML is there but we're missing it
		// Search for the actual table element with various patterns
		$table_patterns = array(
			'/<table[^>]*id\s*=\s*["\']caseSearchResultGrid["\'][^>]*>/is',
			'/<table[^>]*id\s*=\s*caseSearchResultGrid[^>]*>/is',
			'/<table[^>]*["\']caseSearchResultGrid["\'][^>]*>/is',
		);
		
		$table_found = false;
		foreach ( $table_patterns as $pattern ) {
			if ( preg_match( $pattern, $body, $table_match, PREG_OFFSET_CAPTURE ) ) {
				$table_start_pos = $table_match[0][1];
				// Find the closing tag - need to handle nested tables
				$remaining_html = substr( $body, $table_start_pos );
				$table_html = '';
				$depth = 0;
				$pos = 0;
				$in_tag = false;
				
				// Simple parser to find matching </table> tag
				while ( $pos < strlen( $remaining_html ) ) {
					if ( substr( $remaining_html, $pos, 7 ) === '<table' ) {
						$depth++;
						$pos = strpos( $remaining_html, '>', $pos ) + 1;
					} elseif ( substr( $remaining_html, $pos, 8 ) === '</table>' ) {
						$depth--;
						if ( $depth === 0 ) {
							$table_html = substr( $remaining_html, 0, $pos + 8 );
							break;
						}
						$pos += 8;
					} else {
						$pos++;
					}
				}
				
				if ( ! empty( $table_html ) ) {
					$this->logger->info( 'Found caseSearchResultGrid table HTML element', array(
						'table_length' => strlen( $table_html ),
						'table_preview' => substr( $table_html, 0, 3000 ),
					) );
					
					// Search for PDF links in the table HTML
					if ( preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $table_html, $table_link_matches ) ) {
						$this->logger->info( sprintf( 'Found %d PDF links in table HTML element', count( $table_link_matches[1] ) ) );
						// Store these for processing
						if ( empty( $pdf_matches ) || count( $table_link_matches[1] ) > count( $pdf_matches[1] ) ) {
							$pdf_matches = $table_link_matches;
							$pdf_link_count = count( $table_link_matches[1] );
						}
					}
					$table_found = true;
					break;
				}
			}
		}
		
		if ( ! $table_found ) {
			// Table not found as HTML element - it's definitely loaded via JavaScript
			// Check if table data might be embedded as JSON in the HTML
			if ( preg_match_all( '/var\s+\w*[Tt]able\w*\s*=\s*(\[.*?\]);/is', $body, $json_matches ) ) {
				$this->logger->info( 'Found potential table data in JavaScript variables', array(
					'count' => count( $json_matches[1] ),
					'sample' => array_slice( $json_matches[1], 0, 1 ),
				) );
			}
			
			// Check for JSON data in script tags
			if ( preg_match_all( '/<script[^>]*>(.*?)<\/script>/is', $body, $script_matches ) ) {
				foreach ( $script_matches[1] as $script_content ) {
					// Look for JSON arrays or objects that might contain docket data
					if ( preg_match( '/\[.*?docketNumber.*?\]/is', $script_content ) ) {
						$this->logger->info( 'Found potential docket data in script tag' );
					}
				}
			}
			
			$this->logger->warning( 'Table HTML element not found - table is loaded dynamically via JavaScript/AJAX. Need to find AJAX endpoint.' );
		}
		
		// Debug: Check if results are in the HTML
		$has_results = false;
		$results_indicators = array( 'MdjDocketSheet', 'docketNumber', 'case found', 'results', 'table' );
		foreach ( $results_indicators as $indicator ) {
			if ( stripos( $body, $indicator ) !== false ) {
				$has_results = true;
				break;
			}
		}
		
		// Search for PDF links in the raw HTML using regex (more reliable than XPath for dynamic content)
		// Try multiple patterns to catch different encodings
		$pdf_link_count = 0;
		$pdf_matches = array();
		
		// Pattern 1: Standard href with /Report/MdjDocketSheet
		if ( preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $body, $matches ) ) {
			$pdf_link_count = count( $matches[1] );
			$pdf_matches = $matches;
		}
		
		// Pattern 2: With HTML entity encoding (&amp;)
		if ( $pdf_link_count === 0 && preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $body, $matches ) ) {
			$pdf_link_count = count( $matches[1] );
			$pdf_matches = $matches;
		}
		
		// Pattern 3: Decode HTML entities first
		if ( $pdf_link_count === 0 ) {
			$body_decoded = html_entity_decode( $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $body_decoded, $matches ) ) {
				$pdf_link_count = count( $matches[1] );
				$pdf_matches = $matches;
			}
		}
		
		// Pattern 4: Look for docketNumber parameter anywhere in the HTML (might be in data attributes, JavaScript, etc.)
		if ( $pdf_link_count === 0 && preg_match_all( '/docketNumber=([^&\s"\'<>]+)/i', $body, $docket_matches ) ) {
			$unique_dockets = array_unique( $docket_matches[1] );
			$this->logger->info( sprintf( 'Found %d unique docket numbers in HTML (but no PDF links)', count( $unique_dockets ) ), array(
				'sample_dockets' => array_slice( $unique_dockets, 0, 5 ),
			) );
			
			// If we found docket numbers but no links, the table might be loaded via AJAX
			// Look for AJAX endpoints or data attributes
			if ( preg_match_all( '/data-docket[^=]*=["\']([^"\']+)["\']/i', $body, $data_matches ) ) {
				$this->logger->info( sprintf( 'Found %d data-docket attributes', count( $data_matches[1] ) ) );
			}
			
			// Look for AJAX/API endpoints
			if ( preg_match_all( '/(["\']\/[^"\']*api[^"\']*["\']|["\'][^"\']*\/CaseSearch[^"\']*["\']|fetch\(["\'][^"\']*["\'])/i', $body, $ajax_matches ) ) {
				$this->logger->info( 'Found potential AJAX endpoints', array(
					'endpoints' => array_slice( $ajax_matches[0], 0, 10 ),
				) );
			}
		}
		
		// Debug: Check if results table exists in the HTML
		// Look for the actual <table> tag with id="caseSearchResultGrid", not just the string in JavaScript
		$table_pos = false;
		$table_snippet = '';
		
		// Try to find the actual table element
		if ( preg_match( '/<table[^>]*id=["\']caseSearchResultGrid["\'][^>]*>(.*?)<\/table>/is', $body, $table_match ) ) {
			$table_pos = strpos( $body, $table_match[0] );
			$table_snippet = $table_match[0]; // Full table HTML
			$this->logger->info( 'Found caseSearchResultGrid table element in HTML', array(
				'table_position' => $table_pos,
				'table_length' => strlen( $table_snippet ),
				'table_preview' => substr( $table_snippet, 0, 2000 ),
			) );
			
			// Search for PDF links specifically in the table HTML
			if ( preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $table_snippet, $table_matches ) ) {
				$this->logger->info( sprintf( 'Found %d PDF links in table HTML', count( $table_matches[1] ) ) );
				if ( $pdf_link_count === 0 ) {
					$pdf_link_count = count( $table_matches[1] );
					$pdf_matches = $table_matches;
				}
			} else {
				// Try with HTML entity decoding
				$table_decoded = html_entity_decode( $table_snippet, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				if ( preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $table_decoded, $table_matches ) ) {
					$this->logger->info( sprintf( 'Found %d PDF links in decoded table HTML', count( $table_matches[1] ) ) );
					if ( $pdf_link_count === 0 ) {
						$pdf_link_count = count( $table_matches[1] );
						$pdf_matches = $table_matches;
					}
				}
			}
		} else {
			// Fallback: just check if the string exists (might be in JavaScript)
			$table_pos = stripos( $body, 'caseSearchResultGrid' );
			if ( $table_pos !== false ) {
				$this->logger->warning( 'Found caseSearchResultGrid string but not as table element - might be in JavaScript', array(
					'position' => $table_pos,
					'context' => substr( $body, max( 0, $table_pos - 100 ), 300 ),
				) );
			}
		}
		
		// Debug: Log response preview to see what we're getting
		$body_preview = substr( $body, 0, 2000 );
		$this->logger->info( 'Search response received', array(
			'status_code' => $status_code,
			'body_length' => strlen( $body ),
			'has_results_indicators' => $has_results,
			'pdf_links_found_regex' => $pdf_link_count,
			'table_found' => $table_pos !== false,
			'body_preview' => $body_preview,
			'form_data' => $form_data,
		) );
		
		// If we found PDF links via regex, extract them
		if ( $pdf_link_count > 0 && isset( $pdf_matches[1] ) ) {
			$this->logger->info( sprintf( 'Found %d PDF links via regex search', count( $pdf_matches[1] ) ) );
			
			$unique_dockets = array(); // Track unique docket numbers
			
			foreach ( $pdf_matches[1] as $href ) {
				// Decode HTML entities in href
				$href = html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				
				// Extract docket number from href
				$docket_number = '';
				if ( preg_match( '/docketNumber=([^&]+)/', $href, $matches ) ) {
					$docket_number = urldecode( $matches[1] );
				}
				
				// Skip if we already have this docket (links might be duplicated)
				if ( ! empty( $docket_number ) && ! isset( $unique_dockets[ $docket_number ] ) ) {
					// Build full URL
					if ( ! preg_match( '/^https?:\/\//', $href ) ) {
						$href = $this->base_url . '/' . ltrim( $href, '/' );
					}
					
					$unique_dockets[ $docket_number ] = array(
						'docket_number' => $docket_number,
						'docket_url' => $href,
						'pdf_url' => $href,
					);
				}
			}
			
			if ( ! empty( $unique_dockets ) ) {
				$dockets = array_values( $unique_dockets );
				$this->logger->info( sprintf( 'Extracted %d unique dockets from PDF links', count( $dockets ) ) );
				return $dockets;
			}
		}
		
		// Check if results table is present (even if we're still on search page, results might be there)
		$has_results_table = stripos( $body, 'caseSearchResultGrid' ) !== false;
		
		// Check if we got redirected back to the search form (which would indicate the search didn't work)
		$still_on_search_page = stripos( $body, 'Search By:' ) !== false && stripos( $body, 'Date Filed Start Date:' ) !== false;
		
		if ( $still_on_search_page && ! $has_results_table ) {
			// We're still on the search page with no results - the form submission might not have worked
			$this->logger->warning( 'Still on search page after form submission - form may not have processed correctly' );
			
			// Check for error messages
			if ( preg_match( '/<div[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)<\/div>/is', $body, $error_matches ) ) {
				$this->logger->error( 'Form error detected', array( 'error' => strip_tags( $error_matches[1] ) ) );
			}
			
			// Check for validation messages
			if ( preg_match( '/<span[^>]*class="[^"]*field-validation-error[^"]*"[^>]*>(.*?)<\/span>/is', $body, $validation_matches ) ) {
				$this->logger->error( 'Form validation error', array( 'error' => strip_tags( $validation_matches[1] ) ) );
			}
			
			// Even if we're on the search page, continue parsing - results might still be there
			// (the page might show both the form and results)
		} elseif ( $has_results_table ) {
			$this->logger->info( 'Found results table in response (caseSearchResultGrid)' );
		}
		
		// CRITICAL: The table is loaded via JavaScript, but maybe the data is embedded in the HTML
		// Let's search the ENTIRE body with the most aggressive patterns possible
		$full_body_pdf_count = 0;
		$full_body_matches = array();
		
		// Try EVERY possible pattern variation
		$patterns = array(
			'/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', // Standard
			'/href=([^\s>]*\/Report\/MdjDocketSheet[^\s>]*)/i', // No quotes
			'/(\/Report\/MdjDocketSheet[^\s"\'<>]+)/i', // Just path
			'/["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', // Any quotes
			'/MdjDocketSheet[^"\'\s<>]*docketNumber[^"\'\s<>]*=([^"\'\s&<>]+)/i', // Docket number near MdjDocketSheet
		);
		
		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $body, $matches ) ) {
				$count = count( $matches[1] );
				if ( $count > $full_body_pdf_count ) {
					$full_body_pdf_count = $count;
					$full_body_matches = $matches[1];
					$this->logger->info( sprintf( 'Pattern matched %d links: %s', $count, substr( $pattern, 0, 50 ) ) );
				}
			}
		}
		
		// Also try with decoded HTML
		$body_decoded = html_entity_decode( $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $body_decoded, $matches ) ) {
				$count = count( $matches[1] );
				if ( $count > $full_body_pdf_count ) {
					$full_body_pdf_count = $count;
					$full_body_matches = $matches[1];
					$this->logger->info( sprintf( 'Pattern matched %d links in decoded HTML: %s', $count, substr( $pattern, 0, 50 ) ) );
				}
			}
		}
		
		// Also search for docket numbers directly - maybe we can construct URLs
		if ( $full_body_pdf_count === 0 && preg_match_all( '/MJ-\d+-\w+-\d+-\d+/i', $body, $docket_matches ) ) {
			$this->logger->info( sprintf( 'Found %d docket numbers in format MJ-*-*-*-*', count( $docket_matches[0] ) ), array(
				'sample' => array_slice( $docket_matches[0], 0, 5 ),
			) );
		}
		
		if ( $full_body_pdf_count > 0 ) {
			$this->logger->info( sprintf( 'Found %d PDF links in FULL body (not just preview)', $full_body_pdf_count ), array(
				'sample_links' => array_slice( $full_body_matches, 0, 3 ),
			) );
			
			// Extract dockets directly from these links
			$unique_dockets = array();
			foreach ( $full_body_matches as $href ) {
				$href = trim( $href, '"\' ' );
				$href = html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$docket_number = '';
				if ( preg_match( '/docketNumber=([^&]+)/', $href, $docket_matches ) ) {
					$docket_number = urldecode( $docket_matches[1] );
				}
				
				if ( ! empty( $docket_number ) && ! isset( $unique_dockets[ $docket_number ] ) ) {
					if ( ! preg_match( '/^https?:\/\//', $href ) ) {
						$href = $this->base_url . '/' . ltrim( $href, '/' );
					}
					
					$unique_dockets[ $docket_number ] = array(
						'docket_number' => $docket_number,
						'docket_url' => $href,
						'pdf_url' => $href,
					);
				}
			}
			
			if ( ! empty( $unique_dockets ) ) {
				$dockets = array_values( $unique_dockets );
				$this->logger->info( sprintf( 'Extracted %d unique dockets from full body search', count( $dockets ) ) );
				return $dockets;
			}
		} else {
			// Log that we searched but found nothing
			$this->logger->warning( 'Searched entire 2.2MB body with multiple patterns but found 0 PDF links - table definitely loaded via AJAX' );
		}
		
		// Parse HTML to extract docket information
		$dockets = $this->parse_search_results( $body );
		
		$this->logger->info( sprintf( 'Parsed %d dockets from search results', count( $dockets ) ) );
		
		return $dockets;
	}
	
	/**
	 * Extract ASP.NET control ID from HTML
	 *
	 * @param string $html     HTML content
	 * @param string $contains Text the control ID should contain
	 * @param string $tag      HTML tag type (input, select, etc.)
	 * @return string Control ID or empty string
	 */
	private function extract_control_id( $html, $contains, $tag = 'input' ) {
		// Try to find the control by its ID or name attribute containing the search term
		$pattern = '/<' . $tag . '[^>]*(?:id|name)=["\']([^"\']*' . preg_quote( $contains, '/' ) . '[^"\']*)["\'][^>]*>/i';
		
		if ( preg_match( $pattern, $html, $matches ) ) {
			return $matches[1];
		}
		
		// Fallback: try common ASP.NET naming patterns
		$common_patterns = array(
			'/ctl00\$ctl00\$ctl00\$cphMain\$cphDynamicContent\$' . preg_quote( $contains, '/' ) . '/',
			'/ctl00\$cphMain\$cphDynamicContent\$' . preg_quote( $contains, '/' ) . '/',
			'/' . preg_quote( $contains, '/' ) . '/',
		);
		
		foreach ( $common_patterns as $pattern ) {
			if ( preg_match( $pattern, $html, $matches ) ) {
				return $matches[0];
			}
		}
		
		return '';
	}
	
	/**
	 * Parse search results HTML
	 *
	 * @param string $html HTML content
	 * @return array Array of docket data
	 */
	private function parse_search_results( $html ) {
		$dockets = array();
		
		// FIRST: Try regex search on the FULL HTML - this is most reliable
		// The table might be loaded dynamically, but the links might still be in the HTML somewhere
		// Search for PDF links in the entire HTML response with multiple patterns
		$pdf_link_patterns = array(
			'/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', // Standard href with quotes
			'/href=([^\s>]*\/Report\/MdjDocketSheet[^\s>]*)/i', // Without quotes
			'/["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', // Any quotes around URL
			'/(\/Report\/MdjDocketSheet[^\s"\'<>]+)/i', // Just the path (most permissive)
		);
		
		$found_links = array();
		foreach ( $pdf_link_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $html, $matches ) ) {
				foreach ( $matches[1] as $href ) {
					// Clean up the href
					$href = trim( $href, '"\' ' );
					// Decode HTML entities
					$href = html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					// Only add if it looks like a valid PDF link
					if ( strpos( $href, 'MdjDocketSheet' ) !== false && ! in_array( $href, $found_links ) ) {
						$found_links[] = $href;
					}
				}
			}
		}
		
		// Also try with decoded HTML
		$html_decoded = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		foreach ( $pdf_link_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $html_decoded, $matches ) ) {
				foreach ( $matches[1] as $href ) {
					$href = trim( $href, '"\' ' );
					if ( strpos( $href, 'MdjDocketSheet' ) !== false && ! in_array( $href, $found_links ) ) {
						$found_links[] = $href;
					}
				}
			}
		}
		
		// Log what we found
		if ( ! empty( $found_links ) ) {
			$this->logger->info( sprintf( 'Found %d potential PDF links via regex patterns', count( $found_links ) ), array(
				'sample_links' => array_slice( $found_links, 0, 5 ),
			) );
		}
		
		// Also check for docket data in JavaScript/JSON (might be embedded in script tags)
		if ( empty( $found_links ) ) {
			// Look for JSON data in script tags
			if ( preg_match_all( '/<script[^>]*>(.*?)<\/script>/is', $html, $script_matches ) ) {
				foreach ( $script_matches[1] as $script_content ) {
					// Look for docket numbers or PDF URLs in JavaScript
					if ( preg_match_all( '/(\/Report\/MdjDocketSheet[^\s"\'<>]+|docketNumber[=:]\s*["\']?([^"\'\s,}]+))/i', $script_content, $js_matches ) ) {
						$this->logger->info( 'Found potential docket data in JavaScript', array(
							'js_matches_count' => count( $js_matches[0] ),
							'sample' => array_slice( $js_matches[0], 0, 3 ),
						) );
					}
				}
			}
			
			// Look for data attributes or hidden elements
			if ( preg_match_all( '/data-[^=]*=["\']([^"\']*MdjDocketSheet[^"\']*)["\']/i', $html, $data_matches ) ) {
				$this->logger->info( sprintf( 'Found %d data attributes with MdjDocketSheet', count( $data_matches[1] ) ) );
				$found_links = array_merge( $found_links, $data_matches[1] );
			}
		}
		
		// If we found links, extract docket numbers
		if ( ! empty( $found_links ) ) {
			$this->logger->info( sprintf( 'Found %d PDF links in HTML via regex', count( $found_links ) ) );
			
			$unique_dockets = array();
			foreach ( $found_links as $href ) {
				// Extract docket number
				$docket_number = '';
				if ( preg_match( '/docketNumber=([^&]+)/', $href, $docket_matches ) ) {
					$docket_number = urldecode( $docket_matches[1] );
				}
				
				if ( ! empty( $docket_number ) && ! isset( $unique_dockets[ $docket_number ] ) ) {
					// Build full URL
					if ( ! preg_match( '/^https?:\/\//', $href ) ) {
						$href = $this->base_url . '/' . ltrim( $href, '/' );
					}
					
					$unique_dockets[ $docket_number ] = array(
						'docket_number' => $docket_number,
						'docket_url' => $href,
						'pdf_url' => $href,
					);
				}
			}
			
			if ( ! empty( $unique_dockets ) ) {
				$dockets = array_values( $unique_dockets );
				$this->logger->info( sprintf( 'Extracted %d unique dockets from PDF links', count( $dockets ) ) );
				return $dockets;
			}
		} else {
			// Log that we didn't find any links
			$this->logger->warning( 'No PDF links found in HTML - table may be loaded dynamically via JavaScript/AJAX' );
		}
		
		// FALLBACK: Use DOMDocument to parse HTML (if regex didn't work)
		if ( ! class_exists( 'DOMDocument' ) ) {
			$this->logger->error( 'DOMDocument class not available' );
			return $dockets;
		}
		
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Try multiple selectors to find the results table
		// The actual table structure may vary
		$rows = null;
		
		// First, try regex search in raw HTML (most reliable - works even with HTML entities)
		// This should catch the PDF links even if they're loaded dynamically
		$pdf_link_count = preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $html, $href_matches );
		
		// Also try with HTML entity decoding
		if ( $pdf_link_count === 0 ) {
			$html_decoded = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$pdf_link_count = preg_match_all( '/href=["\']([^"\']*\/Report\/MdjDocketSheet[^"\']*)["\']/i', $html_decoded, $href_matches );
		}
		
		// If we found PDF links via regex, extract them immediately
		if ( $pdf_link_count > 0 && isset( $href_matches[1] ) ) {
			$this->logger->info( sprintf( 'Found %d PDF links via regex search in HTML', count( $href_matches[1] ) ) );
			
			$unique_dockets = array(); // Track unique docket numbers
			
			foreach ( $href_matches[1] as $href ) {
				// Decode HTML entities in href
				$href = html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				
				// Extract docket number from href
				$docket_number = '';
				if ( preg_match( '/docketNumber=([^&]+)/', $href, $matches ) ) {
					$docket_number = urldecode( $matches[1] );
				}
				
				// Skip if we already have this docket (links might be duplicated)
				if ( ! empty( $docket_number ) && ! isset( $unique_dockets[ $docket_number ] ) ) {
					// Build full URL
					if ( ! preg_match( '/^https?:\/\//', $href ) ) {
						$href = $this->base_url . '/' . ltrim( $href, '/' );
					}
					
					$unique_dockets[ $docket_number ] = array(
						'docket_number' => $docket_number,
						'docket_url' => $href,
						'pdf_url' => $href,
					);
				}
			}
			
			if ( ! empty( $unique_dockets ) ) {
				$dockets = array_values( $unique_dockets );
				$this->logger->info( sprintf( 'Extracted %d unique dockets from PDF links', count( $dockets ) ) );
				return $dockets;
			}
		}
		
		// Fallback: Try XPath to find PDF links (if regex didn't work)
		$pdf_links = $xpath->query( '//a[contains(@href, "/Report/MdjDocketSheet")]' );
		
		if ( $pdf_links && $pdf_links->length > 0 ) {
			$this->logger->info( sprintf( 'Found %d PDF docket links via XPath', $pdf_links->length ) );
			
			$unique_dockets = array();
			foreach ( $pdf_links as $link ) {
				$href = $link->getAttribute( 'href' );
				$docket_number = trim( $link->textContent );
				
				// Extract docket number from href if not in text
				if ( empty( $docket_number ) && preg_match( '/docketNumber=([^&]+)/', $href, $matches ) ) {
					$docket_number = urldecode( $matches[1] );
				}
				
				if ( ! empty( $docket_number ) && ! isset( $unique_dockets[ $docket_number ] ) ) {
					// Build full URL
					if ( ! preg_match( '/^https?:\/\//', $href ) ) {
						$href = $this->base_url . '/' . ltrim( $href, '/' );
					}
					
					$unique_dockets[ $docket_number ] = array(
						'docket_number' => $docket_number,
						'docket_url' => $href,
						'pdf_url' => $href,
					);
				}
			}
			
			if ( ! empty( $unique_dockets ) ) {
				$dockets = array_values( $unique_dockets );
				$this->logger->info( sprintf( 'Extracted %d unique dockets from XPath links', count( $dockets ) ) );
				return $dockets;
			}
		}
		
		// Debug: Log all links found in the HTML to see what we're working with
		$all_links = $xpath->query( '//a[@href]' );
		$link_info = array();
		$link_count = 0;
		foreach ( $all_links as $link ) {
			$href = $link->getAttribute( 'href' );
			if ( stripos( $href, 'docket' ) !== false || stripos( $href, 'report' ) !== false || stripos( $href, 'mdj' ) !== false ) {
				$link_info[] = array(
					'href' => substr( $href, 0, 200 ),
					'text' => substr( trim( $link->textContent ), 0, 50 ),
				);
				$link_count++;
				if ( $link_count >= 20 ) {
					break; // Limit to first 20 relevant links
				}
			}
		}
		
		if ( ! empty( $link_info ) ) {
			$this->logger->info( sprintf( 'Found %d relevant links in HTML', count( $link_info ) ), array( 'links' => $link_info ) );
		}
		
		// Try different table selectors - prioritize the actual results table ID
		$selectors = array(
			'//table[@id="caseSearchResultGrid"]//tr[position()>1]', // The actual results table
			'//table[@id="gvDocketSheets"]//tr[position()>1]',
			'//table[contains(@class, "grid-view")]//tr[position()>1]', // Grid view tables
			'//table[contains(@class, "docket")]//tr[position()>1]',
			'//table[contains(@id, "Docket")]//tr[position()>1]',
			'//table//tr[position()>1]', // Fallback: any table with multiple rows
		);
		
		foreach ( $selectors as $selector ) {
			$rows = $xpath->query( $selector );
			if ( $rows && $rows->length > 0 ) {
				$this->logger->info( sprintf( 'Found results table using selector: %s (%d rows)', $selector, $rows->length ) );
				break;
			}
		}
		
		// If no table found, log what we do have
		if ( ! $rows || $rows->length === 0 ) {
			// Check for "no results" messages
			if ( stripos( $html, 'no results' ) !== false || stripos( $html, 'no records' ) !== false || stripos( $html, 'no cases found' ) !== false ) {
				$this->logger->info( 'Search returned no results (found "no results" message in HTML)' );
			} else {
				// Log table structure for debugging
				$all_tables = $xpath->query( '//table' );
				$table_info = array();
				foreach ( $all_tables as $table ) {
					$table_info[] = array(
						'id' => $table->getAttribute( 'id' ),
						'class' => $table->getAttribute( 'class' ),
						'rows' => $xpath->query( './/tr', $table )->length,
					);
				}
				$this->logger->warning( 'Could not find docket results table', array(
					'tables_found' => count( $all_tables ),
					'table_info' => $table_info,
					'html_preview' => substr( $html, 0, 2000 ),
				) );
			}
			return $dockets;
		}
		
		foreach ( $rows as $row ) {
			$cells = $row->getElementsByTagName( 'td' );
			
			if ( $cells->length < 3 ) {
				continue;
			}
			
			// Extract docket number (usually in first cell or link)
			$docket_link = $xpath->query( './/a', $row )->item( 0 );
			
			if ( ! $docket_link ) {
				continue;
			}
			
			$docket_number = trim( $docket_link->textContent );
			$href = $docket_link->getAttribute( 'href' );
			
			if ( empty( $docket_number ) ) {
				continue;
			}
			
			// Build full URL if relative
			if ( ! preg_match( '/^https?:\/\//', $href ) ) {
				$href = $this->base_url . '/' . ltrim( $href, '/' );
			}
			
			// Extract other information from cells
			$docket_data = array(
				'docket_number' => $docket_number,
				'docket_url' => $href,
				'pdf_url' => $href, // Assume the link is to the PDF
			);
			
			// Try to extract additional info from cells
			$cell_index = 0;
			foreach ( $cells as $cell ) {
				$text = trim( $cell->textContent );
				
				switch ( $cell_index ) {
					case 0:
						// Docket number (already extracted)
						break;
					case 1:
						$docket_data['filing_date'] = $text;
						break;
					case 2:
						$docket_data['case_type'] = $text;
						break;
					case 3:
						$docket_data['participants'] = $text;
						break;
				}
				
				$cell_index++;
			}
			
			$dockets[] = $docket_data;
		}
		
		return $dockets;
	}
	
	/**
	 * Get full docket details from PDF
	 *
	 * @param string $docket_number Docket number
	 * @param string $pdf_url       URL to the PDF docket sheet
	 * @return array|false Docket data or false on failure
	 */
	private function get_docket_details( $docket_number, $pdf_url = '' ) {
		// If PDF URL not provided, try to construct it
		if ( empty( $pdf_url ) ) {
			// Try to construct PDF URL - we'll need the dnh parameter from the search results
			// For now, we'll download the PDF directly if we have the URL
			$this->logger->warning( sprintf( 'No PDF URL provided for docket %s', $docket_number ) );
			return false;
		}
		
		// Use PDF processor for downloading and extracting text
		$pdf_processor = new PA_Dockets_Scraper_PDF_Processor( $this->logger );
		
		// Download PDF
		$pdf_content = $pdf_processor->download_pdf( $pdf_url );
		
		if ( false === $pdf_content ) {
			$this->logger->error( sprintf( 'Failed to download PDF for docket %s', $docket_number ) );
			return false;
		}
		
		// Extract text from PDF using the PDF processor
		$pdf_text = $pdf_processor->extract_pdf_text( $pdf_content );
		
		if ( empty( $pdf_text ) ) {
			$this->logger->warning( sprintf( 'Could not extract text from PDF for docket %s', $docket_number ) );
		}
		
		// Build docket data
		$docket_data = array(
			'docket_number' => $docket_number,
			'pdf_url' => $pdf_url,
			'pdf_text' => $pdf_text,
			'pdf_content' => base64_encode( $pdf_content ), // Store as base64 for sending to Groq
		);
		
		return $docket_data;
	}
	
	
	/**
	 * Parse docket details HTML
	 *
	 * @param string $html          HTML content
	 * @param string $docket_number Docket number
	 * @return array Docket data
	 */
	private function parse_docket_details( $html, $docket_number ) {
		$docket_data = array(
			'docket_number' => $docket_number,
		);
		
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $docket_data;
		}
		
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Extract docket information from various fields
		// This is a simplified parser - adjust based on actual HTML structure
		
		// Try to find common fields
		$fields_to_extract = array(
			'filing_date' => array( 'Filing Date', 'Date Filed' ),
			'case_type' => array( 'Case Type', 'Type' ),
			'plaintiff' => array( 'Plaintiff', 'Complainant' ),
			'defendant' => array( 'Defendant', 'Respondent' ),
			'charges' => array( 'Charges', 'Offense' ),
			'court' => array( 'Court', 'Magisterial District' ),
			'judge' => array( 'Judge', 'Magistrate' ),
		);
		
		foreach ( $fields_to_extract as $key => $labels ) {
			foreach ( $labels as $label ) {
				// Try to find label and extract value
				$label_nodes = $xpath->query( "//*[contains(text(), '{$label}')]" );
				
				foreach ( $label_nodes as $label_node ) {
					$parent = $label_node->parentNode;
					if ( $parent ) {
						$value_node = $xpath->query( './/text()[normalize-space()]', $parent )->item( 1 );
						if ( $value_node ) {
							$docket_data[ $key ] = trim( $value_node->textContent );
							break 2;
						}
					}
				}
			}
		}
		
		// Extract docket entries/events
		$entries = array();
		$entry_rows = $xpath->query( '//table[@id="gvDocketEntries"]//tr[position()>1]' );
		
		foreach ( $entry_rows as $row ) {
			$cells = $row->getElementsByTagName( 'td' );
			
			if ( $cells->length >= 3 ) {
				$entry = array(
					'date' => trim( $cells->item( 0 )->textContent ),
					'description' => trim( $cells->item( 1 )->textContent ),
					'filed_by' => trim( $cells->item( 2 )->textContent ),
				);
				
				$entries[] = $entry;
			}
		}
		
		if ( ! empty( $entries ) ) {
			$docket_data['entries'] = $entries;
		}
		
		return $docket_data;
	}
}
