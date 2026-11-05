<div align="right" dir="rtl">

# قرارداد API نسخه v1 — ارتباط افزونه و پرتال (Plugin ↔ Portal Gateway)

> **Status:** ✅ Approved Contract · **Version:** 1.0.0 · **Date:** 2026-11-04  
> **عامل مسئول طراحی:** `AG-ORCH` · **نگهداشت:** `AG-DOCS` · **پیاده‌سازی در پرتال:** `AG-PORTAL` (`S3-T10`) · **مصرف در نسخه تجاری:** `AG-PRO` (`P4-T01`…`P4-T19`)  
> **آزمون و انطباق:** `AG-QA` (`C2-T08`, `C2-T10`, `C2-T12`)

---

## ۱. اصول حاکم و خطوط قرمز (Governance & Privacy Constraints)

1. **ممنوعیت مطلق تماس از هستهٔ رایگان (`D-033`):**  
   کدهای موجود در `free/` تحت هیچ شرایطی نباید این API یا هیچ اندپوینت خارجی دیگری را فراخوانی کنند. کلیه ارتباطات منحصراً از طریق افزودنی تجاری (`pro/`) یا اسکریپت نجات (`backupino-rescue.php`) برقرار می‌گردد تا انطباق ۱۰۰٪ با بندهای ۵، ۶ و ۷ قوانین مخزن رسمی WordPress.org حفظ شود.
2. **مدل ارتباطی Pull-Only (خروجی امن HTTPS):**  
   هیچ اندپوینت ورودی (Inbound Webhook) روی سایت مشتری باز نمی‌شود. سایت مشتری است که فرآیندها را از طریق درخواست‌های خروجی HTTPS فراخوانی یا واکشی (Poll/Pull) می‌کند (`D-042`).
3. **محرمانگی اطلاعات هویتی و داده‌ها:**  
   دامنهٔ خام سایت هرگز در هدرها یا بدنه فرستاده نمی‌شود؛ بلکه هش یکطرفهٔ دامنه (`domain_hash`) به همراه شناسه یکتای نصب (`instance_id`) ارسال می‌گردد (`C2-T10`). محتوای سایت، جداول دیتابیس، فایل‌های پشتیبان و اطلاعات `wp-config.php` هرگز به پرتال ارسال نمی‌شوند.
4. **معماری آفلاین‌فرست و کش ۷ روزه (`D-032`):**  
   سند لایسنس به صورت امضاشده در دیتابیس لوکال سایت کش می‌شود. در صورت قطعی اینترنت یا از دسترس خارج شدن پرتال، کلیه امکانات پشتیبان‌گیری تا **۷ روز** بدون وقفه به کار خود ادامه می‌دهند.

---

## ۲. استانداردهای عمومی پروتکل (Protocol Specifications)

* **پروتکل ارتباطی:** `HTTPS` (TLS 1.2 حداقل، TLS 1.3 پیش‌فرض)
* **فرمت داده:** `application/json; charset=utf-8`
* **آدرس پایه در پرتال:**  
  `https://portal.backupino.com/wp-json/backupino/v1/`

### ۲.۱. هدرهای استاندارد در کلیه درخواست‌ها

| نام هدر | الزامی | شرح و ساختار |
|---|---|---|
| `X-Backupino-License-Key` | برای فعال‌سازی | کلید لایسنس خریداری‌شده (فرمت: `BK-XXXX-XXXX-XXXX-XXXX`) |
| `X-Backupino-Activation-ID` | پس از فعال‌سازی | شناسه فعال‌سازی تخصیص‌یافته توسط پرتال (UUIDv4) |
| `X-Backupino-Instance-ID` | بله | شناسه یکتای نصب افزونه در سایت لوکال (UUIDv4 تولیدشده با `random_bytes`) |
| `X-Backupino-Domain-Hash` | بله | `sha256( canonical_domain + instance_id )` بدون پروتکل و بدون `www` |
| `X-Backupino-Timestamp` | بله | زمان یونیکس بر حسب ثانیه (UTC). درخواست‌های با اختلاف بیش از ۳۰۰ ثانیه رد می‌شوند (Anti-Clock Skew). |
| `X-Backupino-Nonce` | بله | رشته تصادفی هگز ۳۲ کاراکتری؛ جلوگیری قطعی از حملات بازپخش (Anti-Replay). |
| `X-Backupino-Plugin-Version` | بله | نسخهٔ جاری افزونه رایگان و پرو، مثال: `free=1.0.2;pro=1.0.0` |
| `Idempotency-Key` | در متدهای تغییردهنده | شناسه یکتای UUIDv4 برای تضمین تکرارپذیری امن درخواست‌ها بدون اثر مضاعف. |

### ۲.۲. ساختار یکپارچه پاسخ‌ها (Response Envelope)

#### پاسخ موفق (`HTTP 200 / 201`):
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": 1730736000,
    "request_id": "req_01ja89c1x4...",
    "server_version": "1.0.0"
  }
}
```

#### پاسخ خطا (`HTTP 4xx / 5xx`):
```json
{
  "success": false,
  "error": {
    "code": "DOMAIN_LIMIT_REACHED",
    "message": "Domain activation limit exceeded for this license.",
    "details": {
      "active_count": 10,
      "quota": 10
    },
    "remediation_url": "https://backupino.com/my-account/licenses/"
  },
  "meta": {
    "timestamp": 1730736000,
    "request_id": "req_01ja89c1x4..."
  }
}
```

### ۲.۳. کاتالوگ جامع کدهای خطا (Error Catalog)

| کد خطا | وضعیت HTTP | مفهوم و اقدام کلاینت |
|---|---|---|
| `UNAUTHORIZED` | 401 | کلید لایسنس یا امضای درخواست معتبر نیست. |
| `LICENSE_EXPIRED` | 403 | اشتراک به پایان رسیده؛ افزونه به حالت رایگان با دسترسی خواندنی برمی‌گردد. |
| `LICENSE_SUSPENDED` | 403 | لایسنس به دلیل نقض شرایط استفاده توسط ادمین معلق شده است. |
| `DOMAIN_LIMIT_REACHED` | 409 | سقف تعداد دامنه‌های مجاز پر شده است (`D-004`). |
| `DOMAIN_MISMATCH` | 403 | هش دامنه با سند فعال‌سازی همخوانی ندارد. |
| `STAGING_DOMAIN_NOT_WHITELISTED` | 400 | دامنه استیجینگ خارج از الگوی رجکس مجاز است (`D-051`). |
| `REPLAY_DETECTED` | 400 | مقدار `X-Backupino-Nonce` پیش‌تر در بازه ۲۴ ساعته مصرف شده است. |
| `CLOCK_SKEW_DETECTED` | 400 | ساعت سرور کلاینت با ساعت پرتال بیش از ۵ دقیقه مغایرت دارد. |
| `RATE_LIMITED` | 429 | تعداد درخواست‌ها فراتر از سقف مجاز است؛ هدر `Retry-After` باید رعایت شود. |
| `VERSION_INCOMPATIBLE` | 426 | نسخه افزونه منسوخ شده و نیاز به ارتقا دارد (`C2-T08`). |
| `IDEMPOTENCY_CONFLICT` | 409 | درخواستی با همین `Idempotency-Key` ولی با بدنهٔ متفاوت قبلاً ارسال شده است. |
| `INTERNAL_SERVER_ERROR` | 500 | خطای سرور پرتال؛ کلاینت باید طبق الگوریتم Exponential Backoff مجدداً تلاش کند. |

### ۲.۴. محدودیت نرخ درخواست (Rate Limiting)

پرتال از الگوریتم **Token Bucket** استفاده می‌کند و در پاسخ‌ها هدرهای استاندارد زیر را بازمی‌گرداند:
* `X-RateLimit-Limit`: حداکثر تعداد مجاز در بازه زمانی
* `X-RateLimit-Remaining`: تعداد درخواست‌های باقی‌مانده
* `X-RateLimit-Reset`: زمان یونیکس ریست شدن سقف

**سقف‌های مجاز:**
* متدهای واکشی عمومی (`/plans`, `/version-matrix`): ۶۰ درخواست در دقیقه بر اساس IP.
* متدهای دوره‌ای و پولینگ (`/heartbeat`, `/command-queue`): ۳۰ درخواست در دقیقه بر اساس لایسنس.
* متدهای ایجاد/تغییر (`/activate`, `/deactivate`, `/backup-index`): ۱۰ درخواست در دقیقه بر اساس لایسنس.

---

## ۳. اندپوینت‌های ۱۱‌گانه قرارداد API v1

---

### ۱. فعال‌سازی لایسنس دامنه (`POST /license/activate`)

ثبت و گره‌زدن لایسنس به یک دامنه و دریافت اولین سند امضاشده.

* **آدرس:** `POST /wp-json/backupino/v1/license/activate`
* **مجوز:** نیاز به هدر `X-Backupino-License-Key`
* **بدنه درخواست (JSON Schema):**
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["site_url_hash", "php_version", "wp_version", "pro_version", "locale", "is_staging"],
  "properties": {
    "site_url_hash": { "type": "string", "pattern": "^[a-f0-9]{64}$" },
    "site_title_sanitized": { "type": "string", "maxLength": 100 },
    "php_version": { "type": "string", "maxLength": 10 },
    "wp_version": { "type": "string", "maxLength": 10 },
    "pro_version": { "type": "string", "maxLength": 10 },
    "locale": { "type": "string", "enum": ["fa_IR", "en_US", "other"] },
    "is_staging": { "type": "boolean" }
  }
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "activation_id": "act_89fa12cd01",
    "plan": "freelancer",
    "domains_used": 3,
    "domains_limit": 10,
    "is_staging": false,
    "issued_at": 1730736000,
    "expires_at": 1762272000,
    "signed_license_blob": "eyJwbGFuIjoiZnJlZWxhbmNlciIsLi4ufQ==.9a8b7c6d...<Ed25519-Signature>",
    "cloud_quota_bytes": 32212254720,
    "features": {
      "multisite": true,
      "whitelabel": true,
      "restore_in_place": true,
      "cloud_s3": true,
      "cloud_ftp": true,
      "incremental": true
    }
  }
}
```

---

### ۲. اعتبارسنجی دوره‌ای لایسنس (`POST /license/validate`)

بررسی آخرین وضعیت فعال‌سازی و دریافت سند تمدیدشدهٔ امضاشده.

* **آدرس:** `POST /wp-json/backupino/v1/license/validate`
* **بدنه درخواست:**
```json
{
  "activation_id": "act_89fa12cd01",
  "cached_license_signature_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "status": "active",
    "plan": "freelancer",
    "expires_at": 1762272000,
    "offline_ttl_seconds": 604800,
    "signed_license_blob": "eyJwbGFuIjoiZnJlZWxhbmNlciIsLi4ufQ==.<signature>"
  }
}
```

---

### ۳. بررسی ضربان سلامت اختیاری (`POST /license/heartbeat`)

ارسال وضعیت سلامت بکاپ‌ها و دریافت آمار دستورات معلق (`D-032`).

* **آدرس:** `POST /wp-json/backupino/v1/license/heartbeat`
* **بدنه درخواست:**
```json
{
  "activation_id": "act_89fa12cd01",
  "last_backup_timestamp": 1730730000,
  "last_backup_status": "completed",
  "total_backups_count": 4,
  "health_status": "good"
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "acknowledged": true,
    "pending_commands_count": 0,
    "next_heartbeat_delay_seconds": 86400,
    "notice": null
  }
}
```

---

### ۴. غیرفعال‌سازی و آزادسازی دامنه (`POST /license/deactivate`)

آزادسازی اسلات دامنه جهت استفاده در سایتی دیگر طبق قوانین پلن (`D-006`).

* **آدرس:** `POST /wp-json/backupino/v1/license/deactivate`
* **بدنه درخواست:**
```json
{
  "activation_id": "act_89fa12cd01",
  "reason": "site_migration_complete"
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "deactivated": true,
    "domains_used": 2,
    "domains_limit": 10,
    "cooling_off_period_seconds": 259200
  }
}
```

---

### ۵. دریافت کاتالوگ پلن‌ها (`GET /plans`)

واکشی سهمیه‌ها، قیمت‌ها و قابلیت‌های عمومی پلن‌ها به صورت داده‌های امضاشده (`D-004`).

* **آدرس:** `GET /wp-json/backupino/v1/plans`
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": "trial",
        "name": "Trial",
        "duration_days": 10,
        "domains_limit": 1,
        "cloud_quota_bytes": 0,
        "retention_customer_cloud": 2,
        "domain_swaps_allowed": 0
      },
      {
        "id": "freelancer",
        "name": "Freelancer",
        "duration_days": 365,
        "domains_limit": 10,
        "cloud_quota_bytes": 32212254720,
        "retention_customer_cloud": 5,
        "domain_swaps_allowed": -1
      }
    ]
  }
}
```

---

### ۶. واکشی آمار مصرف و سهمیه‌ها (`GET /usage`)

بررسی حجم مصرفی ابر اختصاصی و تعداد نسخه‌های نگهداری‌شده.

* **آدرس:** `GET /wp-json/backupino/v1/usage`
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "plan": "freelancer",
    "domains": { "used": 3, "limit": 10 },
    "dedicated_cloud": {
      "used_bytes": 4294967296,
      "limit_bytes": 32212254720,
      "usage_percent": 13.3
    },
    "backup_versions_on_cloud": 4,
    "max_versions_per_domain": 5
  }
}
```

---

### ۷. ثبت متادیتای ایندکس بکاپ (`POST /backup-index`)

گزارش ایجاد بسته جدید روی ابر اختصاصی یا فضاهای متصل جهت نمایش به مالک در پنل.

* **آدرس:** `POST /wp-json/backupino/v1/backup-index`
* **بدنه درخواست:**
```json
{
  "activation_id": "act_89fa12cd01",
  "package_hash": "a1b2c3d4e5f6...<sha256>",
  "package_size_bytes": 104857600,
  "db_size_bytes": 12582912,
  "storage_driver": "s3_arvan",
  "is_incremental": false,
  "created_at": 1730735000
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "indexed": true,
    "retention_action": "none",
    "prune_candidates": []
  }
}
```

---

### ۸. درخواست لینک دانلود امضاشده موقت (`POST /storage/download-url`)

دریافت URL مستقیم با انقضای کوتاه جهت انتقال بسته بکاپ (`D-010`).

* **آدرس:** `POST /wp-json/backupino/v1/storage/download-url`
* **بدنه درخواست:**
```json
{
  "activation_id": "act_89fa12cd01",
  "package_hash": "a1b2c3d4e5f6...",
  "requested_expiry_seconds": 3600
}
```
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "download_url": "https://storage.backupino.com/packages/pkg_xyz?sig=...&exp=1730739600",
    "expires_at": 1730739600,
    "sha256": "a1b2c3d4e5f6..."
  }
}
```

---

### ۹. پرچم‌های قابلیت‌ها و سوئیچ‌های فنی (`GET /feature-flags`)

کنترل تدریجی فعال‌سازی ویژگی‌ها بدون نیاز به آپدیت فوری سورس.

* **آدرس:** `GET /wp-json/backupino/v1/feature-flags`
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "flags": {
      "enable_ftp_tls": true,
      "enable_s3_multipart_v2": true,
      "enable_direct_rescue_agent": true,
      "enable_delta_packs": false
    }
  }
}
```

---

### ۱۰. ماتریس سازگاری نسخه‌ها (`GET /version-matrix`)

بررسی تطابق نسخه‌های `free` و `pro` با نیازمندی‌های پرتال (`C2-T08`).

* **آدرس:** `GET /wp-json/backupino/v1/version-matrix`
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "minimum_supported": {
      "free": "1.0.1",
      "pro": "1.0.0",
      "php": "7.4"
    },
    "latest_stable": {
      "free": "1.0.2",
      "pro": "1.0.0"
    },
    "status": "compatible"
  }
}
```

---

### ۱۱. صف پولینگ فرامین دستی پرتال (`GET /command-queue`)

واکشی فرامین ثبت‌شده توسط مالک در پنل کاربری جهت اجرای ایمن در سایت (`D-042`).

* **آدرس:** `GET /wp-json/backupino/v1/command-queue`
* **پاسخ موفق (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "command_id": "cmd_99210aa",
        "action": "PREPARE_SHADOW_RESTORE",
        "payload": {
          "package_hash": "a1b2c3d4e5f6...",
          "backup_source_url": "https://storage.backupino.com/..."
        },
        "nonce": "7f8b9c0d1e2f3a4b",
        "issued_at": 1730735500,
        "expires_at": 1730736100,
        "signature": "<Ed25519-Signature-of-command-by-portal-private-key>"
      }
    ]
  }
}
```

---

## ۴. چرخهٔ اعتبارسنجی امضای فرامین (Command Signature Verification)

تمام فرامینی که از اندپوینت `/command-queue` واکشی می‌شوند، توسط کلید خصوصی پرتال امضا شده‌اند. کلاینت پرو موظف است قبل از هرگونه اجرا:
1. تاریخ انقضا (`expires_at`) را بسنجد (حداکثر ۱۰ دقیقه عمر دارند).
2. هش دامنه و `instance_id` مندرج در فرمان را با هویت لوکال مقایسه کند.
3. امضای `Ed25519` فرمان را با کلید عمومی پرتال اعتبارسنجی کند.
4. وضعیت اجرای فرمان را پس از پایان از طریق گزارش نتیجه به پرتال اطلاع دهد.

</div>
