<?php
/**
 * Terminal page view
 *
 * @package Coudy_Terminal
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="coudy-terminal-page-content">
		<p><?php esc_html_e( 'Click the button below or use the Terminal link in the admin bar to open the terminal.', 'coudy-terminal' ); ?></p>
		
		<button type="button" class="button button-primary" id="coudy-terminal-open-btn">
			<?php esc_html_e( 'Open Terminal', 'coudy-terminal' ); ?>
		</button>
		
		<div class="coudy-terminal-info" style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
			<h3><?php esc_html_e( 'Terminal Features', 'coudy-terminal' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Execute PHP code directly', 'coudy-terminal' ); ?></li>
				<li><?php esc_html_e( 'Run shell commands', 'coudy-terminal' ); ?></li>
				<li><?php esc_html_e( 'Execute WP-CLI commands', 'coudy-terminal' ); ?></li>
				<li><?php esc_html_e( 'Command history with arrow keys', 'coudy-terminal' ); ?></li>
				<li><?php esc_html_e( 'Keyboard shortcuts: Ctrl+L to clear, Esc to close', 'coudy-terminal' ); ?></li>
			</ul>
		</div>
		
		<div class="coudy-terminal-warning" style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #d63638;">
			<h3><?php esc_html_e( 'Security Warning', 'coudy-terminal' ); ?></h3>
			<p><?php esc_html_e( 'This terminal provides full access to execute code and commands. Only use this if you understand the security implications. All commands are executed with the same permissions as the web server.', 'coudy-terminal' ); ?></p>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#coudy-terminal-open-btn').on('click', function() {
		if (typeof CoudyTerminal !== 'undefined') {
			CoudyTerminal.open();
		}
	});
});
</script>

