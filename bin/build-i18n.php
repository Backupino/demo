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
	'Package name' => 'نام بسته',
	'Package Name' => 'نام بسته',
	'File Size' => 'حجم فایل',
	'Created Date' => 'تاریخ ایجاد',
	'Download Backup Package' => 'دانلود بسته پشتیبان',
	'Backupino Verified Package' => 'بسته تایید شده بک‌آپینو',
	'Download Archive + Installer (Both)' => 'دانلود همزمان بسته پشتیبان و فایل نصبی',
	'Download Backup Archive (.zip)' => 'دانلود بسته پشتیبان (.zip)',
	'Download Installer (installer.php)' => 'دانلود فایل نصاب (installer.php)',
	'Download Archive' => 'دانلود بسته',
	'Download Installer' => 'دانلود نصاب',
	'Download Both' => 'دانلود هر دو فایل',
	'How to restore or migrate:' => 'راهنمای بازگردانی یا انتقال:',
	'Upload both the .zip archive and installer.php into an empty directory on your destination server.' => 'هر دو فایل (آرشیو zip و installer.php) را درون یک پوشه خالی در سرور مقصد آپلود کنید.',
	'Open installer.php in your web browser and follow the on-screen steps.' => 'فایل installer.php را در مرورگر وب خود باز کرده و مراحل روی صفحه را دنبال کنید.',
	'The backup process is running in the background. Please keep this browser window open until the operation finishes completely.' => 'فرآیند پشتیبان‌گیری در پس‌زمینه در حال پردازش است. لطفاً تا پایان کامل عملیات، این پنجره مرورگر را باز نگه دارید.',
	'Live Activity Feed' => 'گزارش زنده فعالیت‌ها',
	'RUNNING' => 'در حال اجرا',
	'Ready to start backup...' => 'آماده برای شروع پشتیبان‌گیری...',
	'Initializing backup job…' => 'در حال آماده‌سازی عملیات پشتیبان‌گیری…',
	'Sealing the archive…' => 'در حال بسته‌بندی نهایی آرشیو…',
	'Finalizing the package' => 'اتمام و ثبت نهایی بسته',
	'Actions' => 'عملیات',
	'No backups yet.' => 'هنوز پشتیبانی وجود ندارد.',
	'Backup Details & Log' => 'جزئیات و لاگ نسخه پشتیبان',
	'Backup Job' => 'عملیات پشتیبان‌گیری',
	'Click to view log and details' => 'مشاهده لاگ و جزئیات پشتیبان',
	'Close' => 'بستن',
	'Cancel' => 'لغو',
	'Cancel backup job' => 'لغو فرآیند پشتیبان‌گیری',
	'Cancel the current backup job?' => 'آیا از لغو عملیات جاری پشتیبان‌گیری مطمئن هستید؟',
	'Backup cancelled.' => 'عملیات پشتیبان‌گیری لغو شد.',
	'Date' => 'تاریخ',
	'Delete backup' => 'حذف نسخه پشتیبان',
	'Download' => 'دانلود',
	'Download Archive (.zip)' => 'دانلود آرشیو فشرده (.zip)',
	'Download Archive and Installer simultaneously' => 'دانلود همزمان بسته پشتیبان و فایل نصبی',
	'Download Installer (.php)' => 'دانلود فایل نصبی (.php)',
	'Execution Log' => 'لاگ اجرایی عملیات',
	'Loading logs…' => 'در حال بارگذاری لاگ‌ها…',
	'Message' => 'پیام',
	'Size' => 'حجم',
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
	'Free' => 'رایگان',
	'Backupino Navigation' => 'ناوبری بک‌آپینو',
	'Archive & Exclusions' => 'بایگانی و استثناها',
	'Storage Destination' => 'مقصد ذخیره‌سازی',
	'Bot Notifications' => 'اطلاع‌رسانی ربات',
	'Recurring Schedule' => 'زمان‌بندی دوره‌ای',
	'Standalone Installer' => 'اینستالر مستقل',
	'Updates & Privacy' => 'به‌روزرسانی‌ها و حریم خصوصی',
	'Quick Navigation' => 'دسترسی سریع',
	'System Status' => 'وضعیت سیستم',
	'Storage & Environment' => 'فضای ذخیره‌سازی و سرور',
	'Standalone Restore' => 'بازیابی مستقل',
	'PHP Version' => 'نسخه PHP',
	'Memory Limit' => 'محدودیت حافظه',
	'Time Limit' => 'محدودیت زمان اجرا',
	'Diagnostic information about active server extensions and write permissions.' => 'اطلاعات تشخیصی درباره افزونه‌های فعال سرور و دسترسی‌های نوشتن فایل.',
	'Each backup includes installer.php alongside the archive zip. You can copy both files to an empty domain or localhost and run installer.php in your browser to restore the entire website in 3 steps without pre-installing WordPress.' => 'هر پشتیبان شامل فایل اینستالر مستقل (installer.php) در کنار فایل فشرده است. می‌توانید این دو فایل را به هاست خام یا لوکال منتقل کرده و با اجرای installer.php در مرورگر، کل سایت را بدون نیاز به نصب اولیه وردپرس در 3 مرحله بازیابی کنید.',
	'Includes WordPress core files, active themes, plugins, and uploads media library.' => 'شامل فایل‌های هسته وردپرس، قالب‌های فعال، افزونه‌ها و رسانه‌های آپلودشده.',
	'Dumps all WordPress database tables into pure standard SQL without relying on mysqldump.' => 'خروجی استاندارد و کامل تمام جداول پایگاه‌داده بدون وابستگی به mysqldump.',
	'Optional note to identify why this backup was created (e.g. before updating WooCommerce).' => 'عنوان دلخواه برای یادآوری دلیل ایجاد این پشتیبان (مثلاً قبل از به‌روزرسانی ووکامرس).',
	'Direct link that always serves the newest completed backup. Requires administrator login before download.' => 'لینک مستقیم و همیشگی برای دانلود آخرین نسخه سالم؛ پیش از دانلود نیازمند ورود مدیر سایت است.',
	'Folders or file patterns that will be excluded from the backup archive (e.g. wp-content/cache/, *.mp4).' => 'پوشه‌ها یا الگوهای فایلی که از آرشیو پشتیبان مستثنی می‌شوند (مانند wp-content/cache/ یا mp4.*).',
	'Files exceeding this size threshold will be skipped to keep backups fast and reliable. Enter 0 for no limit.' => 'فایل‌های بزرگ‌تر از این حجم نادیده گرفته می‌شوند تا فرآیند سبک بماند. مقدار 0 یعنی بدون محدودیت.',
	'HTML formatting tags such as <b>bold</b> and <a href="...">links</a> are supported.' => 'تگ‌های قالب‌بندی متنی مانند <b>درشت</b> و <a href="...">لینک‌ها</a> پشتیبانی می‌شوند.',
	'Batch size for slicing file packing per tick. Default 250 files is balanced for shared hosting.' => 'تعداد فایل‌ها در هر تیک پردازش؛ مقدار پیش‌فرض 250 فایل برای هاست‌های اشتراکی بهینه است.',
	'Primary storage destination for completed packages. In the Free edition, local protected storage is used.' => 'مقصد اصلی ذخیره‌سازی بسته‌ها؛ در نسخه رایگان، پوشه محلی محافظت‌شده روی هاست استفاده می‌شود.',
	'Select which messaging platform to send backup completion or failure alerts to.' => 'انتخاب پیام‌رسانی که گزارش اتمام یا خطای پشتیبان‌گیری به آن ارسال شود.',
	'API token provided by Telegram @BotFather or Bale Bot platform.' => 'توکن API دریافت شده از BotFather تلگرام یا پلتفرم بازوی بله.',
	'Numeric chat or channel ID where notifications should be posted.' => 'شناسه عددی چت یا کانالی که اعلان‌ها به آن ارسال می‌گردد.',
	'Enables recurring background backup jobs using WordPress built-in WP-Cron scheduler.' => 'فعال‌سازی پشتیبان‌گیری خودکار در پس‌زمینه با استفاده از کران وردپرس.',
	'In Free edition, monthly execution triggers on the 1st of each Persian/Gregorian month based on site language.' => 'در نسخه رایگان، اجرای ماهانه در روز اول هر ماه شمسی/میلادی بر اساس زبان سایت فعال می‌شود.',
	'Maximum number of recent backups to keep on disk. Older packages are automatically pruned to save space.' => 'حداکثر تعداد آخرین نسخه‌ها برای نگهداری روی هاست؛ نسخه‌های قدیمی‌تر خودکار حذف می‌شوند.',
	'Password protecting installer.php when accessed via browser during migration or restoration.' => 'رمز عبور محافظت از installer.php در زمان بازیابی یا مهاجرت از طریق مرورگر.',
	'Preset target URL for search & replace when migrating to a known new domain.' => 'آدرس اینترنتی مقصد برای جستجو و جایگزینی خودکار در زمان مهاجرت به دامنه جدید.',
	'100% privacy-compliant: zero tracking, zero telemetry, strictly optional notifications.' => '100٪ منطبق با حریم خصوصی: بدون ردیابی، بدون تله‌متری و اطلاع‌رسانی کاملاً اختیاری.',
	'%1$s files found (%2$s).' => '%1$s فایل شناسایی شد (%2$s).',
	'%1$s rows from %2$s tables (%3$s).' => '%1$s سطر از %2$s جدول (%3$s).',
	'Active' => 'فعال',
	'Archiving files' => 'بایگانی فایل‌ها',
	'Backup Storage Destination' => 'مقصد ذخیره‌سازی پشتیبان',
	'Backup in Progress' => 'در حال ایجاد نسخه پشتیبان',
	'Batch size for slicing file packing per tick. Default 1000 files ensures fast backup on modern systems.' => 'تعداد فایل‌های فشرده‌سازی در هر گام. پیش‌فرض 1000 فایل سرعت بالا و پایداری را تضمین می‌کند.',
	'Cataloging' => 'فهرست‌بندی',
	'Compatible' => 'سازگار',
	'Configure which files, folders, or sizes should be skipped during backup creation to save disk space and maximize speed.' => 'تنظیم پوشه‌ها، فایل‌ها یا حجم‌هایی که هنگام پشتیبان‌گیری نادیده گرفته می‌شوند تا حجم آرشیو کاهش و سرعت افزایش یابد.',
	'Copying the database' => 'پشتیبان‌گیری از پایگاه داده',
	'Database' => 'پایگاه داده',
	'Database Driver' => 'درایور پایگاه داده',
	'Database dump disabled.' => 'خروجی پایگاه داده غیرفعال است.',
	'Enabled' => 'فعال',
	'Encrypted' => 'رمزنگاری‌شده',
	'Environment' => 'محیط سرور',
	'Examples: wp-content/uploads/2022/, *.mp4, node_modules/. Cache and backup folders are always skipped automatically.' => 'نمونه‌ها: wp-content/uploads/2022/، *.mp4، node_modules/. پوشه‌های کش و پشتیبان همواره به‌صورت خودکار مستثنی هستند.',
	'Execution Timeout' => 'سقف زمان اجرا',
	'File archiving disabled.' => 'بایگانی فایل‌ها غیرفعال است.',
	'Files' => 'فایل‌ها',
	'Finalizing the package' => 'نهایی‌سازی بسته پشتیبان',
	'Inactive' => 'غیرفعال',
	'Initializing backup job…' => 'در حال راه‌اندازی فرآیند پشتیبان‌گیری…',
	'Isolated, protected path used to safely store archive files away from public access:' => 'مسیر حفاظت‌شده و ایزوله برای نگهداری ایمن فایل‌های آرشیو به دور از دسترسی عمومی:',
	'Live Activity Feed' => 'گزارش زنده عملیات',
	'Live diagnostic health report of active server extensions, filesystem permissions, and system stability.' => 'گزارش تشخیصی زنده از افزونه‌های فعال سرور، دسترسی‌های فایل و پایداری سیستم.',
	'Messenger Platform' => 'پلتفرم پیام‌رسان',
	'No recent log entries found.' => 'هیچ لاگی ثبت نشده است.',
	'OK' => 'تایید',
	'OpenSSL' => 'OpenSSL',
	'Package %1$s is ready (%2$s).' => 'بسته %1$s آماده است (%2$s).',
	'Packaging' => 'بسته‌بندی',
	'Packed %1$s of %2$s files (%3$s skipped, %4$s).' => 'بسته‌بندی %1$s از %2$s فایل (%3$s صرف‌نظر، %4$s).',
	'PclZip fallback' => 'جایگزین PclZip',
	'Primary Destination Driver' => 'درایور مقصد اصلی',
	'Protected' => 'محافظت‌شده',
	'Protected Directory Path' => 'مسیر دایرکتوری محافظت‌شده',
	'RUNNING' => 'در حال اجرا',
	'Ready' => 'آماده',
	'Ready to start backup...' => 'آماده برای شروع پشتیبان‌گیری...',
	'Refresh' => 'تازه‌سازی',
	'Run Storage Write Test' => 'آزمایش نوشتن در فضای ذخیره‌سازی',
	'Scanning files' => 'اسکن و بررسی فایل‌ها',
	'Sealing the archive…' => 'در حال بستن و نهایی‌سازی آرشیو…',
	'Settings Navigation' => 'فهرست تنظیمات',
	'Storage Status' => 'وضعیت فضای ذخیره‌سازی',
	'Storage folder:' => 'پوشه ذخیره‌سازی:',
	'Streaming' => 'جریان زنده',
	'System & Activity Log Stream' => 'جریان لاگ‌های سیستم و فعالیت‌ها',
	'System Activity Log (Last 100 entries)' => 'لاگ فعالیت سیستم (100 ورودی اخیر)',
	'System Status & Diagnostics' => 'وضعیت سیستم و گزارش سلامت',
	'The SQL dump file could not be opened.' => 'فایل خروجی SQL باز نشد.',
	'The archive could not be finalized (%s).' => 'آرشیو نهایی‌سازی نشد (%s).',
	'The archive could not be hashed.' => 'محاسبه هش آرشیو با خطا مواجه شد.',
	'The installer file could not be generated.' => 'تولید فایل اینستالر با شکست مواجه شد.',
	'This location contains an .htaccess file blocking direct HTTP requests and indexing for maximum security.' => 'این مسیر حاوی یک فایل htaccess. برای مسدودسازی دسترسی مستقیم HTTP و فهرست‌بندی جهت امنیت کامل است.',
	'Upload failed: %s' => 'آپلود ناموفق بود: %s',
	'Writable' => 'قابل نوشتن',
	'ZipArchive' => 'ZipArchive',
	'Telegram Notification' => 'اطلاع‌رسانی تلگرام',
	'Enable Telegram' => 'فعال‌سازی تلگرام',
	'Telegram Bot Token' => 'توکن ربات تلگرام',
	'Telegram Chat ID' => 'شناسه چت تلگرام',
	'Bot token obtained from Telegram @BotFather.' => 'توکن ربات دریافت شده از BotFather@ تلگرام.',
	'Telegram numeric chat or channel ID.' => 'شناسه عددی چت یا کانال تلگرام.',
	'Test Telegram' => 'آزمایش تلگرام',
	'Bale Notification' => 'اطلاع‌رسانی بله',
	'Enable Bale' => 'فعال‌سازی بله',
	'Bale Bot Token' => 'توکن ربات بله',
	'Bale Chat ID' => 'شناسه چت بله',
	'Bot token obtained from Bale @BotFather.' => 'توکن ربات دریافت شده از BotFather@ بله.',
	'Bale numeric chat or channel ID.' => 'شناسه عددی چت یا کانال بله.',
	'Test Bale' => 'آزمایش بله',
	'Backup Message Template' => 'الگوی پیام پشتیبان‌گیری',
	'Message template sent to messengers when a backup finishes.' => 'الگوی پیامی که پس از پایان پشتیبان‌گیری به پیام‌رسان‌ها ارسال می‌شود.',
	'Restoration Message Template' => 'الگوی پیام بازگردانی سایت',
	'Message template sent to messengers when the site is restored using the installer.' => 'الگوی پیامی که پس از بازگردانی سایت توسط فایل نصبی به پیام‌رسان‌ها ارسال می‌شود.',
	'Available tags:' => 'متغیرهای قابل استفاده:',
	'Site Name' => 'نام سایت',
	'Backup Contents' => 'محتوای پشتیبان',
	'Backup Size' => 'حجم فایل',
	'Backup Date' => 'تاریخ ایجاد',
	'Download Link' => 'لینک دانلود مستقیم',
	'New Site URL' => 'آدرس جدید سایت',
	'Admin Login URL' => 'آدرس ورود به پیشخوان',
	'Restoration Date' => 'تاریخ بازگردانی',
	'Backupino can announce every finished backup, with the permanent download link, in Telegram or Bale. You can enable either or both messengers simultaneously.' => 'بک‌آپینو می‌تواند اتمام هر نسخه پشتیبان را به همراه لینک مستقیم در تلگرام یا بله اطلاع‌رسانی کند. می‌توانید یک یا هر دو پیام‌رسان را به‌طور همزمان فعال کنید.',
	'Backupino — Site Migration Completed!' => 'بک‌آپینو — بازگردانی و انتقال وب‌سایت تکمیل شد!',
	'Your website has been successfully restored and migrated to this location. Installer files and temporary data have been cleaned up.' => 'وب‌سایت شما با موفقیت در این موقعیت جدید بازگردانی شد. فایل‌های نصبی و داده‌های موقت پاکسازی شدند.',
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
