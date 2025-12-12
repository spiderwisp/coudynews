<?php
/**
 * Logs page view
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
	
	<div class="pa-dockets-logs-header">
		<form method="get" action="">
			<input type="hidden" name="page" value="pa-dockets-scraper-logs" />
			<select name="log_type">
				<option value=""><?php esc_html_e( 'All Types', 'coudy-ai' ); ?></option>
				<option value="info" <?php selected( $log_type, 'info' ); ?>><?php esc_html_e( 'Info', 'coudy-ai' ); ?></option>
				<option value="success" <?php selected( $log_type, 'success' ); ?>><?php esc_html_e( 'Success', 'coudy-ai' ); ?></option>
				<option value="warning" <?php selected( $log_type, 'warning' ); ?>><?php esc_html_e( 'Warning', 'coudy-ai' ); ?></option>
				<option value="error" <?php selected( $log_type, 'error' ); ?>><?php esc_html_e( 'Error', 'coudy-ai' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'coudy-ai' ), 'secondary', '', false ); ?>
		</form>
		
		<form method="post" action="" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'coudy-ai' ); ?>');">
			<?php wp_nonce_field( 'pa_dockets_scraper_clear_logs' ); ?>
			<?php submit_button( __( 'Clear Logs', 'coudy-ai' ), 'delete', 'pa_dockets_scraper_clear_logs', false ); ?>
		</form>
	</div>
	
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 150px;"><?php esc_html_e( 'Timestamp', 'coudy-ai' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Type', 'coudy-ai' ); ?></th>
				<th><?php esc_html_e( 'Message', 'coudy-ai' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="3"><?php esc_html_e( 'No logs found.', 'coudy-ai' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log['timestamp'] ); ?></td>
						<td>
							<span class="log-type log-type-<?php echo esc_attr( $log['type'] ); ?>">
								<?php echo esc_html( ucfirst( $log['type'] ) ); ?>
							</span>
						</td>
						<td>
							<?php echo esc_html( $log['message'] ); ?>
							<?php if ( ! empty( $log['context'] ) ) : ?>
								<details style="margin-top: 5px;">
									<summary style="cursor: pointer; color: #0073aa;"><?php esc_html_e( 'View Context', 'coudy-ai' ); ?></summary>
									<pre style="background: #f5f5f5; padding: 10px; margin-top: 5px; overflow-x: auto;"><?php echo esc_html( print_r( $log['context'], true ) ); ?></pre>
								</details>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<style>
.pa-dockets-logs-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.log-type {
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: bold;
	text-transform: uppercase;
}

.log-type-info {
	background: #2271b1;
	color: #fff;
}

.log-type-success {
	background: #00a32a;
	color: #fff;
}

.log-type-warning {
	background: #dba617;
	color: #fff;
}

.log-type-error {
	background: #d63638;
	color: #fff;
}
</style>
