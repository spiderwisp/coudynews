<?php
/**
 * @package AWPCP\Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Takes an array of rules with script or styles IDs as keys and setting
 * IDs as values. If the value of the setting evaluates to true, then the
 * dependency will be added to the optional array of dependencies.
 *
 * Used to register script and styles that have dependencies that can be
 * toggled from the General > Advanced settings page.
 *
 * @since 4.0.0
 */
function awpcp_maybe_add_asset_dependencies( $rules, $dependencies = [] ) {
    foreach ( $rules as $dependency => $setting ) {
        if ( awpcp_should_enqueue_asset( $setting ) ) {
            $dependencies[] = $dependency;
        }
    }

    return $dependencies;
}

/**
 * @since 4.0.0
 */
function awpcp_should_enqueue_asset( $setting ) {
    $setting_value = get_awpcp_option( $setting );

    if ( $setting_value === 'both' ) {
        return true;
    }

    if ( $setting_value === 'none' ) {
        return false;
    }

    if ( is_admin() ) {
        return $setting_value === 'admin';
    }

    return $setting_value === 'frontend';
}

/**
 * @since 4.0.0
 */
function awpcp_maybe_enqueue_font_awesome_style() {
    if ( awpcp_should_enqueue_asset( 'enqueue-font-awesome-style' ) ) {
        wp_enqueue_style( 'awpcp-font-awesome' );
    }
}

/**
 * See description of use-font-awesome-brands-style setting.
 *
 * @since 4.0.0
 */
function awpcp_add_font_awesome_style_class_for_brands( $icon_class ) {
    $use = get_awpcp_option( 'use-font-awesome-brands-style' );

    if ( $use === 'always' || awpcp_should_enqueue_asset( 'enqueue-font-awesome-style' ) ) {
        return "fab $icon_class";
    }

    return "fa $icon_class";
}

/**
 * @since 4.0.2
 */
function awpcp_enqueue_select2() {
    wp_enqueue_style( 'select2' );
    wp_enqueue_script( awpcp_get_select2_script_handle() );
}

/**
 * Choose between the handle for our copy of the select2 script or the select2
 * fork included in WooCommerce 3.2.0 and newer.
 *
 * @since 4.0.2
 */
function awpcp_get_select2_script_handle() {
    return awpcp_should_register_select2_script() ? 'select2' : 'selectWoo';
}

/**
 * Determine whether we need to enqueue our copy of the select2 script.
 *
 * We should enqueue the script if WooCommerce is not active or the installed
 * version is older than 3.2.0.
 *
 * @since 4.0.2
 */
function awpcp_should_register_select2_script() {
    if ( ! defined( 'WC_VERSION' ) ) {
        return true;
    }

    return version_compare( constant( 'WC_VERSION' ), '3.2.0', '<' );
}
