<?php
/**
 * Admin Post Meta class for PA Dockets Scraper
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PA_Dockets_Scraper_Admin_Post_Meta {
	
	/**
	 * Content Database instance
	 *
	 * @var PA_Dockets_Scraper_Content_Database
	 */
	private $content_database;
	
	/**
	 * Article Rewriter instance
	 *
	 * @var PA_Dockets_Scraper_Article_Rewriter
	 */
	private $article_rewriter;
	
	/**
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Content_Database $content_database Content Database instance
	 * @param PA_Dockets_Scraper_Article_Rewriter  $article_rewriter Article Rewriter instance
	 */
	public function __construct( $content_database, $article_rewriter ) {
		$this->content_database = $content_database;
		$this->article_rewriter = $article_rewriter;
	}
	
	/**
	 * Initialize hooks
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );
		add_action( 'edit_form_after_title', array( $this, 'render_original_content_after_title' ) );
		add_action( 'wp_ajax_pa_rewrite_post', array( $this, 'ajax_rewrite_post' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Enqueue scripts for post editor
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		
		// Ensure ajaxurl is available
		wp_localize_script( 'jquery', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	}
	
	/**
	 * Add meta boxes to post editor
	 */
	public function add_meta_boxes() {
		// Add meta box for posts that have original content
		// Use 'side' context and 'high' priority to place it at the top
		add_meta_box(
			'pa_content_original_article',
			__( 'Original Article Content', 'coudy-ai' ),
			array( $this, 'render_original_content_meta_box' ),
			'post',
			'normal',
			'high'
		);
	}
	
	/**
	 * Move meta box to top of editor
	 */
	public function move_meta_box_to_top() {
		global $wp_meta_boxes;
		
		// Get the meta box
		if ( isset( $wp_meta_boxes['post']['normal']['high']['pa_content_original_article'] ) ) {
			$meta_box = $wp_meta_boxes['post']['normal']['high']['pa_content_original_article'];
			
			// Remove from current position
			unset( $wp_meta_boxes['post']['normal']['high']['pa_content_original_article'] );
			
			// Add to top of normal column with highest priority
			$wp_meta_boxes['post']['normal']['core']['pa_content_original_article'] = $meta_box;
		}
	}
	
	/**
	 * Render original content after title (at the top of editor)
	 *
	 * @param WP_Post $post Post object
	 */
	public function render_original_content_after_title( $post ) {
		if ( ! $post || ! isset( $post->ID ) ) {
			return;
		}
		
		$original_article_id = get_post_meta( $post->ID, '_pa_content_original_article_id', true );
		
		if ( ! $original_article_id ) {
			return;
		}
		
		$article = $this->content_database->get_article( absint( $original_article_id ) );
		
		if ( ! $article ) {
			return;
		}
		
		// Get current post content
		$current_content = $post->post_content;
		$current_title = $post->post_title;
		
		$this->render_comparison_view( $article, $current_title, $current_content );
	}
	
	/**
	 * Render original content meta box (fallback for posts without original)
	 *
	 * @param WP_Post $post Post object
	 */
	public function render_original_content_meta_box( $post ) {
		$original_article_id = get_post_meta( $post->ID, '_pa_content_original_article_id', true );
		
		if ( ! $original_article_id ) {
			return; // Don't show anything if no original article
		}
		
		$article = $this->content_database->get_article( absint( $original_article_id ) );
		
		if ( ! $article ) {
			echo '<p>' . esc_html__( 'Original article not found.', 'coudy-ai' ) . '</p>';
			return;
		}
		
		// Fallback: render in meta box if not shown after title
		$current_content = $post->post_content;
		$current_title = $post->post_title;
		
		$this->render_comparison_view( $article, $current_title, $current_content );
	}
	
	/**
	 * Render comparison view
	 *
	 * @param object $article Original article object
	 * @param string $current_title Current post title
	 * @param string $current_content Current post content
	 */
	private function render_comparison_view( $article, $current_title, $current_content ) {
		$post_id = get_the_ID();
		$rewrite_nonce = wp_create_nonce( 'pa_rewrite_post_' . $post_id );
		?>
		<div class="pa-original-content-meta-box" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<div class="pa-original-content-header">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Original Article Information', 'coudy-ai' ); ?></h3>
				<div class="pa-original-content-meta" style="background: #f5f5f5; padding: 15px; border-radius: 4px;">
					<p style="margin: 8px 0;">
						<strong><?php esc_html_e( 'Source:', 'coudy-ai' ); ?></strong> 
						<?php echo esc_html( $article->source_name ); ?>
					</p>
					<p style="margin: 8px 0;">
						<strong><?php esc_html_e( 'Original URL:', 'coudy-ai' ); ?></strong> 
						<a href="<?php echo esc_url( $article->url ); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
							<?php echo esc_html( $article->url ); ?>
							<span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>
						</a>
					</p>
					<?php if ( $article->published_date ) : ?>
						<p style="margin: 8px 0;">
							<strong><?php esc_html_e( 'Published:', 'coudy-ai' ); ?></strong> 
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $article->published_date ) ) ); ?>
						</p>
					<?php endif; ?>
					<p style="margin: 8px 0;">
						<strong><?php esc_html_e( 'Discovered:', 'coudy-ai' ); ?></strong> 
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $article->discovered_at ) ) ); ?>
					</p>
				</div>
			</div>
			
			<div class="pa-original-content-actions" style="margin: 15px 0; padding: 10px; background: #f0f0f1; border-radius: 4px;">
				<button type="button" id="pa-rewrite-article" class="button button-primary" style="margin-right: 10px;">
					<?php esc_html_e( 'Rewrite with AI', 'coudy-ai' ); ?>
				</button>
				<button type="button" id="pa-republish-original" class="button button-secondary" style="margin-right: 10px;">
					<?php esc_html_e( 'Republish Original Content', 'coudy-ai' ); ?>
				</button>
				<span class="pa-action-note" style="color: #666; font-size: 13px;">
					<?php esc_html_e( 'Rewrite the article with AI or restore the original content.', 'coudy-ai' ); ?>
				</span>
			</div>
			
			<!-- Rewrite popup -->
			<div id="pa-rewrite-popup" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 100000; min-width: 400px; max-width: 600px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Rewrite Article with AI', 'coudy-ai' ); ?></h3>
				<label for="pa-rewrite-prompt" style="display: block; margin-bottom: 8px; font-weight: 600;">
					<?php esc_html_e( 'Additional Instructions (optional):', 'coudy-ai' ); ?>
				</label>
				<textarea 
					id="pa-rewrite-prompt" 
					rows="4" 
					style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px; margin-bottom: 15px;"
					placeholder="<?php esc_attr_e( 'e.g., "Make this article funny" or "Focus on the fact the suspect is a teacher"', 'coudy-ai' ); ?>"
				></textarea>
				<div style="text-align: right;">
					<button type="button" id="pa-rewrite-cancel" class="button" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'coudy-ai' ); ?>
					</button>
					<button type="button" id="pa-rewrite-submit" class="button button-primary">
						<?php esc_html_e( 'Rewrite', 'coudy-ai' ); ?>
					</button>
				</div>
			</div>
			<div id="pa-rewrite-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999;"></div>
			
			<div class="pa-original-content-comparison" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
				<div class="pa-content-column pa-original-column" style="border: 1px solid #ddd; border-left: 4px solid #dc3232; border-radius: 4px; padding: 15px; background: #fff;">
					<h4 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php esc_html_e( 'Original Article', 'coudy-ai' ); ?></h4>
					<div class="pa-content-preview" style="max-height: 600px; overflow-y: auto;">
						<h5 style="margin-top: 0; color: #23282d;"><?php echo esc_html( $article->title ); ?></h5>
						<?php if ( ! empty( $article->excerpt ) ) : ?>
							<p class="pa-excerpt" style="color: #666; font-style: italic; margin: 10px 0;"><em><?php echo esc_html( $article->excerpt ); ?></em></p>
						<?php endif; ?>
						<?php if ( ! empty( $article->content ) ) : ?>
							<div class="pa-content-body" style="margin-top: 15px;">
								<?php echo wp_kses_post( wpautop( $article->content ) ); ?>
							</div>
						<?php else : ?>
							<p class="pa-no-content" style="color: #999; font-style: italic;"><?php esc_html_e( 'Full content not available. Click the link above to view the original article.', 'coudy-ai' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="pa-content-column pa-rewritten-column" style="border: 1px solid #ddd; border-left: 4px solid #46b450; border-radius: 4px; padding: 15px; background: #fff;">
					<h4 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php esc_html_e( 'AI Rewritten Version', 'coudy-ai' ); ?></h4>
					<div class="pa-content-preview" style="max-height: 600px; overflow-y: auto;">
						<h5 style="margin-top: 0; color: #23282d;"><?php echo esc_html( $current_title ); ?></h5>
						<?php if ( ! empty( $current_content ) ) : ?>
							<div class="pa-content-body" style="margin-top: 15px;">
								<?php echo wp_kses_post( wpautop( $current_content ) ); ?>
							</div>
						<?php else : ?>
							<p class="pa-note" style="color: #666; font-style: italic;"><?php esc_html_e( 'Content will appear here as you edit the post above.', 'coudy-ai' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		
		<style>
		@media (max-width: 1200px) {
			.pa-original-content-comparison {
				grid-template-columns: 1fr !important;
			}
		}
		.pa-content-body p {
			margin: 10px 0;
		}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			var originalTitle = <?php echo json_encode( $article->title ); ?>;
			var originalContent = <?php echo json_encode( ! empty( $article->content ) ? $article->content : '' ); ?>;
			var postId = <?php echo get_the_ID(); ?>;
			var articleId = <?php echo absint( $article->id ); ?>;
			
			// Show rewrite popup
			$('#pa-rewrite-article').on('click', function() {
				$('#pa-rewrite-overlay').show();
				$('#pa-rewrite-popup').show();
				$('#pa-rewrite-prompt').val('');
			});
			
			// Close rewrite popup
			$('#pa-rewrite-cancel, #pa-rewrite-overlay').on('click', function() {
				$('#pa-rewrite-overlay').hide();
				$('#pa-rewrite-popup').hide();
			});
			
			// Submit rewrite
			$('#pa-rewrite-submit').on('click', function() {
				var $btn = $(this);
				var $popup = $('#pa-rewrite-popup');
				var prompt = $('#pa-rewrite-prompt').val();
				
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Rewriting...', 'coudy-ai' ) ); ?>');
				
					$.ajax({
						url: ajaxurl || '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						type: 'POST',
						data: {
							action: 'pa_rewrite_post',
							post_id: postId,
							article_id: articleId,
							additional_prompt: prompt,
							nonce: '<?php echo esc_js( $rewrite_nonce ); ?>'
						},
					success: function(response) {
						if (response.success) {
							// Update title
							if ($('#title').length) {
								$('#title').val(response.data.title);
							}
							
							// Update content in Classic Editor
							if ($('#content').length && typeof tinymce !== 'undefined' && tinymce.get('content')) {
								tinymce.get('content').setContent(response.data.content);
							} else if ($('#content').length) {
								$('#content').val(response.data.content);
							}
							
							// Update content in Block Editor
							if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
								wp.data.dispatch('core/editor').editPost({
									title: response.data.title,
									content: response.data.content
								});
							}
							
							// Update the comparison view
							$('.pa-rewritten-column h5').text(response.data.title);
							$('.pa-rewritten-column .pa-content-body').html(response.data.content);
							
							// Close popup
							$('#pa-rewrite-overlay').hide();
							$('#pa-rewrite-popup').hide();
							
							alert('<?php echo esc_js( __( 'Article rewritten successfully! Please review and save the post.', 'coudy-ai' ) ); ?>');
						} else {
							alert(response.data.message || '<?php echo esc_js( __( 'Failed to rewrite article. Please try again.', 'coudy-ai' ) ); ?>');
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'coudy-ai' ) ); ?>');
					},
					complete: function() {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Rewrite', 'coudy-ai' ) ); ?>');
					}
				});
			});
			
			// Republish original content button
			$('#pa-republish-original').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to replace the current content with the original article content? This action cannot be undone.', 'coudy-ai' ) ); ?>')) {
					return;
				}
				
				// Update title
				if ($('#title').length) {
					$('#title').val(originalTitle);
				}
				
				// Update content in Classic Editor
				if ($('#content').length && typeof tinymce !== 'undefined' && tinymce.get('content')) {
					tinymce.get('content').setContent(originalContent);
				} else if ($('#content').length) {
					$('#content').val(originalContent);
				}
				
				// Update content in Block Editor
				if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
					wp.data.dispatch('core/editor').editPost({
						title: originalTitle,
						content: originalContent
					});
				}
				
				// Update the comparison view
				$('.pa-rewritten-column h5').text(originalTitle);
				$('.pa-rewritten-column .pa-content-body').html(originalContent.split('\n').map(function(p) {
					return p.trim() ? '<p>' + p + '</p>' : '';
				}).join(''));
				
				alert('<?php echo esc_js( __( 'Original content has been loaded. Please save the post to apply changes.', 'coudy-ai' ) ); ?>');
			});
			
			// Update rewritten content when editor content changes
			var updateTimer;
			function updateRewrittenContent() {
				clearTimeout(updateTimer);
				updateTimer = setTimeout(function() {
					var content = '';
					var title = '';
					
					// Try to get content from Classic Editor
					if ($('#content').length) {
						if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
							content = tinymce.get('content').getContent();
						} else {
							content = $('#content').val();
						}
					}
					
					// Try to get content from Block Editor (Gutenberg)
					if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
						content = wp.data.select('core/editor').getEditedPostContent();
						title = wp.data.select('core/editor').getEditedPostAttribute('title');
					}
					
					// Update the rewritten column
					if (content) {
						var $rewrittenBody = $('.pa-rewritten-column .pa-content-body');
						if ($rewrittenBody.length) {
							// Convert blocks to HTML if needed
							if (content.indexOf('<!-- wp:') !== -1) {
								// This is block editor content, we'll show a note
								$rewrittenBody.html('<p style="color: #666; font-style: italic;">Content is being edited in the block editor above. Save to see formatted version.</p>');
							} else {
								// Convert HTML to display
								var displayContent = content;
								if (content.indexOf('<') === -1) {
									// Plain text, convert to paragraphs
									displayContent = content.split('\n').map(function(p) {
										return p.trim() ? '<p>' + p + '</p>' : '';
									}).join('');
								}
								$rewrittenBody.html(displayContent);
							}
						}
					}
					
					// Update title if changed
					if (title) {
						$('.pa-rewritten-column h5').text(title);
					} else if ($('#title').length) {
						$('.pa-rewritten-column h5').text($('#title').val());
					}
				}, 500);
			}
			
			// Listen for content changes
			$('#content').on('input', updateRewrittenContent);
			if (typeof tinymce !== 'undefined') {
				tinymce.get('content') && tinymce.get('content').on('keyup', updateRewrittenContent);
			}
			
			// Listen for block editor changes
			if (typeof wp !== 'undefined' && wp.data) {
			wp.data.subscribe(updateRewrittenContent);
		}
		});
		</script>
		<?php
	}
	
	/**
	 * AJAX handler for rewriting post
	 */
	public function ajax_rewrite_post() {
		check_ajax_referer( 'pa_rewrite_post_' . $_POST['post_id'], 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit posts.', 'coudy-ai' ) ) );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		$additional_prompt = isset( $_POST['additional_prompt'] ) ? sanitize_textarea_field( $_POST['additional_prompt'] ) : '';
		
		if ( ! $post_id || ! $article_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post or article ID.', 'coudy-ai' ) ) );
		}
		
		// Rewrite the article
		$rewritten_data = $this->rewrite_article_for_post( $article_id, $additional_prompt );
		
		if ( ! $rewritten_data ) {
			wp_send_json_error( array( 'message' => __( 'Failed to rewrite article. Please check the logs.', 'coudy-ai' ) ) );
		}
		
		wp_send_json_success( array(
			'title' => $rewritten_data['title'],
			'content' => $rewritten_data['content'],
		) );
	}
	
	/**
	 * Rewrite article and return data (without creating new post)
	 *
	 * @param int    $article_id Article ID
	 * @param string $additional_prompt Optional additional prompt
	 * @return array|false Rewritten article data or false on failure
	 */
	private function rewrite_article_for_post( $article_id, $additional_prompt = '' ) {
		// Use the article rewriter instance
		if ( ! $this->article_rewriter ) {
			return false;
		}
		
		$article = $this->content_database->get_article( $article_id );
		
		if ( ! $article ) {
			return false;
		}
		
		// Get full article content if not already stored
		if ( empty( $article->content ) ) {
			$response = wp_remote_get( $article->url, array( 'timeout' => 15 ) );
			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if ( preg_match( '/<article[^>]*>(.*?)<\/article>/is', $body, $matches ) ) {
					$article->content = wp_strip_all_tags( $matches[1] );
				} else {
					$article->content = wp_strip_all_tags( $body );
				}
			}
			
			if ( empty( $article->content ) ) {
				$article->content = $article->excerpt;
			}
		}
		
		if ( empty( $article->content ) ) {
			return false;
		}
		
		// Use reflection to call private method, or make it public
		// For now, let's use a workaround - call the public rewrite_article but don't create post
		// Actually, better to use the AI generator directly
		$ai_generator = $this->article_rewriter;
		
		// Build prompt using reflection or make method accessible
		// Let's use a simpler approach - call the rewrite but intercept before post creation
		// Actually, the cleanest way is to use the AI generator's method directly
		// But for now, let's duplicate the prompt building logic
		$tone = get_option( 'pa_dockets_article_tone', 'professional' );
		
		$prompt = "You are a professional content writer. Your task is to rewrite the following article in your own words while maintaining factual accuracy and key information.\n\n";
		$prompt .= "ORIGINAL ARTICLE:\n";
		$prompt .= "Title: " . $article->title . "\n\n";
		$prompt .= "Content:\n";
		$prompt .= wp_strip_all_tags( $article->content ) . "\n\n";
		$prompt .= "REWRITE REQUIREMENTS:\n";
		$prompt .= "1. Rewrite the article completely in your own words\n";
		$prompt .= "2. Maintain all factual information and key points\n";
		$prompt .= "3. Use a {$tone} tone\n";
		$prompt .= "4. Create a new, compelling headline (50-70 characters, concise and engaging)\n";
		$prompt .= "5. Structure the article with clear paragraphs and proper flow\n";
		$prompt .= "6. Add original insights or context where appropriate\n";
		$prompt .= "7. Ensure the content is SEO-friendly\n";
		$prompt .= "8. Do not copy sentences verbatim - completely rephrase everything\n";
		$prompt .= "9. Maintain the same general length and depth as the original\n";
		
		if ( ! empty( $additional_prompt ) ) {
			$prompt .= "\nADDITIONAL INSTRUCTIONS:\n";
			$prompt .= sanitize_text_field( $additional_prompt ) . "\n";
		}
		
		$prompt .= "\nSEO REQUIREMENTS:\n";
		$prompt .= "1. META DESCRIPTION: Create a compelling 150-155 character meta description\n";
		$prompt .= "2. KEYWORDS: Generate 5-8 relevant keywords/phrases\n";
		$prompt .= "3. FOCUS KEYPHRASE: Identify the primary search term (1-4 words)\n\n";
		$prompt .= "Return JSON format:\n";
		$prompt .= "{\n";
		$prompt .= '  "title": "Rewritten headline",' . "\n";
		$prompt .= '  "content": "<p>Rewritten article content with HTML paragraphs</p>",' . "\n";
		$prompt .= '  "meta_description": "SEO-optimized description (150-155 characters)",' . "\n";
		$prompt .= '  "keywords": "keyword1, keyword2, keyword3",' . "\n";
		$prompt .= '  "focus_keyphrase": "primary search term"' . "\n";
		$prompt .= "}\n";
		
		// Call AI using the article rewriter's method via reflection
		$reflection = new ReflectionClass( $this->article_rewriter );
		$method = $reflection->getMethod( 'call_ai_rewrite' );
		$method->setAccessible( true );
		
		return $method->invoke( $this->article_rewriter, $prompt, $article );
	}
}

