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
			
			$prompt = "You are a professional news writer. Analyze the court docket text below and write a factual news article.\n\n";
			$prompt .= "INSTRUCTIONS:\n";
			$prompt .= "- Extract and use information from the docket text (case numbers, parties, charges, dates, court actions, etc.)\n";
			$prompt .= "- Write a clear, factual article based on what you find in the docket\n";
			$prompt .= "- DO NOT make up details that aren't in the text\n";
			$prompt .= "- If the text is completely unreadable (mostly symbols, no words), return: {\"error\": \"Docket text is unreadable\"}\n";
			$prompt .= "- Otherwise, write an article using whatever information you can extract from the text\n\n";
			$prompt .= "Return JSON format:\n";
			$prompt .= "{\n";
			$prompt .= '  "title": "Article headline",' . "\n";
			$prompt .= '  "content": "<p>Article content</p>",' . "\n";
			$prompt .= '  "meta_description": "SEO description",' . "\n";
			$prompt .= '  "keywords": "keyword1, keyword2"' . "\n";
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
		$prompt = "Write a professional, SEO-optimized news article about the following court docket case. ";
		$prompt .= "The article should be informative, factual, and suitable for a local news website. ";
		$prompt .= "CRITICAL: Only use information that is actually provided below. DO NOT make up, speculate, or invent details. ";
		$prompt .= "If the information provided is insufficient (e.g., only a docket number with no case details), ";
		$prompt .= "return a JSON response with 'error': 'Insufficient information to generate article. PDF text extraction may have failed.' ";
		$prompt .= "Include relevant details about the case, parties involved, and any charges or proceedings ONLY if they are provided. ";
		$prompt .= "Use a {$tone} tone. ";
		$prompt .= "Format the response as JSON with 'title', 'content', 'meta_description', and 'keywords' fields. ";
		$prompt .= "The content should be well-structured with proper paragraphs and include relevant keywords for SEO.\n\n";
		
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
			$prompt .= "\n\nGenerate a compelling news article title and well-written article content based on this information.";
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
					'content' => 'You are a professional news writer specializing in court reporting. Write clear, factual, and SEO-optimized articles.',
				),
				array(
					'role' => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.3,
			'max_tokens' => 4000,
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
	private function parse_article_response( $response, $docket_data ) {
		$article = array(
			'title' => '',
			'content' => '',
			'meta_description' => '',
			'keywords' => '',
		);
		
		// Remove markdown code blocks if present (```json ... ```)
		$response = preg_replace( '/^```(?:json)?\s*\n?/m', '', $response );
		$response = preg_replace( '/\n?```\s*$/m', '', $response );
		$response = trim( $response );
		
		// Try to parse as JSON first
		$json_data = json_decode( $response, true );
		
		// Log the raw response for debugging
		$this->logger->info( 'Parsing AI response', array(
			'response_length' => strlen( $response ),
			'response_preview' => substr( $response, 0, 500 ),
			'is_json' => is_array( $json_data ),
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
			$this->logger->error( 'AI returned non-JSON response' );
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
