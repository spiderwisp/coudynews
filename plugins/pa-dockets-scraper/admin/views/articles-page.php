<?php
/**
 * Articles page view
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Discovered Articles', 'coudy-ai' ); ?></h1>
	
	
	<div class="pa-articles-filters">
		<form method="get" action="">
			<input type="hidden" name="page" value="pa-content-articles" />
			
			<select name="source">
				<option value=""><?php esc_html_e( 'All Sources', 'coudy-ai' ); ?></option>
				<?php foreach ( $sources as $source_item ) : ?>
					<option value="<?php echo esc_attr( $source_item->id ); ?>" <?php selected( $source_id, $source_item->id ); ?>>
						<?php echo esc_html( $source_item->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'coudy-ai' ); ?></option>
				<option value="new" <?php selected( $status, 'new' ); ?>><?php esc_html_e( 'New', 'coudy-ai' ); ?></option>
				<option value="rewriting" <?php selected( $status, 'rewriting' ); ?>><?php esc_html_e( 'Rewriting', 'coudy-ai' ); ?></option>
				<option value="rewritten" <?php selected( $status, 'rewritten' ); ?>><?php esc_html_e( 'Rewritten', 'coudy-ai' ); ?></option>
				<option value="skipped" <?php selected( $status, 'skipped' ); ?>><?php esc_html_e( 'Skipped', 'coudy-ai' ); ?></option>
			</select>
			
			<input type="text" name="search" placeholder="<?php esc_attr_e( 'Search articles...', 'coudy-ai' ); ?>" value="<?php echo esc_attr( $search ); ?>" />
			
			<?php submit_button( __( 'Filter', 'coudy-ai' ), 'secondary', '', false ); ?>
			
			<?php if ( $source_id || $status || $search ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pa-content-articles' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear Filters', 'coudy-ai' ); ?>
				</a>
			<?php endif; ?>
		</form>
	</div>
	
	<?php if ( ! empty( $articles ) ) : ?>
		<form method="post" action="" id="articles-form">
			<?php wp_nonce_field( 'pa_content_bulk_action' ); ?>
			
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<div class="pa-bulk-actions-wrapper">
						<select name="bulk_action_type" class="pa-bulk-action-select">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'coudy-ai' ); ?></option>
							<option value="rewrite"><?php esc_html_e( 'Rewrite Selected', 'coudy-ai' ); ?></option>
							<option value="skip"><?php esc_html_e( 'Skip Selected', 'coudy-ai' ); ?></option>
						</select>
						<select name="bulk_category" class="pa-bulk-category-select">
							<option value=""><?php esc_html_e( 'Default Category', 'coudy-ai' ); ?></option>
							<?php
							$categories = get_categories( array( 'hide_empty' => false ) );
							foreach ( $categories as $category ) :
								?>
								<option value="<?php echo esc_attr( $category->term_id ); ?>">
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<textarea 
							name="bulk_additional_prompt" 
							class="pa-bulk-additional-prompt" 
							rows="2" 
							placeholder="<?php esc_attr_e( 'Additional instructions (optional)', 'coudy-ai' ); ?>"
							style="width: 100%; margin-top: 5px; padding: 6px 8px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;"
						></textarea>
						<?php submit_button( __( 'Apply', 'coudy-ai' ), 'action', 'pa_bulk_action', false, array( 'class' => 'pa-bulk-apply-btn' ) ); ?>
					</div>
				</div>
				
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav-pages">
						<?php
						$page_links = paginate_links( array(
							'base' => add_query_arg( 'paged', '%#%' ),
							'format' => '',
							'prev_text' => __( '&laquo;' ),
							'next_text' => __( '&raquo;' ),
							'total' => $total_pages,
							'current' => $paged,
						) );
						echo $page_links;
						?>
					</div>
				<?php endif; ?>
			</div>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" id="cb-select-all" />
						</td>
						<th scope="col"><?php esc_html_e( 'Source', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Title', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Published', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Discovered', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'coudy-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $articles as $article ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="article_ids[]" value="<?php echo esc_attr( $article->id ); ?>" />
							</th>
							<td>
								<strong><?php echo esc_html( $article->source_name ); ?></strong>
							</td>
							<td>
								<strong>
									<a href="#" class="article-preview" data-article-id="<?php echo esc_attr( $article->id ); ?>">
										<?php echo esc_html( $article->title ); ?>
									</a>
								</strong>
								<?php if ( ! empty( $article->excerpt ) ) : ?>
									<p class="description"><?php echo esc_html( wp_trim_words( $article->excerpt, 20 ) ); ?></p>
								<?php endif; ?>
							</td>
							<td>
								<?php 
								if ( $article->published_date ) {
									echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $article->published_date ) ) );
								} else {
									esc_html_e( 'N/A', 'coudy-ai' );
								}
								?>
							</td>
							<td>
								<?php echo esc_html( human_time_diff( strtotime( $article->discovered_at ), current_time( 'timestamp' ) ) ); ?> ago
							</td>
							<td>
								<?php
								$status_colors = array(
									'new' => '#2271b1',
									'rewriting' => '#d63638',
									'rewritten' => '#00a32a',
									'skipped' => '#646970',
								);
								$status_labels = array(
									'new' => __( 'New', 'coudy-ai' ),
									'rewriting' => __( 'Rewriting', 'coudy-ai' ),
									'rewritten' => __( 'Rewritten', 'coudy-ai' ),
									'skipped' => __( 'Skipped', 'coudy-ai' ),
								);
								$color = isset( $status_colors[ $article->status ] ) ? $status_colors[ $article->status ] : '#646970';
								$label = isset( $status_labels[ $article->status ] ) ? $status_labels[ $article->status ] : $article->status;
								?>
								<span class="status-badge" style="background-color: <?php echo esc_attr( $color ); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
									<?php echo esc_html( $label ); ?>
								</span>
							</td>
							<td class="pa-actions-cell">
								<div class="pa-row-actions">
									<?php if ( 'new' === $article->status ) : ?>
										<span class="pa-primary-action">
											<button type="button" class="button-link pa-rewrite-trigger" data-article-id="<?php echo esc_attr( $article->id ); ?>">
												<?php esc_html_e( 'Rewrite', 'coudy-ai' ); ?>
											</button>
										</span>
										|
										<span class="pa-secondary-actions">
											<form method="post" action="" class="pa-inline-form">
												<?php wp_nonce_field( 'pa_content_skip_article' ); ?>
												<input type="hidden" name="article_id" value="<?php echo esc_attr( $article->id ); ?>" />
												<button type="submit" name="skip_article" class="button-link"><?php esc_html_e( 'Skip', 'coudy-ai' ); ?></button>
											</form>
										</span>
										|
										<a href="<?php echo esc_url( $article->url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Original', 'coudy-ai' ); ?></a>
									<?php elseif ( 'rewritten' === $article->status && $article->rewritten_post_id ) : ?>
										<a href="<?php echo esc_url( get_permalink( $article->rewritten_post_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Post', 'coudy-ai' ); ?></a>
										|
										<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $article->rewritten_post_id ) ); ?>"><?php esc_html_e( 'Edit Post', 'coudy-ai' ); ?></a>
										|
										<a href="<?php echo esc_url( $article->url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Original', 'coudy-ai' ); ?></a>
									<?php else : ?>
										<a href="<?php echo esc_url( $article->url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Original', 'coudy-ai' ); ?></a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<div class="tablenav bottom">
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav-pages">
						<?php echo $page_links; ?>
					</div>
				<?php endif; ?>
			</div>
		</form>
	<?php else : ?>
		<p><?php esc_html_e( 'No articles found.', 'coudy-ai' ); ?></p>
	<?php endif; ?>
</div>

<!-- Rewrite popup forms (rendered outside table for proper positioning) -->
<?php if ( ! empty( $articles ) ) : ?>
	<?php foreach ( $articles as $article ) : ?>
		<?php if ( 'new' === $article->status ) : ?>
			<div class="pa-rewrite-form-hidden" id="pa-rewrite-<?php echo esc_attr( $article->id ); ?>" style="display: none;">
				<form method="post" action="" class="pa-rewrite-form-popup">
					<?php wp_nonce_field( 'pa_content_rewrite_article' ); ?>
					<input type="hidden" name="article_id" value="<?php echo esc_attr( $article->id ); ?>" />
					<div class="pa-rewrite-popup-content">
						<label for="article_category_<?php echo esc_attr( $article->id ); ?>"><?php esc_html_e( 'Category:', 'coudy-ai' ); ?></label>
						<select name="article_category" id="article_category_<?php echo esc_attr( $article->id ); ?>" class="pa-category-popup">
							<option value=""><?php esc_html_e( 'Default Category', 'coudy-ai' ); ?></option>
							<?php
							$categories = get_categories( array( 'hide_empty' => false ) );
							foreach ( $categories as $category ) :
								?>
								<option value="<?php echo esc_attr( $category->term_id ); ?>">
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<label for="additional_prompt_<?php echo esc_attr( $article->id ); ?>"><?php esc_html_e( 'Additional Instructions (optional):', 'coudy-ai' ); ?></label>
						<textarea 
							name="additional_prompt" 
							id="additional_prompt_<?php echo esc_attr( $article->id ); ?>" 
							class="pa-additional-prompt" 
							rows="3" 
							placeholder="<?php esc_attr_e( 'e.g., "Make this article funny" or "Focus on the fact the suspect is a teacher"', 'coudy-ai' ); ?>"
						></textarea>
						<div class="pa-popup-buttons">
							<?php submit_button( __( 'Rewrite', 'coudy-ai' ), 'primary', 'rewrite_article', false ); ?>
							<button type="button" class="button pa-cancel-rewrite"><?php esc_html_e( 'Cancel', 'coudy-ai' ); ?></button>
						</div>
					</div>
				</form>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>

<!-- Article Preview Modal -->
<div id="article-preview-modal" style="display: none;">
	<div class="article-preview-content">
		<span class="article-preview-close">&times;</span>
		<div id="article-preview-body"></div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#cb-select-all').on('change', function() {
		$('input[name="article_ids[]"]').prop('checked', $(this).prop('checked'));
	});
	
	$('.article-preview').on('click', function(e) {
		e.preventDefault();
		var articleId = $(this).data('article-id');
		
		// Load article preview via AJAX
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pa_get_article_preview',
				article_id: articleId,
				nonce: paContentAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#article-preview-body').html(response.data);
					$('#article-preview-modal').fadeIn();
				}
			}
		});
	});
	
	$('.article-preview-close').on('click', function() {
		$('#article-preview-modal').fadeOut();
	});
	
	$(document).on('click', '#article-preview-modal', function(e) {
		if ($(e.target).is('#article-preview-modal')) {
			$(this).fadeOut();
		}
	});
});
</script>

<style>
#article-preview-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0,0,0,0.7);
	z-index: 100000;
	display: flex;
	align-items: center;
	justify-content: center;
}

.article-preview-content {
	background: white;
	padding: 20px;
	max-width: 800px;
	max-height: 80vh;
	overflow-y: auto;
	position: relative;
	border-radius: 4px;
}

.article-preview-close {
	position: absolute;
	top: 10px;
	right: 15px;
	font-size: 28px;
	cursor: pointer;
	color: #666;
}

.article-preview-close:hover {
	color: #000;
}

.pa-articles-filters {
	background: #fff;
	padding: 15px;
	margin: 20px 0;
	border: 1px solid #ccd0d4;
}

.pa-articles-filters select,
.pa-articles-filters input[type="text"] {
	margin-right: 10px;
}

.status-badge {
	display: inline-block;
}
</style>

