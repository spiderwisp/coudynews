<?php
/**
 * Settings page view
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'pa_dockets_scraper_settings' ); ?>
		
		<h2 class="nav-tab-wrapper">
			<a href="#api-credentials" class="nav-tab nav-tab-active"><?php esc_html_e( 'AI API Credentials', 'coudy-ai' ); ?></a>
			<a href="#source-config" class="nav-tab"><?php esc_html_e( 'Source Configuration', 'coudy-ai' ); ?></a>
			<a href="#article-settings" class="nav-tab"><?php esc_html_e( 'Article Settings', 'coudy-ai' ); ?></a>
		</h2>
		
		<div id="api-credentials" class="tab-content">
			<h2><?php esc_html_e( 'Groq Cloud API Credentials', 'coudy-ai' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Enter your Groq Cloud API credentials from console.groq.com.', 'coudy-ai' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pa_dockets_groq_api_key"><?php esc_html_e( 'API Key', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<input type="password" id="pa_dockets_groq_api_key" name="pa_dockets_groq_api_key" value="<?php echo esc_attr( $groq_api_key ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Your Groq API key from Groq Cloud Console.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pa_dockets_groq_api_url"><?php esc_html_e( 'API URL', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<input type="url" id="pa_dockets_groq_api_url" name="pa_dockets_groq_api_url" value="<?php echo esc_attr( $groq_api_url ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Groq API endpoint URL (default: https://api.groq.com/openai/v1).', 'coudy-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pa_dockets_groq_model"><?php esc_html_e( 'Model', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<select id="pa_dockets_groq_model" name="pa_dockets_groq_model">
							<optgroup label="<?php esc_attr_e( 'Llama 3.3 Series', 'coudy-ai' ); ?>">
								<option value="llama-3.3-70b-versatile" <?php selected( $groq_model, 'llama-3.3-70b-versatile' ); ?>>Llama 3.3 70B Versatile</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Llama 3.2 Series', 'coudy-ai' ); ?>">
								<option value="llama-3.2-90b-text-preview" <?php selected( $groq_model, 'llama-3.2-90b-text-preview' ); ?>>Llama 3.2 90B Text Preview</option>
								<option value="llama-3.2-11b-text-preview" <?php selected( $groq_model, 'llama-3.2-11b-text-preview' ); ?>>Llama 3.2 11B Text Preview</option>
								<option value="llama-3.2-3b-preview" <?php selected( $groq_model, 'llama-3.2-3b-preview' ); ?>>Llama 3.2 3B Preview</option>
								<option value="llama-3.2-1b-preview" <?php selected( $groq_model, 'llama-3.2-1b-preview' ); ?>>Llama 3.2 1B Preview</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Llama 3.1 Series', 'coudy-ai' ); ?>">
								<option value="llama-3.1-70b-versatile" <?php selected( $groq_model, 'llama-3.1-70b-versatile' ); ?>>Llama 3.1 70B Versatile (Deprecated)</option>
								<option value="llama-3.1-8b-instant" <?php selected( $groq_model, 'llama-3.1-8b-instant' ); ?>>Llama 3.1 8B Instant</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Llama 4 Series', 'coudy-ai' ); ?>">
								<option value="llama-4-scout-17b-16e-instruct" <?php selected( $groq_model, 'llama-4-scout-17b-16e-instruct' ); ?>>Llama 4 Scout 17B 16E Instruct</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Mixtral Series', 'coudy-ai' ); ?>">
								<option value="mixtral-8x7b-32768" <?php selected( $groq_model, 'mixtral-8x7b-32768' ); ?>>Mixtral 8x7B (32K context)</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Gemma Series', 'coudy-ai' ); ?>">
								<option value="gemma2-9b-it" <?php selected( $groq_model, 'gemma2-9b-it' ); ?>>Gemma 2 9B IT</option>
								<option value="gemma-7b-it" <?php selected( $groq_model, 'gemma-7b-it' ); ?>>Gemma 7B IT (Deprecated)</option>
							</optgroup>
						</select>
						<p class="description"><?php esc_html_e( 'Select the Groq model to use for article generation.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		
		<div id="source-config" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Source Configuration', 'coudy-ai' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure which sources to scrape and which counties to monitor.', 'coudy-ai' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable PA Web Dockets', 'coudy-ai' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="pa_dockets_enable_dockets" value="1" <?php checked( $enable_dockets, true ); ?> />
							<?php esc_html_e( 'Enable scraping of PA Web Dockets system', 'coudy-ai' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Counties to Monitor', 'coudy-ai' ); ?>
					</th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="pa_dockets_counties[]" value="potter" <?php checked( in_array( 'potter', $counties ), true ); ?> />
								<?php esc_html_e( 'Potter County', 'coudy-ai' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="pa_dockets_counties[]" value="tioga" <?php checked( in_array( 'tioga', $counties ), true ); ?> />
								<?php esc_html_e( 'Tioga County', 'coudy-ai' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="pa_dockets_counties[]" value="mckean" <?php checked( in_array( 'mckean', $counties ), true ); ?> />
								<?php esc_html_e( 'McKean County', 'coudy-ai' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Select which counties to monitor for new dockets.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pa_dockets_search_url"><?php esc_html_e( 'Custom Search URL', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<input type="url" id="pa_dockets_search_url" name="pa_dockets_search_url" value="<?php echo esc_attr( $search_url ); ?>" class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Optional: If the default URLs don\'t work, enter the exact URL to the PA Web Dockets MDJ search page. Leave empty to use default URLs.', 'coudy-ai' ); ?>
							<br />
							<strong><?php esc_html_e( 'Note:', 'coudy-ai' ); ?></strong> 
							<?php esc_html_e( 'You may need to inspect the PA Web Dockets portal to find the correct search URL. Check the logs for 404 errors to see which URLs were tried.', 'coudy-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		
		<div id="article-settings" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Article Settings', 'coudy-ai' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure default settings for generated articles.', 'coudy-ai' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pa_dockets_default_category"><?php esc_html_e( 'Default Category', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<select id="pa_dockets_default_category" name="pa_dockets_default_category">
							<option value="0"><?php esc_html_e( 'None', 'coudy-ai' ); ?></option>
							<?php foreach ( $categories as $category ) : ?>
								<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $default_category, $category->term_id ); ?>>
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Default category for generated articles.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pa_dockets_default_tags"><?php esc_html_e( 'Default Tags', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<input type="text" id="pa_dockets_default_tags" name="pa_dockets_default_tags" value="<?php echo esc_attr( $default_tags ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Comma-separated list of default tags for generated articles.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pa_dockets_article_tone"><?php esc_html_e( 'Article Tone', 'coudy-ai' ); ?></label>
					</th>
					<td>
						<select id="pa_dockets_article_tone" name="pa_dockets_article_tone">
							<option value="professional" <?php selected( $article_tone, 'professional' ); ?>><?php esc_html_e( 'Professional', 'coudy-ai' ); ?></option>
							<option value="informative" <?php selected( $article_tone, 'informative' ); ?>><?php esc_html_e( 'Informative', 'coudy-ai' ); ?></option>
							<option value="conversational" <?php selected( $article_tone, 'conversational' ); ?>><?php esc_html_e( 'Conversational', 'coudy-ai' ); ?></option>
							<option value="formal" <?php selected( $article_tone, 'formal' ); ?>><?php esc_html_e( 'Formal', 'coudy-ai' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Tone/style for generated articles.', 'coudy-ai' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		
		<?php submit_button( __( 'Save Settings', 'coudy-ai' ), 'primary', 'pa_dockets_scraper_save_settings' ); ?>
	</form>
	
	<hr />
	
	<h2><?php esc_html_e( 'Manual Actions', 'coudy-ai' ); ?></h2>
	<form method="post" action="" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to trigger a manual scrape?', 'coudy-ai' ); ?>');">
		<?php wp_nonce_field( 'pa_dockets_scraper_trigger_scrape' ); ?>
		<p>
			<?php esc_html_e( 'Manually trigger a scraping job. This will check for new dockets and process any pending ones.', 'coudy-ai' ); ?>
		</p>
		<?php submit_button( __( 'Trigger Manual Scrape', 'coudy-ai' ), 'secondary', 'pa_dockets_scraper_trigger_scrape' ); ?>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.tab-content').hide();
		$(target).show();
	});
});
</script>
