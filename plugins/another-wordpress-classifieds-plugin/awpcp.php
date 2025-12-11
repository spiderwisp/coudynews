<?php
/**
 * @package AWPCP
 *
 * Plugin Name: AWP Classifieds
 * Plugin URI: https://awpcp.com/
 * Description: Run a free or paid classified ads service on your WordPress site.
 * Version: 4.4.3
 * Author: AWP Classifieds Team
 * Author URI: https://awpcp.com/
 * License: GPLv2 or later
 * Text Domain: another-wordpress-classifieds-plugin
 * Domain Path: /languages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'add_filter' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

if ( ! defined( 'AWPCP_FILE' ) ) {
    define( 'AWPCP_FILE', __FILE__ );
}

if ( ! defined( 'AWPCP_BASENAME' ) ) {
    define( 'AWPCP_BASENAME', plugin_basename( AWPCP_FILE ) );
}

if ( ! defined( 'AWPCP_DIR' ) ) {
    define( 'AWPCP_DIR', rtrim( plugin_dir_path( AWPCP_FILE ), '/' ) );
}

if ( ! defined( 'AWPCP_URL' ) ) {
    define( 'AWPCP_URL', rtrim( plugin_dir_url( AWPCP_FILE ), '/' ) );
}

// TODO: Replace usage of this variable with the AWPCP_VERSION constant.
global $awpcp_db_version;
global $awpcp_imagesurl;
global $hascaticonsmodule;
global $hasregionsmodule;
global $hasextrafieldsmodule;

$awpcp_db_version = '4.4.3';

$awpcp_imagesurl      = AWPCP_URL . '/resources/images';
$hascaticonsmodule    = 0;
$hasextrafieldsmodule = $hasextrafieldsmodule ? true : false;
$hasregionsmodule     = $hasregionsmodule ? true : false;

define( 'AWPCP_VERSION', $awpcp_db_version );
define( 'AWPCP_LISTING_POST_TYPE', 'awpcp_listing' );
define( 'AWPCP_CATEGORY_TAXONOMY', 'awpcp_listing_category' );
define( 'AWPCP_LOWEST_FILTER_PRIORITY', 1000000 );

if ( version_compare( phpversion(), '5.6.0', '<' ) ) {
    add_action( 'admin_init', 'awpcp_outdated_php_version', 1 );

    return;
}

$awpcp_autoload_file = AWPCP_DIR . '/vendor/autoload.php';

if ( ! is_readable( $awpcp_autoload_file ) ) {
    add_action( 'admin_init', 'awpcp_missing_autoload', 1 );

    return;
}

/**
 * Legacy code that needs to be properly cleaned.
 */
if ( ! defined( 'AWPCP_REGION_CONTROL_MODULE' ) && file_exists( AWPCP_DIR . '/awpcp_region_control_module.php' ) ) {
    require_once AWPCP_DIR . '/awpcp_region_control_module.php';
    $hasregionsmodule = true;
}

if ( ! defined( 'AWPCP_EXTRA_FIELDS_MODULE' ) && file_exists( AWPCP_DIR . '/awpcp_extra_fields_module.php' ) ) {
    require_once AWPCP_DIR . '/awpcp_extra_fields_module.php';
    $hasextrafieldsmodule = true;
}

if ( file_exists( AWPCP_DIR . '/awpcp_category_icons_module.php' ) ) {
    require_once AWPCP_DIR . '/awpcp_category_icons_module.php';
    $hascaticonsmodule = 1;
}
/* End of legacy code. */

/**
 * BuddyPress normally attaches bp_loaded to plugins_loaded with priority 10.
 * When changing the priorities below, please make sure that modules are
 * still loaded before bp_loaded so that they can register handlers for
 * BuddyPress actions and filters.
 *
 * See bootstrap() and setup() methods that are called inside awpcp_load_main_plugin().
 */
add_action( 'plugins_loaded', 'awpcp_load_main_plugin', 5 );

// TODO: Configure this on plugin's main class?
add_filter( 'redirect_canonical', 'awpcp_redirect_canonical', 10, 2 );

require_once $awpcp_autoload_file;

// TODO: Remove constructor functions from class fields so that we don't
// have to require the files upfront and be able to take advantage of
// the autoloader.
require_once AWPCP_DIR . '/requires.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * @since 4.0.0
 */
function awpcp_outdated_php_version() {
    add_action( 'admin_notices', 'awpcp_render_plugin_required_php_version_notice' );

    awpcp_self_deactivate();
}

/**
 * @since 4.0.0
 */
function awpcp_render_plugin_required_php_version_notice() {
    awpcp_required_php_version_notice( 'AWP Classifieds Plugin' );
}

/**
 * Renders a notice if the Module Updater plugin is needed.
 *
 * @since 4.4
 */
function awpcp_maybe_render_module_updater_notice() {
    if ( ! awpcp_is_module_updater_needed() ) {
        return;
    }

    $message = __( 'The AWP Module Updater plugin is needed to for your premium modules to work properly. You can download it from the <a href="https://awpcp.com/account/downloads/">downloads page</a>.', 'another-wordpress-classifieds-plugin' );
    echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
}

add_action( 'admin_notices', 'awpcp_maybe_render_module_updater_notice' );

/**
 * Check if the user has any premium licenses.
 *
 * @since 4.4
 */
function awpcp_is_module_updater_needed() {
    if ( defined( 'AWP_MODULE_UPDATER_VERSION' ) ) {
        return false;
    }

    $options = get_option( 'awpcp-options' );

    foreach ( $options as $option_key => $option_value ) {
        if ( str_contains( $option_key, '-license-status' ) ) {
            return true;
        }
    }

    return false;
}

/**
 * @since 4.0.0
 */
function awpcp_required_php_version_notice( $product_name ) {
    $content  = '';
    $content .= '<p><strong>';
    $content .= str_replace( '{product_name}', $product_name, esc_html__( '{product_name} was deactivated because it requires PHP 5.6 or newer.', 'another-wordpress-classifieds-plugin' ) );
    $content .= '</strong></p>';
    $content .= '<p>';
    $content .= esc_html__( 'Hi, we noticed that your site is running on an outdated version of PHP. New versions of PHP are faster, more secure and include the features our product requires.', 'another-wordpress-classifieds-plugin' );
    $content .= '</p>';
    $content .= '<p>';
    $content .= wp_kses_post( __( 'You should upgrade to <strong>PHP 5.6</strong>, but if you want your site to also be considerable faster and even more secure, we recommend going up to <strong>PHP 7.2</strong>.', 'another-wordpress-classifieds-plugin' ) );
    $content .= '</p>';
    $content .= '<p>';
    $content .= wp_kses_post( __( 'Please read <a href="https://wordpress.org/support/upgrade-php/">Upgrading PHP</a> to understand more about PHP and how to upgrade.', 'another-wordpress-classifieds-plugin' ) );
    $content .= '</p>';

    awpcp_activation_failed_notice( $content );
}

/**
 * TODO: Organize functions to print/render flash messages, errors and notices.
 * Right now we have too many ways to do the same thing and none of them seem
 * to be good enough.
 *
 * @since 4.0.0
 */
function awpcp_activation_failed_notice( $content ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<div class="notice notice-error">' . $content . '</div>';
}

/**
 * @since 4.0.0
 */
function awpcp_self_deactivate() {
    deactivate_plugins( plugin_basename( AWPCP_FILE ) );

    if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        unset( $_GET['activate'] );
    }
}

/**
 * @since 4.0.0
 */
function awpcp_missing_autoload() {
    add_action( 'admin_notices', 'awpcp_missing_autoload_notice' );

    awpcp_self_deactivate();
}

/**
 * @since 4.0.0
 */
function awpcp_missing_autoload_notice() {
    $message = esc_html__( 'AWP Classifieds Plugin installation is incomplete. Please {support_link}contact support{/support_link}.', 'another-wordpress-classifieds-plugin' );
    $message = str_replace( '{support_link}', '<a href="https://awpcp.com/contact/">', $message );
    $message = str_replace( '{/support_link}', '</a>', $message );

    awpcp_activation_failed_notice( '<p>' . $message . '</p>' );
}

/**
 * @since 4.0.0
 */
function awpcp_load_main_plugin() {
    awpcp();
}

/**
 * @since 4.0.0     Modified to instantiate the container directly.
 * @return AWPCP
 */
function awpcp() {
    global $awpcp;

    if ( ! is_object( $awpcp ) ) {
        $container = new AWPCP_Container(
            [
                'plugin_basename' => plugin_basename( AWPCP_FILE ),
                'SettingsManager' => new AWPCP_SettingsManager(),
            ]
        );

        include AWPCP_DIR . '/includes/constructor-functions.php';

        $awpcp               = new AWPCP( $container );
        $container['Plugin'] = $awpcp;

        // bootstrap() was originally called as soon as the plugin was loaded and
        // setup() was attached to plugins_loaded. Now both methods are called
        // on plugins_loaded.
        $awpcp->bootstrap();
        $awpcp->setup();
    }

    return $awpcp;
}

// TODO: Remove this code after resolving the plugin activation issue. It's duplicated in AWPCP_Installer->activate().
register_activation_hook( __FILE__, function () {
    if ( get_transient( AWPCP_OnboardingWizard::TRANSIENT_NAME ) !== 'no' ) {
        set_transient(
            AWPCP_OnboardingWizard::TRANSIENT_NAME,
            AWPCP_OnboardingWizard::TRANSIENT_VALUE,
            60
        );
    }
} );
