<div align="right" dir="rtl">

# مشخصات قرارداد نقاط اتصال هسته رایگان — Free Core Extension Hooks Contract

> **Status:** ✅ Approved Contract · **Version:** 1.0.0 · **Date:** 2026-11-05  
> **عامل مسئول طراحی:** `AG-CORE` · **نگهداشت:** `AG-DOCS` · **پیاده‌سازی در هسته:** `AG-CORE` (فاز ۲ طراحی / فاز ۴ یکپارچگی)  
> **مصرف در افزونه تجاری:** `AG-PRO` (`P4-T01`…`P4-T17`) · **ارزیابی سازگاری و امنیت:** `AG-QA` (`C2-T08`, `C2-T12`)  
> **تصمیمات مرجع:** `D-001`, `D-002`, `D-013`, `D-022`, `D-033`, `D-045`

---

## ۱. اصول حاکم بر طراحی نقاط اتصال (Extension Architecture Principles)

1. **معماری افزونه مکمل (Addon Model — `D-022`):**  
   افزونه تجاری (`backupino-pro`) به عنوان یک بسته مکمل، سبک و مستقل طراحی شده که بر بستر افزونه رایگان (`backupino`) نصب و فعال می‌شود. این معماری مانع از تکرار کدهای پایه (DRY) شده و حجم افزونه تجاری را به کمتر از ۳۰۰ کیلوبایت محدود می‌سازد.
2. **استقلال مطلق و ایزولاسیون هسته رایگان (`D-002` / `D-033`):**  
   در سورس هسته رایگان (`free/`) هیچ‌گونه واردسازی کلاس (`use Backupino\Pro\...`)، شرط بررسی وجود کلاس تجاری (`class_exists`) یا وابستگی به لایسنس مجاز نیست. اتصال قابلیت‌ها منحصراً از طریق فیلترها (`apply_filters`) و اکشن‌های استاندارد وردپرس (`do_action`) صورت می‌پذیرد.
3. **ایمنی در غیاب افزونه پرو (Safe Defaults & Graceful Degradation):**  
   چنانچه افزونه پرو غیرفعال باشد، فیلترهای هسته با مقادیر پیش‌فرض بازگشته و هسته رایگان ۱۰۰٪ پایدار، خودکفا و بدون خطا به کار خود ادامه می‌دهد.
4. **حفاظت در برابر خطاهای افزونه‌های جانبی:**  
   موتور صف و پایپ‌لاین هسته (`JobRunner`) هرگونه استثنا (Exception) ناشی از توابع متصل‌شده به هوک‌ها را مدیریت و ثبت نموده و مانع از کرش کردن فرآیند پشتیبان‌گیری یا ایجاد وضعیت‌های نامعتبر می‌شود.

---

## ۲. هوک‌های پیاده‌سازی‌شده و فعال در هسته رایگان (Existing Core Hooks)

این هوک‌ها در حال حاضر در نسخه ۱.۰.۲ هسته رایگان وجود دارند و آماده بهره‌برداری توسط Pro می‌باشند:

### ۲.۱. فیلتر سطح دسترسی مدیریت (`backupino_manage_capability`)
* **محل فراخوانی:** `free/src/Core/Capabilities.php`, `free/src/Download/AccessPolicy.php`, `free/src/Admin/Admin.php`, `free/src/Admin/RestController.php`
* **امضا (Signature):**
  ```php
  apply_filters( 'backupino_manage_capability', string $default_capability = 'manage_options' ): string
  ```
* **کاربرد:** امکان تغییر سطح دسترسی مجاز برای مدیریت، دانلود و ایجاد بسته‌ها (مثلاً تعریف قابلیت‌های سفارشی برای نقش‌های ویژه، مدیران شبکه وردپرس در مالتی‌سایت یا کاربران پنل نمایندگی).

### ۲.۲. فیلتر ثبت درایورهای ذخیره‌سازی (`backupino_storage_drivers`)
* **محل فراخوانی:** `free/src/Storage/StorageManager.php`
* **امضا:**
  ```php
  apply_filters( 'backupino_storage_drivers', array $drivers ): array<string, class-string<Backupino\Storage\Storage>>
  ```
* **مقدار پیش‌فرض هسته:** `array( 'local' => LocalStorage::class )`
* **کاربرد Pro:** ثبت درایورهای ابری نظیر S3 (`S3Storage`)، اف‌تی‌پی (`FtpStorage`)، گوگل درایو و ابر اختصاصی بدون نیاز به تغییر در کلاس‌های هسته.

### ۲.۳. فیلترهای زمان‌بندی دوره‌ای (`backupino_schedule_intervals` و `backupino_schedule_intervals_labels`)
* **محل فراخوانی:** `free/src/Cron/Scheduler.php`
* **امضا:**
  ```php
  apply_filters( 'backupino_schedule_intervals', array $intervals ): array<string, int>
  apply_filters( 'backupino_schedule_intervals_labels', array $labels ): array<string, string>
  ```
* **کاربرد Pro:** افزودن اینتروال‌های سفارشی ساعتی، روزانه، دو بار در روز و هفتگی برای پشتیبان‌گیری خودکار بدون وابستگی به هسته.

### ۲.۴. اکشن تکمیل موفقیت‌آمیز عملیات پشتیبان‌گیری (`backupino_job_completed`)
* **محل فراخوانی:** `free/src/Jobs/JobRunner.php`
* **امضا:**
  ```php
  do_action( 'backupino_job_completed', Backupino\Jobs\Job $job ): void
  ```
* **کاربرد:** ارسال اعلان به تلگرام/بله شخصی در نسخه رایگان، ارسال گزارش به وب‌هوک پرتال در Pro، هماهنگی با ربات مرکزی یا اجرای لاگ‌های ممیزی.

### ۲.۵. فیلترهای توکن طراحی و استایل صفحه دانلود (`backupino_design_tokens` و `backupino_download_page_styles`)
* **محل فراخوانی:** `free/src/Download/PublicDownload.php` (پیاده‌سازی‌شده در `P1-T12`)
* **امضا:**
  ```php
  apply_filters( 'backupino_design_tokens', array $default_tokens ): array
  apply_filters( 'backupino_download_page_styles', string $default_css ): string
  ```
* **کاربرد:** هماهنگ‌سازی فونت‌ها، رنگ‌های سازمانی و قالب صفحه فرود دانلود عمومی با المان‌های برند و قالب اختصاصی پرتال (`D-036`).

---

## ۳. هوک‌های برنامه‌ریزی‌شده و قراردادهای آتی (Planned Extension Hooks)

این نقاط اتصال در فاز دوم طراحی شده و در فاز چهارم همزمان با انتشار افزونه Pro در هسته رایگان مستقر می‌گردند:

### ۳.۱. فیلتر تزریق مراحل پایپ‌لاین (`backupino_stages`)
پایپ‌لاین استاندارد هسته دارای ۴ مرحله است (`scan` $\rightarrow$ `files` $\rightarrow$ `database` $\rightarrow$ `finalize`). این فیلتر به Pro اجازه می‌دهد مراحل پردازشی پیشرفته را به صورت امن در میانه خط لوله تزریق کند.

* **محل فراخوانی:** `Backupino\Plugin::pipeline( Job $job )`
* **امضا:**
  ```php
  apply_filters( 'backupino_stages', array $stages, Backupino\Jobs\Job $job ): array<Backupino\Jobs\Stage>
  ```
* **سناریوهای تزریق در Pro:**
  1. `EncryptionStage`: رمزنگاری بسته با الگوریتم متقارن AES-256-GCM پس از پایان مرحله فایل و دیتابیس.
  2. `CloudUploadStage`: استریم بسته تولیدشده به فضای ابری S3 یا سرورهای دانلود FTP.
  3. `LocalCleanupStage`: حذف فایل آرشیو محلی پس از اطمینان از سلامت آپلود ابری جهت صرفه‌جویی در دیسک هاست.

```text
پایپ‌لاین پایه (Free):
[ScanStage] ──► [FilesStage] ──► [DatabaseStage] ──────────────────────────► [FinalizeStage]

پایپ‌لاین توسعه‌یافته (Pro Addon):
[ScanStage] ──► [FilesStage] ──► [DatabaseStage] ──► [EncryptionStage] ──► [CloudUploadStage] ──► [LocalCleanupStage] ──► [FinalizeStage]
```

### ۳.۲. فیلتر سهمیه‌ها و محدودیت‌ها (`backupino_limits`)
* **محل فراخوانی:** `Backupino\Core\Settings::sanitize()` و موتورهای پردازشی
* **امضا:**
  ```php
  apply_filters( 'backupino_limits', array $limits, string $context = 'general' ): array
  ```
* **ساختار داده‌ای پیش‌فرض هسته:**
  ```php
  array(
      'max_retention_copies' => 10,
      'time_budget_seconds'  => 20,
      'allow_arbitrary_cron' => false,
      'allow_cloud_storage'  => false,
      'allow_restore_shadow' => false,
  )
  ```
* **کاربرد Pro:** آزادسازی سقف نگهداری و فعال‌سازی قابلیت‌های تجاری بر مبنای سند لایسنس اعتبارسنجی‌شده.

### ۳.۳. فیلتر فعال‌سازی ماژول‌های رابط کاربری پرو (`backupino_pro_features`)
* **محل فراخوانی:** `free/src/Admin/Views.php` و متدهای رندر کنترلر پیشخوان
* **امضا:**
  ```php
  apply_filters( 'backupino_pro_features', array $active_features ): array<string, bool>
  ```
* **کاربرد:** فعال‌سازی تب‌های تنظیمات اختصاصی Pro در صفحه تنظیمات افزونه (مانند تب تنظیمات کلاد، فیلدهای رمزنگاری پکیج، و تنظیمات ریستور شادو) بدون بازنویسی رابط کاربری هسته.

### ۳.۴. رویداد انتشار و نمایه پکیج (`backupino_package_published`)
* **محل فراخوانی:** `Backupino\Backup\Stages\FinalizeStage::step()`
* **امضا:**
  ```php
  do_action( 'backupino_package_published', Backupino\Backup\Package $package, Backupino\Jobs\Job $job ): void
  ```
* **کاربرد:** اطلاع به ماژول مدیریت شاخص‌ها جهت ارسال متادیتای پکیج به پرتال مرکزی (`POST /v1/backup-index`) و ساخت ژورنال بسته‌های دلتای بکاپ افزایشی (`D-050`).

### ۳.۵. فیلتر فیلترسازی فایل‌ها و دایرکتوری‌ها (`backupino_file_filter_rules`)
* **محل فراخوانی:** `free/src/Backup/FileFilter.php`
* **امضا:**
  ```php
  apply_filters( 'backupino_file_filter_rules', array $rules, string $context = 'archive' ): array<string>
  ```
* **کاربرد Pro:** افزودن هوشمند قوانین مستثنی‌سازی فایل‌های کش، لاگ‌های حجیم و ایزولاسیون پوشه‌های خاص بر حسب پلن مشتری.

---

## ۴. الگوی استاندارد اتصال در افزونه Pro (Pro Addon Hook Provider Pattern)

کلاس ارکستراسیون افزونه تجاری با الگوی استاندارد زیر به هسته متصل می‌گردد:

```php
namespace Backupino\Pro;

defined( 'ABSPATH' ) || exit;

final class ProAddon {

    public static function bootstrap(): void {
        // ۱. اطمینان از لود بودن هسته رایگان
        if ( ! did_action( 'backupino_loaded' ) && ! class_exists( 'Backupino\Plugin' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'render_missing_core_notice' ) );
            return;
        }

        self::register_hooks();
    }

    private static function register_hooks(): void {
        // ثبت درایورهای ابری
        add_filter( 'backupino_storage_drivers', array( Storage\DriverRegistry::class, 'register' ) );

        // تزریق مراحل رمزنگاری و آپلود ابری به پایپ‌لاین
        add_filter( 'backupino_stages', array( Pipeline\StageInjector::class, 'inject' ), 10, 2 );

        // باز کردن محدودیت‌ها طبق لایسنس
        add_filter( 'backupino_limits', array( License\LimitsProvider::class, 'apply' ), 10, 2 );

        // فعال‌سازی المان‌های پرو در پیشخوان
        add_filter( 'backupino_pro_features', array( Admin\FeatureRegistry::class, 'expose' ) );

        // هوک اطلاع‌رسانی پکیج به پرتال
        add_action( 'backupino_package_published', array( Sync\IndexReporter::class, 'report' ), 10, 2 );
    }

    public static function render_missing_core_notice(): void {
        echo '<div class="notice notice-error"><p>' .
             esc_html__( 'Backupino Pro requires the free Backupino plugin to be installed and active.', 'backupino-pro' ) .
             '</p></div>';
    }
}
add_action( 'plugins_loaded', array( 'Backupino\Pro\ProAddon', 'bootstrap' ), 15 );
```

---

## ۵. چک‌لیست آزمون و اعتبارسنجی نقاط اتصال (`C2-T12`)

1. **تست قطع ارتباط کامل (Zero Failure Test):** غیرفعال‌سازی یا حذف افزونه Pro نباید هیچ‌گونه Notice، Warning یا خطای PHP در هسته رایگان ایجاد کند.
2. **تست تزریق مرحله معیوب:** اگر یکی از استیج‌های تزریق‌شده توسط فیلتر `backupino_stages` دچار خطا شد، مکانیزم rollback و خطایابی `JobRunner` باید خطا را به درستی به کاربر گزارش دهد.
3. **تست ایزولاسیون فضای نام:** هیچ فایلی از `free/src/` نباید به صورت هاردکد شامل کلاس‌های فضای نام `Backupino\Pro\` باشد.

</div>
