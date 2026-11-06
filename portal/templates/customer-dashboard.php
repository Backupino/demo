<?php
/**
 * Backupino Customer Dedicated SaaS Portal Dashboard.
 *
 * Implements S3-T08, D-006, D-015, D-032, D-036, D-042.
 *
 * @var array<string, mixed>|null $data
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

use Backupino\Portal\Ui\CustomerDashboardService;

defined( 'ABSPATH' ) || exit;

// If loaded directly via WooCommerce template override without passed $data, fetch it now:
if ( empty( $data ) ) {
	$user_id = get_current_user_id();
	$data    = CustomerDashboardService::get_customer_overview( $user_id );
}

$user_profile  = $data['user_profile'] ?? array();
$stats         = $data['stats'] ?? array();
$support_sla   = $data['support_sla'] ?? array();
$licenses      = $data['licenses'] ?? array();
$tickets       = $data['tickets'] ?? array();
$invoices      = $data['invoices'] ?? array();
$settings      = $data['settings'] ?? array();
$is_2fa_active = (bool) ( $data['is_2fa_active'] ?? false );
$nonce         = wp_create_nonce( 'backupino_customer_nonce' );
$ajax_url      = admin_url( 'admin-ajax.php' );

// 100% Robust Locale Resolution:
// 1. URL Query parameter (?lang=en or ?lang=fa)
// 2. Cookie (bk_portal_lang)
// 3. User setting from database
// 4. Default site locale (determine_locale())
$lang_req = sanitize_text_field( (string) ( $_GET['lang'] ?? ( $_COOKIE['bk_portal_lang'] ?? ( $settings['preferred_locale'] ?? '' ) ) ) );
if ( in_array( $lang_req, array( 'en', 'fa' ), true ) ) {
	$preferred_loc = $lang_req;
} else {
	$site_locale   = determine_locale();
	$preferred_loc = str_starts_with( $site_locale, 'fa' ) ? 'fa' : 'en';
}

$is_en = ( 'en' === $preferred_loc );
?>

<div class="bk-portal-app" id="bk-customer-portal" dir="<?php echo $is_en ? 'ltr' : 'rtl'; ?>" data-lang="<?php echo esc_attr( $preferred_loc ); ?>">

	<!-- Dedicated Modern Minimalist SaaS Styling (100% Full-Viewport Clean Layout) -->
	<style>
		/* Eradicate any theme narrow wrappers, page headers, and horizontal scroll */
		html, body {
			overflow-x: hidden !important;
			margin: 0 !important;
			padding: 0 !important;
			width: 100% !important;
		}

		/* Suppress theme page headers, titles ("dashboard"), and default WC navigation */
		.page-header,
		.entry-header,
		.entry-title,
		.page-title,
		.woocommerce-MyAccount-navigation {
			display: none !important;
		}

		/* Force all theme wrappers (Hello Elementor, WooCommerce, WordPress) to true 100% width without margins */
		.site-main,
		.page-content,
		.entry-content,
		.content-area,
		.site-content,
		.container,
		.woocommerce,
		.woocommerce-page,
		.woocommerce-account,
		.woocommerce-MyAccount-content,
		body:not([class*=elementor-page-]) .site-main {
			max-width: 100% !important;
			width: 100% !important;
			min-width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			float: none !important;
			box-sizing: border-box !important;
		}

		/* The Portal Root App: Edge-to-Edge full width, perfectly aligned in both RTL and LTR */
		.bk-portal-app {
			width: 100% !important;
			max-width: 100% !important;
			min-width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			box-sizing: border-box !important;
			position: static !important;
			left: auto !important;
			right: auto !important;
			top: auto !important;
			bottom: auto !important;
			background: #f8fafc;
			color: #0f172a;
			font-family: var(--bk-font);
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			line-height: 1.5;
			-webkit-font-smoothing: antialiased;
		}

		:root {
			--bk-brand: #0284c7;
			--bk-brand-hover: #0369a1;
			--bk-brand-light: #f0f9ff;
			--bk-brand-border: #bae6fd;
			--bk-slate-900: #0f172a;
			--bk-slate-800: #1e293b;
			--bk-slate-700: #334155;
			--bk-slate-600: #475569;
			--bk-slate-500: #64748b;
			--bk-slate-400: #94a3b8;
			--bk-slate-200: #e2e8f0;
			--bk-slate-100: #f1f5f9;
			--bk-slate-50: #f8fafc;
			--bk-emerald: #10b981;
			--bk-emerald-bg: #ecfdf5;
			--bk-emerald-border: #a7f3d0;
			--bk-amber: #f59e0b;
			--bk-amber-bg: #fffbeb;
			--bk-amber-border: #fde68a;
			--bk-rose: #ef4444;
			--bk-rose-bg: #fef2f2;
			--bk-rose-border: #fecaca;
			--bk-indigo: #6366f1;
			--bk-indigo-bg: #eef2ff;
			--bk-indigo-border: #c7d2fe;
			--bk-radius: 12px;
			--bk-radius-sm: 8px;
			--bk-radius-lg: 16px;
			--bk-shadow-sm: 0 1px 2px 0 rgba(15, 23, 42, 0.05);
			--bk-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
			--bk-shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.03);
			--bk-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Vazirmatn", "Shabnam", system-ui, sans-serif;
		}

		.bk-portal-app *, .bk-portal-app *::before, .bk-portal-app *::after {
			box-sizing: border-box;
		}

		/* Header Topbar */
		.bk-topbar {
			background: #ffffff;
			border-bottom: 1px solid var(--bk-slate-200);
			padding: 14px 32px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			position: sticky;
			top: 0;
			z-index: 50;
		}

		.bk-brand-box {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.bk-brand-title {
			font-size: 20px;
			font-weight: 800;
			color: var(--bk-slate-900);
			letter-spacing: -0.5px;
			display: flex;
			align-items: center;
			gap: 8px;
			text-decoration: none;
		}

		.bk-brand-pill {
			background: var(--bk-brand-light);
			color: var(--bk-brand);
			border: 1px solid var(--bk-brand-border);
			font-size: 11px;
			font-weight: 700;
			padding: 2px 8px;
			border-radius: 9999px;
		}

		.bk-topbar-actions {
			display: flex;
			align-items: center;
			gap: 16px;
		}

		/* Language Switch */
		.bk-lang-switch {
			display: flex;
			background: var(--bk-slate-100);
			border: 1px solid var(--bk-slate-200);
			border-radius: 9999px;
			padding: 2px;
			gap: 2px;
		}

		.bk-lang-btn {
			border: none;
			background: transparent;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 12px;
			font-weight: 700;
			color: var(--bk-slate-600);
			cursor: pointer;
			transition: all 0.15s ease;
		}

		.bk-lang-btn.active {
			background: #ffffff;
			color: var(--bk-slate-900);
			box-shadow: var(--bk-shadow-sm);
		}

		.bk-user-pill {
			display: flex;
			align-items: center;
			gap: 8px;
			background: var(--bk-slate-100);
			padding: 4px 12px 4px 4px;
			border-radius: 9999px;
			border: 1px solid var(--bk-slate-200);
		}

		.bk-avatar-circle {
			width: 28px;
			height: 28px;
			border-radius: 50%;
			background: var(--bk-brand);
			color: #ffffff;
			font-weight: bold;
			font-size: 13px;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		/* Workspace Architecture */
		.bk-workspace {
			display: flex;
			flex: 1;
			min-height: calc(100vh - 65px);
		}

		/* Sidebar */
		.bk-sidebar {
			width: 260px;
			background: #ffffff;
			border-inline-end: 1px solid var(--bk-slate-200);
			padding: 20px 12px;
			display: flex;
			flex-direction: column;
			gap: 6px;
			flex-shrink: 0;
		}

		.bk-nav-link {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 10px 14px;
			border-radius: var(--bk-radius-sm);
			color: var(--bk-slate-600);
			text-decoration: none;
			font-size: 13.5px;
			font-weight: 600;
			transition: all 0.15s ease;
			cursor: pointer;
			border: none;
			background: transparent;
			width: 100%;
			text-align: inherit;
		}

		.bk-nav-link:hover {
			background: var(--bk-slate-100);
			color: var(--bk-slate-900);
		}

		.bk-nav-link.active {
			background: var(--bk-brand-light);
			color: var(--bk-brand);
			font-weight: 700;
		}

		.bk-nav-badge {
			margin-inline-start: auto;
			background: var(--bk-slate-200);
			color: var(--bk-slate-700);
			font-size: 11px;
			font-weight: 700;
			padding: 2px 7px;
			border-radius: 9999px;
		}

		.bk-nav-link.active .bk-nav-badge {
			background: var(--bk-brand);
			color: #ffffff;
		}

		/* Viewport Area */
		.bk-viewport {
			flex: 1;
			padding: 32px 40px;
			background: #f8fafc;
			overflow-x: hidden;
		}

		.bk-tab-pane {
			display: none;
		}
		.bk-tab-pane.active {
			display: block;
			animation: bkFadeIn 0.2s ease-out;
		}

		@keyframes bkFadeIn {
			from { opacity: 0; transform: translateY(4px); }
			to { opacity: 1; transform: translateY(0); }
		}

		/* Minimalist Cards */
		.bk-card {
			background: #ffffff;
			border: 1px solid var(--bk-slate-200);
			border-radius: var(--bk-radius);
			padding: 24px;
			box-shadow: var(--bk-shadow-sm);
			margin-bottom: 24px;
		}

		.bk-card-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			flex-wrap: wrap;
			gap: 12px;
		}

		.bk-title {
			font-size: 18px;
			font-weight: 800;
			color: var(--bk-slate-900);
			margin: 0;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		/* Metrics */
		.bk-metric-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 18px;
			margin-bottom: 24px;
		}

		.bk-metric-card {
			background: #ffffff;
			border: 1px solid var(--bk-slate-200);
			border-radius: var(--bk-radius);
			padding: 20px;
			box-shadow: var(--bk-shadow-sm);
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.bk-metric-num {
			font-size: 28px;
			font-weight: 800;
			color: var(--bk-slate-900);
			letter-spacing: -0.5px;
		}

		/* SaaS Table Styles (High-Contrast Slate Header, Rounded Corners) */
		.bk-table-wrap {
			width: 100%;
			overflow-x: auto;
			border-radius: var(--bk-radius);
			border: 1px solid var(--bk-slate-200);
			box-shadow: var(--bk-shadow-sm);
			background: #ffffff;
		}

		.bk-table {
			width: 100% !important;
			border-collapse: separate !important;
			border-spacing: 0 !important;
			font-size: 13.5px;
		}

		.bk-table thead tr th {
			background: #0f172a !important;
			color: #f8fafc !important;
			font-weight: 700;
			font-size: 13px;
			padding: 14px 18px;
			letter-spacing: 0.3px;
			border: none !important;
			text-align: inherit;
		}

		.bk-table tbody tr {
			transition: background-color 0.15s ease;
		}

		.bk-table tbody tr:hover td {
			background-color: #f8fafc;
		}

		.bk-table tbody tr td {
			padding: 14px 18px;
			border-bottom: 1px solid #f1f5f9;
			vertical-align: middle;
			background: #ffffff;
		}

		.bk-table tbody tr:last-child td {
			border-bottom: none;
		}

		/* Badges */
		.bk-pill {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			font-size: 11.5px;
			font-weight: 700;
			padding: 3px 9px;
			border-radius: 9999px;
			line-height: 1.3;
		}
		.bk-pill-success { background: var(--bk-emerald-bg); color: var(--bk-emerald); border: 1px solid var(--bk-emerald-border); }
		.bk-pill-warning { background: var(--bk-amber-bg); color: var(--bk-amber); border: 1px solid var(--bk-amber-border); }
		.bk-pill-danger { background: var(--bk-rose-bg); color: var(--bk-rose); border: 1px solid var(--bk-rose-border); }
		.bk-pill-brand { background: var(--bk-brand-light); color: var(--bk-brand); border: 1px solid var(--bk-brand-border); }
		.bk-pill-indigo { background: var(--bk-indigo-bg); color: var(--bk-indigo); border: 1px solid var(--bk-indigo-border); }
		.bk-pill-neutral { background: var(--bk-slate-100); color: var(--bk-slate-700); border: 1px solid var(--bk-slate-200); }

		/* Storage Destination Pills */
		.bk-storage-local { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
		.bk-storage-cloud { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
		.bk-storage-ftp { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
		.bk-storage-s3 { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

		/* Buttons */
		.bk-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			font-family: inherit;
			font-size: 13px;
			font-weight: 600;
			padding: 8px 16px;
			border-radius: var(--bk-radius-sm);
			border: 1px solid transparent;
			cursor: pointer;
			text-decoration: none;
			transition: all 0.15s ease;
		}
		.bk-btn-primary { background: var(--bk-slate-900); color: #ffffff; }
		.bk-btn-primary:hover { background: #1e293b; color: #ffffff; }
		.bk-btn-brand { background: var(--bk-brand); color: #ffffff; }
		.bk-btn-brand:hover { background: var(--bk-brand-hover); color: #ffffff; }
		.bk-btn-secondary { background: #ffffff; border-color: var(--bk-slate-200); color: var(--bk-slate-700); }
		.bk-btn-secondary:hover { background: var(--bk-slate-100); color: var(--bk-slate-900); }
		.bk-btn-danger { background: var(--bk-rose-bg); border-color: var(--bk-rose-border); color: var(--bk-rose); }
		.bk-btn-danger:hover { background: var(--bk-rose); color: #ffffff; }
		.bk-btn-sm { font-size: 12px; padding: 5px 10px; border-radius: 6px; }

		/* Inputs */
		.bk-input, .bk-select, .bk-textarea {
			width: 100%;
			font-family: inherit;
			font-size: 13.5px;
			padding: 10px 14px;
			border: 1px solid var(--bk-slate-200);
			border-radius: var(--bk-radius-sm);
			background: #ffffff;
			color: var(--bk-slate-900);
			outline: none;
			transition: border-color 0.15s ease, box-shadow 0.15s ease;
			box-sizing: border-box;
		}
		.bk-input:focus, .bk-select:focus, .bk-textarea:focus {
			border-color: var(--bk-brand);
			box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
		}

		/* 2-Sided Chat Messenger Stream */
		.bk-chat-stream {
			display: flex;
			flex-direction: column;
			gap: 16px;
			padding: 12px 0 24px;
		}
		.bk-chat-row {
			display: flex;
			width: 100%;
		}
		.bk-chat-row.user {
			justify-content: flex-start;
		}
		.bk-chat-row.staff {
			justify-content: flex-end;
		}
		.bk-chat-bubble {
			max-width: 80%;
			padding: 16px 20px;
			border-radius: 14px;
			box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05);
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.bk-chat-bubble.user {
			background: #f0fdf4;
			border: 1px solid #bbf7d0;
			border-start-start-radius: 4px;
		}
		.bk-chat-bubble.staff {
			background: #f0f9ff;
			border: 1px solid #bae6fd;
			border-start-end-radius: 4px;
		}
		.bk-chat-meta {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 14px;
			font-size: 12px;
			color: var(--bk-slate-500);
			border-bottom: 1px solid rgba(0, 0, 0, 0.06);
			padding-bottom: 6px;
		}
		.bk-chat-sender {
			display: flex;
			align-items: center;
			gap: 6px;
			font-weight: 700;
			color: var(--bk-slate-900);
		}
		.bk-chat-text {
			font-size: 14px;
			line-height: 1.7;
			color: var(--bk-slate-800);
			white-space: pre-wrap;
			word-break: break-word;
		}
		.bk-chat-attachment {
			margin-top: 6px;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #ffffff;
			border: 1px solid var(--bk-slate-200);
			padding: 6px 12px;
			border-radius: 8px;
			font-size: 12.5px;
			text-decoration: none;
			color: var(--bk-brand-hover);
			font-weight: 600;
			transition: all 0.15s ease;
			max-width: fit-content;
		}
		.bk-chat-attachment:hover {
			background: var(--bk-brand-light);
			border-color: var(--bk-brand);
		}

		/* Modals */
		.bk-modal-backdrop {
			display: none;
			position: fixed;
			top: 0; left: 0; right: 0; bottom: 0;
			background: rgba(15, 23, 42, 0.5);
			backdrop-filter: blur(4px);
			z-index: 99999;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		.bk-modal-backdrop.open { display: flex; }

		.bk-modal-card {
			background: #ffffff;
			border-radius: var(--bk-radius-lg);
			box-shadow: var(--bk-shadow-lg);
			width: 100%;
			max-width: 580px;
			max-height: 90vh;
			overflow-y: auto;
			padding: 28px;
			position: relative;
		}

		@media (max-width: 900px) {
			.bk-workspace { flex-direction: column; }
			.bk-sidebar { width: 100%; border-inline-end: none; border-bottom: 1px solid var(--bk-slate-200); flex-direction: row; overflow-x: auto; padding: 10px; }
			.bk-nav-link { white-space: nowrap; }
			.bk-viewport { padding: 20px 16px; }
		}
	</style>

	<!-- Header Topbar -->
	<header class="bk-topbar">
		<div class="bk-brand-box">
			<a href="#" class="bk-brand-title">
				<span>🛡️</span>
				<span data-i18n="brand_title"><?php echo $is_en ? 'Backupino' : 'بکاپینو'; ?></span>
			</a>
			<span class="bk-brand-pill" data-i18n="brand_pill">Cloud Portal</span>
		</div>

		<div class="bk-topbar-actions">
			<!-- Language Toggle Switch (FA / EN) -->
			<div class="bk-lang-switch">
				<button type="button" class="bk-lang-btn <?php echo ! $is_en ? 'active' : ''; ?>" onclick="bkSetLang('fa');">فا</button>
				<button type="button" class="bk-lang-btn <?php echo $is_en ? 'active' : ''; ?>" onclick="bkSetLang('en');">EN</button>
			</div>

			<!-- SLA Tier Badge -->
			<span class="bk-pill bk-pill-brand" title="<?php echo esc_attr( $support_sla['title'] ?? '' ); ?>">
				⚡ <?php echo esc_html( $support_sla['badge'] ?? 'Standard' ); ?>
			</span>

			<!-- User Profile Pill -->
			<div class="bk-user-pill">
				<div class="bk-avatar-circle">
					<?php echo esc_html( mb_substr( (string) ( $user_profile['display_name'] ?? 'U' ), 0, 1 ) ); ?>
				</div>
				<span style="font-size: 13px; font-weight: 700;">
					<?php echo esc_html( $user_profile['display_name'] ?? '' ); ?>
				</span>
			</div>

			<!-- Logout -->
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="bk-btn bk-btn-secondary bk-btn-sm" title="Logout">
				<span data-i18n="logout"><?php echo $is_en ? 'Logout' : 'خروج'; ?></span> 🚪
			</a>
		</div>
	</header>

	<!-- Workspace (Sidebar + Dynamic Viewport) -->
	<div class="bk-workspace">

		<!-- Sidebar Navigation (5 Focused Tabs) -->
		<aside class="bk-sidebar">
			<button type="button" class="bk-nav-link active" data-tab="overview">
				<span>📊</span>
				<span data-i18n="nav_overview"><?php echo $is_en ? 'Overview' : 'نمای کلی'; ?></span>
			</button>
			<button type="button" class="bk-nav-link" data-tab="licenses">
				<span>🔑</span>
				<span data-i18n="nav_licenses"><?php echo $is_en ? 'Licenses & Sites' : 'لایسنس‌ها و سایت‌ها'; ?></span>
				<span class="bk-nav-badge"><?php echo (int) count( $licenses ); ?></span>
			</button>
			<button type="button" class="bk-nav-link" data-tab="tickets">
				<span>🎫</span>
				<span data-i18n="nav_tickets"><?php echo $is_en ? 'Support Tickets' : 'تیکت‌های پشتیبانی'; ?></span>
				<?php if ( ! empty( $stats['open_tickets_count'] ) ) : ?>
					<span class="bk-nav-badge" style="background: var(--bk-rose); color: #fff;"><?php echo (int) $stats['open_tickets_count']; ?></span>
				<?php endif; ?>
			</button>
			<button type="button" class="bk-nav-link" data-tab="invoices">
				<span>💳</span>
				<span data-i18n="nav_invoices"><?php echo $is_en ? 'Orders & Invoices' : 'سفارش‌ها و فاکتورها'; ?></span>
				<span class="bk-nav-badge"><?php echo (int) count( $invoices ); ?></span>
			</button>
			<button type="button" class="bk-nav-link" data-tab="settings">
				<span>⚙️</span>
				<span data-i18n="nav_settings"><?php echo $is_en ? 'General Settings' : 'تنظیمات عمومی'; ?></span>
			</button>
			<button type="button" class="bk-nav-link" data-tab="security">
				<span>🛡️</span>
				<span data-i18n="nav_security"><?php echo $is_en ? 'Security & 2FA' : 'امنیت و ۲FA'; ?></span>
				<span class="bk-nav-badge" style="<?php echo $is_2fa_active ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;'; ?>">
					<?php echo $is_2fa_active ? '2FA' : 'Off'; ?>
				</span>
			</button>
		</aside>

		<!-- Main Viewport -->
		<main class="bk-viewport">

			<!-- ================= TAB 1: OVERVIEW ================= -->
			<div class="bk-tab-pane active" id="pane-overview">
				<div class="bk-card-header">
					<h2 class="bk-title">
						<span>📊</span>
						<span data-i18n="overview_title"><?php echo $is_en ? 'Dashboard Overview' : 'خلاصه وضعیت و داشبورد'; ?></span>
					</h2>
					<button type="button" class="bk-btn bk-btn-brand bk-btn-sm" onclick="bkSwitchTab('tickets'); bkOpenNewTicketView();">
						➕ <span data-i18n="new_ticket_btn"><?php echo $is_en ? 'New Support Ticket' : 'ارسال تیکت جدید'; ?></span>
					</button>
				</div>

				<!-- Metric Cards -->
				<div class="bk-metric-grid">
					<div class="bk-metric-card">
						<span style="font-size: 13px; color: var(--bk-slate-500); font-weight: 600;" data-i18n="stat_active_licenses"><?php echo $is_en ? 'Active Licenses' : 'لایسنس‌های فعال'; ?></span>
						<div class="bk-metric-num"><?php echo (int) ( $stats['active_licenses'] ?? 0 ); ?> <span style="font-size: 14px; font-weight: normal; color: var(--bk-slate-400);">/ <?php echo (int) ( $stats['total_licenses'] ?? 0 ); ?></span></div>
						<span style="font-size: 12px; color: var(--bk-emerald); font-weight: 600;">🟢 <span data-i18n="stat_active_status"><?php echo $is_en ? 'Valid Active Subscription' : 'سرویس فعال و معتبر'; ?></span></span>
					</div>

					<div class="bk-metric-card">
						<span style="font-size: 13px; color: var(--bk-slate-500); font-weight: 600;" data-i18n="stat_connected_sites"><?php echo $is_en ? 'Connected & Synced Sites' : 'سایت‌های متصل و همگام'; ?></span>
						<div class="bk-metric-num"><?php echo (int) ( $stats['total_sites'] ?? 0 ); ?></div>
						<span style="font-size: 12px; color: var(--bk-slate-500);">🌐 <?php echo (int) ( $stats['staging_sites'] ?? 0 ); ?> <span data-i18n="stat_staging_free"><?php echo $is_en ? 'Free Staging Domains' : 'دامنه Staging رایگان'; ?></span></span>
					</div>

					<div class="bk-metric-card">
						<span style="font-size: 13px; color: var(--bk-slate-500); font-weight: 600;" data-i18n="stat_cloud_backups"><?php echo $is_en ? 'Stored Cloud Backups' : 'بکاپ‌های ابری ذخیره شده'; ?></span>
						<div class="bk-metric-num"><?php echo (int) ( $stats['total_backups'] ?? 0 ); ?></div>
						<span style="font-size: 12px; color: var(--bk-slate-500);">📦 <?php echo esc_html( $stats['cloud_used_formatted'] ?? '0 B' ); ?> <span data-i18n="stat_used_storage"><?php echo $is_en ? 'Used Storage' : 'حجم مصرفی'; ?></span></span>
					</div>

					<div class="bk-metric-card">
						<span style="font-size: 13px; color: var(--bk-slate-500); font-weight: 600;" data-i18n="stat_sla_tier"><?php echo $is_en ? 'SLA Support Guarantee' : 'سطح گارانتی پشتیبانی (SLA)'; ?></span>
						<div class="bk-metric-num" style="color: var(--bk-brand); font-size: 22px;"><?php echo esc_html( $support_sla['badge'] ?? 'Standard' ); ?></div>
						<span style="font-size: 12px; color: var(--bk-slate-500);">⏱️ <?php echo esc_html( $support_sla['title'] ?? '' ); ?></span>
					</div>
				</div>

				<!-- Quick Access to Licenses -->
				<div class="bk-card">
					<div class="bk-card-header" style="margin-bottom: 12px;">
						<h3 style="margin: 0; font-size: 16px; font-weight: 700;">🔑 <span data-i18n="recent_licenses"><?php echo $is_en ? 'Your Licenses' : 'لایسنس‌های شما'; ?></span></h3>
						<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkSwitchTab('licenses');">
							<span data-i18n="view_all"><?php echo $is_en ? 'View & Manage Sites' : 'مشاهده و مدیریت دامنه‌ها'; ?></span> ⬅️
						</button>
					</div>

					<?php if ( empty( $licenses ) ) : ?>
						<p style="color: var(--bk-slate-500); font-size: 13px;" data-i18n="no_licenses"><?php echo $is_en ? 'No licenses registered yet.' : 'هنوز لایسنسی ثبت نکرده‌اید.'; ?></p>
					<?php else : ?>
						<div class="bk-table-wrap">
							<table class="bk-table">
								<thead>
									<tr>
										<th data-i18n="th_plan"><?php echo $is_en ? 'Plan' : 'پلن'; ?></th>
										<th data-i18n="th_key"><?php echo $is_en ? 'License Key' : 'کلید لایسنس'; ?></th>
										<th data-i18n="th_slots"><?php echo $is_en ? 'Domain Slots' : 'اسلات دامنه‌ها'; ?></th>
										<th data-i18n="th_status"><?php echo $is_en ? 'Status' : 'وضعیت'; ?></th>
										<th data-i18n="th_expiry"><?php echo $is_en ? 'Expiry' : 'انقضا'; ?></th>
										<th style="text-align: center;" data-i18n="th_actions"><?php echo $is_en ? 'Actions' : 'عملیات'; ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( array_slice( $licenses, 0, 4 ) as $lic ) : ?>
										<tr>
											<td><span class="bk-pill bk-pill-brand"><?php echo esc_html( $lic['plan_name'] ); ?></span></td>
											<td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html( $lic['masked_key'] ); ?></code></td>
											<td><?php echo (int) $lic['active_prod_count']; ?> / <?php echo (int) $lic['max_domains']; ?></td>
											<td><span class="bk-pill bk-pill-success"><?php echo esc_html( $lic['status'] ); ?></span></td>
											<td><?php echo ! empty( $lic['expires_at'] ) ? esc_html( $lic['expires_at'] ) : ( $is_en ? 'Perpetual' : 'نامحدود' ); ?></td>
											<td style="text-align: center;">
												<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkDrillIntoLicense(<?php echo (int) $lic['id']; ?>);">
													<span data-i18n="btn_manage_sites"><?php echo $is_en ? 'Manage Sites & Backups' : 'مدیریت سایت‌ها و بکاپ‌ها'; ?></span> ⬅️
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- ================= TAB 2: LICENSES & SITES (DRILL-DOWN & FULL DOMAIN OPS & ALL BACKUP DESTINATIONS) ================= -->
			<div class="bk-tab-pane" id="pane-licenses">

				<!-- Level 1: All Licenses Grid View -->
				<div id="bk-licenses-level-1">
					<div class="bk-card-header">
						<div>
							<h2 class="bk-title">🔑 <span data-i18n="licenses_title"><?php echo $is_en ? 'Licenses & Connected Sites' : 'لایسنس‌ها و سایت‌های متصل'; ?></span></h2>
							<p style="margin: 4px 0 0; font-size: 13px; color: var(--bk-slate-500);" data-i18n="licenses_subtitle"><?php echo $is_en ? 'Select a license below to manage connected domains, add domain, change domain, and view all backup locations.' : 'برای مدیریت دامنه‌ها، افزودن دامنه، تغییر دامنه، تنظیمات ابری و بکاپ‌های هر سایت، لایسنس مربوطه را انتخاب کنید.'; ?></p>
						</div>
					</div>

					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
						<?php foreach ( $licenses as $lic ) : ?>
							<div class="bk-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
								<div>
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
										<span class="bk-pill bk-pill-brand" style="font-size: 12px;"><?php echo esc_html( $lic['plan_name'] ); ?></span>
										<span class="bk-pill bk-pill-success"><?php echo esc_html( $lic['status'] ); ?></span>
									</div>

									<div style="margin-bottom: 14px;">
										<div style="font-size: 12px; color: var(--bk-slate-500); margin-bottom: 4px;" data-i18n="license_key_label"><?php echo $is_en ? 'License Key:' : 'کلید لایسنس:'; ?></div>
										<div style="display: flex; align-items: center; gap: 8px;">
											<code style="font-size: 13px; font-weight: bold; background: var(--bk-slate-100); padding: 4px 8px; border-radius: 6px; flex: 1;">
												<?php echo esc_html( $lic['masked_key'] ); ?>
											</code>
											<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkCopyText('<?php echo esc_js( $lic['license_key'] ); ?>');">
												📋
											</button>
										</div>
									</div>

									<div style="font-size: 13px; color: var(--bk-slate-600); display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
										<div>🌐 <span data-i18n="slots_used"><?php echo $is_en ? 'Connected Domains:' : 'دامنه‌های متصل:'; ?></span> <strong><?php echo (int) $lic['active_prod_count']; ?> <?php echo $is_en ? 'of' : 'از'; ?> <?php echo (int) $lic['max_domains']; ?></strong></div>
										<div>⏳ <span data-i18n="expires_label"><?php echo $is_en ? 'Expires:' : 'انقضا:'; ?></span> <strong><?php echo ! empty( $lic['expires_at'] ) ? esc_html( $lic['expires_at'] ) : ( $is_en ? 'Perpetual' : 'نامحدود' ); ?></strong></div>
										<div>💾 <span data-i18n="offline_days"><?php echo $is_en ? 'Offline Cache:' : 'حافظه آفلاین:'; ?></span> <strong><?php echo (int) $lic['offline_days_remaining']; ?> <?php echo $is_en ? 'days' : 'روز'; ?></strong></div>
									</div>
								</div>

								<button type="button" class="bk-btn bk-btn-primary" onclick="bkDrillIntoLicense(<?php echo (int) $lic['id']; ?>);" style="width: 100%;">
									<span data-i18n="btn_enter_license"><?php echo $is_en ? 'Manage Domains & Backups' : 'مدیریت دامنه‌ها و بکاپ‌ها'; ?></span> ⬅️
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Level 2: Specific License Detail View (DOMAINS, ADD/CHANGE/DELETE, ALL BACKUPS DESTINATIONS) -->
				<?php foreach ( $licenses as $lic ) : ?>
					<div id="bk-license-detail-<?php echo (int) $lic['id']; ?>" class="bk-license-drill-view" style="display: none;">
						<!-- Breadcrumb / Back button -->
						<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkReturnToLicensesList();">
									⬅️ <span data-i18n="btn_back_to_licenses"><?php echo $is_en ? 'Back to Licenses List' : 'بازگشت به لیست لایسنس‌ها'; ?></span>
								</button>
								<span style="color: var(--bk-slate-400);">/</span>
								<strong style="color: var(--bk-slate-900); font-size: 14px;"><?php echo esc_html( $lic['plan_name'] ); ?> (<code><?php echo esc_html( $lic['license_key'] ); ?></code>)</strong>
							</div>

							<!-- Button to Add New Domain -->
							<button type="button" class="bk-btn bk-btn-brand bk-btn-sm" onclick="bkToggleAddDomainBox(<?php echo (int) $lic['id']; ?>);">
								➕ <span data-i18n="btn_add_domain"><?php echo $is_en ? 'Add New Domain to this License' : 'افزودن دامنه جدید به این لایسنس'; ?></span>
							</button>
						</div>

						<!-- Inline Add Domain Box (Hidden by default) -->
						<div id="bk-add-domain-box-<?php echo (int) $lic['id']; ?>" class="bk-card" style="display: none; background: #f0fdf4; border-color: #bbf7d0; margin-bottom: 20px;">
							<div class="bk-card-header" style="margin-bottom: 12px;">
								<h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #166534;">➕ <span data-i18n="add_domain_title"><?php echo $is_en ? 'Add New Domain to License' : 'افزودن دامنه جدید به لایسنس'; ?></span></h4>
								<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkToggleAddDomainBox(<?php echo (int) $lic['id']; ?>);" data-i18n="close"><?php echo $is_en ? 'Close' : 'بستن'; ?></button>
							</div>
							<form onsubmit="bkSubmitAddDomain(event, <?php echo (int) $lic['id']; ?>);">
								<div style="display: flex; gap: 10px; flex-wrap: wrap;">
									<input type="text" id="new-domain-input-<?php echo (int) $lic['id']; ?>" class="bk-input" style="flex: 1; min-width: 240px;" required placeholder="<?php echo $is_en ? 'e.g. mydomain.com or staging.site.com' : 'مثال: mydomain.ir یا staging.site.com'; ?>">
									<button type="submit" class="bk-btn bk-btn-brand">
										<span data-i18n="btn_register_domain"><?php echo $is_en ? 'Register & Connect Domain' : 'ثبت و اتصال دامنه'; ?></span>
									</button>
								</div>
								<span style="font-size: 12px; color: #15803d; margin-top: 6px; display: block;" data-i18n="staging_free_hint">
									<?php echo $is_en ? '💡 Staging and local domains do not consume license quota and connect completely free.' : '💡 دامنه‌های آزمایشی (Staging و لوکال‌) شامل سقف لایسنس نبوده و کاملاً رایگان متصل می‌شوند.'; ?>
								</span>
							</form>
						</div>

						<!-- License Top Info Bar -->
						<div class="bk-card" style="padding: 18px 24px; background: #ffffff;">
							<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
								<div>
									<div style="font-size: 12px; color: var(--bk-slate-500);" data-i18n="active_license_key"><?php echo $is_en ? 'Active License Key:' : 'کلید فعال لایسنس:'; ?></div>
									<code style="font-size: 15px; font-weight: 800; color: var(--bk-brand);"><?php echo esc_html( $lic['license_key'] ); ?></code>
									<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" style="margin-inline-start: 8px;" onclick="bkCopyText('<?php echo esc_js( $lic['license_key'] ); ?>');">
										📋 <span data-i18n="copy"><?php echo $is_en ? 'Copy' : 'کپی'; ?></span>
									</button>
								</div>

								<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
									<div style="font-size: 13px;">
										<span data-i18n="snapshot_retention_label"><?php echo $is_en ? '⏱️ Snapshot Retention Period:' : '⏱️ مدت نگهداری اسنپ‌شات‌ها:'; ?></span>
										<select class="bk-select" style="width: auto; padding: 4px 8px; font-size: 13px; display: inline-block; margin-inline-start: 6px;" onchange="bkUpdateRetention(<?php echo (int) $lic['id']; ?>, this.value);">
											<option value="24" <?php selected( $lic['snapshot_retention_h'], 24 ); ?>><?php echo $is_en ? '24 Hours' : '۲۴ ساعت'; ?></option>
											<option value="48" <?php selected( $lic['snapshot_retention_h'], 48 ); ?>><?php echo $is_en ? '48 Hours (Standard)' : '۴۸ ساعت (استاندارد)'; ?></option>
											<option value="72" <?php selected( $lic['snapshot_retention_h'], 72 ); ?>><?php echo $is_en ? '72 Hours' : '۷۲ ساعت'; ?></option>
											<option value="168" <?php selected( $lic['snapshot_retention_h'], 168 ); ?>><?php echo $is_en ? '7 Full Days' : '۷ روز کامل'; ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<!-- Connected Domains List -->
						<h3 style="font-size: 16px; font-weight: 800; color: var(--bk-slate-900); margin: 0 0 16px;" data-i18n="domains_heading">
							<?php echo $is_en ? '🌐 Connected Domains, Management & All Backup Destinations' : '🌐 دامنه‌های متصل، مدیریت و بکاپ‌های همه مخازن'; ?>
						</h3>

						<?php if ( empty( $lic['domains'] ) ) : ?>
							<div class="bk-card" style="text-align: center; padding: 36px;">
								<p style="font-size: 14px; color: var(--bk-slate-500); margin: 0 0 8px;" data-i18n="no_domains_connected"><?php echo $is_en ? 'No domain has been connected to this license yet.' : 'هنوز سایتی با این لایسنس فعال نشده است.'; ?></p>
								<button type="button" class="bk-btn bk-btn-brand" onclick="bkToggleAddDomainBox(<?php echo (int) $lic['id']; ?>);">
									➕ <span data-i18n="btn_add_first_domain"><?php echo $is_en ? 'Add First Domain' : 'افزودن اولین دامنه'; ?></span>
								</button>
							</div>
						<?php else : ?>
							<div style="display: flex; flex-direction: column; gap: 24px;">
								<?php foreach ( $lic['domains'] as $dom ) : ?>
									<div class="bk-card" style="margin-bottom: 0;">
										<!-- Domain Header with Actions (Change domain / Delete domain) -->
										<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--bk-slate-200);">
											<div style="display: flex; align-items: center; gap: 10px;">
												<strong style="font-size: 17px; color: var(--bk-slate-900);"><?php echo esc_html( $dom['domain_name'] ); ?></strong>
												<?php if ( $dom['is_staging'] ) : ?>
													<span class="bk-pill bk-pill-neutral">🧪 Staging (<?php echo $is_en ? 'Free' : 'رایگان'; ?>)</span>
												<?php else : ?>
													<span class="bk-pill bk-pill-success">🚀 Production</span>
												<?php endif; ?>
												<span style="font-size: 12px; color: var(--bk-slate-500);">
													Heartbeat: <?php echo esc_html( $dom['last_heartbeat_at'] ?? ( $is_en ? 'Synced' : 'همگام' ) ); ?>
												</span>
											</div>

											<div style="display: flex; align-items: center; gap: 8px;">
												<!-- Change Domain -->
												<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkToggleChangeDomain(<?php echo (int) $dom['id']; ?>);">
													🔄 <span data-i18n="btn_change_domain"><?php echo $is_en ? 'Change Domain' : 'تغییر دامنه'; ?></span>
												</button>

												<!-- Trigger Backup -->
												<button type="button" class="bk-btn bk-btn-brand bk-btn-sm" onclick="bkTriggerBackup(<?php echo (int) $lic['id']; ?>, <?php echo (int) $dom['id']; ?>);">
													☁️ <span data-i18n="btn_manual_backup"><?php echo $is_en ? 'Manual Backup' : 'تهیه بکاپ دستی'; ?></span>
												</button>

												<!-- Delete Domain Slot -->
												<button type="button" class="bk-btn bk-btn-danger bk-btn-sm" onclick="bkDeleteDomain(<?php echo (int) $dom['id']; ?>, '<?php echo esc_js( $dom['domain_name'] ); ?>');">
													🗑️ <span data-i18n="btn_delete_domain"><?php echo $is_en ? 'Delete Domain' : 'حذف دامنه'; ?></span>
												</button>
											</div>
										</div>

										<!-- Inline Change Domain Box (Hidden by default) -->
										<div id="bk-change-domain-box-<?php echo (int) $dom['id']; ?>" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--bk-radius-sm); padding: 14px; margin-bottom: 16px;">
											<h4 style="margin: 0 0 8px; font-size: 13.5px; font-weight: 700; color: #92400e;">🔄 <span data-i18n="change_domain_for"><?php echo $is_en ? 'Change Domain for' : 'تغییر دامنه برای'; ?></span> <?php echo esc_html( $dom['domain_name'] ); ?></h4>
											<form onsubmit="bkSubmitChangeDomain(event, <?php echo (int) $dom['id']; ?>);">
												<div style="display: flex; gap: 8px; flex-wrap: wrap;">
													<input type="text" id="change-domain-input-<?php echo (int) $dom['id']; ?>" class="bk-input" style="flex: 1; min-width: 220px;" required placeholder="<?php echo $is_en ? 'New domain (e.g. newdomain.com)' : 'نام دامنه جدید (مثال: newdomain.com)'; ?>">
													<input type="text" id="change-domain-reason-<?php echo (int) $dom['id']; ?>" class="bk-input" style="flex: 1; min-width: 200px;" placeholder="<?php echo $is_en ? 'Reason (optional)' : 'علت تغییر دامنه (اختیاری)'; ?>">
													<button type="submit" class="bk-btn bk-btn-brand bk-btn-sm" data-i18n="confirm_change"><?php echo $is_en ? 'Confirm Change' : 'تایید تغییر'; ?></button>
													<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkToggleChangeDomain(<?php echo (int) $dom['id']; ?>);" data-i18n="cancel"><?php echo $is_en ? 'Cancel' : 'انصراف'; ?></button>
												</div>
											</form>
										</div>

										<!-- ALL BACKUPS TABLE FOR THIS DOMAIN (LOCAL + DEDICATED S3 + CUSTOM S3 + FTP) -->
										<div>
											<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
												<h4 style="font-size: 14px; font-weight: 700; margin: 0; color: var(--bk-slate-700);" data-i18n="domain_backups_title">
													<?php echo $is_en ? '📦 All Backups for this site (Host, Dedicated Cloud, FTP & S3):' : '📦 تمام بکاپ‌های این سایت (هاست، ابر اختصاصی، هاست دانلود و S3):'; ?>
												</h4>
												<span style="font-size: 12px; color: var(--bk-slate-500);" data-i18n="storage_destinations_all">
													<?php echo $is_en ? 'Comprehensive multi-storage view' : 'نمایش همه‌جانبه مخازن ذخیره‌سازی'; ?>
												</span>
											</div>

											<?php if ( empty( $dom['backups'] ) ) : ?>
												<p style="font-size: 13px; color: var(--bk-slate-500); margin: 6px 0;" data-i18n="no_backups_for_domain"><?php echo $is_en ? 'No backups recorded for this domain yet.' : 'هنوز هیچ نسخه‌ای برای این دامنه ذخیره نشده است.'; ?></p>
											<?php else : ?>
												<div class="bk-table-wrap">
													<table class="bk-table">
														<thead>
															<tr>
																<th data-i18n="th_package"><?php echo $is_en ? 'Package Name' : 'نام پکیج'; ?></th>
																<th data-i18n="th_storage_dest"><?php echo $is_en ? 'Storage Destination' : 'محل ذخیره‌سازی'; ?></th>
																<th data-i18n="th_size"><?php echo $is_en ? 'Size' : 'حجم'; ?></th>
																<th data-i18n="th_date"><?php echo $is_en ? 'Date Created' : 'تاریخ ایجاد'; ?></th>
																<th style="text-align: center;" data-i18n="th_actions"><?php echo $is_en ? 'Actions' : 'عملیات'; ?></th>
															</tr>
														</thead>
														<tbody>
															<?php foreach ( $dom['backups'] as $b ) : ?>
																<?php
																$provider = $b['storage_provider'] ?? 'dedicated-s3';
																$p_badge  = match ( $provider ) {
																	'local' => array(
																		'label' => $is_en ? '🏢 Local Host' : '🏢 هاست لوکال',
																		'class' => 'bk-storage-local',
																	),
																	'ftp_download', 'ftp_storage' => array(
																		'label' => $is_en ? '📁 FTP Storage' : '📁 هاست دانلود FTP',
																		'class' => 'bk-storage-ftp',
																	),
																	's3_personal', 's3_custom' => array(
																		'label' => $is_en ? '🌐 Custom S3 Cloud' : '🌐 فضای ابری S3 شخصی',
																		'class' => 'bk-storage-s3',
																	),
																	default => array(
																		'label' => $is_en ? '☁️ Backupino Cloud' : '☁️ ابر اختصاصی بکاپینو',
																		'class' => 'bk-storage-cloud',
																	),
																};
																?>
																<tr>
																	<td><strong style="color: var(--bk-slate-900);"><?php echo esc_html( $b['package_name'] ); ?></strong></td>
																	<td>
																		<span class="bk-pill <?php echo esc_attr( $p_badge['class'] ); ?>" data-provider="<?php echo esc_attr( $provider ); ?>">
																			<?php echo esc_html( $p_badge['label'] ); ?>
																		</span>
																	</td>
																	<td><span class="bk-pill bk-pill-neutral"><?php echo esc_html( $b['formatted_size'] ); ?></span></td>
																	<td><?php echo esc_html( $b['created_at'] ); ?></td>
																	<td style="text-align: center;">
																		<a href="<?php echo esc_url( $b['download_url'] ); ?>" class="bk-btn bk-btn-secondary bk-btn-sm">
																			📥 <span data-i18n="download"><?php echo $is_en ? 'Download' : 'دانلود'; ?></span>
																		</a>
																		<button type="button" class="bk-btn bk-btn-primary bk-btn-sm" onclick="bkOpenRestoreModal(<?php echo (int) $lic['id']; ?>, <?php echo (int) $b['id']; ?>, '<?php echo esc_js( $b['package_name'] ); ?>');">
																			🔄 <span data-i18n="restore"><?php echo $is_en ? 'Restore (2FA)' : 'بازیابی (2FA)'; ?></span>
																		</button>
																	</td>
																</tr>
															<?php endforeach; ?>
														</tbody>
													</table>
												</div>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

			</div>

			<!-- ================= TAB 3: SUPPORT TICKETS (DEDICATED FULL-PAGE CHAT MESSENGER - NO MODALS) ================= -->
			<div class="bk-tab-pane" id="pane-tickets">

				<!-- View A: Tickets List View -->
				<div id="bk-tkt-list-view">
					<div class="bk-card-header">
						<div>
							<h2 class="bk-title">🎫 <span data-i18n="tickets_title"><?php echo $is_en ? 'Support Tickets & SLA' : 'تیکت‌های پشتیبانی و SLA'; ?></span></h2>
							<span style="font-size: 13px; color: var(--bk-slate-500);">⏱️ <?php echo esc_html( $support_sla['title'] ?? '' ); ?></span>
						</div>
						<button type="button" class="bk-btn bk-btn-brand" onclick="bkOpenNewTicketView();">
							➕ <span data-i18n="btn_new_ticket"><?php echo $is_en ? 'Open Ticket' : 'ثبت تیکت جدید'; ?></span>
						</button>
					</div>

					<?php if ( empty( $tickets ) ) : ?>
						<div class="bk-card" style="text-align: center; padding: 48px;">
							<p style="font-size: 14px; color: var(--bk-slate-500); margin: 0 0 12px;" data-i18n="no_tickets"><?php echo $is_en ? 'No support tickets opened yet.' : 'تاکنون تیکت پشتیبانی ثبت نکرده‌اید.'; ?></p>
							<button type="button" class="bk-btn bk-btn-brand" onclick="bkOpenNewTicketView();">
								<span data-i18n="btn_submit_first_ticket"><?php echo $is_en ? 'Open First Ticket' : 'ثبت اولین تیکت'; ?></span>
							</button>
						</div>
					<?php else : ?>
						<div class="bk-card" style="padding: 0; overflow: hidden;">
							<div class="bk-table-wrap">
								<table class="bk-table">
									<thead>
										<tr>
											<th data-i18n="th_ticket_num"><?php echo $is_en ? 'Ticket #' : 'شماره تیکت'; ?></th>
											<th data-i18n="th_subject"><?php echo $is_en ? 'Subject' : 'موضوع'; ?></th>
											<th data-i18n="th_priority"><?php echo $is_en ? 'Priority' : 'اولویت'; ?></th>
											<th data-i18n="th_status"><?php echo $is_en ? 'Status' : 'وضعیت'; ?></th>
											<th data-i18n="th_sla_deadline"><?php echo $is_en ? 'SLA Deadline' : 'مهلت SLA'; ?></th>
											<th data-i18n="th_created_at"><?php echo $is_en ? 'Created At' : 'تاریخ ایجاد'; ?></th>
											<th style="text-align: center;" data-i18n="th_actions"><?php echo $is_en ? 'Actions' : 'عملیات'; ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $tickets as $t ) : ?>
											<tr>
												<td><strong style="color: var(--bk-brand);"><?php echo esc_html( $t['ticket_number'] ); ?></strong></td>
												<td>
													<span style="font-weight: 600;"><?php echo esc_html( $t['subject'] ); ?></span>
													<?php if ( $t['replies_count'] > 1 ) : ?>
														<span class="bk-pill bk-pill-neutral" style="font-size: 10px; margin-inline-start: 4px;"><?php echo (int) $t['replies_count']; ?> <?php echo $is_en ? 'messages' : 'پیام'; ?></span>
													<?php endif; ?>
												</td>
												<td><span class="bk-pill bk-pill-brand"><?php echo esc_html( strtoupper( $t['priority'] ) ); ?></span></td>
												<td>
													<?php
													$st_pill = match ( $t['status'] ) {
														'open' => 'bk-pill-warning',
														'waiting_customer' => 'bk-pill-danger',
														'resolved', 'closed' => 'bk-pill-neutral',
														default => 'bk-pill-success',
													};
													$st_label = match ( $t['status'] ) {
														'open' => $is_en ? 'Open' : 'باز',
														'waiting_customer' => $is_en ? 'Waiting for You' : 'در انتظار پاسخ شما',
														'resolved' => $is_en ? 'Resolved' : 'پاسخ داده شده',
														'closed' => $is_en ? 'Closed' : 'بسته شده',
														default => ucfirst( (string) $t['status'] ),
													};
													?>
													<span class="bk-pill <?php echo esc_attr( $st_pill ); ?>" data-status="<?php echo esc_attr( $t['status'] ); ?>">
														<?php echo esc_html( $st_label ); ?>
													</span>
												</td>
												<td>
													<?php if ( $t['sla_breached'] ) : ?>
														<span class="bk-pill bk-pill-danger" data-i18n="sla_breached"><?php echo $is_en ? 'Breached' : 'مهلت رد شده'; ?></span>
													<?php else : ?>
														<span style="font-size: 12px; color: var(--bk-slate-600);"><?php echo esc_html( $t['sla_deadline_at'] ?? ( $is_en ? 'N/A' : 'نامشخص' ) ); ?></span>
													<?php endif; ?>
												</td>
												<td><?php echo esc_html( substr( (string) $t['created_at'], 0, 16 ) ); ?></td>
												<td style="text-align: center;">
													<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkOpenTicketThreadView(<?php echo (int) $t['id']; ?>);">
														💬 <span data-i18n="btn_view_chat"><?php echo $is_en ? 'View & Chat' : 'مشاهده و گفتگو'; ?></span>
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- View B: Dedicated New Ticket Page (NOT A MODAL) -->
				<div id="bk-tkt-new-view" style="display: none;">
					<div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
						<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkReturnToTicketsList();">
							⬅️ <span data-i18n="btn_back_to_tickets"><?php echo $is_en ? 'Back to Tickets List' : 'بازگشت به لیست تیکت‌ها'; ?></span>
						</button>
						<span style="color: var(--bk-slate-400);">/</span>
						<strong style="color: var(--bk-slate-900); font-size: 14px;" data-i18n="new_ticket_heading"><?php echo $is_en ? 'Submit New Support Ticket' : 'ارسال تیکت پشتیبانی جدید'; ?></strong>
					</div>

					<div class="bk-card">
						<h3 style="margin: 0 0 16px; font-size: 17px; font-weight: 800;" data-i18n="modal_new_ticket_title">➕ <?php echo $is_en ? 'Open New Ticket' : 'ثبت تیکت جدید'; ?></h3>

						<form id="bk-new-ticket-page-form" onsubmit="bkSubmitNewTicket(event);">
							<div style="margin-bottom: 16px;">
								<label style="display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 6px;" data-i18n="ticket_subject_label"><?php echo $is_en ? 'Subject:' : 'موضوع تیکت:'; ?></label>
								<input type="text" id="ticket-subject" class="bk-input" required placeholder="<?php echo $is_en ? 'e.g. Question regarding periodic cloud backup' : 'مثال: سوال در خصوص بکاپ‌گیری دوره‌ای'; ?>">
							</div>

							<div style="margin-bottom: 16px;">
								<label style="display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 6px;" data-i18n="ticket_license_label"><?php echo $is_en ? 'Related License (Optional):' : 'لایسنس مرتبط (اختیاری):'; ?></label>
								<select id="ticket-license-id" class="bk-select">
									<option value="0" data-i18n="opt_general_ticket"><?php echo $is_en ? 'General / No specific license' : 'عمومی / بدون لایسنس خاص'; ?></option>
									<?php foreach ( $licenses as $lic ) : ?>
										<option value="<?php echo (int) $lic['id']; ?>"><?php echo esc_html( $lic['plan_name'] ); ?> (<?php echo esc_html( $lic['masked_key'] ); ?>)</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="margin-bottom: 20px;">
								<label style="display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 6px;" data-i18n="ticket_msg_label"><?php echo $is_en ? 'Message:' : 'متن پیام تیکت:'; ?></label>
								<textarea id="ticket-message" class="bk-textarea" rows="8" required placeholder="<?php echo $is_en ? 'Describe your issue or inquiry with error messages and reproduction steps...' : 'مشکل، سوال یا درخواست خود را با ذکر جزئیات و پیام‌های خطا شرح دهید...'; ?>"></textarea>
							</div>

							<div style="display: flex; justify-content: flex-end; gap: 10px;">
								<button type="button" class="bk-btn bk-btn-secondary" onclick="bkReturnToTicketsList();" data-i18n="cancel"><?php echo $is_en ? 'Cancel' : 'انصراف'; ?></button>
								<button type="submit" id="btn-submit-ticket" class="bk-btn bk-btn-brand" data-i18n="submit_ticket"><?php echo $is_en ? 'Submit Ticket' : 'ثبت و ارسال تیکت'; ?></button>
							</div>
						</form>
					</div>
				</div>

				<!-- View C: Dedicated Ticket Chat Messenger Page (Interactive 2-Sided Stream) -->
				<div id="bk-tkt-thread-view" style="display: none;">
					<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
						<div style="display: flex; align-items: center; gap: 10px;">
							<button type="button" class="bk-btn bk-btn-secondary bk-btn-sm" onclick="bkReturnToTicketsList();">
								⬅️ <span data-i18n="btn_back_to_tickets"><?php echo $is_en ? 'Back to Tickets List' : 'بازگشت به لیست تیکت‌ها'; ?></span>
							</button>
							<span style="color: var(--bk-slate-400);">/</span>
							<strong id="thread-view-subject" style="color: var(--bk-slate-900); font-size: 15px;"></strong>
						</div>
						<div style="display: flex; align-items: center; gap: 10px;">
							<div id="thread-view-meta"></div>
							<!-- Close Ticket Button -->
							<button type="button" class="bk-btn bk-btn-danger bk-btn-sm" id="btn-thread-close-ticket" onclick="bkSubmitCloseTicket();" style="display: none;">
								🔒 <span data-i18n="btn_close_ticket"><?php echo $is_en ? 'Close Ticket' : 'بستن تیکت'; ?></span>
							</button>
						</div>
					</div>

					<!-- Closed Notification Banner -->
					<div id="tkt-closed-banner" style="display: none; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; color: #475569; font-weight: 600; text-align: center;">
						🔒 <span data-i18n="ticket_is_closed"><?php echo $is_en ? 'This ticket is closed. Further replies are disabled.' : 'این تیکت بسته شده است و امکان ارسال پاسخ جدید وجود ندارد.'; ?></span>
					</div>

					<div class="bk-card" style="padding: 24px;">
						<!-- 2-Sided Chat stream container -->
						<div id="tkt-messages-container" class="bk-chat-stream">
						</div>

						<!-- Reply Box with File Attachment -->
						<div id="tkt-reply-box-wrap" style="border-top: 1px solid var(--bk-slate-200); padding-top: 18px;">
							<h4 style="margin: 0 0 10px; font-size: 14px; font-weight: 700;" data-i18n="reply_box_heading"><?php echo $is_en ? 'Post Reply to Support Staff:' : 'ارسال پاسخ جدید به کارشناس پشتیبانی:'; ?></h4>
							<form id="bk-reply-ticket-form" onsubmit="bkSubmitTicketReply(event);">
								<input type="hidden" id="reply-ticket-id" value="0">
								<div style="margin-bottom: 12px;">
									<textarea id="reply-ticket-message" class="bk-textarea" rows="4" required placeholder="<?php echo $is_en ? 'Write your reply here...' : 'پاسخ خود را اینجا بنویسید...'; ?>"></textarea>
								</div>
								<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
									<div>
										<label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--bk-slate-600); cursor: pointer;">
											📎 <span data-i18n="attach_file"><?php echo $is_en ? 'Attach File (Optional):' : 'پیوست فایل (اختیاری):'; ?></span>
											<input type="file" id="reply-ticket-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.log,.zip" style="font-size: 12px; margin-inline-start: 6px;">
										</label>
										<span style="display: block; font-size: 11px; color: var(--bk-slate-400); margin-top: 2px;" data-i18n="attach_hint">
											<?php echo $is_en ? 'Allowed formats: Images, PDF, TXT, LOG, ZIP (Max 10MB)' : 'فرمت‌های مجاز: عکس، PDF، TXT، LOG، ZIP (حداکثر ۱۰ مگابایت)'; ?>
										</span>
									</div>
									<div style="display: flex; gap: 10px;">
										<button type="submit" id="btn-reply-ticket" class="bk-btn bk-btn-brand" data-i18n="send_reply"><?php echo $is_en ? 'Send Reply' : 'ارسال پاسخ'; ?></button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>

			</div>

			<!-- ================= TAB 4: INVOICES & BILLING ================= -->
			<div class="bk-tab-pane" id="pane-invoices">
				<div class="bk-card-header">
					<h2 class="bk-title">💳 <span data-i18n="invoices_title"><?php echo $is_en ? 'Orders & Official Invoices' : 'سفارش‌ها و فاکتورهای رسمی'; ?></span></h2>
				</div>

				<?php if ( empty( $invoices ) ) : ?>
					<div class="bk-card" style="text-align: center; padding: 48px;">
						<p style="font-size: 14px; color: var(--bk-slate-500); margin: 0;" data-i18n="no_invoices"><?php echo $is_en ? 'No invoices or orders recorded for this account.' : 'هیچ فاکتور یا سفارشی در این حساب ثبت نشده است.'; ?></p>
					</div>
				<?php else : ?>
					<div class="bk-card" style="padding: 0; overflow: hidden;">
						<div class="bk-table-wrap">
							<table class="bk-table">
								<thead>
									<tr>
										<th data-i18n="th_order_num"><?php echo $is_en ? 'Order #' : 'شماره سفارش'; ?></th>
										<th data-i18n="th_date"><?php echo $is_en ? 'Date Created' : 'تاریخ خرید'; ?></th>
										<th data-i18n="th_amount"><?php echo $is_en ? 'Total Amount' : 'مبلغ فاکتور'; ?></th>
										<th data-i18n="th_status"><?php echo $is_en ? 'Status' : 'وضعیت'; ?></th>
										<th style="text-align: center;" data-i18n="th_actions"><?php echo $is_en ? 'Official Invoice' : 'فاکتور رسمی'; ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $invoices as $inv ) : ?>
										<tr>
											<td><strong>#<?php echo esc_html( (string) $inv['order_number'] ); ?></strong></td>
											<td><?php echo esc_html( $inv['date'] ); ?></td>
											<td><strong style="color: var(--bk-emerald);"><?php echo wp_kses_post( $inv['total'] ); ?></strong></td>
											<td><span class="bk-pill bk-pill-success"><?php echo esc_html( strtoupper( $inv['status'] ) ); ?></span></td>
											<td style="text-align: center;">
												<a href="<?php echo esc_url( $inv['invoice_url'] ); ?>" target="_blank" class="bk-btn bk-btn-secondary bk-btn-sm">
													📄 <span data-i18n="btn_view_invoice"><?php echo $is_en ? 'View Invoice' : 'مشاهده فاکتور'; ?></span>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- ================= TAB 5: GENERAL SETTINGS & NOTIFICATION BOT ================= -->
			<div class="bk-tab-pane" id="pane-settings">
				<div class="bk-card-header">
					<h2 class="bk-title">⚙️ <span data-i18n="general_settings_title"><?php echo $is_en ? 'General Settings, Bot & Upgrades' : 'تنظیمات عمومی، ربات و ارتقای سهمیه'; ?></span></h2>
				</div>

				<!-- 1. Central Notification Bot -->
				<div class="bk-card">
					<h3 style="margin: 0 0 10px; font-size: 16px; font-weight: 700;">🤖 <span data-i18n="bot_settings_title"><?php echo $is_en ? 'Central Notification Bot (Telegram & Bale)' : 'ربات اطلاع‌رسانی مرکزی (Telegram & Bale)'; ?></span></h3>
					<p style="font-size: 13px; color: var(--bk-slate-500); margin: 0 0 18px;" data-i18n="bot_settings_desc">
						<?php echo $is_en ? 'Receive real-time notifications for completed backups, expiry alerts, and errors via Telegram or Bale.' : 'دریافت هشدارهای بلادرنگ تهیه بکاپ، انقضای لایسنس و خطاهای احتمالی از طریق پیام‌رسان‌های تلگرام و بله.'; ?>
					</p>

					<form id="bk-bot-settings-form" onsubmit="bkSaveGeneralSettings(event);">
						<div style="margin-bottom: 16px;">
							<label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
								<input type="checkbox" id="cfg-bot-enabled" <?php checked( ! empty( $settings['central_bot_enabled'] ) ); ?>>
								<span data-i18n="bot_enable_checkbox"><?php echo $is_en ? 'Enable Central Notification Bot' : 'فعال‌سازی ربات اطلاع‌رسانی مرکزی'; ?></span>
							</label>
						</div>

						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 16px;">
							<div>
								<label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" data-i18n="bot_messenger_label"><?php echo $is_en ? 'Messenger:' : 'پیام‌رسان هدف:'; ?></label>
								<select id="cfg-bot-messenger" class="bk-select">
									<option value="telegram" <?php selected( $settings['bot_messenger'] ?? '', 'telegram' ); ?>><?php echo $is_en ? 'Telegram' : 'تلگرام (Telegram)'; ?></option>
									<option value="bale" <?php selected( $settings['bot_messenger'] ?? '', 'bale' ); ?>><?php echo $is_en ? 'Bale Messenger' : 'پیام‌رسان بله (Bale)'; ?></option>
								</select>
							</div>

							<div>
								<label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" data-i18n="bot_chat_id_label"><?php echo $is_en ? 'Chat ID:' : 'شناسه چت (Chat ID):'; ?></label>
								<input type="text" id="cfg-bot-chat-id" class="bk-input" value="<?php echo esc_attr( $settings['bot_chat_id'] ?? '' ); ?>" placeholder="<?php echo $is_en ? '@username or 123456789' : '@username یا 123456789'; ?>">
							</div>
						</div>

						<div style="margin-bottom: 18px; display: flex; flex-direction: column; gap: 8px;">
							<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
								<input type="checkbox" id="cfg-notify-backup" <?php checked( ! empty( $settings['notify_on_backup'] ) ); ?>>
								<span data-i18n="notify_backup_ok"><?php echo $is_en ? 'Notify on successful cloud backup completion' : 'ارسال پیام هنگام تکمیل موفقیت‌آمیز بکاپ‌های ابری'; ?></span>
							</label>
							<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
								<input type="checkbox" id="cfg-notify-expire" <?php checked( ! empty( $settings['notify_on_expire'] ) ); ?>>
								<span data-i18n="notify_expiry_warn"><?php echo $is_en ? 'Notify before license expiration' : 'ارسال پیام هشدار قبل از فرارسیدن موعد تمدید لایسنس'; ?></span>
							</label>
						</div>

						<button type="submit" id="btn-save-settings" class="bk-btn bk-btn-primary">
							<span data-i18n="btn_save_bot"><?php echo $is_en ? 'Save Bot Settings' : 'ذخیره تنظیمات ربات'; ?></span>
						</button>
					</form>
				</div>

				<!-- 2. Quota Upgrade & Plan Request -->
				<div class="bk-card">
					<h3 style="margin: 0 0 10px; font-size: 16px; font-weight: 700;">🚀 <span data-i18n="upgrade_title"><?php echo $is_en ? 'Request License or Quota Upgrade' : 'درخواست ارتقای لایسنس و سهمیه اختصاصی'; ?></span></h3>
					<p style="font-size: 13px; color: var(--bk-slate-500); margin: 0 0 18px;" data-i18n="upgrade_desc">
						<?php echo $is_en ? 'Need more domain slots, extra cloud storage, or an upgrade to Dedicated Agency tier? Submit your inquiry here.' : 'در صورتی که نیازمند اسلات دامنه‌های بیشتر، افزایش سهمیه فضای ابری یا انتقال به پلن اختصاصی Agency هستید، درخواست خود را ارسال فرمایید.'; ?>
					</p>

					<form id="bk-upgrade-request-form" onsubmit="bkSubmitUpgradeRequest(event);">
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 16px;">
							<div>
								<label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" data-i18n="upgrade_type_label"><?php echo $is_en ? 'Upgrade Type:' : 'نوع درخواست ارتقا:'; ?></label>
								<select id="upg-type" class="bk-select">
									<option value="more_domains"><?php echo $is_en ? 'Increase Active Domain Slots' : 'افزایش اسلات دامنه‌های فعال'; ?></option>
									<option value="more_cloud"><?php echo $is_en ? 'Increase Dedicated Cloud Storage (GB)' : 'افزایش فضای ابری اختصاصی (GB)'; ?></option>
									<option value="upgrade_agency"><?php echo $is_en ? 'Upgrade to Dedicated Agency Tier (VIP)' : 'ارتقای کامل به پلن اختصاصی Agency (VIP)'; ?></option>
								</select>
							</div>

							<div>
								<label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" data-i18n="upgrade_license_label"><?php echo $is_en ? 'Target License:' : 'برای لایسنس:'; ?></label>
								<select id="upg-license-id" class="bk-select">
									<option value="0"><?php echo $is_en ? 'All Licenses / New License' : 'تمام لایسنس‌ها / لایسنس جدید'; ?></option>
									<?php foreach ( $licenses as $lic ) : ?>
										<option value="<?php echo (int) $lic['id']; ?>"><?php echo esc_html( $lic['plan_name'] ); ?> (<?php echo esc_html( $lic['masked_key'] ); ?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div style="margin-bottom: 16px;">
							<label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" data-i18n="upgrade_notes_label"><?php echo $is_en ? 'Requirements & Project Notes:' : 'توضیحات و نیازمندی‌های پروژه:'; ?></label>
							<textarea id="upg-notes" class="bk-textarea" rows="3" required placeholder="<?php echo $is_en ? 'e.g. Need 10 additional domain slots and 50GB cloud quota upgrade...' : 'مثال: نیازمند اتصال ۱۰ دامنه جدید و ارتقای سهمیه به ۵۰ گیگابایت هستم...'; ?>"></textarea>
						</div>

						<button type="submit" id="btn-submit-upgrade" class="bk-btn bk-btn-brand">
							<span data-i18n="btn_send_upgrade"><?php echo $is_en ? 'Submit Upgrade Inquiry' : 'ارسال درخواست ارتقا به تیم فروش'; ?></span>
						</button>
					</form>
				</div>
			</div>

			<!-- ================= TAB 6: SECURITY & 2FA ================= -->
			<div class="bk-tab-pane" id="pane-security">
				<div class="bk-card-header">
					<h2 class="bk-title">🛡️ <span data-i18n="security_title"><?php echo $is_en ? 'Security & Two-Factor Authentication (2FA)' : 'امنیت و احراز هویت دومرحله‌ای (2FA)'; ?></span></h2>
				</div>

				<div class="bk-card">
					<h3 style="margin: 0 0 10px; font-size: 16px; font-weight: 700;">
						<?php if ( $is_2fa_active ) : ?>
							✅ <span data-i18n="two_fa_active_title"><?php echo $is_en ? 'Two-Factor Auth (TOTP) is Active' : 'احراز هویت دومرحله‌ای (TOTP) فعال است'; ?></span>
						<?php else : ?>
							⚠️ <span data-i18n="two_fa_inactive_title"><?php echo $is_en ? 'Two-Factor Auth is Not Enabled!' : 'احراز هویت دومرحله‌ای فعال نیست!'; ?></span>
						<?php endif; ?>
					</h3>
					<p style="font-size: 13.5px; color: var(--bk-slate-600); line-height: 1.8; margin-bottom: 20px;" data-i18n="two_fa_desc">
						<?php echo $is_en ? 'Backupino security is built upon RFC 6238 standard and compatible with Google Authenticator, 1Password, and Microsoft Authenticator. Enabling this section is strictly required for executing sensitive restore commands (D-042).' : 'پروتکل امنیتی بکاپینو بر پایه استاندارد RFC 6238 بنا شده است و با برنامه‌های Google Authenticator، Microsoft Authenticator و 1Password کاملاً سازگار است. فعال‌سازی این بخش برای انجام عملیات حیاتی نظیر بازگردانی نسخه‌های پشتیبان (D-042) الزامی است.'; ?>
					</p>

					<?php if ( ! $is_2fa_active ) : ?>
						<button type="button" class="bk-btn bk-btn-brand" onclick="bkStart2FaSetup();">
							🔐 <span data-i18n="btn_enable_2fa"><?php echo $is_en ? 'Start 2FA Activation' : 'آغاز مراحل فعال‌سازی 2FA'; ?></span>
						</button>
					<?php else : ?>
						<div style="background: var(--bk-slate-50); border: 1px solid var(--bk-slate-200); border-radius: var(--bk-radius-sm); padding: 18px;">
							<h4 style="margin: 0 0 8px; font-size: 14px; color: var(--bk-rose);" data-i18n="disable_2fa_heading"><?php echo $is_en ? 'Disable 2FA' : 'غیرفعال‌سازی 2FA'; ?></h4>
							<p style="margin: 0 0 12px; font-size: 13px; color: var(--bk-slate-500);" data-i18n="disable_2fa_warning">
								<?php echo $is_en ? 'Disabling 2FA will block issuing remote restore commands for security compliance.' : 'در صورت غیرفعال‌سازی، قابلیت ارسال دستور بازیابی برای سایت‌های متصل غیرفعال خواهد شد.'; ?>
							</p>
							<div style="display: flex; gap: 8px; max-width: 340px;">
								<input type="text" id="bk-disable-totp-code" class="bk-input" placeholder="<?php echo $is_en ? 'Enter 6-digit code' : 'کد ۶ رقمی را وارد کنید'; ?>" maxlength="6">
								<button type="button" class="bk-btn bk-btn-danger" onclick="bkConfirmDisable2Fa();">
									<span data-i18n="btn_disable"><?php echo $is_en ? 'Disable 2FA' : 'غیرفعال‌سازی'; ?></span>
								</button>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</main>
	</div>

	<!-- ================= MODALS (ONLY FOR SECURITY 2FA RESTORE GATE & SETUP) ================= -->

	<!-- 1. Restore Modal (Strict D-042: Owner only + 2FA Gate) -->
	<div class="bk-modal-backdrop" id="modal-restore-gate">
		<div class="bk-modal-card">
			<div class="bk-card-header" style="border-bottom: 1px solid var(--bk-slate-200); padding-bottom: 14px; margin-bottom: 18px;">
				<h3 style="margin: 0; font-size: 18px; font-weight: 800;" data-i18n="modal_restore_title"><?php echo $is_en ? '🔄 Confirm Restore Command' : '🔄 تایید صدور دستور بازیابی (Restore)'; ?></h3>
				<button type="button" onclick="bkCloseModal('modal-restore-gate');" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--bk-slate-400);">&times;</button>
			</div>

			<div style="background: var(--bk-rose-bg); border: 1px solid var(--bk-rose-border); border-radius: var(--bk-radius-sm); padding: 14px; margin-bottom: 18px;">
				<p style="margin: 0; font-size: 13px; color: var(--bk-rose); font-weight: 600;" data-i18n="restore_warning">
					<?php echo $is_en ? '⚠️ Warning: Restoring will overwrite existing website data. 6-digit 2FA code is strictly required (D-042).' : '⚠️ هشدار: بازیابی این نسخه باعث جایگزینی داده‌های فعلی سایت خواهد شد. جهت امنیت، وارد کردن کد ۶ رقمی برنامه تایید دو مرحله‌ای الزامی است.'; ?>
				</p>
			</div>

			<p style="font-size: 13.5px; margin: 0 0 12px;"><span data-i18n="selected_package"><?php echo $is_en ? 'Selected Package:' : 'بسته انتخابی:'; ?></span> <strong id="restore-package-name"></strong></p>

			<form id="bk-restore-form" onsubmit="bkSubmitRestore(event);">
				<input type="hidden" id="restore-license-id" value="0">
				<input type="hidden" id="restore-backup-id" value="0">

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;" data-i18n="totp_label"><?php echo $is_en ? 'Two-Factor Code (TOTP):' : 'کد تایید دو مرحله‌ای (TOTP):'; ?></label>
					<input type="text" id="restore-totp-code" class="bk-input" required maxlength="6" placeholder="123456" style="font-size: 22px; letter-spacing: 6px; text-align: center; font-weight: bold;">
				</div>

				<div style="display: flex; justify-content: flex-end; gap: 10px;">
					<button type="button" class="bk-btn bk-btn-secondary" onclick="bkCloseModal('modal-restore-gate');" data-i18n="cancel"><?php echo $is_en ? 'Cancel' : 'انصراف'; ?></button>
					<button type="submit" id="btn-submit-restore" class="bk-btn bk-btn-danger" data-i18n="confirm_restore"><?php echo $is_en ? 'Verify & Start Restore' : 'تایید و شروع بازیابی'; ?></button>
				</div>
			</form>
		</div>
	</div>

	<!-- 2. 2FA Setup Wizard Modal -->
	<div class="bk-modal-backdrop" id="modal-2fa-setup">
		<div class="bk-modal-card">
			<div class="bk-card-header" style="border-bottom: 1px solid var(--bk-slate-200); padding-bottom: 14px; margin-bottom: 18px;">
				<h3 style="margin: 0; font-size: 18px; font-weight: 800;" data-i18n="modal_2fa_title"><?php echo $is_en ? '🔐 Enable Two-Factor Authentication (2FA)' : '🔐 فعال‌سازی احراز هویت دو مرحله‌ای (2FA)'; ?></h3>
				<button type="button" onclick="bkCloseModal('modal-2fa-setup');" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--bk-slate-400);">&times;</button>
			</div>

			<div style="text-align: center; margin-bottom: 20px;">
				<p style="font-size: 13px; color: var(--bk-slate-600); margin: 0 0 14px;" data-i18n="scan_qr_desc">
					<?php echo $is_en ? 'Scan the QR code below with Google Authenticator, 1Password, or Microsoft Authenticator:' : 'بارکد زیر را با برنامه Google Authenticator، 1Password یا Microsoft Authenticator اسکن نمایید:'; ?>
				</p>
				<div style="display: inline-block; padding: 10px; background: #fff; border: 1px solid var(--bk-slate-200); border-radius: var(--bk-radius-sm);">
					<img id="bk-2fa-qr-img" src="" alt="QR Code" style="width: 180px; height: 180px; display: block;">
				</div>
				<div style="margin-top: 12px;">
					<span style="font-size: 12px; color: var(--bk-slate-500);" data-i18n="manual_key_desc"><?php echo $is_en ? 'Or enter the secret key manually:' : 'یا کلید زیر را به صورت دستی وارد نمایید:'; ?></span><br>
					<code id="bk-2fa-secret-text" style="font-size: 14px; font-weight: bold; background: var(--bk-slate-100); padding: 4px 10px; border-radius: 6px;"></code>
				</div>
			</div>

			<form id="bk-2fa-verify-form" onsubmit="bkConfirmEnable2Fa(event);">
				<div style="margin-bottom: 20px;">
					<label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;" data-i18n="enter_6_digit_code"><?php echo $is_en ? 'Enter the 6-digit verification code from your app:' : 'کد ۶ رقمی نمایش داده شده در اپلیکیشن را وارد کنید:'; ?></label>
					<input type="text" id="bk-2fa-input-code" class="bk-input" required maxlength="6" placeholder="000000" style="font-size: 22px; letter-spacing: 6px; text-align: center; font-weight: bold;">
				</div>

				<div style="display: flex; justify-content: flex-end; gap: 10px;">
					<button type="button" class="bk-btn bk-btn-secondary" onclick="bkCloseModal('modal-2fa-setup');" data-i18n="cancel"><?php echo $is_en ? 'Cancel' : 'انصراف'; ?></button>
					<button type="submit" id="btn-verify-2fa" class="bk-btn bk-btn-primary" data-i18n="btn_confirm_2fa"><?php echo $is_en ? 'Verify & Complete Setup' : 'بررسی و فعال‌سازی نهایی'; ?></button>
				</div>
			</form>
		</div>
	</div>

	<!-- 3. Recovery Codes Modal -->
	<div class="bk-modal-backdrop" id="modal-recovery-codes">
		<div class="bk-modal-card">
			<div class="bk-card-header" style="border-bottom: 1px solid var(--bk-slate-200); padding-bottom: 14px; margin-bottom: 18px;">
				<h3 style="margin: 0; font-size: 18px; font-weight: 800;" data-i18n="modal_recovery_title"><?php echo $is_en ? '🔑 Emergency Recovery Codes' : '🔑 کدهای بازیابی اضطراری (Recovery Codes)'; ?></h3>
				<button type="button" onclick="location.reload();" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--bk-slate-400);">&times;</button>
			</div>

			<div style="background: var(--bk-amber-bg); border: 1px solid var(--bk-amber-border); border-radius: var(--bk-radius-sm); padding: 14px; margin-bottom: 18px;">
				<p style="margin: 0; font-size: 13px; color: var(--bk-amber); font-weight: 600;" data-i18n="recovery_codes_desc">
					<?php echo $is_en ? '⚠️ Save these single-use recovery codes in a secure location.' : '⚠️ کدهای زیر کدهای بازیابی یک‌بار مصرف شما هستند. آنها را در مکانی امن ذخیره کنید.'; ?>
				</p>
			</div>

			<div id="bk-recovery-codes-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px;">
			</div>

			<div style="display: flex; justify-content: space-between; align-items: center;">
				<button type="button" class="bk-btn bk-btn-secondary" onclick="bkCopyRecoveryCodes();">
					📋 <span data-i18n="copy_all_codes"><?php echo $is_en ? 'Copy All Codes' : 'کپی تمام کدها'; ?></span>
				</button>
				<button type="button" class="bk-btn bk-btn-primary" onclick="location.reload();">
					<span data-i18n="saved_done"><?php echo $is_en ? 'Saved, Done' : 'ذخیره کردم، پایان'; ?></span>
				</button>
			</div>
		</div>
	</div>

	<!-- Custom Client-Side Controller & Real-Time Multilingual Engine -->
	<script>
		const BK_PORTAL_NONCE = '<?php echo esc_js( $nonce ); ?>';
		const BK_AJAX_URL     = '<?php echo esc_js( $ajax_url ); ?>';
		let bkCurrentRecoveryCodes = [];

		/**
		 * 100% Complete Language Switching Engine.
		 * Switches Cookie, LocalStorage, Profile Setting and reloads the page in target language.
		 */
		function bkSetLang(lang) {
			if (!lang || (lang !== 'en' && lang !== 'fa')) {
				lang = 'en';
			}

			// 1. Set Cookie valid for root domain (1 Year)
			document.cookie = "bk_portal_lang=" + encodeURIComponent(lang) + "; path=/; max-age=31536000; SameSite=Lax";
			localStorage.setItem('bk_portal_lang', lang);

			// 2. Persist to DB asynchronously
			const fd = new URLSearchParams();
			fd.append('action', 'backupino_customer_save_settings');
			fd.append('_wpnonce', BK_PORTAL_NONCE);
			fd.append('preferred_locale', lang);
			fetch(BK_AJAX_URL, { method: 'POST', body: fd }).catch(() => {});

			// 3. Reload current page with ?lang= to give user a 100% clean, native, server-rendered view
			const url = new URL(window.location.href);
			url.searchParams.set('lang', lang);
			window.location.href = url.toString();
		}

		// Tab Navigation
		function bkSwitchTab(tabName) {
			document.querySelectorAll('.bk-nav-link').forEach(btn => {
				btn.classList.toggle('active', btn.dataset.tab === tabName);
			});
			document.querySelectorAll('.bk-tab-pane').forEach(pane => {
				pane.classList.toggle('active', pane.id === 'pane-' + tabName);
			});
			if (tabName === 'licenses') {
				bkReturnToLicensesList();
			} else if (tabName === 'tickets') {
				bkReturnToTicketsList();
			}
			if (history.pushState) {
				history.pushState(null, null, '#tab-' + tabName);
			}
		}

		// Drill-Down: License -> Domains & Backups
		function bkDrillIntoLicense(licId) {
			bkSwitchTab('licenses');
			document.getElementById('bk-licenses-level-1').style.display = 'none';
			document.querySelectorAll('.bk-license-drill-view').forEach(v => v.style.display = 'none');
			const target = document.getElementById('bk-license-detail-' + licId);
			if (target) {
				target.style.display = 'block';
				window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
			}
		}

		function bkReturnToLicensesList() {
			document.querySelectorAll('.bk-license-drill-view').forEach(v => v.style.display = 'none');
			const l1 = document.getElementById('bk-licenses-level-1');
			if (l1) l1.style.display = 'block';
		}

		// Toggle Add Domain Box
		function bkToggleAddDomainBox(licId) {
			const box = document.getElementById('bk-add-domain-box-' + licId);
			if (box) {
				box.style.display = box.style.display === 'none' ? 'block' : 'none';
			}
		}

		// Submit Add Domain
		function bkSubmitAddDomain(e, licId) {
			e.preventDefault();
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const input = document.getElementById('new-domain-input-' + licId);
			const domain = input.value.trim();
			if (!domain) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_add_domain');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('license_id', licId);
			data.append('domain_name', domain);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'دامنه با موفقیت اضافه شد.' : 'Domain added successfully.'));
						location.reload();
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ثبت دامنه.' : 'Error adding domain.'));
					}
				});
		}

		// Toggle Change Domain Box
		function bkToggleChangeDomain(actId) {
			const box = document.getElementById('bk-change-domain-box-' + actId);
			if (box) {
				box.style.display = box.style.display === 'none' ? 'block' : 'none';
			}
		}

		// Submit Change Domain
		function bkSubmitChangeDomain(e, actId) {
			e.preventDefault();
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const newDom = document.getElementById('change-domain-input-' + actId).value.trim();
			const reason = document.getElementById('change-domain-reason-' + actId).value.trim();
			if (!newDom) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_change_domain');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('activation_id', actId);
			data.append('new_domain', newDom);
			data.append('reason', reason);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'دامنه تغییر یافت.' : 'Domain changed successfully.'));
						location.reload();
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در تغییر دامنه.' : 'Error changing domain.'));
					}
				});
		}

		// Delete Domain Slot permanently
		function bkDeleteDomain(actId, domName) {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const prompt = lang === 'fa'
				? 'آیا مطمئن هستید که می‌خواهید دامنه ' + domName + ' را حذف کنید و اسلات آن را برای سایت دیگری آزاد نمایید؟'
				: 'Are you sure you want to delete domain ' + domName + ' and free up its license slot?';
			if (!confirm(prompt)) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_delete_domain');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('activation_id', actId);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'دامنه حذف شد و اسلات آزاد گردید.' : 'Domain removed and slot freed.'));
						location.reload();
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در حذف دامنه.' : 'Error removing domain.'));
					}
				});
		}

		// Modal helpers
		function bkOpenModal(id) {
			const m = document.getElementById(id);
			if (m) m.classList.add('open');
		}

		function bkCloseModal(id) {
			const m = document.getElementById(id);
			if (m) m.classList.remove('open');
		}

		function bkCopyText(text) {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			navigator.clipboard.writeText(text).then(() => {
				alert(lang === 'fa' ? 'کپی شد: ' + text : 'Copied to clipboard: ' + text);
			}).catch(() => {
				alert(text);
			});
		}

		// Retention update
		function bkUpdateRetention(licenseId, hours) {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_update_retention');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('license_id', licenseId);
			data.append('retention_hours', hours);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'مدت نگهداری ذخیره شد.' : 'Retention period updated.'));
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ذخیره.' : 'Error updating retention.'));
					}
				});
		}

		// Manual Backup Trigger
		function bkTriggerBackup(licenseId, activationId) {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const prompt = lang === 'fa'
				? 'آیا مایل به ارسال دستور تهیه بکاپ دستی برای این سایت هستید؟'
				: 'Are you sure you want to trigger a manual backup for this site?';
			if (!confirm(prompt)) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_create_backup');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('license_id', licenseId);
			data.append('activation_id', activationId);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'دستور بکاپ در صف قرار گرفت.' : 'Backup command queued successfully.'));
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ارسال دستور.' : 'Error sending backup command.'));
					}
				});
		}

		// Restore Modal & Handler (D-042 2FA Gate)
		function bkOpenRestoreModal(licenseId, backupId, pkgName) {
			document.getElementById('restore-license-id').value = licenseId;
			document.getElementById('restore-backup-id').value = backupId;
			document.getElementById('restore-package-name').textContent = pkgName;
			document.getElementById('restore-totp-code').value = '';
			bkOpenModal('modal-restore-gate');
		}

		function bkSubmitRestore(e) {
			e.preventDefault();
			const lang  = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const licId = document.getElementById('restore-license-id').value;
			const bkId  = document.getElementById('restore-backup-id').value;
			const code  = document.getElementById('restore-totp-code').value;
			const btn   = document.getElementById('btn-submit-restore');

			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال اعتبارسنجی...' : 'Validating...';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_restore');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('license_id', licId);
			data.append('backup_id', bkId);
			data.append('totp_code', code);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'تایید و شروع بازیابی' : 'Verify & Start Restore';
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'دستور بازیابی تایید و در صف قرار گرفت.' : 'Restore command verified and queued.'));
						bkCloseModal('modal-restore-gate');
					} else {
						alert(res.data.message || (lang === 'fa' ? 'کد ۲FA نامعتبر است.' : 'Invalid 2FA code.'));
					}
				})
				.catch(() => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'تایید و شروع بازیابی' : 'Verify & Start Restore';
					alert(lang === 'fa' ? 'خطای ارتباط با سرور.' : 'Network connection error.');
				});
		}

		// TICKETS DEDICATED FULL-VIEW NAVIGATION (NO MODALS)
		function bkOpenNewTicketView() {
			document.getElementById('bk-tkt-list-view').style.display = 'none';
			document.getElementById('bk-tkt-thread-view').style.display = 'none';
			document.getElementById('bk-tkt-new-view').style.display = 'block';
			document.getElementById('ticket-subject').value = '';
			document.getElementById('ticket-message').value = '';
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}

		function bkReturnToTicketsList() {
			document.getElementById('bk-tkt-new-view').style.display = 'none';
			document.getElementById('bk-tkt-thread-view').style.display = 'none';
			document.getElementById('bk-tkt-list-view').style.display = 'block';
		}

		function bkOpenTicketThreadView(ticketId) {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			document.getElementById('bk-tkt-list-view').style.display = 'none';
			document.getElementById('bk-tkt-new-view').style.display = 'none';
			document.getElementById('bk-tkt-thread-view').style.display = 'block';

			const container = document.getElementById('tkt-messages-container');
			container.innerHTML = `<p style="text-align: center; color: var(--bk-slate-500); padding: 24px;">${lang === 'fa' ? 'در حال بارگذاری مکاتبات تیکت...' : 'Loading ticket conversation...'}</p>`;
			document.getElementById('reply-ticket-id').value = ticketId;
			document.getElementById('reply-ticket-message').value = '';
			const fileInput = document.getElementById('reply-ticket-file');
			if (fileInput) fileInput.value = '';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_get_ticket');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('ticket_id', ticketId);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						const tkt = res.data;
						document.getElementById('thread-view-subject').textContent = tkt.subject;
						document.getElementById('thread-view-meta').innerHTML = `
							<span class="bk-pill bk-pill-brand">${tkt.ticket_number}</span>
							<span class="bk-pill bk-pill-success">${tkt.status}</span>
							<span class="bk-pill bk-pill-indigo">${tkt.priority}</span>
						`;

						// Handle Closed State
						const isClosed = (tkt.status === 'closed' || tkt.status === 'resolved');
						const closeBtn = document.getElementById('btn-thread-close-ticket');
						const closedBanner = document.getElementById('tkt-closed-banner');
						const replyWrap = document.getElementById('tkt-reply-box-wrap');

						if (isClosed) {
							if (closeBtn) closeBtn.style.display = 'none';
							if (closedBanner) closedBanner.style.display = 'block';
							if (replyWrap) replyWrap.style.display = 'none';
						} else {
							if (closeBtn) closeBtn.style.display = 'inline-flex';
							if (closedBanner) closedBanner.style.display = 'none';
							if (replyWrap) replyWrap.style.display = 'block';
						}

						if (!tkt.replies || tkt.replies.length === 0) {
							container.innerHTML = `<p style="color: var(--bk-slate-500); padding: 20px;">${lang === 'fa' ? 'هیچ پیامی ثبت نشده است.' : 'No messages found.'}</p>`;
							return;
						}

						// 2-Sided Chat Messenger Stream (Customer vs Staff)
						let html = '';
						tkt.replies.forEach(r => {
							const isStaff = r.is_staff;
							const rowClass = isStaff ? 'staff' : 'user';
							const senderTitle = isStaff
								? (lang === 'fa' ? '🛡️ پشتیبان رسمی بکاپینو' : '🛡️ Backupino Official Support')
								: (r.sender_name || (lang === 'fa' ? 'شما' : 'You'));
							const roleBadge = isStaff
								? `<span class="bk-pill bk-pill-brand" style="font-size: 10px;">${lang === 'fa' ? 'پشتیبان رسمی' : 'Staff'}</span>`
								: `<span class="bk-pill bk-pill-neutral" style="font-size: 10px;">${lang === 'fa' ? 'کاربر' : 'Customer'}</span>`;

							let attachmentHtml = '';
							if (r.attachment_url) {
								attachmentHtml = `
									<div style="margin-top: 8px;">
										<a href="${r.attachment_url}" target="_blank" download class="bk-chat-attachment">
											📎 <span>${r.attachment_name || (lang === 'fa' ? 'دانلود فایل پیوست' : 'Download Attachment')}</span>
										</a>
									</div>
								`;
							}

							html += `
								<div class="bk-chat-row ${rowClass}">
									<div class="bk-chat-bubble ${rowClass}">
										<div class="bk-chat-meta">
											<div class="bk-chat-sender">
												<span>${senderTitle}</span>
												${roleBadge}
											</div>
											<span>${r.created_at}</span>
										</div>
										<div class="bk-chat-text">${r.message}</div>
										${attachmentHtml}
									</div>
								</div>
							`;
						});
						container.innerHTML = html;
					} else {
						container.innerHTML = '<p style="color: var(--bk-rose); padding: 20px;">' + (res.data.message || (lang === 'fa' ? 'خطا در دریافت تیکت.' : 'Error loading ticket.')) + '</p>';
					}
				});
		}

		function bkSubmitNewTicket(e) {
			e.preventDefault();
			const lang    = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const subject = document.getElementById('ticket-subject').value;
			const licId   = document.getElementById('ticket-license-id').value;
			const msg     = document.getElementById('ticket-message').value;
			const btn     = document.getElementById('btn-submit-ticket');

			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال ثبت...' : 'Submitting...';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_create_ticket');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('subject', subject);
			data.append('license_id', licId);
			data.append('message', msg);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'ثبت و ارسال تیکت' : 'Submit Ticket';
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'تیکت با موفقیت ثبت شد.' : 'Ticket created successfully.'));
						location.reload();
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ثبت تیکت.' : 'Error creating ticket.'));
					}
				});
		}

		function bkSubmitTicketReply(e) {
			e.preventDefault();
			const lang     = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const ticketId = document.getElementById('reply-ticket-id').value;
			const msg      = document.getElementById('reply-ticket-message').value;
			const btn      = document.getElementById('btn-reply-ticket');

			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال ارسال...' : 'Sending...';

			const formData = new FormData();
			formData.append('action', 'backupino_customer_reply_ticket');
			formData.append('_wpnonce', BK_PORTAL_NONCE);
			formData.append('ticket_id', ticketId);
			formData.append('message', msg);

			const fileInput = document.getElementById('reply-ticket-file');
			if (fileInput && fileInput.files && fileInput.files[0]) {
				formData.append('attachment', fileInput.files[0]);
			}

			fetch(BK_AJAX_URL, { method: 'POST', body: formData })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'ارسال پاسخ' : 'Send Reply';
					if (res.success) {
						document.getElementById('reply-ticket-message').value = '';
						if (fileInput) fileInput.value = '';
						bkOpenTicketThreadView(ticketId);
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ارسال پاسخ.' : 'Error sending reply.'));
					}
				})
				.catch(() => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'ارسال پاسخ' : 'Send Reply';
					alert(lang === 'fa' ? 'خطای ارتباط.' : 'Network error.');
				});
		}

		function bkSubmitCloseTicket() {
			const lang     = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const ticketId = document.getElementById('reply-ticket-id').value;
			const prompt   = lang === 'fa' ? 'آیا از بستن این تیکت اطمینان دارید؟' : 'Are you sure you want to close this ticket?';
			if (!confirm(prompt)) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_close_ticket');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('ticket_id', ticketId);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'تیکت با موفقیت بسته شد.' : 'Ticket closed successfully.'));
						bkOpenTicketThreadView(ticketId);
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در بستن تیکت.' : 'Error closing ticket.'));
					}
				});
		}

		// General Settings & Upgrade Handlers
		function bkSaveGeneralSettings(e) {
			e.preventDefault();
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const btn = document.getElementById('btn-save-settings');
			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال ذخیره...' : 'Saving...';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_save_settings');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('central_bot_enabled', document.getElementById('cfg-bot-enabled').checked ? 1 : 0);
			data.append('bot_messenger', document.getElementById('cfg-bot-messenger').value);
			data.append('bot_chat_id', document.getElementById('cfg-bot-chat-id').value);
			data.append('notify_on_backup', document.getElementById('cfg-notify-backup').checked ? 1 : 0);
			data.append('notify_on_expire', document.getElementById('cfg-notify-expire').checked ? 1 : 0);
			data.append('preferred_locale', lang);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'ذخیره تنظیمات ربات' : 'Save Bot Settings';
					alert(res.data.message || (lang === 'fa' ? 'تنظیمات ذخیره شد.' : 'Settings saved.'));
				});
		}

		function bkSubmitUpgradeRequest(e) {
			e.preventDefault();
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const btn = document.getElementById('btn-submit-upgrade');
			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال ارسال...' : 'Sending...';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_request_upgrade');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('upgrade_type', document.getElementById('upg-type').value);
			data.append('license_id', document.getElementById('upg-license-id').value);
			data.append('notes', document.getElementById('upg-notes').value);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'ارسال درخواست ارتقا به تیم فروش' : 'Submit Upgrade Inquiry';
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? 'درخواست ارتقا ثبت شد.' : 'Upgrade request submitted.'));
						document.getElementById('upg-notes').value = '';
						bkSwitchTab('tickets');
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در ثبت درخواست.' : 'Error submitting request.'));
					}
				});
		}

		// 2FA Setup Flow
		function bkStart2FaSetup() {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_2fa_setup');
			data.append('_wpnonce', BK_PORTAL_NONCE);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						document.getElementById('bk-2fa-qr-img').src = res.data.qr_url;
						document.getElementById('bk-2fa-secret-text').textContent = res.data.secret;
						document.getElementById('bk-2fa-input-code').value = '';
						bkOpenModal('modal-2fa-setup');
					} else {
						alert(res.data.message || (lang === 'fa' ? 'خطا در راه‌اندازی 2FA.' : 'Error setting up 2FA.'));
					}
				});
		}

		function bkConfirmEnable2Fa(e) {
			e.preventDefault();
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const code = document.getElementById('bk-2fa-input-code').value;
			const btn  = document.getElementById('btn-verify-2fa');

			btn.disabled = true;
			btn.textContent = lang === 'fa' ? 'در حال اعتبارسنجی...' : 'Validating...';

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_2fa_enable');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('totp_code', code);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = lang === 'fa' ? 'بررسی و فعال‌سازی نهایی' : 'Verify & Complete Setup';
					if (res.success) {
						bkCloseModal('modal-2fa-setup');
						bkCurrentRecoveryCodes = res.data.recovery_codes || [];
						const container = document.getElementById('bk-recovery-codes-list');
						let html = '';
						bkCurrentRecoveryCodes.forEach(c => {
							html += `<code style="background: #f1f5f9; padding: 6px; border-radius: 4px; font-weight: bold; text-align: center;">${c}</code>`;
						});
						container.innerHTML = html;
						bkOpenModal('modal-recovery-codes');
					} else {
						alert(res.data.message || (lang === 'fa' ? 'کد وارد شده اشتباه است.' : 'Invalid 2FA code entered.'));
					}
				});
		}

		function bkCopyRecoveryCodes() {
			if (bkCurrentRecoveryCodes.length > 0) {
				bkCopyText(bkCurrentRecoveryCodes.join('\n'));
			}
		}

		function bkConfirmDisable2Fa() {
			const lang = document.getElementById('bk-customer-portal').dataset.lang || 'en';
			const code = document.getElementById('bk-disable-totp-code').value;
			if (!code || code.length !== 6) {
				alert(lang === 'fa' ? 'لطفاً کد ۶ رقمی را وارد کنید.' : 'Please enter the 6-digit code.');
				return;
			}
			const prompt = lang === 'fa' ? 'آیا مطمئن هستید که می‌خواهید احراز هویت دو مرحله‌ای را غیرفعال کنید؟' : 'Are you sure you want to disable 2FA?';
			if (!confirm(prompt)) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_customer_2fa_disable');
			data.append('_wpnonce', BK_PORTAL_NONCE);
			data.append('totp_code', code);

			fetch(BK_AJAX_URL, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message || (lang === 'fa' ? '2FA غیرفعال شد.' : '2FA disabled.'));
						location.reload();
					} else {
						alert(res.data.message || (lang === 'fa' ? 'کد نامعتبر است.' : 'Invalid 2FA code.'));
					}
				});
		}

		// Initialize
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.bk-nav-link').forEach(btn => {
				btn.addEventListener('click', function() {
					bkSwitchTab(this.dataset.tab);
				});
			});

			if (window.location.hash) {
				const target = window.location.hash.replace('#tab-', '');
				if (document.getElementById('pane-' + target)) {
					bkSwitchTab(target);
				}
			}
		});
	</script>
</div>
