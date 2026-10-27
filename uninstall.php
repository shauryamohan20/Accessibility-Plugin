<?php
/**
 * Fired when the plugin is deleted (not deactivated).
 * Removes all options from the database, on every site of a multisite network.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function ez_accessibility_uninstall_site(): void {
	delete_option( 'ez_accessibility_settings' );
	delete_option( 'qudra_accesskit_settings' );
}

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $ez_accessibility_site_id ) {
		switch_to_blog( $ez_accessibility_site_id );
		ez_accessibility_uninstall_site();
		restore_current_blog();
	}
} else {
	ez_accessibility_uninstall_site();
}
