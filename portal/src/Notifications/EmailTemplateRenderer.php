<?php
/**
 * Bilingual Email Template Renderer with D-036 Inline Tokens.
 *
 * Implements S3-T13: High-deliverability HTML email templates with inline CSS
 * strictly derived from DesignTokens, complete RTL/LTR directional logic,
 * and Persian/English bilingual text.
 *
 * @package Backupino\Portal\Notifications
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Notifications;

use Backupino\Portal\Support\DateFormatter;

defined( 'ABSPATH' ) || exit;

/**
 * Renders HTML transactional emails.
 */
final class EmailTemplateRenderer {

	/**
	 * Render an email for a specific event and locale.
	 *
	 * @param string               $event   Event key (e.g. 'license_expiring_soon').
	 * @param string               $locale  Locale code ('fa', 'fa_IR', 'en', 'en_US').
	 * @param array<string, mixed> $context Dynamic event payload.
	 * @return array{ subject: string, html: string, text: string }
	 */
	public static function render( string $event, string $locale, array $context = array() ): array {
		$is_fa   = 0 === strpos( $locale, 'fa' );
		$payload = self::build_event_payload( $event, $is_fa, $context );

		$subject = $payload['subject'];
		$html    = self::render_layout( $is_fa, $payload );
		$text    = self::render_plain_text( $subject, $payload );

		return array(
			'subject' => $subject,
			'html'    => $html,
			'text'    => $text,
		);
	}

	/**
	 * Build translated event payload containing texts, badges, metadata rows, and CTA.
	 *
	 * @param string               $event
	 * @param bool                 $is_fa
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private static function build_event_payload( string $event, bool $is_fa, array $context ): array {
		$portal_url = home_url( '/dashboard/' );

		switch ( $event ) {
			case 'license_expiring_soon':
				$expiry_raw  = $context['expires_at'] ?? time() + ( 3 * 86400 );
				$expiry_date = DateFormatter::format_localized( $expiry_raw, $is_fa ? 'fa' : 'en' );
				$renew_url   = $context['renew_url'] ?? ( $portal_url . '?tab=licenses' );

				return array(
					'subject'     => $is_fa ? 'هشدار انقضای اشتراک لایسنس بکاپینو' : 'Backupino License Expiring Soon',
					'badge_text'  => $is_fa ? 'نیاز به تمدید' : 'Action Required',
					'badge_type'  => 'amber',
					'title'       => $is_fa ? 'اشتراک لایسنس شما به‌زودی به پایان می‌رسد' : 'Your Backupino Subscription Is Expiring Soon',
					'description' => $is_fa
						? 'لایسنس شما ظرف ۳ روز آینده منقضی خواهد شد. برای اطمینان از استمرار پشتیبان‌گیری خودکار، پیش‌گیری از وقفه در بکاپ‌های ابری و حفظ دوره‌های بازگردانی، لطفاً پیش از پایان موعد اشتراک خود را تمدید فرمایید.'
						: 'Your license will expire within the next 3 days. To maintain continuous automated cloud backups and keep disaster recovery services active, please renew your subscription before the expiration date.',
					'rows'        => array(
						( $is_fa ? 'کلید لایسنس' : 'License Key' )    => '<code>' . esc_html( $context['license_key'] ?? 'BK-****-****' ) . '</code>',
						( $is_fa ? 'پلن اشتراک' : 'Plan' )           => esc_html( $context['plan_name'] ?? 'Freelancer' ),
						( $is_fa ? 'تاریخ انقضا' : 'Expiry Date' )     => esc_html( $expiry_date ),
						( $is_fa ? 'وضعیت' : 'Status' )              => $is_fa ? 'در آستانه انقضا (T-3 روز)' : 'Expiring Soon (T-3 days)',
					),
					'cta_text'    => $is_fa ? 'تمدید آنلاین لایسنس' : 'Renew Subscription Now',
					'cta_url'     => $renew_url,
				);

			case 'license_grace_period':
				$grace_raw   = $context['grace_expires_at'] ?? time() + ( 7 * 86400 );
				$grace_date  = DateFormatter::format_localized( $grace_raw, $is_fa ? 'fa' : 'en' );
				$renew_url   = $context['renew_url'] ?? ( $portal_url . '?tab=licenses' );

				return array(
					'subject'     => $is_fa ? '⚠️ دوره مهلت ۷ روزه لایسنس بکاپینو فعال شد' : '⚠️ Backupino Grace Period Active (7 Days Remaining)',
					'badge_text'  => $is_fa ? 'دوره مهلت ۷ روزه' : 'Grace Period',
					'badge_type'  => 'amber',
					'title'       => $is_fa ? 'فرصت پایانی: ورود لایسنس به دوره مهلت ۷ روزه' : 'Final Notice: License Entered 7-Day Grace Period',
					'description' => $is_fa
						? 'موعد رسمی لایسنس شما به پایان رسیده و دورهٔ مهلت اضطراری ۷ روزه (D-032) فعال شده است. پس از سپری شدن این مهلت، لایسنس به حالت فقط-خواندنی تنزل یافته و بکاپ‌های زمان‌بندی‌شده متوقف خواهند شد.'
						: 'Your subscription period has ended and the 7-day grace period (D-032) is now active. Once this period lapses, your license will downgrade to read-only mode and scheduled backups will halt.',
					'rows'        => array(
						( $is_fa ? 'کلید لایسنس' : 'License Key' )    => '<code>' . esc_html( $context['license_key'] ?? 'BK-****-****' ) . '</code>',
						( $is_fa ? 'پلن' : 'Plan' )                  => esc_html( $context['plan_name'] ?? 'Freelancer' ),
						( $is_fa ? 'مهلت نهایی' : 'Grace Deadline' ) => esc_html( $grace_date ),
						( $is_fa ? 'وضعیت فعلی' : 'Current Mode' )   => $is_fa ? 'فعال موقت (۷ روز تا تنزل)' : 'Active Grace (7d until downgrade)',
					),
					'cta_text'    => $is_fa ? 'تمدید فوری و تثبیت لایسنس' : 'Renew Immediately',
					'cta_url'     => $renew_url,
				);

			case 'payment_failed':
				$checkout_url = $context['checkout_url'] ?? ( $portal_url . '?tab=orders' );

				return array(
					'subject'     => $is_fa ? 'تراکنش ناموفق پرداخت در سامانه بکاپینو' : 'Backupino Payment Transaction Failed',
					'badge_text'  => $is_fa ? 'پرداخت ناموفق' : 'Payment Failed',
					'badge_type'  => 'rose',
					'title'       => $is_fa ? 'عملیات پرداخت سفارش با خطا مواجه شد' : 'Order Payment Unsuccessful',
					'description' => $is_fa
						? 'تراکنش پرداخت سفارش شما توسط درگاه بانکی یا سرویس پرداخت تأیید نگردید. مبلغی از حساب شما کسر نشده یا در صورت کسر، طبق ضوابط بانکی حداکثر ظرف ۷۲ ساعت عودت داده می‌شود.'
						: 'The payment transaction for your order could not be completed by the payment provider. No funds were captured, or if debited, will be automatically refunded by your banking network.',
					'rows'        => array(
						( $is_fa ? 'شماره سفارش' : 'Order ID' )       => '#' . esc_html( (string) ( $context['order_id'] ?? '-' ) ),
						( $is_fa ? 'مبلغ' : 'Amount' )                => esc_html( (string) ( $context['amount'] ?? '-' ) ) . ' ' . esc_html( (string) ( $context['currency'] ?? '' ) ),
						( $is_fa ? 'علت خطا' : 'Failure Reason' )     => esc_html( $context['error_message'] ?? ( $is_fa ? 'تراکنش لغو شده توسط کاربر یا درگاه' : 'Canceled or gateway error' ) ),
					),
					'cta_text'    => $is_fa ? 'پرداخت مجدد سفارش' : 'Retry Payment',
					'cta_url'     => $checkout_url,
				);

			case 'backup_error_or_quota':
				$issue = $context['issue_type'] ?? 'backup_failure';
				$is_quota = 'quota_exceeded' === $issue;

				return array(
					'subject'     => $is_quota
						? ( $is_fa ? 'هشدار تکمیل ظرفیت فضای ابری بکاپینو' : 'Backupino Cloud Storage Quota Exceeded' )
						: ( $is_fa ? 'خطا در اجرای پشتیبان‌گیری سایت' : 'Backup Execution Failed on Connected Site' ),
					'badge_text'  => $is_quota ? ( $is_fa ? 'سقف حافظه ابری' : 'Storage Quota' ) : ( $is_fa ? 'خطای بکاپ' : 'Backup Alert' ),
					'badge_type'  => 'rose',
					'title'       => $is_quota
						? ( $is_fa ? 'فضای ابری اختصاص‌یافته به لایسنس تکمیل شده است' : 'Cloud Storage Limit Reached' )
						: ( $is_fa ? 'عملیات پشتیبان‌گیری خودکار سایت ناموفق بود' : 'Scheduled Backup Encountered an Error' ),
					'description' => $is_quota
						? ( $is_fa
							? 'حجم پکیج‌های ابری شما به سقف مجاز پلن اشتراک رسیده است. تا زمان آزادسازی فضا یا ارتقای پلن، ایجاد نسخه‌های پشتیبان جدید در فضای ابری متوقف خواهد شد.'
							: 'Your cloud backup storage has reached the plan allocation limit. Automated cloud uploads will pause until older snapshots are pruned or your plan is upgraded.' )
						: ( $is_fa
							? 'عملیات پشتیبان‌گیری در دامنهٔ متصل با خطا مواجه شد. لطفاً برای بررسی لاگ‌های سرور و وضعیت افزونه به پنل مراجعه فرمایید.'
							: 'The backup job on your connected domain failed. Please review the server error logs and plugin status in your customer portal.' ),
					'rows'        => array(
						( $is_fa ? 'دامنه متصل' : 'Connected Domain' ) => '<code>' . esc_html( $context['domain'] ?? '-' ) . '</code>',
						( $is_fa ? 'شرح وضعیت' : 'Issue Details' )     => esc_html( $context['details'] ?? ( $is_quota ? ( $is_fa ? 'مصرف بیش از ۱۰۰٪ سهمیه پلن' : 'Usage exceeded 100%' ) : ( $is_fa ? 'پاسخ نامعتبر از سرور میزبان' : 'Execution timeout or disk error' ) ) ),
						( $is_fa ? 'زمان رخداد' : 'Timestamp' )         => esc_html( DateFormatter::format_localized( time(), $is_fa ? 'fa' : 'en' ) ),
					),
					'cta_text'    => $is_fa ? 'مدیریت فضا و مشاهده بکاپ‌ها' : 'Manage Storage & Backups',
					'cta_url'     => $portal_url . '?tab=licenses',
				);

			case 'restore_completed':
			default:
				$stage     = $context['stage'] ?? 'committed';
				$is_commit = 'committed' === $stage;
				$is_shadow = 'shadow_ready' === $stage;

				$title = $is_commit
					? ( $is_fa ? 'بازگردانی اطلاعات با موفقیت نهایی شد' : 'Disaster Recovery Restore Committed Successfully' )
					: ( $is_shadow
						? ( $is_fa ? 'محیط پیش‌نمایش سایه (Shadow) آماده بررسی است' : 'Shadow Restore Preview Is Ready for Inspection' )
						: ( $is_fa ? 'عملیات بازگردانی لغو گردید' : 'Restore Operation Aborted' ) );

				$desc = $is_commit
					? ( $is_fa
						? 'عملیات بازگردانی پایگاه داده و فایل‌ها بر اساس پروتکل D-042 با جابجایی اتمیک جداول با موفقیت کامل انجام شد و سایت زنده شما به‌روزرسانی گردید.'
						: 'The two-phase restore completed successfully per protocol D-042. Atomic table swap verified and your live site is fully operational.' )
					: ( $is_shadow
						? ( $is_fa
							? 'محیط ایزوله سایه با موفقیت ساخته شد. لطفاً پیوند پیش‌نمایش موقت را بررسی کرده و سپس دستور نهایی‌سازی (Commit) را از پنل صادر فرمایید.'
							: 'The isolated shadow tables were constructed. Please inspect the temporary preview link and proceed with the Commit confirmation from your dashboard.' )
						: ( $is_fa
							? 'دستور لغو بازگردانی اجرا شد، جداول سایه پاکسازی شدند و سایت زنده بدون تغییر حفظ گردید.'
							: 'Restore was aborted. Shadow tables have been discarded and the production environment remains untouched.' ) );

				$badge_type = $is_commit ? 'emerald' : ( $is_shadow ? 'sky' : 'amber' );

				return array(
					'subject'     => $is_fa ? 'گزارش پروتکل بازگردانی بکاپینو' : 'Backupino Restore Protocol Report',
					'badge_text'  => $is_commit ? ( $is_fa ? 'نهایی شد (Commit)' : 'Committed' ) : ( $is_shadow ? ( $is_fa ? 'آماده پیش‌نمایش' : 'Preview Ready' ) : ( $is_fa ? 'لغو شد' : 'Aborted' ) ),
					'badge_type'  => $badge_type,
					'title'       => $title,
					'description' => $desc,
					'rows'        => array(
						( $is_fa ? 'دامنه مقصد' : 'Target Domain' )     => '<code>' . esc_html( $context['domain'] ?? '-' ) . '</code>',
						( $is_fa ? 'مرحله عملیات' : 'Stage' )          => esc_html( $stage ),
						( $is_fa ? 'تعداد جداول' : 'Tables Verified' )  => esc_html( (string) ( $context['tables_count'] ?? '-' ) ),
						( $is_fa ? 'هش سلامت' : 'Checksum Match' )     => ! empty( $context['checksum_ok'] ) ? '✅ Passed' : 'Verified',
					),
					'cta_text'    => $is_fa ? 'ورود به پنل کاربری بکاپینو' : 'Open Backupino Portal',
					'cta_url'     => $portal_url . '?tab=licenses',
				);
		}
	}

	/**
	 * Render the full HTML responsive email layout.
	 *
	 * @param bool                 $is_fa
	 * @param array<string, mixed> $payload
	 * @return string
	 */
	private static function render_layout( bool $is_fa, array $payload ): string {
		$dir        = $is_fa ? 'rtl' : 'ltr';
		$lang       = $is_fa ? 'fa' : 'en';
		$font_stack = $is_fa ? DesignTokens::FONT_FA : DesignTokens::FONT_EN;
		$text_align = $is_fa ? 'right' : 'left';
		$opp_align  = $is_fa ? 'left' : 'right';

		// Badge colors
		$badge_bg     = DesignTokens::BRAND_LIGHT;
		$badge_border = DesignTokens::BRAND_BORDER;
		$badge_color  = DesignTokens::BRAND;

		if ( 'emerald' === $payload['badge_type'] ) {
			$badge_bg     = DesignTokens::EMERALD_BG;
			$badge_border = DesignTokens::EMERALD_BORDER;
			$badge_color  = DesignTokens::EMERALD;
		} elseif ( 'amber' === $payload['badge_type'] ) {
			$badge_bg     = DesignTokens::AMBER_BG;
			$badge_border = DesignTokens::AMBER_BORDER;
			$badge_color  = DesignTokens::AMBER;
		} elseif ( 'rose' === $payload['badge_type'] ) {
			$badge_bg     = DesignTokens::ROSE_BG;
			$badge_border = DesignTokens::ROSE_BORDER;
			$badge_color  = DesignTokens::ROSE;
		}

		$rows_html = '';
		if ( ! empty( $payload['rows'] ) && is_array( $payload['rows'] ) ) {
			foreach ( $payload['rows'] as $label => $val ) {
				$rows_html .= '
				<tr>
					<td style="padding: 10px 14px; border-bottom: 1px solid ' . DesignTokens::SLATE_200 . '; color: ' . DesignTokens::SLATE_500 . '; font-size: 13px; font-weight: 500; text-align: ' . $text_align . '; width: 35%;">' . esc_html( $label ) . '</td>
					<td style="padding: 10px 14px; border-bottom: 1px solid ' . DesignTokens::SLATE_200 . '; color: ' . DesignTokens::SLATE_800 . '; font-size: 13px; font-weight: 600; text-align: ' . $opp_align . ';">' . $val . '</td>
				</tr>';
			}
		}

		$cta_html = '';
		if ( ! empty( $payload['cta_text'] ) && ! empty( $payload['cta_url'] ) ) {
			$cta_html = '
			<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 28px 0 10px; width: 100%;">
				<tr>
					<td align="center">
						<a href="' . esc_url( $payload['cta_url'] ) . '" target="_blank" style="display: inline-block; background-color: ' . DesignTokens::BRAND . '; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: ' . DesignTokens::RADIUS_MD . '; font-size: 14px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);">' . esc_html( $payload['cta_text'] ) . '</a>
					</td>
				</tr>
			</table>';
		}

		$portal_title = $is_fa ? 'بکاپینو' : 'Backupino';
		$portal_sub   = $is_fa ? 'سامانه ابری مدیریت و پشتیبان‌گیری وردپرس' : 'Enterprise WordPress Cloud Disaster Recovery';
		$privacy_note = $is_fa
			? 'این پیام سیستمی به صورت خودکار و بر اساس تنظیمات حساب کاربری شما ارسال شده است (D-038). هیچ اطلاعات شخصی یا حساسی ردیابی نمی‌شود.'
			: 'This automated notification was generated according to your account preferences (D-038). Your privacy and data isolation are strictly protected.';
		$rights       = $is_fa ? 'تمامی حقوق برای سامانه ابری بکاپینو محفوظ است.' : 'All rights reserved. Backupino Cloud Services.';

		return '<!DOCTYPE html>
<html lang="' . esc_attr( $lang ) . '" dir="' . esc_attr( $dir ) . '">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>' . esc_html( $payload['subject'] ) . '</title>
	<!--[if mso]>
	<style type="text/css">
		table, td, p, a, h1, h2 { font-family: Arial, sans-serif !important; }
	</style>
	<![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: ' . DesignTokens::SLATE_50 . '; font-family: ' . $font_stack . '; -webkit-font-smoothing: antialiased; direction: ' . $dir . ';">
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: ' . DesignTokens::SLATE_50 . '; padding: 36px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: ' . DesignTokens::RADIUS_LG . '; overflow: hidden; border: 1px solid ' . DesignTokens::SLATE_200 . '; box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.05);">
					<!-- Header -->
					<tr>
						<td style="background-color: ' . DesignTokens::SLATE_900 . '; padding: 22px 28px; text-align: ' . $text_align . ';">
							<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td style="vertical-align: middle; text-align: ' . $text_align . ';">
										<span style="color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">' . esc_html( $portal_title ) . '</span>
										<span style="display: inline-block; background-color: rgba(2, 132, 199, 0.25); color: #38bdf8; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 9999px; margin-right: 8px; margin-left: 8px;">Cloud Portal</span>
										<div style="color: ' . DesignTokens::SLATE_400 . '; font-size: 12px; margin-top: 4px;">' . esc_html( $portal_sub ) . '</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Content Body -->
					<tr>
						<td style="padding: 32px 28px; text-align: ' . $text_align . ';">
							<!-- Status Badge -->
							<div style="margin-bottom: 16px;">
								<span style="display: inline-block; background-color: ' . $badge_bg . '; border: 1px solid ' . $badge_border . '; color: ' . $badge_color . '; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: ' . DesignTokens::RADIUS_SM . ';">' . esc_html( $payload['badge_text'] ) . '</span>
							</div>

							<!-- Title -->
							<h1 style="margin: 0 0 14px; font-size: 18px; font-weight: 800; color: ' . DesignTokens::SLATE_900 . '; line-height: 1.4;">' . esc_html( $payload['title'] ) . '</h1>

							<!-- Description -->
							<p style="margin: 0 0 24px; font-size: 14px; color: ' . DesignTokens::SLATE_600 . '; line-height: 1.65;">' . esc_html( $payload['description'] ) . '</p>

							<!-- Key Details Card -->
							' . ( ! empty( $rows_html ) ? '
							<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: ' . DesignTokens::SLATE_50 . '; border: 1px solid ' . DesignTokens::SLATE_200 . '; border-radius: ' . DesignTokens::RADIUS_MD . '; border-collapse: separate; border-spacing: 0; overflow: hidden; margin-bottom: 16px;">
								' . $rows_html . '
							</table>' : '' ) . '

							<!-- CTA Button -->
							' . $cta_html . '
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="background-color: ' . DesignTokens::SLATE_100 . '; border-top: 1px solid ' . DesignTokens::SLATE_200 . '; padding: 20px 28px; text-align: center;">
							<p style="margin: 0 0 6px; font-size: 12px; color: ' . DesignTokens::SLATE_500 . '; line-height: 1.5;">' . esc_html( $privacy_note ) . '</p>
							<p style="margin: 0; font-size: 11px; color: ' . DesignTokens::SLATE_400 . ';">&copy; ' . gmdate( 'Y' ) . ' ' . esc_html( $rights ) . '</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}

	/**
	 * Render plain text fallback version.
	 *
	 * @param string               $subject
	 * @param array<string, mixed> $payload
	 * @return string
	 */
	private static function render_plain_text( string $subject, array $payload ): string {
		$out  = $subject . "\n" . str_repeat( '=', mb_strlen( $subject ) ) . "\n\n";
		$out .= ( $payload['title'] ?? '' ) . "\n\n";
		$out .= ( $payload['description'] ?? '' ) . "\n\n";

		if ( ! empty( $payload['rows'] ) && is_array( $payload['rows'] ) ) {
			foreach ( $payload['rows'] as $k => $v ) {
				$clean_val = wp_strip_all_tags( (string) $val = $v );
				$out .= $k . ': ' . $clean_val . "\n";
			}
			$out .= "\n";
		}

		if ( ! empty( $payload['cta_url'] ) ) {
			$out .= ( $payload['cta_text'] ?? 'Action' ) . ': ' . $payload['cta_url'] . "\n\n";
		}

		$out .= "Backupino Official Portal - https://backupino.com\n";
		return $out;
	}
}
