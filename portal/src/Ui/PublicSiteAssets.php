<?php
/**
 * Public Site Assets & Typography Manager.
 *
 * Implements font Arad enqueue from persian-elementor, theme styling,
 * and design system tokens for the public site.
 *
 * @package Backupino\Portal\Ui
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Ui;

defined( 'ABSPATH' ) || exit;

/**
 * Handles front-end assets for Elementor templates and public pages.
 */
final class PublicSiteAssets {

	/**
	 * Initialize hooks.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ), 20 );
		add_action( 'elementor/frontend/after_enqueue_styles', array( self::class, 'enqueue_font_arad' ) );
	}

	/**
	 * Enqueue front-end CSS, JS, and font Arad.
	 */
	public static function enqueue_assets(): void {
		// Do not interfere with isolated customer dashboard
		if ( CustomerDashboardController::is_customer_dashboard_view() ) {
			return;
		}

		self::enqueue_font_arad();

		$plugin_url = plugin_dir_url( dirname( __DIR__, 1 ) );
		$version    = defined( 'BACKUPINO_PORTAL_VERSION' ) ? BACKUPINO_PORTAL_VERSION : '1.0.3';

		wp_enqueue_style(
			'backupino-public-site',
			$plugin_url . 'assets/css/site.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'backupino-public-site',
			$plugin_url . 'assets/js/site.js',
			array(),
			$version,
			true
		);
	}

	/**
	 * Ensure font Arad is loaded from persian-elementor plugin if present.
	 */
	public static function enqueue_font_arad(): void {
		if ( wp_style_is( 'backupino-font-arad', 'enqueued' ) ) {
			return;
		}

		$font_css_path = WP_PLUGIN_DIR . '/persian-elementor/assets/css/font.css';
		$font_css_url  = plugins_url( 'persian-elementor/assets/css/font.css' );

		if ( file_exists( $font_css_path ) ) {
			wp_enqueue_style(
				'backupino-font-arad',
				$font_css_url,
				array(),
				'1.0.0'
			);
		}
	}
}
