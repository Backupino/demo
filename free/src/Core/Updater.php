<?php
/**
 * GitHub & WordPress.org Release Updater.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Core;

/**
 * Checks for plugin updates directly from the official GitHub repository releases,
 * integrates with WordPress core update transients and auto-update mechanisms.
 */
final class Updater {

	/**
	 * Official GitHub repository.
	 */
	public const GITHUB_REPO = 'Backupino/plugin';

	/**
	 * GitHub latest release API endpoint.
	 */
	public const API_URL = 'https://api.github.com/repos/Backupino/plugin/releases/latest';

	/**
	 * Cache key for the update check transient.
	 */
	public const CACHE_KEY = 'backupino_update_check';

	/**
	 * Register update hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'purge_cache' ), 10, 2 );
	}

	/**
	 * Check for updates against GitHub releases and inject into the WordPress update transient.
	 *
	 * @param mixed $transient WordPress update transient.
	 * @return mixed
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( ! defined( 'BACKUPINO_FILE' ) ) {
			return $transient;
		}

		$plugin_slug = plugin_basename( BACKUPINO_FILE );
		$release     = $this->fetch_latest_release();

		if ( null === $release || empty( $release['version'] ) ) {
			return $transient;
		}

		$current_version = defined( 'BACKUPINO_VERSION' ) ? BACKUPINO_VERSION : '1.0.0';

		if ( version_compare( (string) $release['version'], $current_version, '>' ) ) {
			$item = (object) array(
				'id'            => 'backupino/backupino.php',
				'slug'          => 'backupino',
				'plugin'        => $plugin_slug,
				'new_version'   => (string) $release['version'],
				'url'           => 'https://github.com/Backupino/plugin',
				'package'       => (string) $release['package'],
				'tested'        => '6.7',
				'requires_php'  => '7.4',
				'icons'         => array(
					'svg' => defined( 'BACKUPINO_URL' ) ? BACKUPINO_URL . 'assets/images/icon.svg' : '',
				),
			);

			$transient->response[ $plugin_slug ] = $item;
			unset( $transient->no_update[ $plugin_slug ] );
		} else {
			$item = (object) array(
				'id'           => 'backupino/backupino.php',
				'slug'         => 'backupino',
				'plugin'       => $plugin_slug,
				'new_version'  => $current_version,
				'url'          => 'https://github.com/Backupino/plugin',
				'package'      => '',
				'tested'       => '6.7',
				'requires_php' => '7.4',
			);

			$transient->no_update[ $plugin_slug ] = $item;
		}

		return $transient;
	}

	/**
	 * Supply plugin information for the WordPress core "View details" modal.
	 *
	 * @param mixed  $result Existing result.
	 * @param string $action API action.
	 * @param object $args   Query arguments.
	 * @return mixed
	 */
	public function plugin_info( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || 'backupino' !== $args->slug ) {
			return $result;
		}

		$release         = $this->fetch_latest_release();
		$current_version = defined( 'BACKUPINO_VERSION' ) ? BACKUPINO_VERSION : '1.0.0';
		$version         = ( null !== $release && ! empty( $release['version'] ) ) ? $release['version'] : $current_version;
		$download_url    = ( null !== $release && ! empty( $release['package'] ) ) ? $release['package'] : '';
		$body            = ( null !== $release && ! empty( $release['body'] ) ) ? $release['body'] : '';

		return (object) array(
			'name'           => 'Backupino',
			'slug'           => 'backupino',
			'version'        => $version,
			'author'         => '<a href="https://profiles.wordpress.org/backupino/">Backupino</a>',
			'author_profile' => 'https://profiles.wordpress.org/backupino/',
			'homepage'       => 'https://backupino.com',
			'requires'       => '6.0',
			'tested'         => '6.7',
			'requires_php'   => '7.4',
			'download_link'  => $download_url,
			'sections'       => array(
				'description' => esc_html__( 'Fast, resumable WordPress backups, cloning and migration with a self-contained installer.', 'backupino' ),
				'changelog'   => nl2br( esc_html( $body ) ),
			),
			'banners'        => array(),
		);
	}

	/**
	 * Fetch latest release from GitHub API with caching.
	 *
	 * @return array{version: string, package: string, body: string}|null
	 */
	public function fetch_latest_release(): ?array {
		$cached = get_site_transient( self::CACHE_KEY );

		if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
			return $cached;
		}

		if ( ! function_exists( 'wp_remote_get' ) ) {
			return null;
		}

		$current_version = defined( 'BACKUPINO_VERSION' ) ? BACKUPINO_VERSION : '1.0.0';
		$home            = function_exists( 'home_url' ) ? (string) home_url() : 'https://example.com';

		$response = wp_remote_get(
			self::API_URL,
			array(
				'timeout'    => 10,
				'user-agent' => 'Backupino/' . $current_version . '; ' . $home,
				'headers'    => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache failure for 2 hours to prevent aggressive requests
			set_site_transient( self::CACHE_KEY, array( 'version' => '', 'package' => '', 'body' => '' ), 2 * HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			return null;
		}

		$tag_name    = (string) $body['tag_name'];
		$version     = ltrim( $tag_name, 'vV' );
		$package_url = '';

		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
				if ( preg_match( '/^backupino.*\.zip$/i', $name ) && ! empty( $asset['browser_download_url'] ) ) {
					$package_url = (string) $asset['browser_download_url'];
					break;
				}
			}
		}

		if ( '' === $package_url && ! empty( $body['zipball_url'] ) ) {
			$package_url = (string) $body['zipball_url'];
		}

		$release = array(
			'version' => $version,
			'package' => $package_url,
			'body'    => isset( $body['body'] ) ? (string) $body['body'] : '',
		);

		set_site_transient( self::CACHE_KEY, $release, 12 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Purge update cache on upgrade completion.
	 *
	 * @param mixed $upgrader Upgrader instance.
	 * @param array<string, mixed> $options  Hook options.
	 * @return void
	 */
	public function purge_cache( $upgrader = null, array $options = array() ): void {
		delete_site_transient( self::CACHE_KEY );
	}
}
