<?php
/**
 * Remote Live Metrics and GitHub Version Provider for Backupino.
 *
 * Implements dynamic GitHub release version fetching and WordPress.org repository stats
 * with transient caching and resilient fallbacks.
 *
 * @package Backupino\Portal\Support
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Provides dynamic versions and live stats for headers, footers, and landing pages.
 */
final class LiveStatsProvider {

	public const GITHUB_REPO_API = 'https://api.github.com/repos/Backupino/plugin/releases/latest';
	public const WPORG_PLUGIN_SLUG = 'backupino';
	public const TRANSIENT_VERSION = 'backupino_github_latest_release';
	public const TRANSIENT_STATS = 'backupino_wporg_live_stats';

	/**
	 * Default fallback baseline values.
	 */
	public const FALLBACK_VERSION = '1.0.3';
	public const FALLBACK_ACTIVE_INSTALLS = 10000;
	public const FALLBACK_RATING = 4.9;
	public const FALLBACK_NUM_RATINGS = 124;
	public const FALLBACK_DOWNLOADS = 28500;

	/**
	 * Initialize hooks and shortcodes.
	 */
	public static function init(): void {
		// Register shortcodes for Elementor, templates, and content
		add_shortcode( 'backupino_version', array( self::class, 'shortcode_version' ) );
		add_shortcode( 'backupino_wp_stats', array( self::class, 'shortcode_stats' ) );
		add_shortcode( 'backupino_header_trust_chip', array( self::class, 'shortcode_header_trust_chip' ) );

		// Clean cache hook (e.g. for deployment or webhook)
		add_action( 'backupino_flush_remote_metrics', array( self::class, 'flush_cache' ) );
	}

	/**
	 * Get latest plugin version dynamically from GitHub Releases or fallback.
	 *
	 * @param bool $with_prefix True to return e.g. "v1.0.3", false for "1.0.3".
	 * @return string
	 */
	public static function get_latest_version( bool $with_prefix = true ): string {
		$cached = get_transient( self::TRANSIENT_VERSION );
		if ( false !== $cached && is_string( $cached ) && ! empty( $cached ) ) {
			return $with_prefix ? ( str_starts_with( $cached, 'v' ) ? $cached : 'v' . $cached ) : ltrim( $cached, 'v' );
		}

		$version = self::fetch_github_release_version();
		if ( empty( $version ) ) {
			// Fallback to local defined constant or baseline
			$version = defined( 'BACKUPINO_PORTAL_VERSION' ) ? BACKUPINO_PORTAL_VERSION : self::FALLBACK_VERSION;
		}

		// Cache for 6 hours (21600 seconds)
		set_transient( self::TRANSIENT_VERSION, $version, 6 * HOUR_IN_SECONDS );

		return $with_prefix ? ( str_starts_with( $version, 'v' ) ? $version : 'v' . $version ) : ltrim( $version, 'v' );
	}

	/**
	 * Fetch latest release tag from GitHub API.
	 *
	 * @return string|null
	 */
	private static function fetch_github_release_version(): ?string {
		if ( ! function_exists( 'wp_remote_get' ) ) {
			return null;
		}

		$response = wp_remote_get(
			self::GITHUB_REPO_API,
			array(
				'timeout'    => 5,
				'user-agent' => 'Backupino-Portal/1.0 (+https://backupino.com)',
				'headers'    => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		$tag = sanitize_text_field( (string) $data['tag_name'] );
		return ! empty( $tag ) ? $tag : null;
	}

	/**
	 * Get live WordPress.org stats (active installs, rating, downloads).
	 *
	 * @return array{
	 *     version: string,
	 *     active_installs: int,
	 *     active_installs_formatted: string,
	 *     active_installs_fa: string,
	 *     rating: float,
	 *     rating_stars: string,
	 *     num_ratings: int,
	 *     downloads: int,
	 *     downloads_formatted: string
	 * }
	 */
	public static function get_wporg_stats(): array {
		$cached = get_transient( self::TRANSIENT_STATS );
		if ( false !== $cached && is_array( $cached ) && isset( $cached['active_installs'] ) ) {
			return $cached;
		}

		$stats = self::fetch_wporg_stats();
		if ( empty( $stats ) ) {
			$stats = array(
				'version'                   => self::FALLBACK_VERSION,
				'active_installs'           => self::FALLBACK_ACTIVE_INSTALLS,
				'active_installs_formatted' => '10,000+',
				'active_installs_fa'        => '+۱۰,۰۰۰',
				'rating'                    => self::FALLBACK_RATING,
				'rating_stars'              => '4.9 ★',
				'num_ratings'               => self::FALLBACK_NUM_RATINGS,
				'downloads'                 => self::FALLBACK_DOWNLOADS,
				'downloads_formatted'       => '28,500+',
			);
		}

		// Cache for 3 hours (10800 seconds)
		set_transient( self::TRANSIENT_STATS, $stats, 3 * HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Fetch plugin information from WordPress.org API.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function fetch_wporg_stats(): ?array {
		if ( ! function_exists( 'wp_remote_post' ) ) {
			return null;
		}

		$url = 'https://api.wordpress.org/plugins/info/1.2/';
		$args = array(
			'timeout' => 5,
			'body'    => array(
				'action'  => 'plugin_information',
				'request' => serialize( (object) array(
					'slug'   => self::WPORG_PLUGIN_SLUG,
					'fields' => array(
						'active_installs' => true,
						'rating'          => true,
						'ratings'         => true,
						'downloaded'      => true,
						'version'         => true,
					),
				) ),
			),
		);

		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = unserialize( $body );

		if ( ! is_object( $data ) || isset( $data->error ) ) {
			return null;
		}

		$installs = isset( $data->active_installs ) ? (int) $data->active_installs : self::FALLBACK_ACTIVE_INSTALLS;
		$rating_raw = isset( $data->rating ) ? (float) $data->rating : 98.0;
		$rating = round( ( $rating_raw / 100 ) * 5, 1 );
		$downloads = isset( $data->downloaded ) ? (int) $data->downloaded : self::FALLBACK_DOWNLOADS;
		$num_ratings = isset( $data->num_ratings ) ? (int) $data->num_ratings : self::FALLBACK_NUM_RATINGS;

		return array(
			'version'                   => ! empty( $data->version ) ? (string) $data->version : self::FALLBACK_VERSION,
			'active_installs'           => $installs,
			'active_installs_formatted' => number_format( $installs ) . '+',
			'active_installs_fa'        => self::to_fa_digits( number_format( $installs ) ) . '+',
			'rating'                    => $rating,
			'rating_stars'              => $rating . ' ★',
			'num_ratings'               => $num_ratings,
			'downloads'                 => $downloads,
			'downloads_formatted'       => number_format( $downloads ) . '+',
		);
	}

	/**
	 * Convert Latin digits to Persian digits for FA formatting.
	 *
	 * @param string $str
	 * @return string
	 */
	private static function to_fa_digits( string $str ): string {
		$latin = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa    = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $latin, $fa, $str );
	}

	/**
	 * Flush cached values immediately.
	 */
	public static function flush_cache(): void {
		delete_transient( self::TRANSIENT_VERSION );
		delete_transient( self::TRANSIENT_STATS );
	}

	/**
	 * Shortcode: [backupino_version format="v|raw"]
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public static function shortcode_version( $atts ): string {
		$a = shortcode_atts(
			array(
				'format' => 'v',
			),
			is_array( $atts ) ? $atts : array()
		);

		$with_prefix = ( 'v' === $a['format'] );
		return esc_html( self::get_latest_version( $with_prefix ) );
	}

	/**
	 * Shortcode: [backupino_wp_stats metric="installs|rating|downloads|stars"]
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public static function shortcode_stats( $atts ): string {
		$a = shortcode_atts(
			array(
				'metric' => 'installs',
				'lang'   => '',
			),
			is_array( $atts ) ? $atts : array()
		);

		$stats = self::get_wporg_stats();
		$is_fa = ( 'fa' === $a['lang'] || str_starts_with( determine_locale(), 'fa' ) );

		switch ( $a['metric'] ) {
			case 'rating':
				return esc_html( (string) $stats['rating'] );
			case 'stars':
				return esc_html( $stats['rating_stars'] );
			case 'downloads':
				return esc_html( $stats['downloads_formatted'] );
			case 'installs':
			default:
				return esc_html( $is_fa ? $stats['active_installs_fa'] : $stats['active_installs_formatted'] );
		}
	}

	/**
	 * Shortcode: [backupino_header_trust_chip]
	 * Renders a glassmorphic micro-badge for Elementor header.
	 *
	 * @return string HTML
	 */
	public static function shortcode_header_trust_chip(): string {
		$stats = self::get_wporg_stats();
		$is_fa = str_starts_with( determine_locale(), 'fa' );
		$installs = $is_fa ? $stats['active_installs_fa'] : $stats['active_installs_formatted'];
		$rating = $stats['rating'];
		$label = $is_fa ? 'نصب فعال در مخزن وردپرس' : 'active installs on WordPress.org';

		ob_start();
		?>
		<a href="https://wordpress.org/plugins/backupino/" target="_blank" rel="noopener noreferrer" class="bk-header-trust-chip" title="<?php echo esc_attr( $label ); ?>">
			<span class="bk-chip-star" aria-hidden="true">★</span>
			<span class="bk-chip-rating"><?php echo esc_html( (string) $rating ); ?></span>
			<span class="bk-chip-divider" aria-hidden="true">/</span>
			<svg class="bk-chip-wp" width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
				<path d="M10 0C4.477 0 0 4.477 0 10c0 5.523 4.477 10 10 10 5.523 0 10-4.477 10-10C20 4.477 15.523 0 10 0zm0 18.2A8.2 8.2 0 1118.2 10a8.2 8.2 0 01-8.2 8.2zM1.8 10a8.2 8.2 0 005.15 7.64L3.48 7.37A8.15 8.15 0 001.8 10zm11.75-.4c0-.98-.35-1.65-.65-2.18-.4-.68-.78-1.25-.78-1.92 0-.75.58-1.45 1.4-1.45.06 0 .13.01.19.02A8.17 8.17 0 0010 1.8a8.16 8.16 0 00-5.74 2.37c.18.01.35.03.49.03 1.25 0 3.19-.16 3.19-.16.65-.04.72.93.08.97 0 0-.66.08-1.39.12l4.43 13.17 2.66-7.98-1.9-5.19c-.65-.04-1.27-.12-1.27-.12-.65-.04-.58-1.01.07-.97 0 0 1.98.16 3.15.16 1.25 0 3.19-.16 3.19-.16.65-.04.73.93.08.97 0 0-.66.08-1.39.12l4.4 13.08A8.18 8.18 0 0013.55 9.6zM10 18.2a8.16 8.16 0 002.58-.42L9.2 8.1 5.8 17.65A8.15 8.15 0 0010 18.2z"/>
			</svg>
			<span class="bk-chip-installs"><?php echo esc_html( $installs ); ?></span>
		</a>
		<?php
		return (string) ob_get_clean();
	}
}
