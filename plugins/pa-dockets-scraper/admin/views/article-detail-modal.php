<?php
/**
 * Article detail modal content
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This will be loaded via AJAX
$article = isset( $article ) ? $article : null;

if ( ! $article ) {
	echo '<p>' . esc_html__( 'Article not found.', 'coudy-ai' ) . '</p>';
	return;
}
?>

<div class="article-detail">
	<h2><?php echo esc_html( $article->title ); ?></h2>
	
	<div class="article-meta">
		<p>
			<strong><?php esc_html_e( 'Source:', 'coudy-ai' ); ?></strong> 
			<?php echo esc_html( $article->source_name ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'URL:', 'coudy-ai' ); ?></strong> 
			<a href="<?php echo esc_url( $article->url ); ?>" target="_blank"><?php echo esc_html( $article->url ); ?></a>
		</p>
		<?php if ( $article->published_date ) : ?>
			<p>
				<strong><?php esc_html_e( 'Published:', 'coudy-ai' ); ?></strong> 
				<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $article->published_date ) ) ); ?>
			</p>
		<?php endif; ?>
		<p>
			<strong><?php esc_html_e( 'Discovered:', 'coudy-ai' ); ?></strong> 
			<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $article->discovered_at ) ) ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Status:', 'coudy-ai' ); ?></strong> 
			<?php echo esc_html( ucfirst( $article->status ) ); ?>
		</p>
	</div>
	
	<?php if ( ! empty( $article->excerpt ) ) : ?>
		<div class="article-excerpt">
			<h3><?php esc_html_e( 'Excerpt', 'coudy-ai' ); ?></h3>
			<p><?php echo esc_html( $article->excerpt ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $article->content ) ) : ?>
		<div class="article-content">
			<h3><?php esc_html_e( 'Content', 'coudy-ai' ); ?></h3>
			<div class="article-content-text">
				<?php echo wp_kses_post( wpautop( $article->content ) ); ?>
			</div>
		</div>
	<?php endif; ?>
	
	<div class="article-actions">
		<?php if ( 'new' === $article->status ) : ?>
			<form method="post" action="" style="display: inline;">
				<?php wp_nonce_field( 'pa_content_rewrite_article' ); ?>
				<input type="hidden" name="article_id" value="<?php echo esc_attr( $article->id ); ?>" />
				<?php submit_button( __( 'Rewrite This Article', 'coudy-ai' ), 'primary', 'rewrite_article', false ); ?>
			</form>
		<?php elseif ( 'rewritten' === $article->status && $article->rewritten_post_id ) : ?>
			<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $article->rewritten_post_id ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View Rewritten Post', 'coudy-ai' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>

<style>
.article-detail {
	padding: 10px;
}

.article-detail h2 {
	margin-top: 0;
}

.article-meta {
	background: #f5f5f5;
	padding: 15px;
	border-radius: 4px;
	margin: 15px 0;
}

.article-meta p {
	margin: 5px 0;
}

.article-excerpt,
.article-content {
	margin: 20px 0;
}

.article-content-text {
	max-height: 400px;
	overflow-y: auto;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 4px;
}

.article-actions {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #ddd;
}
</style>

