<?php
/**
 * Plugin Name:       IncluAccess – Accessibility Toolbar
 * Plugin URI:        https://www.greyteck.site/products/incluaccess/
 * Description:       A lightweight accessibility toolbar for your visitors: text size, contrast, readable fonts, link highlighting, motion control and more. Multilingual, with automatic RTL support.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Greyteck Studios
 * Author URI:        https://greyteck.site
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       incluaccess
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

// ── Constants ────────────────────────────────────────────────────────────────
define( 'INCLUACCESS_VERSION',       '1.0.0' );
define( 'INCLUACCESS_FILE',          __FILE__ );
define( 'INCLUACCESS_DIR',           plugin_dir_path( __FILE__ ) );
define( 'INCLUACCESS_URL',           plugin_dir_url( __FILE__ ) );
define( 'INCLUACCESS_OPTION',        'incluaccess_settings' );

// ── Includes ─────────────────────────────────────────────────────────────────
require_once INCLUACCESS_DIR . 'includes/class-incluaccess-settings.php';
require_once INCLUACCESS_DIR . 'includes/class-incluaccess-frontend.php';

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'incluaccess_bootstrap' );
function incluaccess_bootstrap(): void {
	incluaccess_maybe_migrate();
	IncluAccess_Settings::init();
	IncluAccess_Frontend::init();
}

// ── Activation: set defaults ─────────────────────────────────────────────────
register_activation_hook( INCLUACCESS_FILE, 'incluaccess_activate' );
function incluaccess_activate(): void {
	incluaccess_maybe_migrate();
	if ( false === get_option( INCLUACCESS_OPTION ) ) {
		add_option( INCLUACCESS_OPTION, IncluAccess_Settings::defaults() );
	}
}

// ── Carry settings over from the plugin's earlier names, once ───────────────
// Earlier development builds used the same settings format; "Qudra AccessKit" used an older one.
function incluaccess_maybe_migrate(): void {
	if ( false !== get_option( INCLUACCESS_OPTION ) ) {
		return;
	}
	$previous = get_option( 'ez_accessibility_settings' );
	$qudra    = get_option( 'qudra_accesskit_settings' );
	if ( is_array( $previous ) ) {
		add_option( INCLUACCESS_OPTION, IncluAccess_Settings::sanitize_settings( wp_parse_args( $previous, IncluAccess_Settings::defaults() ) ) );
	} elseif ( is_array( $qudra ) ) {
		add_option( INCLUACCESS_OPTION, IncluAccess_Settings::from_legacy( $qudra ) );
	} else {
		return;
	}
	delete_option( 'ez_accessibility_settings' );
	delete_option( 'qudra_accesskit_settings' );
}

// ── "Settings" link on the Plugins screen ────────────────────────────────────
add_filter( 'plugin_action_links_' . plugin_basename( INCLUACCESS_FILE ), 'incluaccess_action_links' );
function incluaccess_action_links( array $links ): array {
	$url = admin_url( 'admin.php?page=' . IncluAccess_Settings::SLUG );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'incluaccess' ) . '</a>' );
	return $links;
}
