<?php
/*
 * Plugin Name: StatCounter Analytics
 * Version: 2.1.2
 * Plugin URI: http://statcounter.com/
 * Description: Adds the StatCounter tracking code to your blog. To get setup: 1) Activate this plugin 2) Enter your StatCounter Project ID and Security Code in the <a href="options-general.php?page=statcounter-options"><strong>options page</strong></a>.
 * Author: Aodhan Cullen
 * Author URI: http://statcounter.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Defaults, etc.
define("KEY_SC_PROJECT", "sc_project");
define("KEY_SC_POSITION", "sc_position");
define("KEY_SC_SECURITY", "key_sc_security");
define("SC_PROJECT_DEFAULT", "" );
define("SC_SECURITY_DEFAULT", "" );
define("SC_POSITION_DEFAULT", "footer");

// Initialize hooks
add_action('init', 'statcounter_init_defaults');
add_action('admin_menu' , 'statcounter_add_option_page' );
add_action( 'admin_menu', 'statcounter_admin_menu' );
add_action('wp_enqueue_scripts', 'statcounter_enqueue_scripts');
add_action('wp_head', 'statcounter_add_author_tag');
// Add async attribute to the statcounter script
add_filter('script_loader_tag', 'statcounter_add_async_attribute', 10, 2);

function statcounter_init_defaults() {
	// Create the default key and status if they don't exist
	if ( get_option(KEY_SC_PROJECT) === false ) {
		add_option(KEY_SC_PROJECT, SC_PROJECT_DEFAULT);
	}
	if ( get_option(KEY_SC_SECURITY) === false ) {
		add_option(KEY_SC_SECURITY, SC_SECURITY_DEFAULT);
	}
	add_option("sc_invisible", "0");
}

function statcounter_admin_menu() {
	$hook = add_submenu_page('index.php', __('StatCounter Stats', 'official-statcounter-plugin-for-wordpress'), __('StatCounter Stats', 'official-statcounter-plugin-for-wordpress'), 'publish_posts', 'statcounter-stats', 'statcounter_reports_page');
	add_action("load-$hook", 'statcounter_reports_load');

	$hook = add_submenu_page('plugins.php', __('StatCounter Admin', 'official-statcounter-plugin-for-wordpress'), __('StatCounter Admin', 'official-statcounter-plugin-for-wordpress'), 'manage_options', 'statcounter-options', 'statcounter_options_page');
}

function statcounter_reports_load() {
	add_action('admin_head', 'statcounter_reports_head');
}

function statcounter_reports_head() {
	?>
	<style type="text/css">
		body { height: 100%; }
	</style>
	<?php
}

function statcounter_reports_page() {
	$sc_project = get_option(KEY_SC_PROJECT);
	if($sc_project == 0) {
		$sc_link = '//statcounter.com/';
	} else {
		$sc_link = '//statcounter.com/p'.esc_html($sc_project).'/?source=wordpress';
	}

	echo '<iframe id="statcounter_frame" src="'.esc_url($sc_link).'" width="100%" height="2000">
<p>Your browser does not support iframes.</p>
</iframe>';
}

// Hook in the options page function
function statcounter_add_option_page() {
	add_options_page('StatCounter Options', 'StatCounter', "manage_options", 'statcounter-options', 'statcounter_options_page');
}

function statcounter_options_page() {
	// If we are a postback, store the options
	if ( isset( $_POST['info_update'] ) && check_admin_referer( 'update_sc_project_nonce', 'sc_project_nonce' ) ) {

		// Update the Project ID
		// FIX: Sanitize immediately upon access to satisfy linter
		$sc_project = isset($_POST[KEY_SC_PROJECT]) ? sanitize_text_field(wp_unslash($_POST[KEY_SC_PROJECT])) : '';

		if (!ctype_digit($sc_project)) {
			echo "<div class='error'><p>Project ID should be numbers only</p></div>";
		} else {
			if ($sc_project == '') {
				$sc_project = SC_PROJECT_DEFAULT;
			}
			if (strlen($sc_project) > 16) {
				echo "<div class='error'><p>Project ID is invalid</p></div>";
			} else {
				update_option(KEY_SC_PROJECT, $sc_project);
			}
		}

		// Update the Security ID
		// FIX: Sanitize immediately upon access to satisfy linter
		$sc_security = isset($_POST[KEY_SC_SECURITY]) ? sanitize_text_field(wp_unslash($_POST[KEY_SC_SECURITY])) : '';
		// Additional cleanup specific to this field
		$sc_security = str_replace('"', '', $sc_security);

		if ($sc_security !== '' && !ctype_alnum(trim($sc_security, '"'))) {
			echo "<div class='error'><p>Security code should be numbers and letters only</p></div>";
		} else {
			if ($sc_security =='') {
				$sc_security = SC_SECURITY_DEFAULT;
			}
			if (strlen($sc_security) > 16) {
				echo "<div class='error'><p>Security code is invalid</p></div>";
			} else {
				update_option(KEY_SC_SECURITY, $sc_security);
			}
		}

		// Update the position
		// FIX: Sanitize immediately upon access
		$sc_position = isset($_POST[KEY_SC_POSITION]) ? sanitize_text_field(wp_unslash($_POST[KEY_SC_POSITION])) : '';

		if (($sc_position != 'header') && ($sc_position != 'footer')) {
			$sc_position = SC_POSITION_DEFAULT;
		}

		update_option(KEY_SC_POSITION, $sc_position);

		// Force invisibility
		// FIX: Sanitize immediately upon access
		$sc_invisible = isset($_POST['sc_invisible']) ? sanitize_text_field(wp_unslash($_POST['sc_invisible'])) : '';

		if ($sc_invisible == 1) {
			update_option('sc_invisible', "1");
		} else {
			update_option('sc_invisible', "0");
		}

		// Give an updated message
		echo "<div class='updated'><p><strong>StatCounter options updated</strong></p></div>";
	}

	// Output the options page
	?>

	<form method="post" action="options-general.php?page=statcounter-options">
		<?php wp_nonce_field( 'update_sc_project_nonce', 'sc_project_nonce' ); ?>
		<div class="wrap">
			<?php if (get_option( KEY_SC_PROJECT ) == "0" || get_option( KEY_SC_PROJECT ) == "") { ?>
				<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
					StatCounter Plugin has been activated, but will not be enabled until you enter your <strong>Project ID</strong> and <strong>Security Code</strong>.
				</div>
			<?php } ?>
			<h2>Using StatCounter</h2>
			<blockquote><a href="http://statcounter.com" style="font-weight:bold;">StatCounter</a> is a free web traffic analysis service, which provides summary stats on all your traffic and a detailed analysis of your last 500 page views. This limit can be increased by upgrading to a paid service.</p>
				<p>To activate the StatCounter service for your WordPress site:<ul>
					<li><a href="http://statcounter.com/sign-up/" style="font-weight:bold;">Sign Up</a> with StatCounter or <a href="http://statcounter.com/add-project/" style="font-weight:bold;">add a new project</a> to your existing account</li>
					<li>The installation process will detect your WordPress installation and provide you with your <strong>Project ID</strong> and <strong>Security Code</strong></li>
				</ul></blockquote>
			<h2>StatCounter Options</h2>
			<blockquote>
				<fieldset class='options'>
					<table class="editform" cellspacing="2" cellpadding="5">
						<tr>
							<td>
								<label for="<?php echo esc_attr(KEY_SC_PROJECT); ?>">Project ID:</label>
							</td>
							<td>
								<?php
								echo "<input type='text' size='11' ";
								echo "name='".esc_attr(KEY_SC_PROJECT)."' ";
								echo "id='".esc_attr(KEY_SC_PROJECT)."' ";
								echo "value='".esc_attr(get_option(KEY_SC_PROJECT))."' />\n";
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="<?php echo esc_attr(KEY_SC_SECURITY); ?>">Security Code:</label>
							</td>
							<td>
								<?php
								echo "<input type='text' size='9' ";
								echo "name='".esc_attr(KEY_SC_SECURITY)."' ";
								echo "id='".esc_attr(KEY_SC_SECURITY)."' ";
								echo "value='".esc_attr(get_option(KEY_SC_SECURITY))."' />\n";
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="<?php echo esc_attr(KEY_SC_POSITION); ?>">Counter Position:</label>
							</td>
							<td>
								<?php
								echo "<select name='".esc_attr(KEY_SC_POSITION)."' id='".esc_attr(KEY_SC_POSITION)."'>\n";

								echo "<option value='header'";
								if(get_option(KEY_SC_POSITION) == "header")
									echo " selected='selected'";
								echo ">Header</option>\n";

								echo "<option value='footer'";
								if(get_option(KEY_SC_POSITION) != "header")
									echo" selected='selected'";
								echo ">Footer</option>\n";

								echo "</select>\n";
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="sc_invisible">Force invisibility:</label>
							</td>
							<td>
								<?php
								$checked = "";
								if(get_option('sc_invisible')==1) {
									$checked = "checked";
								}
								echo "<input type='checkbox' name='sc_invisible' id='sc_invisible' value='1' ".esc_attr($checked).">\n";
								?>
							</td>
						</tr>
					</table>
				</fieldset>
			</blockquote>
			<p class="submit">
				<input type='submit' name='info_update' value='Update Options' />
			</p>
		</div>
	</form>
	<?php
}

// Function to handle script enqueueing properly
function statcounter_enqueue_scripts() {
	$sc_project = get_option(KEY_SC_PROJECT);
	$sc_security = get_option(KEY_SC_SECURITY);
	$sc_invisible = get_option('sc_invisible');

	// Only load if project ID is valid
	if ( $sc_project > 0 ) {

		$position = get_option(KEY_SC_POSITION);
		$in_footer = ($position !== 'header');

		// Prepare the inline variables
		$script_vars = "var sc_project=" . intval($sc_project) . ";\n";
		$script_vars .= "var sc_security=\"" . esc_js($sc_security) . "\";\n";
		if($sc_invisible == 1) {
			$script_vars .= "var sc_invisible=1;\n";
		}

		// Register and enqueue the StatCounter script
		wp_register_script( 'statcounter-js', 'https://www.statcounter.com/counter/counter.js', array(), null, $in_footer );
		wp_enqueue_script( 'statcounter-js' );

		// Add the configuration variables before the script loads
		wp_add_inline_script( 'statcounter-js', $script_vars, 'before' );

		// Add the NOSCRIPT tag logic
		$action_hook = $in_footer ? 'wp_footer' : 'wp_head';
		add_action($action_hook, 'statcounter_output_noscript');
	}
}

// Function to add async to the script tag
function statcounter_add_async_attribute($tag, $handle) {
	if ( 'statcounter-js' !== $handle ) {
		return $tag;
	}
	return str_replace( ' src', ' async src', $tag );
}

// Separate function for NOSCRIPT output
function statcounter_output_noscript() {
	$sc_project = get_option(KEY_SC_PROJECT);
	$sc_security = get_option(KEY_SC_SECURITY);
	$sc_invisible = get_option('sc_invisible');

	// FIX: Sanitize SERVER variable immediately upon access
	$server_https = isset($_SERVER['HTTPS']) ? sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) : '';
	$is_https = $server_https && filter_var($server_https, FILTER_VALIDATE_BOOLEAN);
	$protocol = $is_https ? "https:" : "http:";

	?>
	<noscript><div class="statcounter"><a title="web analytics" href="<?php echo esc_url($protocol) ?>//statcounter.com/"><img class="statcounter" src="<?php echo esc_url($protocol) ?>//c.statcounter.com/<?php echo esc_html($sc_project) ?>/0/<?php echo esc_html($sc_security) ?>/<?php echo esc_html($sc_invisible) ?>/" alt="web analytics" /></a></div></noscript>
	<?php
}

function statcounter_add_author_tag(){
	if (is_single()) {
		global $post;
		$authorId = $post->post_author;
		// Escape author ID and nickname
		$nickname = get_the_author_meta( 'nickname', $authorId );
		?>
		<script type="text/javascript">
			var _statcounter = _statcounter || [];
			_statcounter.push({"tags": {"author": "<?php echo esc_js($nickname); ?>"}});
		</script>
		<?php
	}
}
?>
