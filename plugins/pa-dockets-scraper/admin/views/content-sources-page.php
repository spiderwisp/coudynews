<?php
/**
 * Content Sources page view
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Content Sources', 'coudy-ai' ); ?></h1>
	
	<div class="pa-content-sources">
		<div class="pa-sources-header">
			<button type="button" class="button button-primary" id="add-source-btn">
				<?php esc_html_e( 'Add New Source', 'coudy-ai' ); ?>
			</button>
		</div>
		
		<?php if ( $edit_source ) : ?>
			<div class="pa-source-form" id="edit-source-form">
				<h2><?php esc_html_e( 'Edit Source', 'coudy-ai' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'pa_content_edit_source' ); ?>
					<input type="hidden" name="source_id" value="<?php echo esc_attr( $edit_source->id ); ?>" />
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="source_name"><?php esc_html_e( 'Source Name', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="text" id="source_name" name="source_name" value="<?php echo esc_attr( $edit_source->name ); ?>" class="regular-text" required />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="source_url"><?php esc_html_e( 'Website URL', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="url" id="source_url" name="source_url" value="<?php echo esc_attr( $edit_source->url ); ?>" class="regular-text" required />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rss_url"><?php esc_html_e( 'RSS Feed URL', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="url" id="rss_url" name="rss_url" value="<?php echo esc_attr( $edit_source->rss_url ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Leave empty to auto-detect or use web scraping only.', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="scraping_method"><?php esc_html_e( 'Scraping Method', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<select id="scraping_method" name="scraping_method">
									<option value="rss" <?php selected( $edit_source->scraping_method, 'rss' ); ?>><?php esc_html_e( 'RSS Feed Only', 'coudy-ai' ); ?></option>
									<option value="scrape" <?php selected( $edit_source->scraping_method, 'scrape' ); ?>><?php esc_html_e( 'Web Scraping Only', 'coudy-ai' ); ?></option>
									<option value="both" <?php selected( $edit_source->scraping_method, 'both' ); ?>><?php esc_html_e( 'Both RSS and Scraping', 'coudy-ai' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="check_interval"><?php esc_html_e( 'Check Interval (hours)', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="number" id="check_interval" name="check_interval" value="<?php echo esc_attr( $edit_source->check_interval ); ?>" min="1" max="168" class="small-text" />
								<p class="description"><?php esc_html_e( 'How often to check for new articles (1-168 hours).', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Active', 'coudy-ai' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="is_active" value="1" <?php checked( $edit_source->is_active, 1 ); ?> />
									<?php esc_html_e( 'Actively track this source', 'coudy-ai' ); ?>
								</label>
							</td>
						</tr>
					</table>
					
					<?php submit_button( __( 'Update Source', 'coudy-ai' ), 'primary', 'edit_source' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pa-content-sources' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'coudy-ai' ); ?></a>
				</form>
			</div>
		<?php else : ?>
			<div class="pa-source-form" id="add-source-form" style="display:none;">
				<h2><?php esc_html_e( 'Add New Source', 'coudy-ai' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'pa_content_add_source' ); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="new_source_name"><?php esc_html_e( 'Source Name', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="text" id="new_source_name" name="source_name" class="regular-text" required />
								<p class="description"><?php esc_html_e( 'A friendly name for this source (e.g., "Local News Site").', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="new_source_url"><?php esc_html_e( 'Website URL', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="url" id="new_source_url" name="source_url" class="regular-text" required />
								<p class="description"><?php esc_html_e( 'The base URL of the website to track.', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="new_rss_url"><?php esc_html_e( 'RSS Feed URL', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="url" id="new_rss_url" name="rss_url" class="regular-text" />
								<button type="button" class="button" id="auto-detect-rss"><?php esc_html_e( 'Auto-Detect', 'coudy-ai' ); ?></button>
								<p class="description"><?php esc_html_e( 'Leave empty to auto-detect or use web scraping only.', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="new_scraping_method"><?php esc_html_e( 'Scraping Method', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<select id="new_scraping_method" name="scraping_method">
									<option value="rss"><?php esc_html_e( 'RSS Feed Only', 'coudy-ai' ); ?></option>
									<option value="scrape"><?php esc_html_e( 'Web Scraping Only', 'coudy-ai' ); ?></option>
									<option value="both"><?php esc_html_e( 'Both RSS and Scraping', 'coudy-ai' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'RSS is faster and more reliable. Web scraping uses AI to parse pages.', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="new_check_interval"><?php esc_html_e( 'Check Interval (hours)', 'coudy-ai' ); ?></label>
							</th>
							<td>
								<input type="number" id="new_check_interval" name="check_interval" value="24" min="1" max="168" class="small-text" />
								<p class="description"><?php esc_html_e( 'How often to check for new articles (default: 24 hours).', 'coudy-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Active', 'coudy-ai' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="is_active" value="1" checked />
									<?php esc_html_e( 'Actively track this source', 'coudy-ai' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td>
								<label>
									<input type="checkbox" name="check_immediately" value="1" />
									<?php esc_html_e( 'Check for articles immediately after adding', 'coudy-ai' ); ?>
								</label>
							</td>
						</tr>
					</table>
					
					<?php submit_button( __( 'Add Source', 'coudy-ai' ), 'primary', 'add_source' ); ?>
					<button type="button" class="button" id="cancel-add-source"><?php esc_html_e( 'Cancel', 'coudy-ai' ); ?></button>
				</form>
			</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $sources ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'URL', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Method', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Articles', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Last Checked', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'coudy-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'coudy-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sources as $source ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $source->name ); ?></strong></td>
							<td><a href="<?php echo esc_url( $source->url ); ?>" target="_blank"><?php echo esc_html( $source->url ); ?></a></td>
							<td><?php echo esc_html( ucfirst( $source->scraping_method ) ); ?></td>
							<td><?php echo esc_html( $source->article_count ); ?></td>
							<td>
								<?php 
								if ( $source->last_checked ) {
									echo esc_html( human_time_diff( strtotime( $source->last_checked ), current_time( 'timestamp' ) ) ) . ' ago';
								} else {
									esc_html_e( 'Never', 'coudy-ai' );
								}
								?>
							</td>
							<td>
								<?php if ( $source->is_active ) : ?>
									<span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php esc_html_e( 'Active', 'coudy-ai' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-dismiss" style="color: red;"></span> <?php esc_html_e( 'Inactive', 'coudy-ai' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=pa-content-articles&source=' . $source->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'View Articles', 'coudy-ai' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=pa-content-sources&check=' . $source->id ), 'pa_content_check_source_' . $source->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Check Now', 'coudy-ai' ); ?>
								</a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=pa-content-sources&edit=' . $source->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'coudy-ai' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=pa-content-sources&delete=' . $source->id ), 'pa_content_delete_source_' . $source->id ) ); ?>" 
								   class="button button-small button-link-delete" 
								   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this source? All associated articles will also be deleted.', 'coudy-ai' ); ?>');">
									<?php esc_html_e( 'Delete', 'coudy-ai' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No content sources added yet. Click "Add New Source" to get started.', 'coudy-ai' ); ?></p>
		<?php endif; ?>
	</div>
</div>


