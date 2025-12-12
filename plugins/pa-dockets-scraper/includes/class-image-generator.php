<?php
/**
 * Image Generator class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Image_Generator {
	
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
		$this->api_url = 'https://api.openai.com/v1/images/generations';
		$this->api_key = get_option( 'pa_dockets_openai_api_key', '' );
	}
	
	/**
	 * Generate and attach image to post
	 *
	 * @param array  $article_data Article data (title, content)
	 * @param object $docket       Docket object
	 * @param int    $post_id      Post ID
	 * @return int|false Attachment ID or false on failure
	 */
	public function generate_image( $article_data, $docket, $post_id ) {
		// Check if image generation is enabled
		$enable_images = get_option( 'pa_dockets_enable_image_generation', true );
		if ( ! $enable_images ) {
			$this->logger->info( 'Image generation is disabled' );
			return false;
		}
		
		// Check if API key is configured
		if ( empty( $this->api_key ) ) {
			$this->logger->warning( 'OpenAI API key not configured, skipping image generation' );
			return false;
		}
		
		// Build image prompt
		$prompt = $this->build_image_prompt( $article_data, $docket );
		
		if ( empty( $prompt ) ) {
			$this->logger->warning( 'Failed to build image prompt' );
			return false;
		}
		
		// Generate image using DALL-E API
		$image_url = $this->call_dalle_api( $prompt );
		
		if ( ! $image_url ) {
			$this->logger->error( 'Failed to generate image from DALL-E API' );
			return false;
		}
		
		// Download and attach image
		$attachment_id = $this->download_and_attach_image( $image_url, $post_id, $article_data['title'] );
		
		if ( ! $attachment_id ) {
			$this->logger->error( 'Failed to download and attach image' );
			return false;
		}
		
		// Set as featured image
		$result = $this->set_featured_image( $post_id, $attachment_id );
		
		if ( $result ) {
			$this->logger->success( sprintf( 'Generated and attached image for post %d', $post_id ), array(
				'post_id' => $post_id,
				'attachment_id' => $attachment_id,
			) );
		}
		
		return $attachment_id;
	}
	
	/**
	 * Build image prompt from article and docket data
	 *
	 * @param array  $article_data Article data
	 * @param object $docket       Docket object
	 * @return string Image prompt
	 */
	private function build_image_prompt( $article_data, $docket ) {
		$prompt_parts = array();
		
		// Base style
		$prompt_parts[] = 'Professional news photography style';
		
		// Location context
		if ( isset( $docket->county ) && ! empty( $docket->county ) ) {
			$county = ucfirst( $docket->county ) . ' County';
			$prompt_parts[] = $county . ' courthouse exterior';
		} else {
			$prompt_parts[] = 'Pennsylvania courthouse exterior';
		}
		
		// Case type context
		if ( isset( $docket->raw_data['case_type'] ) && ! empty( $docket->raw_data['case_type'] ) ) {
			$case_type = strtolower( $docket->raw_data['case_type'] );
			if ( stripos( $case_type, 'criminal' ) !== false ) {
				$prompt_parts[] = 'criminal court proceedings';
			} elseif ( stripos( $case_type, 'civil' ) !== false ) {
				$prompt_parts[] = 'civil court proceedings';
			}
		}
		
		// General legal/news theme
		$prompt_parts[] = 'journalistic photography';
		$prompt_parts[] = 'high quality';
		$prompt_parts[] = 'news article image';
		
		// Avoid specific people or faces
		$prompt_parts[] = 'no people visible';
		$prompt_parts[] = 'architectural focus';
		
		$prompt = implode( ', ', $prompt_parts );
		
		// Log the prompt for debugging
		$this->logger->info( 'Generated image prompt', array( 'prompt' => $prompt ) );
		
		return $prompt;
	}
	
	/**
	 * Call OpenAI DALL-E API
	 *
	 * @param string $prompt Image generation prompt
	 * @return string|false Image URL or false on failure
	 */
	private function call_dalle_api( $prompt ) {
		$request_body = array(
			'model' => 'dall-e-3',
			'prompt' => $prompt,
			'n' => 1,
			'size' => '1024x1024',
			'quality' => 'standard',
			'response_format' => 'url',
		);
		
		$response = wp_remote_post( $this->api_url, array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $request_body ),
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'DALL-E API request failed', array( 'error' => $response->get_error_message() ) );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( 200 !== $status_code ) {
			$this->logger->error( sprintf( 'DALL-E API returned status code: %d', $status_code ), array( 'response' => $body ) );
			return false;
		}
		
		$data = json_decode( $body, true );
		
		if ( ! isset( $data['data'][0]['url'] ) ) {
			$this->logger->error( 'Invalid DALL-E API response format', array( 'response' => $body ) );
			return false;
		}
		
		$image_url = $data['data'][0]['url'];
		
		$this->logger->info( 'Successfully generated image from DALL-E', array( 'image_url' => $image_url ) );
		
		return $image_url;
	}
	
	/**
	 * Download image and create WordPress attachment
	 *
	 * @param string $image_url Image URL from DALL-E
	 * @param int    $post_id   Post ID
	 * @param string $title     Post title for image filename
	 * @return int|false Attachment ID or false on failure
	 */
	private function download_and_attach_image( $image_url, $post_id, $title ) {
		// Download image
		$image_data = wp_remote_get( $image_url, array( 'timeout' => 30 ) );
		
		if ( is_wp_error( $image_data ) ) {
			$this->logger->error( 'Failed to download image', array( 'error' => $image_data->get_error_message() ) );
			return false;
		}
		
		$image_body = wp_remote_retrieve_body( $image_data );
		$image_type = wp_remote_retrieve_header( $image_data, 'content-type' );
		
		if ( empty( $image_body ) ) {
			$this->logger->error( 'Downloaded image is empty' );
			return false;
		}
		
		// Determine file extension from content type
		$extension = 'jpg';
		if ( strpos( $image_type, 'png' ) !== false ) {
			$extension = 'png';
		} elseif ( strpos( $image_type, 'webp' ) !== false ) {
			$extension = 'webp';
		}
		
		// Create filename from post title
		$filename = sanitize_file_name( $title );
		$filename = preg_replace( '/[^a-z0-9-]/i', '-', $filename );
		$filename = preg_replace( '/-+/', '-', $filename );
		$filename = trim( $filename, '-' );
		$filename = substr( $filename, 0, 100 ); // Limit length
		$filename = $filename . '-' . time() . '.' . $extension;
		
		// Upload file
		$upload = wp_upload_bits( $filename, null, $image_body );
		
		if ( $upload['error'] ) {
			$this->logger->error( 'Failed to upload image', array( 'error' => $upload['error'] ) );
			return false;
		}
		
		// Create attachment
		$attachment = array(
			'post_mime_type' => $image_type,
			'post_title' => sanitize_file_name( $title ),
			'post_content' => '',
			'post_status' => 'inherit',
		);
		
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
		
		if ( is_wp_error( $attachment_id ) ) {
			$this->logger->error( 'Failed to create attachment', array( 'error' => $attachment_id->get_error_message() ) );
			return false;
		}
		
		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );
		
		$this->logger->info( 'Created attachment for generated image', array(
			'attachment_id' => $attachment_id,
			'filename' => $filename,
		) );
		
		return $attachment_id;
	}
	
	/**
	 * Set image as featured image for post
	 *
	 * @param int $post_id       Post ID
	 * @param int $attachment_id Attachment ID
	 * @return bool Success
	 */
	private function set_featured_image( $post_id, $attachment_id ) {
		$result = set_post_thumbnail( $post_id, $attachment_id );
		
		if ( $result ) {
			$this->logger->info( 'Set featured image for post', array(
				'post_id' => $post_id,
				'attachment_id' => $attachment_id,
			) );
		} else {
			$this->logger->warning( 'Failed to set featured image', array(
				'post_id' => $post_id,
				'attachment_id' => $attachment_id,
			) );
		}
		
		return $result;
	}
}

