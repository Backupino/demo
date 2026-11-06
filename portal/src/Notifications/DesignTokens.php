<?php
/**
 * Single Source of Truth for Design Tokens (D-036).
 *
 * Provides shared color palettes, border radii, typography, and inline styling
 * helpers across customer dashboards, transactional emails, and the public download page.
 *
 * @package Backupino\Portal\Notifications
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Encapsulates CSS tokens and common inline styles.
 */
final class DesignTokens {

	public const BRAND         = '#0284c7'; // Sky-600
	public const BRAND_HOVER   = '#0369a1'; // Sky-700
	public const BRAND_LIGHT   = '#f0f9ff'; // Sky-50
	public const BRAND_BORDER  = '#bae6fd'; // Sky-200

	public const SLATE_950     = '#020617';
	public const SLATE_900     = '#0f172a'; // Primary dark header
	public const SLATE_800     = '#1e293b';
	public const SLATE_700     = '#334155';
	public const SLATE_600     = '#475569';
	public const SLATE_500     = '#64748b'; // Muted text
	public const SLATE_400     = '#94a3b8';
	public const SLATE_300     = '#cbd5e1';
	public const SLATE_200     = '#e2e8f0'; // Borders
	public const SLATE_100     = '#f1f5f9'; // Soft fills
	public const SLATE_50      = '#f8fafc'; // Body background

	public const EMERALD       = '#10b981';
	public const EMERALD_BG    = '#ecfdf5';
	public const EMERALD_BORDER= '#a7f3d0';

	public const AMBER         = '#f59e0b';
	public const AMBER_BG      = '#fffbeb';
	public const AMBER_BORDER  = '#fde68a';

	public const ROSE          = '#ef4444';
	public const ROSE_BG       = '#fef2f2';
	public const ROSE_BORDER   = '#fecaca';

	public const INDIGO        = '#6366f1';
	public const INDIGO_BG     = '#eef2ff';
	public const INDIGO_BORDER = '#c7d2fe';

	public const RADIUS_SM     = '6px';
	public const RADIUS_MD     = '8px';
	public const RADIUS_LG     = '12px';
	public const RADIUS_XL     = '16px';

	public const FONT_FA       = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Vazirmatn', 'Arad', Tahoma, system-ui, sans-serif";
	public const FONT_EN       = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', system-ui, sans-serif";

	/**
	 * Get CSS variables string (:root block).
	 *
	 * @return string
	 */
	public static function get_css_variables(): string {
		return '
:root {
	--bk-brand: ' . self::BRAND . ';
	--bk-brand-hover: ' . self::BRAND_HOVER . ';
	--bk-brand-light: ' . self::BRAND_LIGHT . ';
	--bk-brand-border: ' . self::BRAND_BORDER . ';
	--bk-slate-900: ' . self::SLATE_900 . ';
	--bk-slate-800: ' . self::SLATE_800 . ';
	--bk-slate-700: ' . self::SLATE_700 . ';
	--bk-slate-600: ' . self::SLATE_600 . ';
	--bk-slate-500: ' . self::SLATE_500 . ';
	--bk-slate-400: ' . self::SLATE_400 . ';
	--bk-slate-200: ' . self::SLATE_200 . ';
	--bk-slate-100: ' . self::SLATE_100 . ';
	--bk-slate-50: ' . self::SLATE_50 . ';
	--bk-emerald: ' . self::EMERALD . ';
	--bk-emerald-bg: ' . self::EMERALD_BG . ';
	--bk-emerald-border: ' . self::EMERALD_BORDER . ';
	--bk-amber: ' . self::AMBER . ';
	--bk-amber-bg: ' . self::AMBER_BG . ';
	--bk-amber-border: ' . self::AMBER_BORDER . ';
	--bk-rose: ' . self::ROSE . ';
	--bk-rose-bg: ' . self::ROSE_BG . ';
	--bk-rose-border: ' . self::ROSE_BORDER . ';
	--bk-indigo: ' . self::INDIGO . ';
	--bk-radius-sm: ' . self::RADIUS_SM . ';
	--bk-radius: ' . self::RADIUS_LG . ';
	--bk-radius-lg: ' . self::RADIUS_XL . ';
	--bk-font-fa: ' . self::FONT_FA . ';
	--bk-font-en: ' . self::FONT_EN . ';
}';
	}
}
