<div align="right" dir="rtl">

# سند رزرو فیلدها و اسکیمای دیتابیس ابر اختصاصی (فاز ۷) — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه تسک:** `C2-T11` · **فاز:** فاز ۲ (قرارداد مشترک «Gate»)  
> **عامل مسئول:** `AG-PORTAL` (معمار پورتال و پایگاه داده)  
> **تصمیمات مرتبط:** `D-007`، `D-008`، `D-015`، `D-041`، `D-044`، `D-052` در [`DECISIONS.md`](DECISIONS.md)  
> **دامنه:** رزرو کامل ساختار داده‌ای، ستون‌ها و ایندکس‌های جداول دیتابیس پورتال برای قابلیت‌های ابر اختصاصی (Dedicated Cloud Storage) فاز ۷ به منظور جلوگیری قطعی از نیاز به هرگونه مهاجرت دیتابیس (`ALTER TABLE`) در زمان انتشار فاز ۷.

---

## ۱. چرایی و اصول حاکم بر رزرو داده‌ای

1. **انتقال ابر اختصاصی به فاز ۷ (`D-041`):** برای حفظ سبکی MVP و تمرکز کامل فازهای ۳ و ۴ روی هسته پرتال و افزونه Pro، پیاده‌سازی زیرساخت ذخیره‌سازی ابری به پس از لانچ موکول شده است.
2. **پیشگیری از Migrationهای پرریسک (Zero Future Alter Table):** در زمان راه‌اندازی فاز ۳ (پورتال وردپرس/ووکامرس)، جداول دیتابیس با فیلدهای رزروشده فاز ۷ ساخته می‌شوند. در فازهای ۳ تا ۶، مقادیر این فیلدها به صورت پیش‌فرض غیرفعال (`enabled: false`) هستند و هیچ کد یا پردازشی برای انتقال فایل به کلاد اجرا نخواهد شد.
3. **رمزنگاری سرتاسری و Zero-Knowledge (`D-044`):** رزرو فیلدهای کلید رمزگذاری‌شده دیتا (`wrapped_DEK`) و سهمیه‌های تفکیک‌شده به ازای هر دامنه تا کاربر نهایی بالاترین سطح امنیت را بدون امکان بازگشایی توسط مدیران سرور تجربه کند.
4. **سیاست نگهداری ۶۰ روزه با ۳ اخطار:** ثبت زمان‌بندی اخطارها و تاریخ پاکسازی در سطح رکوردهای داده‌ای برای محافظت از اطلاعات کاربر پس از انقضای لایسنس.

---

## ۲. اسکیمای جامع دیتابیس در پورتال مرکزی (MySQL / MariaDB DDL)

این جداول منحصراً روی پایگاه داده پورتال رسمی بکاپینو (`backupino.com`) ایجاد می‌شوند و هیچ ارتباطی با دیتابیس سایت‌های مشتریان ندارند:

```sql
-- ============================================================================
-- جدول لایسنس‌ها (wp_backupino_licenses) با فیلدهای رزروشده ابر فاز ۷
-- ============================================================================
CREATE TABLE IF NOT EXISTS `wp_backupino_licenses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `license_key` VARCHAR(64) NOT NULL UNIQUE,
    `plan_slug` VARCHAR(32) NOT NULL, -- trial, personal, freelancer, agency
    `status` ENUM('active', 'expiring_soon', 'grace_period', 'downgraded', 'revoked') NOT NULL DEFAULT 'active',
    `max_domains` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `active_domains_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` DATETIME NULL,
    `grace_expires_at` DATETIME NULL,
    
    -- === فیلدهای رزروشده ابر اختصاصی فاز ۷ (D-007, D-041) ===
    `cloud_storage_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `cloud_quota_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0, -- ۳۰GB برای فریلنسر، ۱۰۰GB برای سازمانی
    `cloud_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `cloud_max_versions_per_domain` SMALLINT UNSIGNED NOT NULL DEFAULT 5, -- ۵ نسخه فریلنسر، ۱۰ نسخه سازمانی
    `cloud_zero_knowledge_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1, -- D-044
    `cloud_retention_days` SMALLINT UNSIGNED NOT NULL DEFAULT 60, -- سیاست نگهداری ۶۰ روز پس از انقضا
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_licenses` (`user_id`, `status`),
    INDEX `idx_license_status` (`status`, `expires_at`),
    INDEX `idx_cloud_enabled` (`cloud_storage_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- جدول فعال‌سازی دامنه‌ها (wp_backupino_activations) با فیلدهای رزروشده سهمیه هر دامنه
-- ============================================================================
CREATE TABLE IF NOT EXISTS `wp_backupino_activations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` BIGINT UNSIGNED NOT NULL,
    `domain_hash` CHAR(64) NOT NULL,
    `domain_name` VARCHAR(255) NOT NULL,
    `is_staging` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, -- D-051
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `last_heartbeat_at` DATETIME NULL,
    `instance_id` CHAR(32) NOT NULL,
    
    -- === فیلدهای رزروشده ابر اختصاصی برای هر دامنه (D-008) ===
    `cloud_domain_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `cloud_domain_versions_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `cloud_last_backup_at` DATETIME NULL,
    
    `activated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deactivated_at` DATETIME NULL,
    
    FOREIGN KEY (`license_id`) REFERENCES `wp_backupino_licenses` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_license_domain` (`license_id`, `domain_hash`),
    INDEX `idx_domain_hash` (`domain_hash`),
    INDEX `idx_active_heartbeat` (`is_active`, `last_heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- جدول رزروشده پشتیبان‌های ابر اختصاصی (wp_backupino_cloud_backups - فاز ۷)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `wp_backupino_cloud_backups` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` BIGINT UNSIGNED NOT NULL,
    `activation_id` BIGINT UNSIGNED NOT NULL,
    `domain_hash` CHAR(64) NOT NULL,
    `backup_hash` CHAR(64) NOT NULL,
    `package_name` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `storage_provider` VARCHAR(32) NOT NULL DEFAULT 'dedicated-s3',
    `storage_path` VARCHAR(512) NOT NULL, -- مسیر آبجکت در باکت اختصاصی
    
    -- === متادیتای امنیت Zero-Knowledge و بازیابی شمیر (D-044) ===
    `wrapped_dek` TEXT NULL, -- کلید رمزگذاری‌شده دیتا با کلید عمومی کاربر
    `shamir_recovery_hint` VARCHAR(255) NULL, -- راهنمای بخش‌های کلید شمیر
    
    -- === چرخه نگهداری داده‌ها (۶۰ روز / ۳ اخطار) ===
    `retention_status` ENUM(
        'active',           -- لایسنس فعال، بکاپ معتبر
        'grace_notice_1',   -- روز اول انقضا (اخطار ۱)
        'grace_notice_2',   -- روز ۳۰ انقضا (اخطار ۲)
        'grace_notice_3',   -- روز ۵۰ انقضا (اخطار نهایی)
        'purged'            -- روز ۶۰ (فایل فیزیکی حذف شده)
    ) NOT NULL DEFAULT 'active',
    `retention_purge_scheduled_at` DATETIME NULL,
    `purged_at` DATETIME NULL,
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`license_id`) REFERENCES `wp_backupino_licenses` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`activation_id`) REFERENCES `wp_backupino_activations` (`id`) ON DELETE CASCADE,
    INDEX `idx_domain_created` (`domain_hash`, `created_at`),
    INDEX `idx_retention` (`retention_status`, `retention_purge_scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## ۳. قرارداد ساختار داده‌ای در API لایسنس (`API-CONTRACT-V1.md`)

پاسخ اندپوینت‌های فعال‌سازی (`/license/activate`) و اعتبارسنجی (`/license/validate`) از هم‌اکنون این فیلدها را در قالب JSON استاندارد تعریف و ارسال می‌کنند:

```json
{
  "plan": {
    "slug": "freelancer",
    "name": "Backupino Freelancer",
    "max_domains": 10,
    "cloud": {
      "enabled": false,
      "quota_bytes": 32212254720,
      "used_bytes": 0,
      "available_bytes": 32212254720,
      "max_versions_per_domain": 5,
      "zero_knowledge": true
    }
  },
  "usage": {
    "active_domains": 3,
    "cloud_total_bytes": 0,
    "cloud_packages_count": 0
  }
}
```

- **رفتار در فازهای ۳ تا ۶:** ویژگی `cloud.enabled` مقدار `false` دارد و کاربر در پیشخوان فقط قابلیت اتصال به ابرهای شخصی (S3، Google Drive و غیره) را مشاهده می‌کند.
- **رفتار در فاز ۷:** در پلن‌های واجد شرایط (فریلنسر و سازمانی)، فلگ `cloud.enabled` به صورت `true` بازگردانده شده و سهمیه ابری بدون هیچ تغییری در ساختار کلاینت فعال می‌گردد.

---

## ۴. چرخه نگهداری داده‌های ابری پس از انقضا (۶۰ روز / ۳ اخطار)

چرخه نگهداری ابر اختصاصی بکاپینو منحصراً بر پایه متغیرهای زمانی دیتابیس هدایت می‌شود:

```
[ انقضای لایسنس ] ──> روز ۱: اخطار اول + سوییچ به Read-Only
       │
       ├──> روز ۳۰: اخطار دوم ایمیلی (یادآوری ۳۰ روز مانده به حذف فایل‌ها)
       │
       ├──> روز ۵۰: اخطار نهایی ایمیلی (شمارش معکوس ۱۰ روزه)
       │
       └──> روز ۶۰: اجرای CronJob پاکسازی آبجکت از باکت S3 + تغییر وضعیت به purged
```

1. **حفظ دسترسی خواندنی (`D-015`):** کاربر در طول ۶۰ روز دوره مهلت، می‌تواند بکاپ‌های موجود را بدون محدودیت دانلود کند اما اجازه آپلود بکاپ جدید روی ابر اختصاصی را ندارد.
2. **ثبت حسابداری:** رکورد بکاپ در جدول دیتابیس باقی می‌ماند (`retention_status = 'purged'`) تا سابقه و لاگ‌های حسابداری مخدوش نشود، اما فایل فیزیکی از سرورهای باکت حذف می‌گردد.

---

## ۵. قرارداد رزرو اندپوینت Signed Upload URL برای فاز ۷

در فاز ۷، افزونه Pro پکیج بکاپ را مستقیماً از هاست مشتری به باکت اختصاصی آپلود می‌کند (بدون عبور از پهنای باند سرور وردپرس پورتال). اندپوینت رزروشده به صورت زیر عمل خواهد کرد:

- **مسیر درخواست:** `POST /api/v1/storage/upload-url`
- **ورودی:**
  ```json
  {
    "domain_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
    "package_name": "backup-2026-11-05-full.bkp",
    "expected_size_bytes": 104857600,
    "sha256": "4a539e083c79c8ca6...e6a"
  }
  ```
- **بررسی پرتال:**
  1. آیا `cloud_storage_enabled == 1` است؟
  2. آیا `cloud_used_bytes + expected_size_bytes <= cloud_quota_bytes` است؟
  3. آیا تعداد نسخه‌های دامنه کمتر از `cloud_max_versions_per_domain` است؟ (اگر نه، قدیمی‌ترین نسخه برای چرخش علامت‌گذاری می‌شود).
- **پاسخ پرتال:**
  ```json
  {
    "signed_upload_url": "https://dedicated-storage.backupino.com/tenant-12/pkg.bkp?AWSAccessKeyId=...&Expires=...&Signature=...",
    "expires_in_seconds": 900
  }
  ```

</div>
