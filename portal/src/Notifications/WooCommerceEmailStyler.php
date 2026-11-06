<?php
/**
 * WooCommerce Email and Public Download Page Styler using D-036 Tokens.
 *
 * Implements S3-T14: Consumes shared design tokens via `woocommerce_email_styles`
 * and `backupino_download_page_styles` to unify visual styling across
 * transactional WooCommerce notifications and the public download page.
 *
 * @package Backupino\Portal\Notifications
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Injects tokenized CSS into WooCommerce emails and public download landing pages.
 */
final class WooCommerceEmailStyler {

	/**
	 * Register style filter hooks.
	 */
	public static function init(): void {
		// WooCommerce Email Styles filter
		add_filter( 'woocommerce_email_styles', array( self::class, 'filter_woocommerce_email_styles' ), 99, 2 );

		// Public Download Page Styles filter (D-036, P1-T12)
		add_filter( 'backupino_download_page_styles', array( self::class, 'filter_download_page_styles' ), 10, 1 );
	}

	/**
	 * Inject Backupino D-036 Design Tokens and modern styling into WooCommerce emails.
	 *
	 * @param string      $css   Existing WooCommerce email CSS.
	 * @param object|null $email WooCommerce email object.
	 * @return string Modified CSS.
	 */
	public static function filter_woocommerce_email_styles( string $css, $email = null ): string {
		$custom_css = '
/* ==========================================================================
   Backupino D-036 Tokenized WooCommerce Email Styles
   ========================================================================== */

body, html {
	background-color: ' . DesignTokens::SLATE_50 . ' !important;
	font-family: ' . DesignTokens::FONT_FA . ' !important;
	-webkit-font-smoothing: antialiased !important;
	color: ' . DesignTokens::SLATE_800 . ' !important;
}

#wrapper {
	background-color: ' . DesignTokens::SLATE_50 . ' !important;
	padding: 36px 12px !important;
}

#template_container {
	background-color: #ffffff !important;
	border: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
	border-radius: ' . DesignTokens::RADIUS_LG . ' !important;
	box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.05) !important;
	overflow: hidden !important;
	max-width: 600px !important;
}

#template_header {
	background-color: ' . DesignTokens::SLATE_900 . ' !important;
	border-bottom: 0 !important;
	color: #ffffff !important;
	border-top-left-radius: ' . DesignTokens::RADIUS_LG . ' !important;
	border-top-right-radius: ' . DesignTokens::RADIUS_LG . ' !important;
	padding: 24px 28px !important;
}

#template_header h1,
#template_header h1 a {
	color: #ffffff !important;
	font-size: 20px !important;
	font-weight: 800 !important;
	letter-spacing: -0.3px !important;
	margin: 0 !important;
}

#template_header_image img {
	max-width: 160px !important;
	margin-bottom: 12px !important;
}

#template_body {
	padding: 28px !important;
}

#body_content {
	background-color: #ffffff !important;
}

#body_content table td {
	padding: 12px !important;
	font-size: 13px !important;
}

#body_content table td th {
	font-weight: 700 !important;
	color: ' . DesignTokens::SLATE_900 . ' !important;
}

h1, h2, h3, h4 {
	color: ' . DesignTokens::SLATE_900 . ' !important;
	font-weight: 700 !important;
}

h2 {
	font-size: 16px !important;
	margin-top: 24px !important;
	margin-bottom: 12px !important;
	padding-bottom: 8px !important;
	border-bottom: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
}

p {
	color: ' . DesignTokens::SLATE_600 . ' !important;
	font-size: 14px !important;
	line-height: 1.6 !important;
}

a {
	color: ' . DesignTokens::BRAND . ' !important;
	font-weight: 600 !important;
	text-decoration: none !important;
}

a:hover {
	color: ' . DesignTokens::BRAND_HOVER . ' !important;
	text-decoration: underline !important;
}

/* WooCommerce Tables & Order Details */
table.td {
	border: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
	border-radius: ' . DesignTokens::RADIUS_MD . ' !important;
	overflow: hidden !important;
}

table.td th {
	background-color: ' . DesignTokens::SLATE_100 . ' !important;
	color: ' . DesignTokens::SLATE_700 . ' !important;
	border: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
	font-size: 12px !important;
	font-weight: 700 !important;
	text-transform: uppercase !important;
}

table.td td {
	border: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
	color: ' . DesignTokens::SLATE_700 . ' !important;
}

.order_item td {
	border-bottom: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
}

/* Call to Action Buttons */
.button,
a.button {
	display: inline-block !important;
	background-color: ' . DesignTokens::BRAND . ' !important;
	color: #ffffff !important;
	padding: 12px 28px !important;
	border-radius: ' . DesignTokens::RADIUS_MD . ' !important;
	font-weight: 700 !important;
	font-size: 13px !important;
	text-align: center !important;
	text-decoration: none !important;
	box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2) !important;
	border: none !important;
}

.button:hover,
a.button:hover {
	background-color: ' . DesignTokens::BRAND_HOVER . ' !important;
	color: #ffffff !important;
}

/* Footer Styling */
#template_footer {
	background-color: ' . DesignTokens::SLATE_100 . ' !important;
	border-top: 1px solid ' . DesignTokens::SLATE_200 . ' !important;
	border-bottom-left-radius: ' . DesignTokens::RADIUS_LG . ' !important;
	border-bottom-right-radius: ' . DesignTokens::RADIUS_LG . ' !important;
	padding: 18px 24px !important;
	text-align: center !important;
}

#template_footer #credit {
	color: ' . DesignTokens::SLATE_500 . ' !important;
	font-size: 12px !important;
	margin: 0 !important;
}
';

		return $css . "\n" . $custom_css;
	}

	/**
	 * Supply unified design token CSS for the public download page (D-036).
	 *
	 * @param string $css Existing styles or empty string.
	 * @return string Complete styling for the public download page.
	 */
	public static function filter_download_page_styles( string $css = '' ): string {
		$download_page_css = '
/* ==========================================================================
   Backupino D-036 Public Download Page Unified Styling
   ========================================================================== */

' . DesignTokens::get_css_variables() . '

.bk-download-container {
	max-width: 860px;
	margin: 48px auto;
	padding: 0 20px;
	font-family: var(--bk-font-fa);
	direction: rtl;
	color: var(--bk-slate-800);
}

.bk-download-container[dir="ltr"] {
	direction: ltr;
	font-family: var(--bk-font-en);
}

.bk-download-card {
	background: #ffffff;
	border: 1px solid var(--bk-slate-200);
	border-radius: var(--bk-radius);
	box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
	overflow: hidden;
	padding: 36px 32px;
	margin-bottom: 24px;
	backdrop-filter: blur(12px);
}

.bk-download-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	border-bottom: 1px solid var(--bk-slate-200);
	padding-bottom: 24px;
	margin-bottom: 28px;
}

.bk-download-title {
	font-size: 24px;
	font-weight: 800;
	color: var(--bk-slate-900);
	margin: 0 0 6px 0;
	letter-spacing: -0.5px;
}

.bk-download-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	font-weight: 700;
	padding: 4px 12px;
	border-radius: 9999px;
	background: var(--bk-emerald-bg);
	border: 1px solid var(--bk-emerald-border);
	color: var(--bk-emerald);
}

.bk-download-btn-primary {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	background-color: var(--bk-brand);
	color: #ffffff;
	font-weight: 700;
	font-size: 15px;
	padding: 14px 32px;
	border-radius: var(--bk-radius-sm);
	text-decoration: none;
	transition: all 0.2s ease;
	box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
}

.bk-download-btn-primary:hover {
	background-color: var(--bk-brand-hover);
	transform: translateY(-1px);
	box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4);
	color: #ffffff;
}

.bk-checksum-table {
	width: 100%;
	border-collapse: separate;
	border-spacing: 0;
	background: var(--bk-slate-50);
	border: 1px solid var(--bk-slate-200);
	border-radius: var(--bk-radius-sm);
	overflow: hidden;
	margin-top: 24px;
}

.bk-checksum-table td {
	padding: 12px 16px;
	font-size: 13px;
	border-bottom: 1px solid var(--bk-slate-200);
}

.bk-checksum-table tr:last-child td {
	border-bottom: none;
}

.bk-checksum-code {
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
	font-size: 12px;
	background: #ffffff;
	padding: 4px 8px;
	border: 1px solid var(--bk-slate-200);
	border-radius: 4px;
	color: var(--bk-slate-700);
	word-break: break-all;
}
';

		return $css . "\n" . $download_page_css;
	}
}
