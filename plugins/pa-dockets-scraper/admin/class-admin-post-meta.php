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
	 * Constructor
	 *
	 * @param PA_Dockets_Scraper_Content_Database $content_database Content Database instance
	 */
	public function __construct( $content_database ) {
		$this->content_database = $content_database;
	}
	
	/**
	 * Initialize hooks
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );
		add_action( 'edit_form_after_title', array( $this, 'render_original_content_after_title' ) );
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
				<button type="button" id="pa-republish-original" class="button button-secondary" style="margin-right: 10px;">
					<?php esc_html_e( 'Republish Original Content', 'coudy-ai' ); ?>
				</button>
				<span class="pa-action-note" style="color: #666; font-size: 13px;">
					<?php esc_html_e( 'This will replace the current post content with the original article content verbatim.', 'coudy-ai' ); ?>
				</span>
			</div>
			
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
}

