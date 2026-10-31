<?php
/**
 * i18n Builder for Backupino.
 *
 * Extracts translatable strings from the free/ codebase, builds backupino.pot,
 * creates backupino-fa_IR.po, and compiles it into binary backupino-fa_IR.mo.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

$root = dirname( __DIR__ );
$free = $root . '/free';
$lang = $free . '/languages';

if ( ! is_dir( $lang ) ) {
	mkdir( $lang, 0777, true );
}

$translations = array(
	'Backupino' => 'بک‌آپینو',
	'Backups' => 'پشتیبان‌ها',
	'Settings' => 'تنظیمات',
	'Backupino settings' => 'تنظیمات بک‌آپینو',
	'Back to backups' => 'بازگشت به پشتیبان‌ها',
	'Create a backup' => 'ایجاد پشتیبان',
	'Everything is packed into one archive plus a standalone installer that rebuilds the site on any empty server.' => 'همه‌چیز در یک آرشیو به همراه اینستالر مستقل بسته‌بندی می‌شود که سایت را روی هر سرور خالی بازسازی می‌کند.',
	'JavaScript is required to build backups.' => 'برای ایجاد پشتیبان، فعال بودن جاوااسکریپت الزامی است.',
	'Include files' => 'شامل فایل‌ها',
	'Include database' => 'شامل پایگاه داده',
	'Label (optional)' => 'برچسب (اختیاری)',
	'Before updating plugins' => 'قبل از به‌روزرسانی افزونه‌ها',
	'Create backup' => 'ایجاد پشتیبان',
	'Destination' => 'مقصد ذخیره‌سازی',
	'Packages are stored on: %s' => 'بسته‌ها ذخیره می‌شوند در: %s',
	'Local folder: %s' => 'پوشه محلی: %s',
	'Test storage' => 'آزمایش ذخیره‌سازی',
	'Permanent download link' => 'لینک دائمی دانلود',
	'This link always points at the newest backup. It never expires, but the visitor must be signed in as an administrator before the download starts.' => 'این لینک همیشه به جدیدترین نسخه پشتیبان اشاره دارد. تاریخ انقضا ندارد، اما پیش از آغاز دانلود باید به عنوان مدیر وارد شده باشید.',
	'Copy link' => 'کپی لینک',
	'Recent backups' => 'پشتیبان‌های اخیر',
	'Created' => 'تاریخ ایجاد',
	'Status' => 'وضعیت',
	'Progress' => 'پیشرفت',
	'Package' => 'بسته',
	'Actions' => 'عملیات',
	'No backups yet.' => 'هنوز پشتیبانی وجود ندارد.',
	'Delete' => 'حذف',
	'Archive' => 'آرشیو',
	'Excluded paths and patterns (one per line)' => 'مسیرها و الگوهای مستثنی (هر خط یک مورد)',
	'Examples: wp-content/uploads/2022/, *.mp4, node_modules/. Cache and backup folders are always skipped.' => 'نمونه‌ها: wp-content/uploads/2022/، *.mp4، node_modules/. پوشه‌های کش و پشتیبان همواره مستثنی هستند.',
	'Skip files larger than (MB, 0 = no limit)' => 'صرف‌نظر از فایل‌های بزرگ‌تر از (مگابایت، 0 = بدون محدودیت)',
	'Files packed per request' => 'تعداد فایل‌های بسته‌بندی در هر درخواست',
	'Storage' => 'فضای ذخیره‌سازی',
	'Backup archives and standalone installers are stored locally in a secure, protected directory on your server.' => 'آرشیوهای پشتیبان و اینستالرهای مستقل در یک دایرکتوری امن و محافظت‌شده روی سرور شما نگهداری می‌شوند.',
	'Notifications' => 'اعلان‌ها',
	'Backupino can announce every finished backup, with the permanent download link, in Telegram or Bale. The bot belongs to you: create one with @BotFather, then paste its token and your chat id here.' => 'بک‌آپینو می‌تواند اتمام هر نسخه پشتیبان را به همراه لینک دائمی دانلود در تلگرام یا بله اطلاع‌رسانی کند. ربات متعلق به خودتان است: یکی در BotFather بسازید، سپس توکن و شناسه چت خود را اینجا قرار دهید.',
	'Messenger' => 'پیام‌رسان',
	'Disabled' => 'غیرفعال',
	'Bot token' => 'توکن ربات',
	'Chat id' => 'شناسه چت (Chat ID)',
	'Send test message' => 'ارسال پیام آزمایشی',
	'%d message is waiting to be resent on the next scheduled run.' => '%d پیام در صف انتظار برای ارسال مجدد در اجرای زمان‌بندی بعدی قرار دارد.',
	'Schedule' => 'زمان‌بندی',
	'Automatic recurring backups trigger via WordPress cron. In Persian locale, monthly schedules automatically align with the start of the Jalali month.' => 'پشتیبان‌گیری خودکار دوره‌ای از طریق کرون وردپرس انجام می‌شود. در زبان فارسی، زمان‌بندی‌های ماهانه به‌طور خودکار با ابتدای ماه شمسی همگام می‌گردند.',
	'Run automatic backups' => 'اجرای خودکار پشتیبان‌گیری',
	'Frequency' => 'دوره تکرار',
	'Monthly (first day of the month)' => 'ماهانه (روز اول هر ماه)',
	'Keep at most (backups)' => 'حداکثر تعداد نگهداری (نسخه‌ها)',
	'Installer' => 'اینستالر',
	'Configure preset credentials for the standalone migration installer generated with each backup.' => 'تنظیم اطلاعات پیش‌فرض ورود برای اینستالر مهاجرت مستقل که همراه با هر پشتیبان تولید می‌شود.',
	'Installer password (optional)' => 'رمز عبور اینستالر (اختیاری)',
	'Default new URL (optional)' => 'آدرس اینترنتی جدید پیش‌فرض (اختیاری)',
	'Updates & Community' => 'به‌روزرسانی‌ها و جامعه کاربری',
	'Receive critical security notices, product updates, and roadmap announcements (opt-in).' => 'دریافت اطلاعیه‌های مهم امنیتی، به‌روزرسانی‌های محصول و اخبار نقشه راه (اختیاری).',
	'We respect your privacy. No telemetry or personal data is collected or sent without your explicit consent.' => 'ما به حریم خصوصی شما احترام می‌گذاریم. هیچ داده آماری یا شخصی بدون رضایت صریح شما جمع‌آوری یا ارسال نمی‌شود.',
	'Save settings' => 'ذخیره تنظیمات',
	'System status' => 'وضعیت سیستم',
	'Loading…' => 'در حال بارگذاری…',
	'Starting…' => 'در حال شروع…',
	'Working…' => 'در حال پردازش…',
	'The job failed.' => 'عملیات با شکست مواجه شد.',
	'Delete this package permanently?' => 'آیا این بسته برای همیشه حذف شود؟',
	'Settings saved.' => 'تنظیمات ذخیره شد.',
	'Link copied to the clipboard.' => 'لینک در کلیپ‌بورد کپی شد.',
	'Test message sent.' => 'پیام آزمایشی ارسال شد.',
	'You are not allowed to manage backups.' => 'شما دسترسی لازم برای مدیریت پشتیبان‌ها را ندارید.',
	'You are not allowed to download backups.' => 'شما دسترسی لازم برای دانلود پشتیبان‌ها را ندارید.',
	'Invalid file name.' => 'نام فایل نامعتبر است.',
	'This package no longer exists on the server.' => 'این بسته دیگر روی سرور موجود نیست.',
	'Storage is writable.' => 'فضای ذخیره‌سازی قابل نوشتن است.',
	'The Zip extension is not available.' => 'اکستنشن Zip در دسترس نیست.',
	'Could not open the archive (error %d).' => 'امکان باز کردن آرشیو وجود نداشت (خطای %d).',
	'The archive is not open.' => 'آرشیو باز نیست.',
	'File is not readable: %s' => 'فایل قابل خواندن نیست: %s',
	'Could not add file to archive: %s' => 'افزودن فایل به آرشیو ممکن نبود: %s',
	'The archive could not be finalized.' => 'آرشیو نهایی‌سازی نشد.',
	'Backupino: every minute' => 'بک‌آپینو: هر دقیقه',
	'Once weekly' => 'هفته‌ای یک‌بار',
	'Once monthly (first day of the month)' => 'ماهانه (روز اول هر ماه)',
	'Scheduled backup' => 'پشتیبان زمان‌بندی‌شده',
	'This download link is not valid.' => 'این لینک دانلود معتبر نیست.',
	'There is no backup ready to download yet.' => 'هنوز نسخه پشتیبانی آماده دانلود نیست.',
	'The database could not be created: %s' => 'امکان ایجاد دیتابیس وجود نداشت: %s',
	'PHP version' => 'نسخه PHP',
	'PHP extension: %s' => 'اکستنشن PHP: %s',
	'loaded' => 'فعال',
	'missing' => 'یافت نشد',
	'Archive support' => 'پشتیبانی از آرشیو',
	'ZipArchive available' => 'ZipArchive در دسترس است',
	'ZipArchive missing' => 'ZipArchive یافت نشد',
	'MySQL driver' => 'درایور MySQL',
	'none' => 'هیچ',
	'Writable directory' => 'دایرکتوری با دسترسی نوشتن',
	'Archive file' => 'فایل آرشیو',
	'not found: %s' => 'یافت نشد: %s',
	'Archive checksum' => 'چک‌سام آرشیو',
	'verified against the package manifest' => 'تایید شده بر اساس مانیفست بسته',
	'Free disk space' => 'فضای خالی دیسک',
	'unknown' => 'نامشخص',
	'needed' => 'مورد نیاز',
	'The Zip extension is not available on this server.' => 'اکستنشن Zip روی این سرور در دسترس نیست.',
	'The archive file is missing.' => 'فایل آرشیو یافت نشد.',
	'The archive cannot be opened. Is it complete?' => 'امکان باز کردن آرشیو وجود ندارد. آیا فایل کامل است؟',
	'Skipped unsafe entry: %s' => 'از ورودی ناامن صرف‌نظر شد: %s',
	'Unknown pipeline stage.' => 'مرحله پایپ‌لاین نامشخص است.',
	'Cancelled by the user.' => 'توسط کاربر لغو شد.',
	'Queued' => 'در صف',
	'Running' => 'در حال اجرا',
	'Completed' => 'تکمیل شده',
	'Failed' => 'ناموفق',
	'Cancelled' => 'لغو شده',
	'The bot token or chat id is missing.' => 'توکن ربات یا شناسه چت تنظیم نشده است.',
	'There is nothing to send.' => 'پیامی برای ارسال وجود ندارد.',
	'The messenger answered with HTTP %d.' => 'پیام‌رسان با کد وضعیت HTTP %d پاسخ داد.',
	'The messenger rejected the message.' => 'پیام‌رسان پیام را رد کرد.',
	'Telegram' => 'تلگرام',
	'Bale' => 'بله',
	'Backupino — backup finished: %s' => 'بک‌آپینو — پشتیبان‌گیری تکمیل شد: %s',
	'Contents: %s' => 'محتوا: %s',
	'Size: %s' => 'حجم: %s',
	'Date: %s' => 'تاریخ: %s',
	'Download (sign in as an administrator):' => 'دانلود (ورود به عنوان مدیر):',
	'Backupino — test message from %s' => 'بک‌آپینو — پیام آزمایشی از %s',
	'Notifications are working. Backups will be announced here.' => 'اعلان‌ها فعال هستند. گزارش پشتیبان‌گیری‌ها در اینجا اعلام خواهد شد.',
	'files and database' => 'فایل‌ها و پایگاه داده',
	'database only' => 'فقط پایگاه داده',
	'files only' => 'فقط فایل‌ها',
	'No messenger is configured.' => 'هیچ پیام‌رسانی تنظیم نشده است.',
	'This server' => 'همین سرور',
	'Could not read the package file.' => 'خواندن فایل بسته ممکن نبود.',
	'Could not write the package file.' => 'نوشتن فایل بسته ممکن نبود.',
	'Package not found.' => 'بسته یافت نشد.',
	'The requested file does not exist.' => 'فایل درخواستی وجود ندارد.',
	'Backupino requires PHP %1$s or newer. This server runs PHP %2$s.' => 'بک‌آپینو به PHP %1$s یا جدیدتر نیاز دارد. نسخه فعلی این سرور %2$s است.',
	'The PHP extension "%s" is required but not loaded.' => 'اکستنشن PHP به نام "%s" الزامی است اما بارگذاری نشده است.',
	'Neither the Zip extension nor PclZip is available, so archives cannot be created.' => 'نه اکستنشن Zip و نه PclZip در دسترس نیستند؛ بنابراین امکان ایجاد آرشیو وجود ندارد.',
	'PHP memory limit is %s. Backups will run in small chunks, but 128M or more is recommended.' => 'محدودیت حافظه PHP برابر با %s است. پشتیبان‌گیری در تکه‌های کوچک اجرا می‌شود، اما حداقل ۱۲۸ مگابایت توصیه می‌گردد.',
	'max_execution_time is %d seconds. Long steps will be split automatically.' => 'مقدار max_execution_time برابر با %d ثانیه است. مراحل طولانی به‌طور خودکار خرد می‌شوند.',
	'OpenSSL is missing: S3 uploads that require HTTPS signatures and encrypted packages will be unavailable.' => 'اکستنشن OpenSSL وجود ندارد: بارگذاری‌های S3 که نیاز به امضای HTTPS و بسته‌های رمزگذاری‌شده دارند غیرفعال خواهند بود.',
	'mbstring is missing: multibyte site titles may be truncated on URL replacement.' => 'اکستنشن mbstring وجود ندارد: عناوین چندبایتی سایت ممکن است هنگام بازنویسی آدرس‌ها ناقص شوند.',
	'Backupino cannot run on this server:' => 'بک‌آپینو امکان اجرا روی این سرور را ندارد:',
);

// Scan all php files in free/
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $free ) );
$strings  = array();

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
		continue;
	}

	$relPath = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
	$content = file_get_contents( $file->getPathname() );

	// Match gettext functions with 'backupino' textdomain
	// e.g. __( 'text', 'backupino' ), esc_html__( 'text', 'backupino' ), esc_html_e( 'text', 'backupino' ), etc.
	if ( preg_match_all( "/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(\s*(['\"])(.*?)(?<!\\\\)\\1\s*,\s*['\"]backupino['\"]\s*\)/s", $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $matches[2] as $match ) {
			$raw = stripcslashes( $match[0] );
			$line = substr_count( substr( $content, 0, $match[1] ), "\n" ) + 1;
			if ( ! isset( $strings[ $raw ] ) ) {
				$strings[ $raw ] = array();
			}
			$strings[ $raw ][] = $relPath . ':' . $line;
		}
	}
}

// Add any predefined translations not caught by regex
foreach ( array_keys( $translations ) as $key ) {
	if ( ! isset( $strings[ $key ] ) ) {
		$strings[ $key ] = array( 'free/backupino.php:1' );
	}
}

ksort( $strings );

// Build .pot file
$potHeader = <<<POT
# Copyright (C) 2026 Backupino
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Backupino 1.0.0\\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/backupino\\n"
"Last-Translator: Backupino Team <support@backupino.com>\\n"
"Language-Team: English <support@backupino.com>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: 2026-10-31T23:59:00+00:00\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"X-Generator: Backupino i18n build tool\\n"
"X-Domain: backupino\\n"


POT;

$potContent = $potHeader;
foreach ( $strings as $msgid => $locations ) {
	foreach ( $locations as $loc ) {
		$potContent .= "#: {$loc}\n";
	}
	$escapedMsgid = addcslashes( $msgid, "\"\n\r\t" );
	$potContent .= "msgid \"{$escapedMsgid}\"\n";
	$potContent .= "msgstr \"\"\n\n";
}

file_put_contents( $lang . '/backupino.pot', $potContent );
echo "Wrote " . count( $strings ) . " strings to {$lang}/backupino.pot\n";

// Build Persian .po file
$poHeader = <<<PO
# Persian translation for Backupino
# Copyright (C) 2026 Backupino
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Backupino 1.0.0\\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/backupino\\n"
"Last-Translator: Backupino Team <support@backupino.com>\\n"
"Language-Team: Persian <support@backupino.com>\\n"
"Language: fa_IR\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"PO-Revision-Date: 2026-10-31 23:59+0330\\n"
"X-Generator: Backupino i18n build tool\\n"
"X-Domain: backupino\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"


PO;

$poContent = $poHeader;
$entries = array();

foreach ( $strings as $msgid => $locations ) {
	foreach ( $locations as $loc ) {
		$poContent .= "#: {$loc}\n";
	}
	$msgstr = $translations[ $msgid ] ?? $msgid;
	$escapedMsgid  = addcslashes( $msgid, "\"\n\r\t" );
	$escapedMsgstr = addcslashes( $msgstr, "\"\n\r\t" );
	$poContent .= "msgid \"{$escapedMsgid}\"\n";
	$poContent .= "msgstr \"{$escapedMsgstr}\"\n\n";

	$entries[ $msgid ] = $msgstr;
}

file_put_contents( $lang . '/backupino-fa_IR.po', $poContent );
echo "Wrote {$lang}/backupino-fa_IR.po\n";

// Compile .mo binary
// Format specification: GNU gettext MO file
// Magic: 0x950412de
ksort( $entries );
$count = count( $entries ) + 1; // +1 for header entry "" => headers

$origKeys = array_merge( array( '' ), array_keys( $entries ) );
$transKeys = array_merge(
	array( "Project-Id-Version: Backupino 1.0.0\nReport-Msgid-Bugs-To: https://wordpress.org/support/plugin/backupino\nLanguage: fa_IR\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: nplurals=2; plural=(n > 1);\n" ),
	array_values( $entries )
);

$origTable = '';
$transTable = '';
$origOffsets = array();
$transOffsets = array();

$tableOffset = 28 + ( $count * 16 ); // 28 bytes header + count * 8 (orig) + count * 8 (trans)

$data = '';
for ( $i = 0; $i < $count; $i++ ) {
	$str = $origKeys[ $i ];
	$len = strlen( $str );
	$off = $tableOffset + strlen( $data );
	$origTable .= pack( 'V2', $len, $off );
	$data .= $str . "\0";
}

for ( $i = 0; $i < $count; $i++ ) {
	$str = $transKeys[ $i ];
	$len = strlen( $str );
	$off = $tableOffset + strlen( $data );
	$transTable .= pack( 'V2', $len, $off );
	$data .= $str . "\0";
}

$moHeader = pack(
	'V7',
	0x950412de, // Magic
	0,          // Format revision
	$count,     // Number of strings
	28,         // Offset of table with original strings
	28 + ( $count * 8 ), // Offset of table with translation strings
	0,          // Hashing table size
	0           // Hashing table offset
);

$moBinary = $moHeader . $origTable . $transTable . $data;
file_put_contents( $lang . '/backupino-fa_IR.mo', $moBinary );
echo "Compiled {$lang}/backupino-fa_IR.mo (" . strlen( $moBinary ) . " bytes)\n";
