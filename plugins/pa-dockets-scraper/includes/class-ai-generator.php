<?php
/**
 * AI Generator class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_AI_Generator {
	
	/**
	 * Logger instance
	 *
	 * @var PA_Dockets_Scraper_Logger
	 */
	private $logger;
	
	/**
	 * API endpoint
	 *
	 * @var string
	 */
	private $api_url;
	
	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Logger $logger Logger instance
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
		$this->api_url = get_option( 'pa_dockets_groq_api_url', 'https://api.groq.com/openai/v1' );
		$this->api_key = get_option( 'pa_dockets_groq_api_key', '' );
	}
	
	/**
	 * Generate article from docket data
	 *
	 * @param array $docket_data Docket data
	 * @return array|false Article data (title, content) or false on failure
	 */
	public function generate_article( $docket_data ) {
		if ( empty( $this->api_key ) ) {
			$this->logger->error( 'Groq API key not configured' );
			return false;
		}
		
		// Build prompt from docket data
		$prompt = $this->build_prompt( $docket_data );
		
		// Generate article using Groq API
		$article = $this->call_groq_api( $prompt );
		
		if ( ! $article ) {
			return false;
		}
		
		// Parse response to extract title and content
		$parsed_article = $this->parse_article_response( $article, $docket_data );
		
		// If parsing failed (returned false), return false
		if ( $parsed_article === false ) {
			$this->logger->error( 'Failed to parse article response from AI' );
			return false;
		}
		
		// Final validation - if title or content is invalid, return false
		if ( empty( $parsed_article['title'] ) || empty( $parsed_article['content'] ) ) {
			$this->logger->error( 'Generated article has invalid title or content', array(
				'has_title' => ! empty( $parsed_article['title'] ),
				'has_content' => ! empty( $parsed_article['content'] ),
			) );
			return false;
		}
		
		return $parsed_article;
	}
	
	/**
	 * Build prompt from docket data
	 *
	 * @param array $docket_data Docket data
	 * @return string|array Prompt text or array with messages for vision API
	 */
	private function build_prompt( $docket_data ) {
		$tone = get_option( 'pa_dockets_article_tone', 'professional' );
		
		// If we have PDF text, use that as the primary source
		if ( ! empty( $docket_data['pdf_text'] ) ) {
			// Log the extracted text for debugging
			$this->logger->info( 'PDF text extracted for AI', array(
				'total_length' => strlen( $docket_data['pdf_text'] ),
				'first_2000_chars' => substr( $docket_data['pdf_text'], 0, 2000 ),
				'last_500_chars' => substr( $docket_data['pdf_text'], -500 ),
			) );
			
			// Limit PDF text to first 8000 characters to avoid token limits
			$pdf_text = substr( $docket_data['pdf_text'], 0, 8000 );
			
			$prompt = "You are a professional news writer specializing in court reporting. Write a detailed, factual news article based on the court docket below.\n\n";
			$prompt .= "ARTICLE REQUIREMENTS:\n";
			$prompt .= "1. HEADLINE: Create a compelling headline that includes key details (defendant name, location, main charges). Example: \"Middletown Teen Faces Theft, Drug Charge in Potter County; Case Moves to Common Pleas Court\"\n";
			$prompt .= "2. LOCATION CONTEXT: Include all location details from the docket (defendant's city/county, incident location, court location). Use format like \"COUDERSPORT, Pa. —\" for the lead.\n";
			$prompt .= "3. DETAILED INFORMATION: Extract and include:\n";
			$prompt .= "   - Full case number and court information\n";
			$prompt .= "   - Defendant's full name, age (if available), and location\n";
			$prompt .= "   - Complete charge descriptions with legal citations (e.g., \"18 Pa.C.S. § 3921(a)\")\n";
			$prompt .= "   - Incident date and location\n";
			$prompt .= "   - Arresting agency and officer names\n";
			$prompt .= "   - Judge/magistrate names\n";
			$prompt .= "   - Bail information and conditions\n";
			$prompt .= "   - Court proceedings (hearings, waivers, dispositions)\n";
			$prompt .= "   - Financial records (fines, costs, restitution)\n";
			$prompt .= "   - Current case status\n";
			$prompt .= "4. NARRATIVE STRUCTURE: Write in a clear, chronological narrative with proper paragraphs. Use transitions and context to connect information.\n";
			$prompt .= "5. LEGAL ACCURACY: Include full legal citations for charges. Do not abbreviate charge names - use complete descriptions.\n";
			$prompt .= "6. CONCLUSION: End with a statement about presumption of innocence and the prosecuting authority.\n\n";
			$prompt .= "CRITICAL RULES:\n";
			$prompt .= "- ONLY use information that is explicitly present in the docket text\n";
			$prompt .= "- DO NOT invent, speculate, or make up any details\n";
			$prompt .= "- If information is missing, acknowledge it (e.g., \"age not listed in records\")\n";
			$prompt .= "- If the text is completely unreadable, return: {\"error\": \"Docket text is unreadable\"}\n";
			$prompt .= "- Write in a professional journalistic style suitable for local news\n\n";
			$prompt .= "Return JSON format:\n";
			$prompt .= "{\n";
			$prompt .= '  "title": "Compelling headline with key details",' . "\n";
			$prompt .= '  "content": "<p>Detailed article paragraphs with all relevant information</p>",' . "\n";
			$prompt .= '  "meta_description": "SEO description (155 characters)",' . "\n";
			$prompt .= '  "keywords": "keyword1, keyword2, keyword3"' . "\n";
			$prompt .= "}\n\n";
			$prompt .= "Court Docket Text:\n";
			$prompt .= $pdf_text;
			
			return $prompt;
		}
		
		// Check if we have PDF content but no extracted text - this means we need to extract it differently
		if ( ! empty( $docket_data['pdf_content'] ) && empty( $docket_data['pdf_text'] ) ) {
			// We have PDF content but couldn't extract text - inform the AI about this limitation
			$this->logger->warning( 'PDF text extraction failed, but PDF content is available. Attempting to work with available data.' );
			
			// Try to provide better context - check if we have the uploaded file path
			if ( ! empty( $docket_data['uploaded_file'] ) && file_exists( $docket_data['uploaded_file'] ) ) {
				// We have the file but couldn't extract text - this is a limitation
				$prompt = "IMPORTANT: A court docket PDF file was uploaded, but text extraction from the PDF failed. ";
				$prompt .= "You are being asked to write an article, but you only have limited information. ";
				$prompt .= "DO NOT make up or fabricate details about the case. ";
				$prompt .= "If you do not have sufficient real information from the docket to write a meaningful article, ";
				$prompt .= "return a JSON response with an 'error' field explaining that more information is needed.\n\n";
			}
		}
		
		// Fallback to structured data if no PDF text
		$prompt = "You are an experienced court reporter. Write a detailed, factual news article about the following court docket case.\n\n";
		$prompt .= "ARTICLE REQUIREMENTS:\n";
		$prompt .= "1. HEADLINE: Create a compelling headline with key details (defendant name, location, main charges)\n";
		$prompt .= "2. LOCATION CONTEXT: Include all location details (defendant's city/county, incident location, court location). Use format like \"COUDERSPORT, Pa. —\" for the lead.\n";
		$prompt .= "3. DETAILED INFORMATION: Include all available information:\n";
		$prompt .= "   - Full case number and court information\n";
		$prompt .= "   - Defendant's full name, age (if available), and location\n";
		$prompt .= "   - Complete charge descriptions with legal citations if available\n";
		$prompt .= "   - Incident date and location\n";
		$prompt .= "   - Arresting agency and officer names (if available)\n";
		$prompt .= "   - Judge/magistrate names\n";
		$prompt .= "   - Bail information and conditions\n";
		$prompt .= "   - Court proceedings (hearings, waivers, dispositions)\n";
		$prompt .= "   - Financial records (fines, costs, restitution) if available\n";
		$prompt .= "   - Current case status\n";
		$prompt .= "4. NARRATIVE STRUCTURE: Write in a clear, chronological narrative with proper paragraphs. Use transitions and context.\n";
		$prompt .= "5. LEGAL ACCURACY: Include full legal citations for charges. Do not abbreviate charge names.\n";
		$prompt .= "6. CONCLUSION: End with a statement about presumption of innocence and the prosecuting authority.\n\n";
		$prompt .= "CRITICAL RULES:\n";
		$prompt .= "- ONLY use information that is actually provided below\n";
		$prompt .= "- DO NOT make up, speculate, or invent details\n";
		$prompt .= "- If information is missing, acknowledge it appropriately\n";
		$prompt .= "- If the information provided is insufficient (e.g., only a docket number), return: {\"error\": \"Insufficient information to generate article. PDF text extraction may have failed.\"}\n";
		$prompt .= "- Write in a professional journalistic style suitable for local news\n";
		$prompt .= "- Use a {$tone} tone\n\n";
		$prompt .= "Return JSON format:\n";
		$prompt .= "{\n";
		$prompt .= '  "title": "Compelling headline with key details",' . "\n";
		$prompt .= '  "content": "<p>Detailed article paragraphs with all relevant information</p>",' . "\n";
		$prompt .= '  "meta_description": "SEO description (155 characters)",' . "\n";
		$prompt .= '  "keywords": "keyword1, keyword2, keyword3"' . "\n";
		$prompt .= "}\n\n";
		
		$prompt .= "Docket Information:\n";
		$prompt .= "Docket Number: " . ( isset( $docket_data['docket_number'] ) ? $docket_data['docket_number'] : 'N/A' ) . "\n";
		
		// Check if we have meaningful data beyond just the docket number
		$has_meaningful_data = false;
		
		if ( isset( $docket_data['filing_date'] ) && ! empty( $docket_data['filing_date'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Filing Date: " . $docket_data['filing_date'] . "\n";
		}
		
		if ( isset( $docket_data['case_type'] ) && ! empty( $docket_data['case_type'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Case Type: " . $docket_data['case_type'] . "\n";
		}
		
		if ( isset( $docket_data['plaintiff'] ) && ! empty( $docket_data['plaintiff'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Plaintiff: " . $docket_data['plaintiff'] . "\n";
		}
		
		if ( isset( $docket_data['defendant'] ) && ! empty( $docket_data['defendant'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Defendant: " . $docket_data['defendant'] . "\n";
		}
		
		if ( isset( $docket_data['participants'] ) && ! empty( $docket_data['participants'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Participants: " . $docket_data['participants'] . "\n";
		}
		
		if ( isset( $docket_data['charges'] ) && ! empty( $docket_data['charges'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Charges: " . $docket_data['charges'] . "\n";
		}
		
		if ( isset( $docket_data['court'] ) && ! empty( $docket_data['court'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Court: " . $docket_data['court'] . "\n";
		}
		
		if ( isset( $docket_data['judge'] ) && ! empty( $docket_data['judge'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "Judge/Magistrate: " . $docket_data['judge'] . "\n";
		}
		
		if ( isset( $docket_data['entries'] ) && is_array( $docket_data['entries'] ) && ! empty( $docket_data['entries'] ) ) {
			$has_meaningful_data = true;
			$prompt .= "\nDocket Entries:\n";
			foreach ( $docket_data['entries'] as $entry ) {
				if ( isset( $entry['date'] ) ) {
					$prompt .= "- " . $entry['date'];
				}
				if ( isset( $entry['description'] ) ) {
					$prompt .= ": " . $entry['description'];
				}
				$prompt .= "\n";
			}
		}
		
		// If we only have a docket number and no other meaningful data, tell the AI not to generate content
		if ( ! $has_meaningful_data ) {
			$prompt .= "\n\nWARNING: Only a docket number is available. No case details, parties, charges, or other information was extracted from the PDF. ";
			$prompt .= "DO NOT write an article based solely on a docket number. ";
			$prompt .= "Instead, return JSON with: {\"error\": \"Unable to extract sufficient information from the docket PDF. Please ensure the PDF is readable and contains case details.\"}";
		} else {
			$prompt .= "\n\nWrite a detailed, professional news article following all the requirements above. Include all available information in a well-structured narrative format.";
		}
		
		return $prompt;
	}
	
	/**
	 * Call Groq API
	 *
	 * @param string $prompt Prompt text
	 * @return string|false API response or false on failure
	 */
	private function call_groq_api( $prompt ) {
		$endpoint = rtrim( $this->api_url, '/' ) . '/chat/completions';
		
		// Get model from settings, default to llama-3.3-70b-versatile (updated from decommissioned model)
		$model = get_option( 'pa_dockets_groq_model', 'llama-3.3-70b-versatile' );
		
		$request_body = array(
			'model' => $model,
			'messages' => array(
				array(
					'role' => 'system',
					'content' => 'You are an experienced court reporter and news writer. Write detailed, factual news articles about court cases. Include all relevant details: locations, dates, charges with legal citations, court proceedings, bail information, and case status. Write in a professional journalistic style with clear narrative flow. Always include location context (city, county, state) and use proper legal terminology.',
				),
				array(
					'role' => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.2,
			'max_tokens' => 6000,
		);
		
		$response = wp_remote_post( $endpoint, array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $request_body ),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Groq API request failed', array( 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( 200 !== $status_code ) {
			$this->logger->error( sprintf( 'Groq API returned status code: %d', $status_code ), array( 'response' => $body ) );
			return false;
		}
		
		$data = json_decode( $body, true );
		
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			$this->logger->error( 'Invalid Groq API response format', array( 'response' => $body ) );
			return false;
		}
		
		$ai_response = $data['choices'][0]['message']['content'];
		
		// Log the raw AI response for debugging
		$this->logger->info( 'Raw AI response received', array(
			'response_length' => strlen( $ai_response ),
			'response_preview' => substr( $ai_response, 0, 1000 ),
		) );
		
		return $ai_response;
	}
	
	/**
	 * Parse article response
	 *
	 * @param string $response    API response
	 * @param array  $docket_data Docket data
	 * @return array|false Article data (title, content, meta_description, keywords) or false on error
	 */
	/**
	 * Sanitize JSON string by removing control characters from string values
	 * This preserves the JSON structure while cleaning string content
	 *
	 * @param string $json_string JSON string
	 * @return string Sanitized JSON string
	 */
	private function sanitize_json_string( $json_string ) {
		// Remove control characters globally
		// Valid JSON should not contain unescaped control characters anywhere
		// This is safe because control chars (0x00-0x1F) are not valid in JSON structure
		// and should be escaped if needed in string values
		$cleaned = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $json_string );
		
		// Also try to fix any malformed unicode sequences
		// Sometimes control chars can corrupt unicode escapes
		$cleaned = preg_replace_callback( '/\\\\u([0-9a-fA-F]{4})/', function( $matches ) {
			$code = hexdec( $matches[1] );
			// If it's a control character, replace with a space or skip
			if ( $code >= 0x00 && $code <= 0x1F && ! in_array( $code, array( 0x09, 0x0A, 0x0D ) ) ) {
				return ' '; // Replace with space
			}
			return $matches[0]; // Keep valid unicode escape
		}, $cleaned );
		
		return $cleaned;
	}
	
	/**
	 * Check if JSON string appears complete (has matching braces)
	 *
	 * @param string $json_string JSON string
	 * @return bool True if JSON appears complete
	 */
	private function is_json_complete( $json_string ) {
		$open_braces = substr_count( $json_string, '{' );
		$close_braces = substr_count( $json_string, '}' );
		return $open_braces > 0 && $open_braces === $close_braces;
	}

	private function parse_article_response( $response, $docket_data ) {
		$article = array(
			'title' => '',
			'content' => '',
			'meta_description' => '',
			'keywords' => '',
		);
		
		// Remove markdown code blocks if present (```json ... ``` or ``` ... ```)
		// Extract content between code blocks
		if ( preg_match( '/```(?:json)?\s*\r?\n?(.*?)\r?\n?```/s', $response, $matches ) ) {
			$response = $matches[1];
		} elseif ( preg_match( '/`(?:json)?\s*\r?\n?(.*?)\r?\n?`/s', $response, $matches ) ) {
			$response = $matches[1];
		} else {
			// Try removing from start/end if no match
			$response = preg_replace( '/^```(?:json)?\s*\r?\n?/m', '', $response );
			$response = preg_replace( '/\r?\n?```\s*$/m', '', $response );
			$response = preg_replace( '/^`(?:json)?\s*\r?\n?/m', '', $response );
			$response = preg_replace( '/\r?\n?`\s*$/m', '', $response );
		}
		$response = trim( $response );
		
		// Try to extract JSON object from response (in case there's extra text)
		$json_data = null;
		$json_error = null;
		
		// First, try parsing the entire response as JSON
		$json_data = json_decode( $response, true );
		$json_error = json_last_error();
		
		// If parsing failed, try cleaning and parsing again
		if ( ! is_array( $json_data ) && $json_error !== JSON_ERROR_NONE ) {
			// Sanitize the response to remove control characters
			$response_cleaned = $this->sanitize_json_string( $response );
			
			// Also try to fix any encoding issues
			if ( function_exists( 'mb_convert_encoding' ) ) {
				// Try to ensure it's valid UTF-8
				$response_cleaned = mb_convert_encoding( $response_cleaned, 'UTF-8', 'UTF-8' );
			}
			
			$json_data_cleaned = json_decode( $response_cleaned, true );
			$json_error_cleaned = json_last_error();
			
			if ( is_array( $json_data_cleaned ) ) {
				$json_data = $json_data_cleaned;
				$json_error = $json_error_cleaned;
				$response = $response_cleaned; // Use cleaned version for further processing
			} elseif ( $json_error_cleaned === JSON_ERROR_CTRL_CHAR ) {
				// Still has control character error - try more aggressive cleaning
				// Remove ALL control characters globally as last resort
				$response_aggressive = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $response_cleaned );
				
				// Try with UTF-8 ignore flag
				if ( defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
					$json_data_aggressive = json_decode( $response_aggressive, true, 512, JSON_INVALID_UTF8_IGNORE );
					$json_error_aggressive = json_last_error();
				} else {
					$json_data_aggressive = json_decode( $response_aggressive, true );
					$json_error_aggressive = json_last_error();
				}
				
				if ( is_array( $json_data_aggressive ) ) {
					$json_data = $json_data_aggressive;
					$json_error = $json_error_aggressive;
					$response = $response_aggressive;
				}
			}
		}
		
		// If that fails, try to extract JSON object from the response
		if ( ! is_array( $json_data ) && $json_error !== JSON_ERROR_NONE ) {
			// Find the first { and try to find matching }
			$start_pos = strpos( $response, '{' );
			if ( $start_pos !== false ) {
				$brace_count = 0;
				$end_pos = $start_pos;
				$in_string = false;
				$escape_next = false;
				$unicode_escape_count = 0;
				
				// Find the matching closing brace
				for ( $i = $start_pos; $i < strlen( $response ); $i++ ) {
					$char = $response[$i];
					
					if ( $unicode_escape_count > 0 ) {
						$unicode_escape_count--;
						continue;
					}
					
					if ( $escape_next ) {
						if ( $char === 'u' ) {
							$unicode_escape_count = 4; // \uXXXX
						}
						$escape_next = false;
						continue;
					}
					
					if ( $char === '\\' ) {
						$escape_next = true;
						continue;
					}
					
					if ( $char === '"' ) {
						$in_string = ! $in_string;
						continue;
					}
					
					if ( ! $in_string ) {
						if ( $char === '{' ) {
							$brace_count++;
						} elseif ( $char === '}' ) {
							$brace_count--;
							if ( $brace_count === 0 ) {
								$end_pos = $i + 1;
								break;
							}
						}
					}
				}
				
				if ( $brace_count === 0 && $end_pos > $start_pos ) {
					$json_string = substr( $response, $start_pos, $end_pos - $start_pos );
					
					// Verify JSON appears complete
					if ( ! $this->is_json_complete( $json_string ) ) {
						// Try to find the actual end
						$last_brace = strrpos( $response, '}', $start_pos );
						if ( $last_brace !== false && $last_brace > $start_pos ) {
							$json_string = substr( $response, $start_pos, $last_brace - $start_pos + 1 );
						}
					}
					
					$json_string = $this->sanitize_json_string( $json_string );
					$json_data = json_decode( $json_string, true );
					$json_error = json_last_error();
					
					// If still failing, try with JSON_INVALID_UTF8_IGNORE flag (PHP 7.2+)
					if ( ! is_array( $json_data ) && defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
						$json_data = json_decode( $json_string, true, 512, JSON_INVALID_UTF8_IGNORE );
						$json_error = json_last_error();
					}
				}
			}
		}
		
		// Final fallback: try simple extraction with last closing brace
		if ( ! is_array( $json_data ) && $json_error !== JSON_ERROR_NONE && strpos( $response, '{' ) !== false ) {
			// Extract JSON using simple method (first { to last })
			$start_pos = strpos( $response, '{' );
			$end_pos = strrpos( $response, '}' );
			if ( $start_pos !== false && $end_pos !== false && $end_pos > $start_pos ) {
				$json_string = substr( $response, $start_pos, $end_pos - $start_pos + 1 );
				
				// Log before sanitization for debugging
				$this->logger->info( 'Final fallback JSON extraction', array(
					'json_length' => strlen( $json_string ),
					'json_preview' => substr( $json_string, 0, 500 ),
					'json_end' => substr( $json_string, -200 ),
					'is_complete' => $this->is_json_complete( $json_string ),
				) );
				
				$json_string = $this->sanitize_json_string( $json_string );
				$json_data = json_decode( $json_string, true );
				$json_error = json_last_error();
				
				// Log after sanitization
				if ( ! is_array( $json_data ) ) {
					$this->logger->warning( 'JSON parse failed after sanitization', array(
						'json_error' => json_last_error_msg(),
						'json_error_code' => $json_error,
						'sanitized_length' => strlen( $json_string ),
						'sanitized_preview' => substr( $json_string, 0, 500 ),
					) );
				}
				
				// Try with UTF-8 ignore flag if available (PHP 7.2+)
				if ( ! is_array( $json_data ) && defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
					$json_data = json_decode( $json_string, true, 512, JSON_INVALID_UTF8_IGNORE );
					$json_error = json_last_error();
					
					if ( is_array( $json_data ) ) {
						$this->logger->info( 'JSON parsed successfully with JSON_INVALID_UTF8_IGNORE flag' );
					}
				}
				
				// Try with UTF-8 substitute flag if available (PHP 7.2+)
				if ( ! is_array( $json_data ) && defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
					$json_data = json_decode( $json_string, true, 512, JSON_INVALID_UTF8_SUBSTITUTE );
					$json_error = json_last_error();
					
					if ( is_array( $json_data ) ) {
						$this->logger->info( 'JSON parsed successfully with JSON_INVALID_UTF8_SUBSTITUTE flag' );
					}
				}
				
				// Last resort: try with both UTF-8 ignore and partial flag
				if ( ! is_array( $json_data ) && defined( 'JSON_INVALID_UTF8_IGNORE' ) && defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ) {
					$json_data = json_decode( $json_string, true, 512, JSON_INVALID_UTF8_IGNORE | JSON_PARTIAL_OUTPUT_ON_ERROR );
					$json_error = json_last_error();
					
					if ( is_array( $json_data ) ) {
						$this->logger->info( 'JSON parsed successfully with JSON_INVALID_UTF8_IGNORE | JSON_PARTIAL_OUTPUT_ON_ERROR flags' );
					}
				}
				
				// Ultimate fallback: try one more aggressive clean and parse
				if ( ! is_array( $json_data ) ) {
					// Remove ALL non-printable characters except newlines, tabs, carriage returns
					$ultra_clean = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/', '', $json_string );
					$json_data = json_decode( $ultra_clean, true );
					$json_error = json_last_error();
					
					if ( is_array( $json_data ) ) {
						$this->logger->info( 'JSON parsed successfully after ultra-aggressive cleaning' );
					} else {
						// Log a sample of problematic bytes for debugging
						$problem_bytes = array();
						for ( $i = 0; $i < min( strlen( $json_string ), 1000 ); $i++ ) {
							$ord = ord( $json_string[$i] );
							if ( $ord < 0x20 && ! in_array( $ord, array( 0x09, 0x0A, 0x0D ) ) ) {
								$problem_bytes[] = sprintf( '0x%02X at position %d', $ord, $i );
								if ( count( $problem_bytes ) >= 10 ) {
									break;
								}
							}
						}
						if ( ! empty( $problem_bytes ) ) {
							$this->logger->warning( 'Control characters found in JSON', array( 'problem_bytes' => $problem_bytes ) );
						}
					}
				}
			}
		}
		
		// Log the raw response for debugging
		$open_braces = substr_count( $response, '{' );
		$close_braces = substr_count( $response, '}' );
		$this->logger->info( 'Parsing AI response', array(
			'response_length' => strlen( $response ),
			'response_preview' => substr( $response, 0, 500 ),
			'is_json' => is_array( $json_data ),
			'json_error_code' => $json_error,
			'json_error' => $json_error !== JSON_ERROR_NONE ? json_last_error_msg() : null,
			'has_opening_brace' => strpos( $response, '{' ) !== false,
			'has_closing_brace' => strrpos( $response, '}' ) !== false,
			'brace_count' => array( 'open' => $open_braces, 'close' => $close_braces ),
			'json_complete' => $this->is_json_complete( $response ),
		) );
		
		// Basic corruption check - reject if response is mostly question marks or control characters
		$question_mark_ratio = substr_count( $response, '?' ) / max( strlen( $response ), 1 );
		$control_char_ratio = preg_match_all( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $response ) / max( strlen( $response ), 1 );
		
		if ( $question_mark_ratio > 0.3 || $control_char_ratio > 0.2 ) {
			$this->logger->error( 'AI returned corrupted response', array(
				'question_mark_ratio' => $question_mark_ratio,
				'control_char_ratio' => $control_char_ratio,
			) );
			return false;
		}
		
		if ( is_array( $json_data ) ) {
			// Check for error response - this must be checked FIRST
			if ( isset( $json_data['error'] ) ) {
				$error_message = is_string( $json_data['error'] ) ? $json_data['error'] : 'Unknown error';
				$this->logger->error( 'AI returned error: ' . $error_message );
				return false; // Return false immediately, don't create post
			}
			
			if ( isset( $json_data['title'] ) ) {
				$article['title'] = sanitize_text_field( $json_data['title'] );
			}
			
			if ( isset( $json_data['content'] ) ) {
				$article['content'] = wp_kses_post( $json_data['content'] );
			}
			
			if ( isset( $json_data['meta_description'] ) ) {
				$article['meta_description'] = sanitize_text_field( $json_data['meta_description'] );
			}
			
			if ( isset( $json_data['keywords'] ) ) {
				$article['keywords'] = sanitize_text_field( $json_data['keywords'] );
			}
		} else {
			// Not JSON - reject (AI should return JSON)
			$this->logger->error( 'AI returned non-JSON response', array(
				'json_error' => $json_error !== JSON_ERROR_NONE ? json_last_error_msg() : 'Unknown error',
				'response_preview' => substr( $response, 0, 2000 ),
				'response_length' => strlen( $response ),
			) );
			return false;
		}
		
		// Generate defaults if missing
		if ( empty( $article['meta_description'] ) && ! empty( $article['content'] ) ) {
			$article['meta_description'] = $this->generate_meta_description( $article['content'] );
		}
		
		if ( empty( $article['keywords'] ) ) {
			$article['keywords'] = $this->generate_keywords( $docket_data, $article );
		}
		
		if ( empty( $article['title'] ) ) {
			$article['title'] = $this->generate_title( $docket_data );
		}
		
		// Basic validation - must have title and content
		if ( empty( $article['title'] ) || empty( $article['content'] ) ) {
			$this->logger->error( 'Article missing title or content' );
			return false;
		}
		
		return $article;
	}
	
	/**
	 * Generate meta description from content
	 *
	 * @param string $content Article content
	 * @return string Meta description
	 */
	private function generate_meta_description( $content ) {
		// Remove HTML tags
		$text = wp_strip_all_tags( $content );
		
		// Get first 155 characters
		$description = substr( $text, 0, 155 );
		
		// Cut at last complete word
		$description = substr( $description, 0, strrpos( $description, ' ' ) );
		
		return $description . '...';
	}
	
	/**
	 * Generate keywords from docket data and article
	 *
	 * @param array $docket_data Docket data
	 * @param array $article     Article data
	 * @return string Comma-separated keywords
	 */
	private function generate_keywords( $docket_data, $article ) {
		$keywords = array();
		
		// Add county name
		if ( isset( $docket_data['county'] ) ) {
			$keywords[] = ucfirst( $docket_data['county'] ) . ' County';
		}
		
		// Add case type
		if ( isset( $docket_data['case_type'] ) ) {
			$keywords[] = $docket_data['case_type'];
		}
		
		// Add "court docket", "Pennsylvania", etc.
		$keywords[] = 'Court Docket';
		$keywords[] = 'Pennsylvania';
		
		// Add location-based keywords
		if ( isset( $docket_data['county'] ) ) {
			$keywords[] = ucfirst( $docket_data['county'] ) . ' County News';
		}
		
		return implode( ', ', array_unique( $keywords ) );
	}
	
	/**
	 * Generate title from docket data
	 *
	 * @param array $docket_data Docket data
	 * @return string Title
	 */
	private function generate_title( $docket_data ) {
		$title_parts = array();
		
		if ( isset( $docket_data['defendant'] ) ) {
			$title_parts[] = $docket_data['defendant'];
		}
		
		if ( isset( $docket_data['charges'] ) ) {
			$title_parts[] = $docket_data['charges'];
		} elseif ( isset( $docket_data['case_type'] ) ) {
			$title_parts[] = $docket_data['case_type'] . ' Case';
		}
		
		if ( isset( $docket_data['county'] ) ) {
			$title_parts[] = 'in ' . ucfirst( $docket_data['county'] ) . ' County';
		}
		
		if ( empty( $title_parts ) ) {
			return 'Court Docket Case: ' . ( isset( $docket_data['docket_number'] ) ? $docket_data['docket_number'] : 'New Case' );
		}
		
		return implode( ' - ', $title_parts );
	}
	
	/**
	 * Check if text is valid (not binary/garbage data)
	 *
	 * @param string $text Text to validate
	 * @return bool True if valid text
	 */
	private function is_valid_text( $text ) {
		if ( empty( $text ) ) {
			return false;
		}
		
		// Remove HTML tags for validation
		$plain_text = wp_strip_all_tags( $text );
		
		if ( strlen( $plain_text ) < 3 ) {
			return false;
		}
		
		// Count printable characters
		$printable_count = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $plain_text ) );
		$total_chars = strlen( $plain_text );
		
		// At least 60% of characters should be printable (less strict for titles)
		$printable_ratio = $printable_count / max( $total_chars, 1 );
		if ( $printable_ratio < 0.6 ) {
			return false;
		}
		
		// Check for excessive repetition (likely binary data)
		if ( preg_match( '/(.)\1{20,}/', $plain_text ) ) {
			return false;
		}
		
		// Check for common text patterns - must have some letters
		$has_letters = preg_match( '/[a-zA-Z]{2,}/', $plain_text );
		
		return $has_letters;
	}
}
