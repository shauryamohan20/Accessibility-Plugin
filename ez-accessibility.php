<?php
/**
 * Plugin Name:       EZ Accessibility
 * Plugin URI:        https://www.greyteck.site/products/ez-accessibility/
 * Description:       A lightweight accessibility toolbar for your visitors: text size, contrast, readable fonts, link highlighting, motion control and more. Multilingual, with automatic RTL support.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Greyteck Studios
 * Author URI:        https://greyteck.site
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ez-accessibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

// ── Constants ────────────────────────────────────────────────────────────────
define( 'EZ_ACCESSIBILITY_VERSION',       '1.0.0' );
define( 'EZ_ACCESSIBILITY_FILE',          __FILE__ );
define( 'EZ_ACCESSIBILITY_DIR',           plugin_dir_path( __FILE__ ) );
define( 'EZ_ACCESSIBILITY_URL',           plugin_dir_url( __FILE__ ) );
define( 'EZ_ACCESSIBILITY_OPTION',        'ez_accessibility_settings' );
define( 'EZ_ACCESSIBILITY_LEGACY_OPTION', 'qudra_accesskit_settings' ); // pre-rename option, migrated once

// ── Includes ─────────────────────────────────────────────────────────────────
require_once EZ_ACCESSIBILITY_DIR . 'includes/class-ez-accessibility-settings.php';
require_once EZ_ACCESSIBILITY_DIR . 'includes/class-ez-accessibility-frontend.php';

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'ez_accessibility_bootstrap' );
function ez_accessibility_bootstrap(): void {
	ez_accessibility_maybe_migrate();
	EZ_Accessibility_Settings::init();
	EZ_Accessibility_Frontend::init();
}

// ── Activation: set defaults ─────────────────────────────────────────────────
register_activation_hook( EZ_ACCESSIBILITY_FILE, 'ez_accessibility_activate' );
function ez_accessibility_activate(): void {
	ez_accessibility_maybe_migrate();
	if ( false === get_option( EZ_ACCESSIBILITY_OPTION ) ) {
		add_option( EZ_ACCESSIBILITY_OPTION, EZ_Accessibility_Settings::defaults() );
	}
}

// ── Carry settings over from the old "Qudra AccessKit" option name ───────────
function ez_accessibility_maybe_migrate(): void {
	if ( false !== get_option( EZ_ACCESSIBILITY_OPTION ) ) {
		return;
	}
	$legacy = get_option( EZ_ACCESSIBILITY_LEGACY_OPTION );
	if ( is_array( $legacy ) ) {
		add_option( EZ_ACCESSIBILITY_OPTION, EZ_Accessibility_Settings::from_legacy( $legacy ) );
		delete_option( EZ_ACCESSIBILITY_LEGACY_OPTION );
	}
}

// ── "Settings" link on the Plugins screen ────────────────────────────────────
add_filter( 'plugin_action_links_' . plugin_basename( EZ_ACCESSIBILITY_FILE ), 'ez_accessibility_action_links' );
function ez_accessibility_action_links( array $links ): array {
	$url = admin_url( 'admin.php?page=' . EZ_Accessibility_Settings::SLUG );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ez-accessibility' ) . '</a>' );
	return $links;
}
