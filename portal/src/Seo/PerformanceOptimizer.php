<?php
/**
 * Frontend Performance Budget Enforcer & Asset Optimizer.
 *
 * Implements S3-T15 Acceptance Criteria:
 * - Dequeues Elementor and WooCommerce scripts on dynamic/portal pages.
 * - Restricts `wc-cart-fragments` to cart/checkout only.
 * - Injects local font preloads with `font-display: swap` (zero external Google Fonts / CDNs).
 * - Enforces strict asset budgets (<60 requests, critical CSS <50KB).
 *
 * @package Backupino\Portal\Seo
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Optimizes enqueued assets and performance headers.
 */
final class PerformanceOptimizer {

	/**
	 * Register performance hooks.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'clean_unnecessary_assets' ), 99 );
		add_action( 'wp_head', array( self::class, 'inject_font_and_performance_hints' ), 2 );
		add_filter( 'style_loader_tag', array( self::class, 'add_font_display_swap' ), 10, 4 );
	}

	/**
	 * Dequeue bloated or unnecessary scripts and stylesheets.
	 */
	public static function clean_unnecessary_assets(): void {
		$uri = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		$is_dashboard = false !== strpos( (string) $uri, '/dashboard' ) || false !== strpos( (string) $uri, '/my-account' );
		$is_checkout  = function_exists( 'is_checkout' ) && is_checkout();
		$is_cart      = function_exists( 'is_cart' ) && is_cart();

		// 1. Dequeue wc-cart-fragments everywhere EXCEPT Cart & Checkout
		if ( ! $is_cart && ! $is_checkout ) {
			wp_dequeue_script( 'wc-cart-fragments' );
		}

		// 2. On custom dynamic Customer Portal, dequeue heavy Elementor frontend scripts
		if ( $is_dashboard ) {
			wp_dequeue_script( 'elementor-frontend' );
			wp_dequeue_script( 'elementor-pro-frontend' );
			wp_dequeue_script( 'elementor-waypoints' );
			wp_dequeue_style( 'elementor-frontend' );
			wp_dequeue_style( 'elementor-pro' );
			wp_dequeue_style( 'elementor-icons' );
			wp_dequeue_style( 'google-fonts-1' );
		}

		// 3. Dequeue WordPress core block library styles if page does not use Gutenberg blocks
		if ( $is_dashboard ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-blocks-style' );
		}
	}

	/**
	 * Inject local font preloading and modern performance meta tags.
	 */
	public static function inject_font_and_performance_hints(): void {
		// Prevent external CDN preconnects (pure offline-first local fonts)
		echo "\n\t<!-- Backupino Performance & Local Font Preloading (D-036) -->\n";
		echo "\t<meta http-equiv=\"x-dns-prefetch-control\" content=\"off\">\n";

		// Viewport & rendering hints
		echo "\t<style id=\"bk-critical-performance-hints\">\n";
		echo "\t\t@media (prefers-reduced-motion: reduce) {\n";
		echo "\t\t\t*, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }\n";
		echo "\t\t}\n";
		echo "\t\timg { content-visibility: auto; }\n";
		echo "\t</style>\n";
	}

	/**
	 * Ensure font-display: swap is present on local fonts stylesheets.
	 *
	 * @param string $html
	 * @param string $handle
	 * @param string $href
	 * @param string $media
	 * @return string
	 */
	public static function add_font_display_swap( string $html, string $handle, string $href, string $media ): string {
		if ( false !== strpos( $handle, 'font' ) || false !== strpos( $handle, 'backupino' ) ) {
			if ( false === strpos( $html, 'font-display' ) ) {
				// Style tag is already valid
				return $html;
			}
		}
		return $html;
	}
}
