<div align="right" dir="rtl">

# سند قواعد لیست سفید دامنه‌های محلی و استیجینگ — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه تسک:** `C2-T14` · **فاز:** فاز ۲ (قرارداد مشترک «Gate»)  
> **عامل مسئول:** `AG-ORCH` (طراح قرارداد و معماری سیستم)  
> **تصمیمات مرتبط:** `D-003`، `D-005`، `D-006`، `D-046`، `D-051`، `D-052` در [`DECISIONS.md`](DECISIONS.md)  
> **پیشنیازها:** اسناد مصوب [`API-CONTRACT-V1.md`](API-CONTRACT-V1.md)، [`LICENSE-CONTRACT-ED25519.md`](LICENSE-CONTRACT-ED25519.md) و [`HYBRID-LICENSE-MODEL-B.md`](HYBRID-LICENSE-MODEL-B.md).  
> **دامنه:** تدوین قواعد ریاضی، الگوریتم نرمال‌سازی دامنه، رجکس‌های مجاز و مکانیزم‌های ضد-سوءاستفاده برای محیط‌های توسعه محلی و آزمایشی (Local & Staging Whitelist) بدون کسر سهمیه لایسنس.

---

## ۱. فلسفه و اهداف تجاری

1. **حمایت از چرخه توسعه چابک (Developer-Friendly Licensing):** توسعه‌دهندگان وردپرس و آژانس‌ها وب‌سایت‌های خود را در محیط‌های لوکال (مانند LocalWP, XAMPP, Docker) یا سرورهای تست/استیجینگ آماده‌سازی می‌کنند.
2. **عدم کسر سهمیه لایسنس (`D-051`):** فعال‌سازی افزونه Pro در محیط‌های محلی و استیجینگ نباید از ظرفیت دامنه‌های لایسنس (`max_domains`) کسر کند و نباید کاربر را وادار به ابطال یا جابجایی دستی لایسنس نماید.
3. **پیشگیری از سوءاستفاده (Anti-Gaming):** تعریف معیارهای دقیق برای تفکیک سایت‌های تولیدی واقعی (Production) از محیط‌های تستی، تا امکان فعال‌سازی سایت تجاری به بهانه استیجینگ مسدود گردد.

---

## ۲. الگوریتم نرمال‌سازی دامنه (Normalization Algorithm)

پیش از ارزیابی الگوها، آدرس دامنه سایت توسط تابع قطعی زیر پاکسازی و یکدست می‌شود:

```php
/**
 * Normalize site URL to extract clean host domain.
 *
 * @param string $raw_url Raw URL or host (e.g. "https://Staging.MySite.com:8080/wp/").
 * @return string Clean lowercase host (e.g. "staging.mysite.com").
 */
function backupino_normalize_domain( string $raw_url ): string {
    $url = trim( strtolower( $raw_url ) );
    
    // اطمینان از وجود پیشوند طرح جهت پارس دقیق
    if ( ! preg_match( '#^https?://#i', $url ) ) {
        $url = 'https://' . $url;
    }
    
    $host = (string) parse_url( $url, PHP_URL_HOST );
    if ( empty( $host ) ) {
        $host = trim( $raw_url );
    }
    
    // حذف پورت‌های احتمالی
    $host = preg_replace( '/:\d+$/', '', $host );
    
    return strtolower( trim( $host, " \t\n\r\0\x0B./" ) );
}
```

---

## ۳. کاتالوگ جامع الگوهای لیست سفید (Whitelist Patterns & Regex Catalog)

هر دامنه‌ای که با یکی از گروه‌های چهارگانه زیر تطابق داشته باشد، به عنوان **استیجینگ (`is_staging = true`)** علامت‌گذاری شده و بدون کسر سهمیه فعال می‌شود:

### ۳.۱. لوپ‌بک و دامنه‌های محلی (Localhost & Loopback)
- **دامنه‌ها:**
  - `localhost`
  - `127.0.0.1`
  - `::1`
- **رجکس تطبیق:**
  ```regex
  ^(localhost|127\.0\.0\.1|\[::1\])$
  ```

### ۳.۲. دامنه‌های سطح بالا (TLD) ویژه توسعه محلی (RFC 2606 / RFC 6761)
- **پسوندها:** `*.test`, `*.example`, `*.invalid`, `*.localhost`, `*.local`, `*.internal`, `*.lan`
- **مثال‌ها:** `client.test`, `shop.local`, `dev-site.lan`
- **رجکس تطبیق:**
  ```regex
  \.(test|example|invalid|localhost|local|internal|lan)$
  ```

### ۳.۳. ساب‌دامنه‌های استیجینگ و توسعه (Staging Subdomains)
این پیشوندها صرفاً زمانی پذیرفته می‌شوند که در موقعیت **ساب‌دامنه** قرار داشته باشند (حداقل ۲ نقطه در دامنه):
- **پیشوندها:** `staging.*`, `dev.*`, `test.*`, `stage.*`, `beta.*`, `qa.*`, `uat.*`
- **مثال‌های معتبر:** `staging.company.com`, `dev.onlineshop.ir`, `test.agency.co.uk`
- **مثال‌های نامعتبر (غیر استیجینگ):** `staging.com` (دامنه ریشه است)، `dev.ir` (دامنه ریشه است).
- **رجکس تطبیق:**
  ```regex
  ^(staging|dev|test|stage|beta|qa|uat)\.[a-z0-9\-]+(\.[a-z0-9\-]+)+$
  ```

### ۳.۴. ساب‌دامنه‌های ارائه‌دهندگان معتبر هاستینگ استیجینگ
محیط‌های پیش‌فرض تست سرورهای شناخته‌شده ابری:
- **لیست ارائه‌دهندگان:**
  - `*.wpenginepowered.com` (WP Engine)
  - `*.kinsta.cloud` (Kinsta)
  - `*.pantheonsite.io` (Pantheon)
  - `*.flywheelsites.com` (Flywheel)
  - `*.cloudwaysapps.com` (Cloudways)
  - `*.myftpupload.com` (GoDaddy Staging)
  - `*.ngrok.io` / `*.ngrok-free.app` (تونل‌های توسعه موقت)
- **رجکس تطبیق:**
  ```regex
  \.(wpenginepowered\.com|kinsta\.cloud|pantheonsite\.io|flywheelsites\.com|cloudwaysapps\.com|myftpupload\.com|ngrok\.io|ngrok-free\.app)$
  ```

---

## ۴. تابع اعتبارسنجی قطعی (Deterministic Detection Function)

```php
/**
 * Determine if a domain is a valid development/staging environment.
 *
 * @param string $domain Normalized domain.
 * @return bool True if staging/local, false if production.
 */
function backupino_is_staging_domain( string $domain ): bool {
    $domain = backupino_normalize_domain( $domain );
    
    // ۱. بررسی لوپ‌بک و لوکال‌هاست
    if ( in_array( $domain, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
        return true;
    }
    
    // ۲. بررسی TLDهای توسعه
    if ( preg_match( '/\.(test|example|invalid|localhost|local|internal|lan)$/i', $domain ) ) {
        return true;
    }
    
    // ۳. بررسی ساب‌دامنه‌های معتبر استیجینگ (حداقل دو نقطه برای تضمین ساب‌دامنه بودن)
    if ( preg_match( '/^(staging|dev|test|stage|beta|qa|uat)\.[a-z0-9\-]+(\.[a-z0-9\-]+)+$/i', $domain ) ) {
        return true;
    }
    
    // ۴. بررسی هاست‌های کلاد استیجینگ
    if ( preg_match( '/\.(wpenginepowered\.com|kinsta\.cloud|pantheonsite\.io|flywheelsites\.com|cloudwaysapps\.com|myftpupload\.com|ngrok\.io|ngrok-free\.app)$/i', $domain ) ) {
        return true;
    }
    
    return false;
}
```

---

## ۵. کنترل‌های امنیتی و ضد-سوءاستفاده (Anti-Abuse Controls)

برای جلوگیری از تبدیل سایت‌های تجاری دائم به دامنه‌های استیجینگ:
1. **سقف دامنه‌های استیجینگ فعال همزمان (Soft Cap):**
   - هر لایسنس سقف مشخصی برای اتصال همزمان دامنه‌های استیجینگ دارد:
     - **پلن آزمایشی و شخصی (۱ دامنه اصلی):** حداکثر **۳** دامنه استیجینگ همزمان.
     - **پلن فریلنسر (۱۰ دامنه اصلی):** حداکثر **۲۰** دامنه استیجینگ همزمان.
     - **پلن سازمانی (۳۰ دامنه اصلی):** حداکثر **۶۰** دامنه استیجینگ همزمان.
   - عبور از این سقف با خطای `ERR_STAGING_LIMIT_REACHED` مسدود می‌شود و کاربر باید استیجینگ‌های منقضی را از پنل لغو کند.
2. **قاعده خنک‌سازی تغییر دامنه اصلی (Cooling-off Period):**
   - اگر دامنه پروداکشن لایسنس تغییر کند، تا **۷۲ ساعت** اجازه تغییر مجدد دامنه اصلی وجود ندارد؛ اما دامنه‌های استیجینگ مشمول این قفل زمانی نمی‌شوند تا سرعت تست افت نکند.

---

## ۶. یکپارچگی در دیتابیس پورتال و کلاینت Pro

1. **پایگاه‌داده پرتال (`wp_backupino_activations`):**
   - فیلد `is_staging = 1` برای دامنه‌های تاییدشده ذخیره می‌شود.
   - ستون `active_domains_count` در جدول لایسنس تغییر نمی‌کند (`D-052`).
2. **پاسخ توکن لایسنس:**
   - فیلد متادیتا شامل `is_staging: true` در توکن Ed25519 قرار می‌گیرد.
3. **رابط کاربری پیشخوان وردپرس:**
   - در ویجت وضعیت افزونه، نشانگر گرافیکی متفاوتی برای محیط تستی درج می‌شود:
     `وضعیت لایسنس: فعال [محیط آزمایشی / استیجینگ - بدون کسر سهمیه]`

</div>
