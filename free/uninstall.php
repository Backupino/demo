<?php
/**
 * Uninstall routine.
 *
 * Runs only when the user deletes the plugin from the WordPress admin, and only
 * removes data when `BACKUPINO_REMOVE_DATA` is defined as true.
 *
 * @package Backupino
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove every trace of the plugin for the current site.
 *
 * @return void
 */
function backupino_uninstall_site(): void {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'backupino_jobs`' );

	delete_option( 'backupino_settings' );
	delete_option( 'backupino_schema_version' );
	delete_option( 'backupino_schedule_state' );
	delete_option( 'backupino_installed_at' );
	delete_option( 'backupino_download_key' );
	delete_option( 'backupino_notify_queue' );

	$uploads = wp_upload_dir( null, false );

	if ( ! empty( $uploads['basedir'] ) ) {
		$dir = rtrim( (string) $uploads['basedir'], '/' ) . '/backupino';

		if ( is_dir( $dir ) ) {
			$items = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $items as $item ) {
				if ( $item->isDir() ) {
					@rmdir( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				} else {
					@unlink( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}

			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
}

if ( defined( 'BACKUPINO_REMOVE_DATA' ) && BACKUPINO_REMOVE_DATA ) {
	if ( is_multisite() ) {
		$backupino_sites = get_sites( array( 'fields' => 'ids' ) );

		foreach ( $backupino_sites as $backupino_site_id ) {
			switch_to_blog( (int) $backupino_site_id );
			backupino_uninstall_site();
			restore_current_blog();
		}
	} else {
		backupino_uninstall_site();
	}
}
