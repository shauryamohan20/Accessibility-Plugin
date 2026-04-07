<?php
/**
 * Plugin Name:       Qudra AccessKit WP
 * Plugin URI:        https://qudra-apn.org/
 * Description:       Lightweight, secure accessibility toolkit with multilingual support (EN/AR/HE), floating panel, contrast modes, font controls, and more.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Qudra
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       qudra-accesskit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

// ── Constants ────────────────────────────────────────────────────────────────
define( 'QAK_VERSION',   '1.3' );
define( 'QAK_FILE',      __FILE__ );
define( 'QAK_DIR',       plugin_dir_path( __FILE__ ) );
define( 'QAK_URL',       plugin_dir_url( __FILE__ ) );
define( 'QAK_OPTION',    'qudra_accesskit_settings' );

// ── Autoload includes ─────────────────────────────────────────────────────────
require_once QAK_DIR . 'includes/class-qak-settings.php';
require_once QAK_DIR . 'includes/class-qak-frontend.php';

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'qak_bootstrap' );
function qak_bootstrap(): void {
	QAK_Settings::init();
	QAK_Frontend::init();
}

// ── Activation: set defaults ──────────────────────────────────────────────────
register_activation_hook( QAK_FILE, 'qak_activate' );
function qak_activate(): void {
	if ( false === get_option( QAK_OPTION ) ) {
		add_option( QAK_OPTION, QAK_Settings::defaults() );
	}
}
