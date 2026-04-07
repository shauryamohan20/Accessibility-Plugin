<?php
/**
 * Fired when the plugin is deleted (not deactivated).
 * Removes all options from the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'qudra_accesskit_settings' );
