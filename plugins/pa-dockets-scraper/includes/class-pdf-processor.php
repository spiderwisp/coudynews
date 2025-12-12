<?php
/**
 * PDF Processor class for Coudy AI
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_PDF_Processor {
	
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
	 * Download PDF from URL
	 *
	 * @param string $pdf_url URL to the PDF
	 * @return string|false PDF content or false on failure
	 */
	public function download_pdf( $pdf_url ) {
		$response = wp_remote_get( $pdf_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept' => 'application/pdf,application/octet-stream,*/*',
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( sprintf( 'Failed to download PDF from %s', $pdf_url ), array( 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		
		if ( 200 !== $status_code ) {
			$this->logger->error( sprintf( 'PDF download returned status code: %d for %s', $status_code, $pdf_url ) );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		
		// Verify it's actually a PDF
		if ( substr( $body, 0, 4 ) !== '%PDF' ) {
			$this->logger->warning( sprintf( 'Downloaded content does not appear to be a PDF from %s', $pdf_url ) );
			return false;
		}
		
		$this->logger->info( sprintf( 'Successfully downloaded PDF (%d bytes) from %s', strlen( $body ), $pdf_url ) );
		
		return $body;
	}
	
	/**
	 * Extract text from PDF content
	 *
	 * @param string $pdf_content PDF binary content
	 * @return string Extracted text
	 */
	public function extract_pdf_text( $pdf_content ) {
		// Try using pdftotext if available (requires poppler-utils)
		if ( function_exists( 'shell_exec' ) ) {
			$pdftotext_result = shell_exec( 'which pdftotext 2>/dev/null || where pdftotext 2>nul' );
			$pdftotext_path = $pdftotext_result ? trim( $pdftotext_result ) : '';
			if ( ! empty( $pdftotext_path ) ) {
				// Save to temp file
				$temp_file = wp_tempnam( 'docket_' );
				file_put_contents( $temp_file, $pdf_content );
				
				// Extract text
				$text = shell_exec( escapeshellarg( $pdftotext_path ) . ' -layout ' . escapeshellarg( $temp_file ) . ' - 2>/dev/null' );
				
				// Clean up
				@unlink( $temp_file );
				
				if ( ! empty( $text ) ) {
					$this->logger->info( 'Successfully extracted text from PDF using pdftotext' );
					return trim( $text );
				}
			}
		}
		
		// Method 2: Try using Smalot\PdfParser if available (requires manual installation)
		if ( class_exists( 'Smalot\PdfParser\Parser' ) ) {
			try {
				$parser = new \Smalot\PdfParser\Parser();
				$pdf = $parser->parseContent( $pdf_content );
				$text = $pdf->getText();
				
				if ( ! empty( $text ) && strlen( trim( $text ) ) > 50 ) {
					$this->logger->info( 'Successfully extracted text from PDF using Smalot\PdfParser' );
					return trim( $text );
				}
			} catch ( Exception $e ) {
				$this->logger->warning( 'Smalot\PdfParser failed: ' . $e->getMessage() );
			}
		}
		
		// Method 3: Use simple PHP-based PDF parser (works on shared hosting, basic extraction)
		require_once PA_DOCKETS_SCRAPER_PLUGIN_DIR . 'includes/class-simple-pdf-parser.php';
		$text = PA_Dockets_Scraper_Simple_PDF_Parser::extract_text( $pdf_content );
		
		if ( ! empty( $text ) && strlen( trim( $text ) ) > 20 ) {
			$this->logger->info( sprintf( 'Successfully extracted text from PDF using simple PHP parser (%d characters)', strlen( $text ) ) );
			return trim( $text );
		}
		
		// Log why extraction failed for debugging
		if ( empty( $text ) ) {
			$this->logger->warning( 'Simple PDF parser returned empty text. PDF may use unsupported encoding or structure.' );
		} else {
			$this->logger->warning( sprintf( 'Simple PDF parser returned text but it was too short (%d chars): %s', strlen( $text ), substr( $text, 0, 100 ) ) );
		}
		
		// If we got some text but it's short, log it for debugging
		if ( ! empty( $text ) && strlen( trim( $text ) ) > 0 ) {
			$this->logger->warning( sprintf( 'PDF text extraction returned short text (%d chars): %s', strlen( $text ), substr( $text, 0, 100 ) ) );
		}
		
		// If all methods fail, log and return empty
		$this->logger->warning( 'PDF text extraction failed with all methods. PDF may be image-based, encrypted, or in an unsupported format.' );
		
		return '';
	}
	
	/**
	 * Process uploaded PDF file
	 *
	 * @param string $file_path Path to uploaded PDF file
	 * @param string $pre_extracted_text Optional pre-extracted text from browser (PDF.js)
	 * @return array|false Docket data or false on failure
	 */
	public function process_uploaded_pdf( $file_path, $pre_extracted_text = '' ) {
		if ( ! file_exists( $file_path ) ) {
			$this->logger->error( sprintf( 'PDF file not found: %s', $file_path ) );
			return false;
		}
		
		// Read PDF content
		$pdf_content = file_get_contents( $file_path );
		
		if ( false === $pdf_content ) {
			$this->logger->error( sprintf( 'Failed to read PDF file: %s', $file_path ) );
			return false;
		}
		
		// Verify it's actually a PDF
		if ( substr( $pdf_content, 0, 4 ) !== '%PDF' ) {
			$this->logger->error( sprintf( 'File does not appear to be a PDF: %s', $file_path ) );
			return false;
		}
		
		// Use pre-extracted text if provided, otherwise extract from PDF
		if ( ! empty( $pre_extracted_text ) && strlen( trim( $pre_extracted_text ) ) > 50 ) {
			$this->logger->info( 'Using pre-extracted text from browser (PDF.js)' );
			$pdf_text = $pre_extracted_text;
			// Log the browser-extracted text
			$this->logger->info( 'Browser-extracted PDF text', array(
				'text_length' => strlen( $pdf_text ),
				'full_text' => $pdf_text,
			) );
		} else {
			// Extract text from PDF using PHP parser
			if ( ! empty( $pre_extracted_text ) ) {
				$this->logger->info( 'Browser-extracted text was invalid or empty, falling back to PHP extraction' );
			}
			$pdf_text = $this->extract_pdf_text( $pdf_content );
			// Log the PHP-extracted text
			if ( ! empty( $pdf_text ) ) {
				$this->logger->info( 'PHP-extracted PDF text', array(
					'text_length' => strlen( $pdf_text ),
					'full_text' => $pdf_text,
				) );
			}
		}
		
		// Clean up and validate extracted text
		if ( ! empty( $pdf_text ) ) {
			// Log text before cleaning
			$this->logger->info( 'PDF text before cleaning', array(
				'text_length' => strlen( $pdf_text ),
				'full_text' => $pdf_text,
			) );
			
			// Clean up extracted text - remove control characters and improve readability
			// Remove control characters (keep only \n, \r, \t)
			$pdf_text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $pdf_text );
			
			// Normalize whitespace
			$pdf_text = preg_replace( '/[ \t]+/', ' ', $pdf_text );
			$pdf_text = preg_replace( '/\n{3,}/', "\n\n", $pdf_text );
			$pdf_text = preg_replace( '/[ \t]+\n/', "\n", $pdf_text );
			$pdf_text = preg_replace( '/\n[ \t]+/', "\n", $pdf_text );
			
			$pdf_text = trim( $pdf_text );
			
			// Log text after cleaning
			$this->logger->info( 'PDF text after cleaning', array(
				'text_length' => strlen( $pdf_text ),
				'full_text' => $pdf_text,
			) );
			
			// Final validation - reject if it's garbage (only for PHP-extracted text, browser text already validated)
			if ( empty( $pre_extracted_text ) && ! empty( $pdf_text ) ) {
				$sample = substr( $pdf_text, 0, 1000 );
				$printable_ascii = preg_match_all( '/[\x20-\x7E]/', $sample );
				$total_chars = strlen( $sample );
				$printable_ratio = $total_chars > 0 ? $printable_ascii / $total_chars : 0;
				$question_mark_ratio = substr_count( $sample, '?' ) / max( $total_chars, 1 );
				$high_unicode_count = preg_match_all( '/[\x80-\xFF]/', $sample );
				$high_unicode_ratio = $high_unicode_count / max( $total_chars, 1 );
				
				// If less than 40% printable ASCII, or too many question marks, or too many high unicode chars, it's garbage
				if ( $printable_ratio < 0.4 || $question_mark_ratio > 0.15 || $high_unicode_ratio > 0.3 ) {
					$this->logger->error( 'PHP-extracted PDF text is corrupted/unreadable', array(
						'printable_ratio' => $printable_ratio,
						'question_mark_ratio' => $question_mark_ratio,
						'high_unicode_ratio' => $high_unicode_ratio,
						'sample' => substr( $pdf_text, 0, 200 ),
					) );
					$pdf_text = ''; // Clear it so we don't send garbage to AI
				}
			}
		}
		
		// Extract docket number from filename if possible
		$filename = basename( $file_path );
		$docket_number = '';
		if ( preg_match( '/(MJ-\d+-\w+-\d+-\d+)/i', $filename, $matches ) ) {
			$docket_number = $matches[1];
		} else {
			// Generate a unique identifier
			$docket_number = 'UPLOAD-' . date( 'YmdHis' ) . '-' . substr( md5( $filename ), 0, 8 );
		}
		
		// Build docket data structure
		$docket_data = array(
			'docket_number' => $docket_number,
			'pdf_url' => '', // No URL for uploaded files
			'pdf_text' => $pdf_text,
			'pdf_content' => base64_encode( $pdf_content ), // Store as base64 for sending to Groq
			'source' => 'manual_upload',
			'uploaded_file' => $file_path,
		);
		
		// Determine extraction method used
		$extraction_method = ! empty( $pre_extracted_text ) && ! empty( $pdf_text ) ? 'browser' : 'php';
		
		// If no text was extracted, return false
		if ( empty( $pdf_text ) ) {
			$this->logger->error( sprintf( 'Failed to extract text from PDF: %s (tried %s extraction)', $filename, $extraction_method === 'browser' ? 'browser and PHP' : 'PHP' ) );
			return false;
		}
		
		$this->logger->info( sprintf( 'Successfully processed uploaded PDF: %s', $filename ), array(
			'docket_number' => $docket_number,
			'pdf_size' => strlen( $pdf_content ),
			'text_extracted' => true,
			'extraction_method' => $extraction_method,
			'text_length' => strlen( $pdf_text ),
		) );
		
		// Add extraction method to docket data
		$docket_data['extraction_method'] = $extraction_method;
		
		return $docket_data;
	}
}
