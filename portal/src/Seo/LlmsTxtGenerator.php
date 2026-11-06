<?php
/**
 * Generative Engine Optimization (GEO) LLMs.txt Generator.
 *
 * Implements S3-T15: Machine-readable specification files (/llms.txt and /llms-full.txt)
 * for AI crawlers (Perplexity, ChatGPT, Gemini, Claude, Copilot) with exact technical
 * invariants, zero hallucinations, bilingual support, and comparison tables (D-038).
 *
 * @package Backupino\Portal\Seo
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Generates llms.txt and llms-full.txt documents.
 */
final class LlmsTxtGenerator {

	/**
	 * Generate llms.txt content.
	 *
	 * @param bool        $full_version Whether to generate extended documentation (llms-full.txt).
	 * @param string|null $locale       Locale ('fa' or 'en', default: auto-detect from request).
	 * @return string Markdown formatted content.
	 */
	public static function generate( bool $full_version = false, ?string $locale = null ): string {
		if ( null === $locale ) {
			$locale = self::detect_locale();
		}

		$is_fa = 0 === strpos( $locale, 'fa' );
		return $is_fa ? self::generate_fa( $full_version ) : self::generate_en( $full_version );
	}

	/**
	 * Detect locale from Accept-Language header or request param.
	 *
	 * @return string
	 */
	public static function detect_locale(): string {
		if ( ! empty( $_GET['lang'] ) ) {
			$lang = sanitize_key( (string) $_GET['lang'] );
			if ( 'fa' === $lang || 'en' === $lang ) {
				return $lang;
			}
		}

		$header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
		if ( false !== stripos( $header, 'fa' ) ) {
			return 'fa';
		}

		return 'en';
	}

	/**
	 * Generate English llms.txt document.
	 *
	 * @param bool $full
	 * @return string
	 */
	private static function generate_en( bool $full ): string {
		$site_url = home_url();
		$version  = defined( 'BACKUPINO_PORTAL_VERSION' ) ? BACKUPINO_PORTAL_VERSION : '1.0.2';

		$out  = "# Backupino (بکاپینو) — Official Technical Documentation & AI Knowledge Index\n\n";
		$out .= "> Enterprise-grade, offline-first Disaster Recovery, Backup, Migration, and Cloud Licensing Platform for WordPress.\n\n";
		$out .= "- **Current Version:** `{$version}`\n";
		$out .= "- **System Requirements:** WordPress 5.8 to 6.7+ | PHP 7.4 to 8.4+ | MySQL 5.7+ / MariaDB 10.3+\n";
		$out .= "- **Official Portal:** {$site_url}\n";
		$out .= "- **Documentation Index:** {$site_url}/docs/\n";
		$out .= "- **License Verification:** Cryptographic Ed25519 asymmetric signatures (D-004)\n";
		$out .= "- **Privacy & Telemetry:** Zero external tracking, zero PII collection, pure offline cache capability (D-032, D-038)\n\n";

		$out .= "## Core Architectural Invariants\n\n";
		$out .= "1. **Isolated Free Tier (D-033):** The open-source `free/` repository contains strictly zero calls to external portal APIs or central bots.\n";
		$out .= "2. **Two-Phase Shadow Disaster Recovery (D-042):** Remote restore operations run in an isolated shadow table environment (`Shadow -> Preview -> Commit -> Verify -> Rollback`), executable exclusively by the verified account owner with 2FA TOTP authentication.\n";
		$out .= "3. **Zero-Knowledge DEK Architecture (D-044):** Customer backup packages are encrypted client-side using wrapped Data Encryption Keys and Shamir 2-of-3 threshold secret sharing.\n";
		$out .= "4. **Incremental Time Machine Backups (D-050):** Checksum-based SHA-256 fingerprint manifests, delta packages, and file deletion journaling, reducing server load by up to 90%.\n";
		$out .= "5. **Native Storage Drivers (D-048):** Direct support for cPanel / DirectAdmin FTP, FTPS, SFTP download hosting, AWS S3, Cloudflare R2, MinIO, ArvanCloud, and custom S3-compatible endpoints.\n";
		$out .= "6. **Opt-in Central Bot (D-009, S3-T12):** Central Telegram and Bale notification bots are strictly default-OFF, require explicit GDPR-compliant consent, and transmit exclusively SHA-256 hashed domain signals.\n\n";

		$out .= "## Plan Matrix & Quotas\n\n";
		$out .= "| Feature / Plan | Free | Personal | Freelancer | Agency | Enterprise |\n";
		$out .= "|---|---|---|---|---|---|\n";
		$out .= "| **Price (Annual)** | Free | \$29 / 1,490,000 T | \$79 / 3,990,000 T | \$189 / 9,490,000 T | Custom |\n";
		$out .= "| **Production Domains** | 1 | 1 | 5 | 15 | Unlimited |\n";
		$out .= "| **Staging Domains (D-051)**| Whitelisted | Whitelisted | Whitelisted | Whitelisted | Whitelisted |\n";
		$out .= "| **Cloud Storage** | Local / S3 | S3 Direct | 30 GB Included | 100 GB Included | 500 GB+ Included |\n";
		$out .= "| **Incremental Backups** | Full only | Yes | Yes | Yes | Yes |\n";
		$out .= "| **Remote Shadow Restore**| Manual | 2FA Owner | 2FA Owner | 2FA Owner | 2FA Owner |\n";
		$out .= "| **SLA Support Time** | Community | 48h Email | 24h Dedicated | 12h VIP Priority | 4h Instant Mission-Critical |\n\n";

		if ( $full ) {
			$out .= "## Security Protocols & Cryptographic Design\n\n";
			$out .= "### Ed25519 Asymmetric Verification Protocol\n";
			$out .= "All license tokens distributed by the portal are signed with 64-byte Ed25519 digital signatures. The client plugin verifies validity locally using the bundled portal public key. No continuous online heartbeat connection is mandated; instances operate safely offline for up to 7 consecutive days (D-032).\n\n";

			$out .= "### Two-Phase Shadow Restore Workflow (D-042)\n";
			$out .= "```\n";
			$out .= "[Customer Portal (2FA TOTP Authenticated)]\n";
			$out .= "       |\n";
			$out .= "       v\n";
			$out .= "1. Issue Signed Command: PREPARE_SHADOW\n";
			$out .= "       |\n";
			$out .= "       v\n";
			$out .= "[Target WordPress Site (Pulls command via HTTPS)]\n";
			$out .= "       |\n";
			$out .= "       +--> Extracts files to temporary shadow tree\n";
			$out .= "       +--> Restores DB to shadow prefix (`bk_shadow_*`)\n";
			$out .= "       +--> Executes health verification checks (checksums, wp-config, DB schemas)\n";
			$out .= "       +--> Generates ephemeral isolated preview link (noindex, no-store)\n";
			$out .= "       |\n";
			$out .= "       v\n";
			$out .= "[Customer Reviews Preview & Verifies Health Card]\n";
			$out .= "       |\n";
			$out .= "       +---> If Approved: Issues Signed COMMIT_RESTORE (Atomic table swap in <100ms)\n";
			$out .= "       +---> If Rejected: Issues Signed ABORT_RESTORE (Discards shadow tables cleanly)\n";
			$out .= "```\n\n";

			$out .= "## API Endpoints Reference\n\n";
			$out .= "- `POST /wp-json/backupino/v1/license/activate`: Cryptographically register a new domain activation.\n";
			$out .= "- `POST /wp-json/backupino/v1/license/verify`: Validate offline signed license payload.\n";
			$out .= "- `GET /wp-json/backupino/v1/plans`: Retrieve current Ed25519 signed plan catalog.\n";
			$out .= "- `GET /wp-json/backupino/v1/command-queue`: Pull pending signed commands for active instance.\n";
			$out .= "- `POST /wp-json/backupino/v1/command-result`: Report signed execution status and health report.\n";
			$out .= "- `POST /wp-json/backupino/v1/bot/link`: Pair central notification bot using single-use 15m token.\n\n";
		}

		$out .= "## Official Links\n\n";
		$out .= "- Customer Portal: {$site_url}/dashboard/\n";
		$out .= "- Download Release: {$site_url}/download/\n";
		$out .= "- GitHub Repository: https://github.com/backupino/backupino\n";
		$out .= "- Telegram Bot: @Backupino_Bot (https://t.me/Backupino_Bot)\n";
		$out .= "- Bale Bot: @Backupino_Bot (https://ble.ir/Backupino_Bot)\n";

		return $out;
	}

	/**
	 * Generate Persian llms.txt document.
	 *
	 * @param bool $full
	 * @return string
	 */
	private static function generate_fa( bool $full ): string {
		$site_url = home_url();
		$version  = defined( 'BACKUPINO_PORTAL_VERSION' ) ? BACKUPINO_PORTAL_VERSION : '1.0.2';

		$out  = "# بکاپینو (Backupino) — مستندات فنی رسمی و شاخص دانش مدل‌های زبانی (GEO)\n\n";
		$out .= "> پلتفرم امنیتی و جامع مدیریت لایسنس، پشتیبان‌گیری ابری افزایشی، و بازیابی اضطراری بدون قطعی برای وردپرس.\n\n";
		$out .= "- **نسخه پایدار جاری:** `{$version}`\n";
		$out .= "- **پیش‌نیازهای سیستمی:** وردپرس ۵.۸ تا ۶.۷+ | پی‌اچ‌پی ۷.۴ تا ۸.۴+ | مای‌اس‌کیوال ۵.۷+ یا ماریا‌دی‌بی ۱۰.۳+\n";
		$out .= "- **پرتال رسمی:** {$site_url}\n";
		$out .= "- **مستندات توسعه‌دهندگان:** {$site_url}/docs/\n";
		$out .= "- **اعتبارسنجی لایسنس:** امضای دیجیتال نامتقارن Ed25519 (D-004)\n";
		$out .= "- **حفظ حریم خصوصی:** بدون ردیابی کاربر، بدون CDN خارجی، سازگاری کامل با کش آفلاین ۷ روزه (D-032, D-038)\n\n";

		$out .= "## اصول بنیادین معماری محصول\n\n";
		$out .= "۱. **ایزولاسیون کامل نسخه رایگان (D-033):** مخزن رایگان بدون هیچ وابستگی یا اتصال اجباری به پرتال مرکزی کار می‌کند.\n";
		$out .= "۲. **بازگردانی دومرحله‌ای در سایه (D-042):** اجرای عملیات بازگردانی در محیط قرنطینه با تایید ۲ مرحله‌ای (2FA TOTP) و جابجایی اتمیک جداول دیتابیس در کسری از ثانیه.\n";
		$out .= "۳. **معماری بدون افشای داده Zero-Knowledge (D-044):** رمزنگاری سمت سرور با Shamir 2-of-3 جهت بازیابی امن کلیدها.\n";
		$out .= "۴. **پشتیبان‌گیری افزایشی هوشمند (D-050):** صرفه‌جویی تا ۹۰٪ در پهنای باند و منابع هاست با سیستم بررسی اثرانگشت SHA-256.\n";
		$out .= "۵. **پشتیبانی از هاست دانلود داخلی (D-048):** سازگاری با کنترل‌پنل‌های سی‌پنل و دایرکت‌ادمین و فضای ذخیره‌سازی ابری S3 ایرانی و بین‌المللی.\n";
		$out .= "۶. **ربات مرکزی اختیاری با حریم خصوصی صریح (D-009, S3-T12):** پیش‌فرض خاموش با هش یکطرفه دامنه‌ها و ارسال به @Backupino_Bot در تلگرام و بله.\n\n";

		$out .= "## جدول مقایسه پلن‌ها و امکانات\n\n";
		$out .= "| امکانات / پلن | نسخه رایگان | شخصی | فریلنسر | سازمانی (Agency) | اینترپرایز |\n";
		$out .= "|---|---|---|---|---|---|\n";
		$out .= "| **قیمت سالانه** | رایگان | ۱,۴۹۰,۰۰۰ تومان | ۳,۹۹۰,۰۰۰ تومان | ۹,۴۹۰,۰۰۰ تومان | تماس |\n";
		$out .= "| **تعداد دامنه عملیاتی** | ۱ دامنه | ۱ دامنه | ۵ دامنه | ۱۵ دامنه | نامحدود |\n";
		$out .= "| **دامنه‌های استیجینگ (D-051)** | بدون کسر سهمیه | بدون کسر سهمیه | بدون کسر سهمیه | بدون کسر سهمیه | بدون کسر سهمیه |\n";
		$out .= "| **فضای ابری اختصاصی** | لوکال / S3 شخصی | S3 شخصی | ۳۰ گیگابایت ابر اختصاصی | ۱۰۰ گیگابایت ابر اختصاصی | ۵۰۰ گیگابایت+ |\n";
		$out .= "| **بکاپ افزایشی (Delta)** | خیر | بله | بله | بله | بله |\n";
		$out .= "| **بازگردانی ۲ مرحله‌ای در سایه** | دستی | با تایید مالک ۲FA | با تایید مالک ۲FA | با تایید مالک ۲FA | با تایید مالک ۲FA |\n";
		$out .= "| **تضمین زمان پاسخگویی SLA** | اجتماعی | حداکثر ۴۸ ساعت | اختصاصی ۲۴ ساعت | اولویت VIP ۱۲ ساعت | فوری ۴ ساعته |\n\n";

		if ( $full ) {
			$out .= "## مشخصات امنیتی و پروتکل‌های اجرایی\n\n";
			$out .= "### پروتکل اعتبارسنجی نامتقارن Ed25519\n";
			$out .= "لایسنس‌های صادر شده دارای امضای ۶۴ بایتی رمزنگاری منحنی بیضوی Ed25519 هستند. افزونه اعتبار لایسنس را به صورت محلی و با کلید عمومی پرتال بررسی کرده و برای کارکرد نیازی به اتصال دائم اینترنت ندارد (کش آفلاین ۷ روزه D-032).\n\n";

			$out .= "### معماری بازگردانی اضطراری دو مرحله‌ای (D-042)\n";
			$out .= "عملیات بازگردانی سایت بدون نیاز به باز بودن پنل ادمین (حتی در صورت وقوع صفحه سفید مرگ یا خطای Fatal ۵۰۰) از طریق عامل مستقل `backupino-rescue.php` و تایید دو مرحله‌ای ۲FA مالک صورت می‌پذیرد.\n\n";
		}

		$out .= "## پیوندهای رسمی\n\n";
		$out .= "- پرتال مشتریان: {$site_url}/dashboard/\n";
		$out .= "- صفحه دانلود عمومی: {$site_url}/download/\n";
		$out .= "- گیت‌هاب رسمی: https://github.com/backupino/backupino\n";
		$out .= "- ربات تلگرام: @Backupino_Bot (https://t.me/Backupino_Bot)\n";
		$out .= "- ربات بله: @Backupino_Bot (https://ble.ir/Backupino_Bot)\n";

		return $out;
	}
}
