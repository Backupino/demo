<?php
/**
 * Public download endpoint.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Download;

use Backupino\Core\Capabilities;
use Backupino\Plugin;
use Backupino\Support\Fs;

/**
 * Serves the permanent link from the front end.
 *
 * A visitor without a session is bounced to the login screen and comes back to
 * the very same URL, so the download starts on its own once they sign in.
 */
final class PublicDownload {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_serve' ), 5 );
	}

	/**
	 * Serve the request when the query flag is present.
	 *
	 * @return void
	 */
	public static function maybe_serve(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing flag, not a state change.
		if ( ! isset( $_GET[ DownloadLink::QUERY ] ) ) {
			return;
		}

		$plugin = Plugin::instance();
		$link   = $plugin->download_link();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the key is the credential.
		$key = isset( $_GET[ DownloadLink::KEY_QUERY ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ DownloadLink::KEY_QUERY ] ) ) : '';

		$path = ( new LatestPackage() )->find( $plugin->paths()->packages() );

		$policy = new AccessPolicy( $link );

		switch ( $policy->evaluate( $key, is_user_logged_in(), Capabilities::user_can_manage(), null !== $path ) ) {
			case AccessPolicy::LOGIN:
				wp_safe_redirect( $link->login_url() );
				exit;

			case AccessPolicy::FORBIDDEN:
				wp_die(
					esc_html__( 'This download link is not valid.', 'backupino' ),
					'',
					array( 'response' => 403 )
				);
				break;

			case AccessPolicy::MISSING:
				wp_die(
					esc_html__( 'There is no backup ready to download yet.', 'backupino' ),
					'',
					array( 'response' => 404 )
				);
				break;
		}

		$plugin->logger()->info(
			'Package downloaded through the permanent link.',
			array(
				'user' => get_current_user_id(),
				'file' => basename( (string) $path ),
			)
		);

		Fs::stream_download( (string) $path );
	}
}
