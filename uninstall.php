<?php
/**
 * Fired when the plugin is deleted (not deactivated).
 * Removes all options from the database, on every site of a multisite network.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function incluaccess_uninstall_site(): void {
	delete_option( 'incluaccess_settings' );
	delete_option( 'ez_accessibility_settings' );
	delete_option( 'qudra_accesskit_settings' );
}

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $incluaccess_site_id ) {
		switch_to_blog( $incluaccess_site_id );
		incluaccess_uninstall_site();
		restore_current_blog();
	}
} else {
	incluaccess_uninstall_site();
}
