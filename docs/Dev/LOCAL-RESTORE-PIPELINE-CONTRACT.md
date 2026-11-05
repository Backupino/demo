<div align="right" dir="rtl">

# قرارداد خط لوله بازگردانی محلی خالص — Pure Local Restore Pipeline Contract

> **Status:** ✅ Approved Contract · **Version:** 1.0.0 · **Date:** 2026-11-05  
> **عامل مسئول طراحی:** `AG-CORE` · **نگهداشت:** `AG-DOCS` · **پیاده‌سازی در هسته:** `AG-CORE` (فاز ۲ طراحی / فاز ۴ پیاده‌سازی اینترفیس‌ها)  
> **ارکستراسیون در افزونه پرو:** `AG-PRO` (`P4-T05`…`P4-T10`) · **تست و ارزیابی ایمنی:** `AG-QA` (`P4-T18`)  
> **تصمیمات مرجع:** `D-022`, `D-027`, `D-028`, `D-042`, `D-043`

---

## ۱. اهداف و اصول حاکم بر بازگردانی محلی درجا (In-Place Restore)

1. **استفاده مجدد از کلاس‌های آزموده‌شده نصاب (`D-022`):**  
   هسته رایگان شامل مجموعه‌ای غنی و یونیت‌تست‌شده از موتورهای استخراج، ایمپورت دیتابیس و ریپلایسر در پوشه `src/Installer/` است (`Extractor`, `Importer`, `SqlSplitter`, `UrlRewrite`, `ConfigWriter`). مکانیزم بازگردانی محلی دقیقاً از همین کلاس‌ها به عنوان گام‌های خط لوله پیش‌فرض استفاده می‌کند و نیازی به بازنویسی یا تکثیر کد در Pro نیست.
2. **جداسازی مرزها و عدم وابستگی متقابل:**  
   افزونه تجاری Pro هرگز کلاس‌های داخلی نصاب را به صورت هاردکد (`use Backupino\Installer\...`) فراخوانی نمی‌کند؛ بلکه هماهنگی و ارکستراسیون منحصراً از طریق اینترفیس `RestoreStep` و فیلتر `backupino_restore_pipeline` انجام می‌شود.
3. **اصل تغییرناپذیری سایت تا لحظه تأیید (Two-Phase Shadow Execution — `D-042`):**  
   فرآیند بازگردانی در دو فاز اصلی اجرا می‌شود: فاز آماده‌سازی در پوشه موقت/جداول با پیشوند موقت (`Shadow`) $\rightarrow$ بررسی سلامت سایت $\rightarrow$ تأیید نهایی کاربر/مالک $\rightarrow$ اعمال اتمیک تغییرات (`Commit`).
4. **تضمین بازگشت به عقب (Automated Rollback Guarantee):**  
   پیش از لمس هر فایلی از سایت لایو، یک اسنپ‌شات اضطراری تهیه می‌شود (`D-043`). در صورت وقوع هرگونه خطای سیستمی یا لغو توسط کاربر، تمام تغییرات به صورت معکوس لغو شده و وضعیت قبلی سایت بازگردانی می‌گردد.

---

## ۲. اینترفیس قرارداد گام بازگردانی (`RestoreStep Interface`)

هر مرحله از عملیات بازگردانی باید اینترفیس استاندارد زیر را پیاده‌سازی نماید:

```php
namespace Backupino\Restore;

/**
 * قرارداد یک گام از خط لوله بازگردانی
 */
interface RestoreStep {

    /**
     * شناسه یکتای گام (مثال: 'validate', 'extract', 'database', 'url_rewrite')
     */
    public function id(): string;

    /**
     * عنوان خوانا برای نمایش در پیشرفت زنده به کاربر
     */
    public function label(): string;

    /**
     * وزن تخمینی این گام در کل فرآیند (بین ۱ تا ۱۰۰) جهت محاسبه درصد پیشرفت
     */
    public function weight(): int;

    /**
     * آیا این مرحله قابل تقسیم به اسلایس‌های زمانی و از سرگیری (Resumable) است؟
     */
    public function is_resumable(): bool;

    /**
     * اجرای یک اسلایس از این مرحله
     *
     * @param RestoreContext $context کانتکست و متادیتای عملیات
     * @return RestoreStepResult وضعیت پایان یا نیاز به ادامه
     * @throws RestoreException در صورت خطای غیرقابل بازیابی
     */
    public function step( RestoreContext $context ): RestoreStepResult;

    /**
     * بازگردانی تغییرات این مرحله در صورت بروز خطا در مراحل بعدی
     *
     * @param RestoreContext $context
     * @return void
     */
    public function rollback( RestoreContext $context ): void;
}
```

---

## ۳. خط لوله و مراحل پیش‌فرض هسته (Default Core Restore Pipeline)

هسته رایگان مراحل زیر را به عنوان گام‌های پایه ثبت کرده و از طریق فیلتر `backupino_restore_pipeline` در اختیار قرار می‌دهد:

```text
                                خط لوله بازگردانی محلی (Restore Pipeline)
                                
[۱. اعتبارسنجی پکیج] ──► [۲. اسنپ‌شات ایمنی] ──► [۳. استخراج فایل‌ها] ──► [۴. ایمپورت پایگاه‌داده]
  ValidatePackageStep    PreRestoreSnapshotStep      ExtractStep               DatabaseImportStep
                                                                                       │
                                                                                       ▼
[۷. تثبیت نهایی و کش] ◄── [۶. پیکربندی wp-config] ◄── [۵. بازنویسی لینک‌ها و سریالایز] ◄───┘
 FinalizeRestoreStep         ConfigStep                    UrlRewriteStep
```

### تشریح مراحل ۷گانه:

| گام | نام کلاس | وزن | ماهیت | شرح عملکرد فنی |
|---|---|---|---|---|
| **۱** | `ValidatePackageStep` | ۵٪ | اتمیک | اعتبارسنجی امضا و هش SHA-256 پکیج، تست سازگاری نسخه PHP و دیتابیس، سنجش فضای دیسک ($\ge 2\times$ حجم آرشیو). |
| **۲** | `PreRestoreSnapshotStep` | ۱۰٪ | چندمرحله‌ای | تهیه اسنپ‌شات سریع از دیتابیس و فایل‌های حیاتی در پوشه ایزوله با نگهداری ۴۸ ساعته (`D-043`). |
| **۳** | `ExtractStep` | ۳۵٪ | چندمرحله‌ای | استخراج استریمی دسته‌های ۲۰۰تایی فایل‌ها با کلاس `Extractor`، حفاظت مطلق در برابر Zip-Slip، و قرنطینه فایل‌های `.htaccess` و `.user.ini` (`D-027`). |
| **۴** | `DatabaseImportStep` | ۳۰٪ | چندمرحله‌ای | پردازش استیتمنت‌های SQL با کلاس `SqlSplitter` و درج با `Importer`؛ بازنویسی آنی پیشوند جداول (Table Prefix) در صورت تغییر پیشوند دیتابیس مقصد. |
| **۵** | `UrlRewriteStep` | ۱۵٪ | چندمرحله‌ای | پیمایش هوشمند تمام ستون‌های متنی جداول با کلاس‌های `Replacer` و `SerializedParser`؛ تصحیح بایت‌های طول رشته در ساختارهای تو در توی سریالایز و JSON. |
| **۶** | `ConfigStep` | ۳٪ | اتمیک | بازنویسی تمیز `wp-config.php` با کلاس `ConfigWriter`؛ تولید کلیدها و سالت‌های امنیتی جدید با `random_bytes` و حفظ تنظیمات خاص سرور. |
| **۷** | `FinalizeRestoreStep` | ۲٪ | اتمیک | پاکسازی فایل‌های موقت، تست سلامت ارتباط دیتابیس، خروج فایل‌های سرور از قرنطینه، خالی کردن کش آبجکت (Redis/Memcached) و بازتولید rewrite rules. |

---

## ۴. کانتکست و اشیای وضعیت (`RestoreContext` & `RestoreStepResult`)

### ۴.۱. کلاس کانتکست عملیات (`RestoreContext`):
حاوی تمام اطلاعات اجرایی، مسیر بسته، دیتابیس مقصد، و کرسر ذخیره‌شده مراحل است:
```php
namespace Backupino\Restore;

final class RestoreContext {
    public string $package_path;
    public string $package_hash;
    public string $target_url;
    public string $source_url;
    public string $target_prefix;
    public string $source_prefix;
    public string $snapshot_id;
    public bool   $is_shadow_mode = false;
    public array  $step_cursors   = array();
    public array  $quarantined_files = array();

    // ذخیره وضعیت در دیتابیس یا فایل ایزوله 0600
    public function persist(): void { ... }
}
```

### ۴.۲. کلاس نتیجه اجرای هر گام (`RestoreStepResult`):
```php
namespace Backupino\Restore;

final class RestoreStepResult {
    public const DONE = 'done';
    public const CONTINUE = 'continue';

    public string $status;
    public int    $progress_percentage;
    public string $message;
    public array  $cursor;

    public static function done( string $message = '' ): self { ... }
    public static function continue( int $progress, array $cursor, string $message = '' ): self { ... }
}
```

---

## ۵. فیلتر گسترش خط لوله (`backupino_restore_pipeline`)

افزونه تجاری Pro یا ابزارهای مانیتورینگ می‌توانند مراحل اختصاصی خود را به این خط لوله اضافه کنند یا ترتیب را تغییر دهند:

```php
apply_filters( 'backupino_restore_pipeline', array $default_steps, Backupino\Restore\RestoreContext $context ): array<Backupino\Restore\RestoreStep>
```

### نمونه استفاده در Pro برای تزریق مرحله رمزگشایی و اعتبارسنجی لایسنس:
```php
add_filter( 'backupino_restore_pipeline', function( array $steps, RestoreContext $context ) {
    // تزریق گام رمزگشایی پکیج ابری قبل از استخراج
    $new_steps = array();
    foreach ( $steps as $step ) {
        if ( 'extract' === $step->id() ) {
            $new_steps[] = new \Backupino\Pro\Restore\DecryptPackageStep();
        }
        $new_steps[] = $step;
        if ( 'finalize' === $step->id() ) {
            $new_steps[] = new \Backupino\Pro\Restore\ReactivateLicenseStep();
        }
    }
    return $new_steps;
}, 10, 2 );
```

---

## ۶. سناریوی بازگردانی سایه و تأیید دومرحله‌ای (`Two-Phase Shadow Restore — D-042`)

در افزونه Pro یا هنگام فراخوانی از عامل نجات (`backupino-rescue.php`):

1. **فاز سایه (Shadow Phase):**
   * فایل‌ها در دایرکتوری موقت `wp-content/backupino/shadow_<hash>/` استخراج می‌شوند.
   * جداول دیتابیس با پیشوند موقت (مثلاً `bkshadow_wp_`) وارد می‌شوند.
   * بازنویسی لینک‌ها روی جداول سایه انجام می‌شود.
   * **سایت اصلی و زنده مشتری دست‌نخورده باقی می‌ماند.**
2. **بررسی سلامت و پیش‌نمایش (Health Check & Preview):**
   * یک توکن موقت دسترسی ادمین (`noindex/no-store`) جهت مشاهده نتیجه تست صادر می‌شود.
   * وضعیت جداول و فایل‌ها بررسی و گزارش آن به کاربر ارائه می‌شود.
3. **تأیید و اعمال اتمیک (Atomic Commit Phase):**
   * با کلیک دکمه تأیید نهایی، با دستور `RENAME TABLE` در دیتابیس، جداول جدید جایگزین جداول قدیمی می‌شوند.
   * فایل‌های تغییریافته با ژورنال اتمیک جابجا می‌گردند.
   * در صورت هرگونه خطا، اسکریپت `rollback` فوراً وضعیت اسنپ‌شات را بازمی‌گرداند.

---

## ۷. استانداردهای آزمون قرارداد بازگردانی محلی (`C2-T12`)

1. **تست رفتار اتمیک Rollback:** در صورتی که مرحله پنجم (`UrlRewriteStep`) با خطای شبیه‌سازی‌شده متوقف شود، توابع `rollback` مراحل ۴، ۳ و ۲ اجرا شده و هیچ فایلی از سایت تخریب نمی‌گردد.
2. **تست عدم نشت کدهای Pro به کلاس‌های Installer:** کلاس‌های نصاب مستقل باید بدون حضور Pro و بدون نیاز به افزونه‌های تجاری بتوانند به صورت Standalone اجرا شوند.
3. **تست قرنطینه فایل‌های سرور (`D-027`):** فایل‌های `.htaccess` و `.user.ini` تا پایان مرحله دیتابیس و اعتبارسنجی در حالت قرنطینه باقی مانده و سپس به صورت امن جایگزین شوند.

</div>
