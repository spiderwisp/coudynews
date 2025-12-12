<?php
/**
 * Simple PDF Parser class for Coudy AI
 * 
 * A lightweight PHP-based PDF text extractor that works on shared hosting
 * without external dependencies. This is a basic implementation that extracts
 * text from simple PDFs. For complex PDFs, consider using Smalot\PdfParser.
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Simple_PDF_Parser {
	
	/**
	 * Extract text from PDF content using basic PHP parsing
	 *
	 * @param string $pdf_content PDF binary content
	 * @return string Extracted text
	 */
	public static function extract_text( $pdf_content ) {
		$text = '';
		$extracted_strings = array();
		
		// Log PDF structure for debugging
		$bt_count = substr_count( $pdf_content, ' BT ' ) + substr_count( $pdf_content, "\nBT\n" ) + substr_count( $pdf_content, "\rBT\r" );
		$stream_count = substr_count( $pdf_content, 'stream' );
		
		// Basic PDF text extraction - looks for text objects in the PDF stream
		// This is a simplified approach that works for many PDFs
		
		// Find all text objects (between BT and ET markers) - be more flexible with whitespace
		if ( preg_match_all( '/BT\s*(.*?)\s*ET/s', $pdf_content, $matches ) ) {
			foreach ( $matches[1] as $text_object ) {
				// Extract text strings (between parentheses)
				// Use a more permissive pattern that handles nested parentheses better
				if ( preg_match_all( '/\(((?:[^()\\\\]|\\\\.|\([^()]*\))*)\)/s', $text_object, $text_matches ) ) {
					foreach ( $text_matches[1] as $text_string ) {
						// Skip very short strings
						if ( strlen( trim( $text_string ) ) < 1 ) {
							continue;
						}
						
						// Decode escape sequences
						$decoded = self::decode_pdf_string( $text_string );
						
						// Only accept if it has some printable ASCII (filter out pure binary)
						if ( strlen( trim( $decoded ) ) > 0 ) {
							$printable_count = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $decoded ) );
							$printable_ratio = $printable_count / max( strlen( $decoded ), 1 );
							// Must be at least 40% printable ASCII to avoid binary garbage
							if ( $printable_ratio >= 0.4 && $printable_count > 2 ) {
								$extracted_strings[] = $decoded;
							}
						}
					}
				}
				
				// Text in angle brackets: <hex> - be more permissive
				if ( preg_match_all( '/<([0-9A-Fa-f\s]{4,})>/s', $text_object, $hex_matches ) ) {
					foreach ( $hex_matches[1] as $hex_string ) {
						$hex_clean = preg_replace( '/\s+/', '', $hex_string );
						// Only process if it looks like text (even number of hex digits)
						if ( strlen( $hex_clean ) >= 4 && strlen( $hex_clean ) % 2 === 0 && strlen( $hex_clean ) <= 200 ) {
							$decoded = self::hex_to_text( $hex_clean );
							if ( strlen( trim( $decoded ) ) > 0 ) {
								$printable_count = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $decoded ) );
								if ( $printable_count > 0 ) {
									$extracted_strings[] = $decoded;
								}
							}
						}
					}
				}
			}
		}
		
		// Skip stream extraction - streams often contain compressed/encoded binary data
		// that causes garbage output. Only extract from text objects (BT/ET blocks).
		
		// If no BT/ET blocks found, try extracting from raw PDF content directly
		// Some PDFs embed text differently
		if ( empty( $extracted_strings ) ) {
			// Look for text patterns in the raw PDF (common words, dates, etc.)
			// Extract sequences that look like readable text
			if ( preg_match_all( '/\(([A-Za-z0-9\s\.\,\:\-\(\)\/]{3,50})\)/', $pdf_content, $raw_matches ) ) {
				foreach ( $raw_matches[1] as $raw_string ) {
					$decoded = self::decode_pdf_string( $raw_string );
					$printable_count = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $decoded ) );
					$printable_ratio = $printable_count / max( strlen( $decoded ), 1 );
					if ( $printable_ratio >= 0.8 && strlen( trim( $decoded ) ) > 2 ) {
						$extracted_strings[] = $decoded;
					}
				}
			}
		}
		
		// Combine extracted strings
		$text = implode( ' ', $extracted_strings );
		
		// Clean up the text - remove excessive whitespace
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );
		
		// Final validation - be less strict, just check for minimum length and some printable content
		if ( strlen( $text ) < 10 ) {
			return '';
		}
		
		// Check that we have at least 40% printable ASCII characters (filter binary garbage)
		$printable_ratio = strlen( preg_replace( '/[^\x20-\x7E\n\r\t]/', '', $text ) ) / max( strlen( $text ), 1 );
		if ( $printable_ratio < 0.4 ) {
			return '';
		}
		
		return $text;
	}
	
	
	/**
	 * Decode PDF string (handle escape sequences)
	 *
	 * @param string $string PDF string
	 * @return string Decoded string
	 */
	private static function decode_pdf_string( $string ) {
		// Handle common PDF escape sequences
		$string = str_replace( '\\n', "\n", $string );
		$string = str_replace( '\\r', "\r", $string );
		$string = str_replace( '\\t', "\t", $string );
		$string = str_replace( '\\b', "\b", $string );
		$string = str_replace( '\\f', "\f", $string );
		$string = str_replace( '\\(', '(', $string );
		$string = str_replace( '\\)', ')', $string );
		$string = str_replace( '\\\\', '\\', $string );
		
		// Handle octal escape sequences (\ddd)
		$string = preg_replace_callback( '/\\\\([0-7]{1,3})/', function( $matches ) {
			return chr( octdec( $matches[1] ) );
		}, $string );
		
		return $string;
	}
	
	/**
	 * Convert hex string to text
	 *
	 * @param string $hex Hex string
	 * @return string Text
	 */
	private static function hex_to_text( $hex ) {
		// Remove whitespace
		$hex = preg_replace( '/\s+/', '', $hex );
		
		// Skip if not valid hex or too short
		if ( ! preg_match( '/^[0-9A-Fa-f]+$/', $hex ) || strlen( $hex ) < 4 ) {
			return '';
		}
		
		// Convert hex pairs to characters
		$text = '';
		$printable_count = 0;
		
		for ( $i = 0; $i < strlen( $hex ); $i += 2 ) {
			$hex_pair = substr( $hex, $i, 2 );
			if ( strlen( $hex_pair ) === 2 ) {
				$char_code = hexdec( $hex_pair );
				// Only process reasonable ASCII range (0-127)
				if ( $char_code <= 127 ) {
					$char = chr( $char_code );
					// Only include printable characters and common whitespace
					if ( ctype_print( $char ) || in_array( $char, array( "\n", "\r", "\t", ' ' ) ) ) {
						$text .= $char;
						if ( ctype_print( $char ) ) {
							$printable_count++;
						}
					}
				}
			}
		}
		
		// If less than 50% printable, it's probably not text
		if ( strlen( $text ) > 0 && ( $printable_count / strlen( $text ) ) < 0.5 ) {
			return '';
		}
		
		return $text;
	}
}
