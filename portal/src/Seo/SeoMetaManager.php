<?php
/**
 * SEO & GEO Meta Tags, Hreflang, Noindex Guard, and Schema Markup Coordinator.
 *
 * Implements S3-T15: Complete semantic SEO, GEO (Generative Engine Optimization),
 * structured JSON-LD schemas, bidirectional hreflang tags, and noindex guards for
 * private customer dashboard routes (D-038).
 *
 * @package Backupino\Portal\Seo
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Manages SEO and GEO head tags, hreflang links, robots policies, and JSON-LD markup.
 */
final class SeoMetaManager {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'wp_head', array( self::class, 'render_head_tags' ), 1 );
		add_action( 'template_redirect', array( self::class, 'handle_special_routes' ) );
		add_filter( 'wp_robots', array( self::class, 'filter_robots' ) );
	}

	/**
	 * Intercept /llms.txt and /llms-full.txt requests.
	 */
	public static function handle_special_routes(): void {
		$uri = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		if ( empty( $uri ) ) {
			return;
		}

		$is_llms_full = preg_match( '#/llms-full\.txt$#i', $uri );
		$is_llms      = preg_match( '#/llms\.txt$#i', $uri );

		if ( $is_llms_full || $is_llms ) {
			if ( ! headers_sent() ) {
				header( 'Content-Type: text/plain; charset=utf-8' );
				header( 'X-Robots-Tag: index, follow' );
				header( 'Cache-Control: public, max-age=3600' );
			}

			echo LlmsTxtGenerator::generate( (bool) $is_llms_full );
			exit;
		}
	}

	/**
	 * Determine if current page is a sensitive customer portal route.
	 *
	 * @return bool
	 */
	public static function is_sensitive_route(): bool {
		$uri = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );

		// Customer portal and operator dashboard
		if ( false !== strpos( (string) $uri, '/dashboard' ) || false !== strpos( (string) $uri, '/my-account' ) ) {
			return true;
		}

		// WooCommerce cart & checkout
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		// Remote restore preview link or action token
		$query = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY );
		if ( ! empty( $_GET['restore_preview'] ) || ! empty( $_GET['shadow_token'] ) || false !== strpos( (string) $query, 'restore_preview' ) || false !== strpos( (string) $query, 'shadow_token' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter WordPress robots directives.
	 *
	 * @param array<string, bool|string> $robots
	 * @return array<string, bool|string>
	 */
	public static function filter_robots( array $robots ): array {
		if ( self::is_sensitive_route() ) {
			return array(
				'noindex'   => true,
				'nofollow'  => true,
				'noarchive' => true,
			);
		}

		$robots['max-snippet']       = '-1';
		$robots['max-image-preview'] = 'large';
		$robots['max-video-preview'] = '-1';

		return $robots;
	}

	/**
	 * Render SEO, GEO, and JSON-LD tags into wp_head.
	 */
	public static function render_head_tags(): void {
		// 1. Sensitive Route Guard
		if ( self::is_sensitive_route() ) {
			echo "\n\t<!-- Backupino Security: Sensitive Route Protection (D-042) -->\n";
			echo "\t<meta name=\"robots\" content=\"noindex, nofollow, noarchive\" />\n";
			echo "\t<meta name=\"googlebot\" content=\"noindex, nofollow, noarchive\" />\n\n";
			return;
		}

		$current_url = home_url( $_SERVER['REQUEST_URI'] ?? '/' );
		$clean_url   = strtok( $current_url, '?' );
		$is_fa       = str_starts_with( determine_locale(), 'fa' ) || ( isset( $_GET['lang'] ) && 'fa' === $_GET['lang'] );

		$title = $is_fa
			? 'بکاپینو (Backupino) — پلتفرم ابری پشتیبان‌گیری، بازیابی اضطراری و لایسنس وردپرس'
			: 'Backupino — Enterprise WordPress Disaster Recovery, Incremental Backups & Cloud Licensing';

		$desc = $is_fa
			? 'پشتیبان‌گیری ابری و بازیابی دومرحله‌ای سایت‌های وردپرس با امضای رمزنگاری Ed25519، ذخیره‌سازی افزایشی و بدون تحمیل قطعی به سایت زنده.'
			: 'Offline-first, enterprise-grade WordPress disaster recovery and incremental cloud backup platform powered by Ed25519 asymmetric cryptography.';

		// 2. Canonical & Hreflang Tags (Bidirectional)
		echo "\n\t<!-- Backupino Semantic SEO & Bidirectional Hreflang (D-038) -->\n";
		echo "\t<link rel=\"canonical\" href=\"" . esc_url( $clean_url ) . "\" />\n";
		echo "\t<link rel=\"alternate\" hreflang=\"en\" href=\"" . esc_url( add_query_arg( 'lang', 'en', $clean_url ) ) . "\" />\n";
		echo "\t<link rel=\"alternate\" hreflang=\"fa\" href=\"" . esc_url( add_query_arg( 'lang', 'fa', $clean_url ) ) . "\" />\n";
		echo "\t<link rel=\"alternate\" hreflang=\"x-default\" href=\"" . esc_url( $clean_url ) . "\" />\n";

		// 3. OpenGraph & Twitter Cards
		echo "\t<meta property=\"og:type\" content=\"website\" />\n";
		echo "\t<meta property=\"og:title\" content=\"" . esc_attr( $title ) . "\" />\n";
		echo "\t<meta property=\"og:description\" content=\"" . esc_attr( $desc ) . "\" />\n";
		echo "\t<meta property=\"og:url\" content=\"" . esc_url( $current_url ) . "\" />\n";
		echo "\t<meta property=\"og:site_name\" content=\"Backupino\" />\n";
		echo "\t<meta property=\"og:locale\" content=\"" . ( $is_fa ? 'fa_IR' : 'en_US' ) . "\" />\n";
		echo "\t<meta property=\"og:locale:alternate\" content=\"" . ( $is_fa ? 'en_US' : 'fa_IR' ) . "\" />\n";
		echo "\t<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
		echo "\t<meta name=\"twitter:title\" content=\"" . esc_attr( $title ) . "\" />\n";
		echo "\t<meta name=\"twitter:description\" content=\"" . esc_attr( $desc ) . "\" />\n";

		// 4. AI Machine-Readable Reference Link (GEO)
		echo "\t<link rel=\"alternate\" type=\"text/plain\" href=\"" . esc_url( home_url( '/llms.txt' ) ) . "\" title=\"LLMs.txt Technical Knowledge Index\" />\n";

		// 5. JSON-LD Semantic Schema Markup
		$schema = self::build_schema_graph( $is_fa );
		echo "\t<script type=\"application/ld+json\">\n";
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo "\n\t</script>\n\n";
	}

	/**
	 * Build JSON-LD structured schema graph.
	 *
	 * @param bool $is_fa
	 * @return array<string, mixed>
	 */
	public static function build_schema_graph( bool $is_fa = false ): array {
		$site_url = home_url();

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				// SoftwareApplication
				array(
					'@type'               => 'SoftwareApplication',
					'@id'                 => $site_url . '/#software',
					'name'                => 'Backupino',
					'alternateName'       => 'بکاپینو',
					'applicationCategory' => 'SecurityApplication',
					'operatingSystem'     => 'WordPress 5.8+, PHP 7.4-8.4+, MySQL 5.7+ / MariaDB 10.3+',
					'url'                 => $site_url,
					'softwareVersion'     => defined( 'BACKUPINO_PORTAL_VERSION' ) ? BACKUPINO_PORTAL_VERSION : '1.0.2',
					'inLanguage'          => array( 'en', 'fa' ),
					'description'         => $is_fa
						? 'پلتفرم ابری پشتیبان‌گیری افزایشی و بازیابی اضطراری بدون قطعی برای وردپرس.'
						: 'Enterprise WordPress disaster recovery, incremental backups, and cryptographically verified cloud licensing.',
					'offers'              => array(
						array(
							'@type'         => 'Offer',
							'name'          => 'Backupino Free',
							'price'         => '0',
							'priceCurrency' => 'USD',
							'availability'  => 'https://schema.org/InStock',
						),
						array(
							'@type'         => 'Offer',
							'name'          => 'Backupino Personal',
							'price'         => '29.00',
							'priceCurrency' => 'USD',
							'availability'  => 'https://schema.org/InStock',
						),
						array(
							'@type'         => 'Offer',
							'name'          => 'Backupino Freelancer',
							'price'         => '79.00',
							'priceCurrency' => 'USD',
							'availability'  => 'https://schema.org/InStock',
						),
						array(
							'@type'         => 'Offer',
							'name'          => 'Backupino Agency',
							'price'         => '189.00',
							'priceCurrency' => 'USD',
							'availability'  => 'https://schema.org/InStock',
						),
					),
				),

				// Organization
				array(
					'@type'       => 'Organization',
					'@id'         => $site_url . '/#organization',
					'name'        => 'Backupino',
					'alternateName' => 'بکاپینو',
					'url'         => $site_url,
					'logo'        => $site_url . '/assets/images/logo.png',
					'contactPoint'=> array(
						'@type'             => 'ContactPoint',
						'contactType'       => 'customer support',
						'availableLanguage' => array( 'English', 'Persian' ),
					),
				),

				// FAQPage (Machine-readable for Generative Engines)
				array(
					'@type'      => 'FAQPage',
					'@id'        => $site_url . '/#faq',
					'mainEntity' => array(
						array(
							'@type'          => 'Question',
							'name'           => $is_fa ? 'بکاپینو چیست و چگونه کار می‌کند؟' : 'What is Backupino and how does it work?',
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $is_fa
									? 'بکاپینو یک سیستم جامع بازیابی اضطراری و پشتیبان‌گیری افزایشی برای سایت‌های وردپرسی است که با استفاده از رمزنگاری نامتقارن Ed25519 و پروتکل بازیابی در سایه (D-042)، از وقوع قطعی و از دست رفتن داده‌ها جلوگیری می‌کند.'
									: 'Backupino is an enterprise WordPress disaster recovery and incremental backup platform utilizing Ed25519 asymmetric signatures and two-phase shadow table restoration (D-042) to eliminate site downtime.',
							),
						),
						array(
							'@type'          => 'Question',
							'name'           => $is_fa ? 'آیا بکاپینو نیازمند اتصال دائم به اینترنت است؟' : 'Does Backupino require continuous internet connectivity?',
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $is_fa
									? 'خیر، افزونه به صورت آفلاین‌محور (Offline-first) طراحی شده و به لطف امضای رمزنگاری محلی، تا ۷ روز پیاپی بدون نیاز به دسترسی به سرور مرکزی کارایی کامل خود را حفظ می‌کند (D-032).'
									: 'No, Backupino is strictly offline-first. Thanks to local cryptographic verification, installations maintain full capability for up to 7 consecutive days without contacting the portal (D-032).',
							),
						),
						array(
							'@type'          => 'Question',
							'name'           => $is_fa ? 'روش بازگردانی در سایه (Shadow Restore) چیست؟' : 'What is the Two-Phase Shadow Restore protocol?',
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $is_fa
									? 'بازگردانی در سایه داده‌ها را ابتدا در جداول قرنطینه بازسازی کرده و پیش‌نمایش ایزوله ارائه می‌دهد. پس از تایید مالک سایت با تایید دو مرحله‌ای ۲FA، جابجایی اتمیک جداول در کمتر از ۱۰۰ میلی‌ثانیه انجام می‌شود.'
									: 'Shadow restore extracts data into isolated quarantine tables and generates a secure preview link. Upon customer approval with 2FA TOTP verification, an atomic database swap occurs in under 100ms.',
							),
						),
					),
				),
			),
		);
	}
}
