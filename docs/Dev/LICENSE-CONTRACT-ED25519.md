<div align="right" dir="rtl">

# قرارداد سند لایسنس با امضای Ed25519 — License Verification Specification

> **Status:** ✅ Approved Contract · **Version:** 1.0.0 · **Date:** 2026-11-05  
> **عامل مسئول طراحی:** `AG-ORCH` · **نگهداشت:** `AG-DOCS` · **پیاده‌سازی در پرتال:** `AG-PORTAL` (`S3-T04`, `S3-T10`) · **مصرف در افزونه تجاری:** `AG-PRO` (`P4-T02`, `P4-T03`)  
> **آزمون و اعتبارسنجی:** `AG-QA` (`C2-T12`, `P4-T18`) · **تصمیمات مرجع:** `D-031`, `D-032`, `D-044`, `D-051`

---

## ۱. اهداف و اصول حاکم بر معماری لایسنس

1. **امنیت نامتقارن (Asymmetric Cryptography):**  
   صدور لایسنس منحصراً توسط **کلید خصوصی (Private Key)** پرتال انجام می‌شود. افزونهٔ Pro روی سایت مشتری فقط دارای **کلید عمومی (Public Key)** است؛ بنابراین حتی در صورت دیکامپایل یا مهندسی معکوس کدهای Pro، هیچ کاربری قادر به جعل یا تولید لایسنس معتبر نخواهد بود.
2. **اعتبارسنجی ۱۰۰٪ مستقل و آفلاین (Offline-First Validation):**  
   اعتبارسنجی لایسنس به صورت محلی و با ریاضیات محض منحنی بیضوی Ed25519 انجام می‌شود. در صورت قطع ارتباط با اینترنت، تحریم، فیلترینگ یا از دسترس خارج شدن پرتال، افزونه بدون هیچ مشکلی به مدت **۷ روز کامل (قابل تمدید در هر اتصال)** در حالت فعال باقی می‌ماند (`D-032`).
3. **کارایی فوق‌سریع و حجم اندک:**  
   کلید عمومی تنها **۳۲ بایت** و امضای نهایی دقیقاً **۶۴ بایت** است. فرآیند Verify روی هاست‌های اشتراکی ضعیف زیر **۱ میلی‌ثانیه** زمان می‌برد و بر خلاف RSA هیچ پیچیدگی یا خطای Padding ندارد (`D-031`).
4. **حفظ حریم خصوصی:**  
   دامنه‌های مجاز در سند لایسنس به صورت هش امن یکطرفه ذخیره می‌شوند تا اطلاعات محرمانه مشتری افشا نگردد.

---

## ۲. مشخصات رمزنگاری Ed25519 (Cryptographic Parameters)

| مؤلفه | طول استاندارد | انکودینگ / فرمت ذخیره‌سازی | محل نگهداری |
|---|---|---|---|
| **Curve** | Curve25519 (Ed25519 / Edwards-curve Digital Signature Algorithm) | RFC 8032 | — |
| **Private Key (Signing)** | ۶۴ بایت (۳۲ بایت Seed + ۳۲ بایت PubKey) | متغیر محیطی حفاظت‌شده در سرور پرتال (`PORTAL_ED25519_PRIVATE_KEY`) | **منحصراً سرور پرتال** (هیچ‌گاه در سورس پرو یا بسته دانلود قرار نمی‌گیرد) |
| **Public Key (Verifying)** | ۳۲ بایت | هگز ۶۴ کاراکتری یا Base64 در ثابت کلاس PHP Pro | ثابت امن در `Backupino\Pro\License\PublicKey::ED25519_PUBKEY` |
| **Signature** | ۶۴ بایت | باینری خام، انکود به Base64Url (۸۶ کاراکتر) | انتهای رشته سند لایسنس |

---

## ۳. ساختار داده‌های سند لایسنس (Canonical Payload Schema)

برای جلوگیری از هرگونه خطای عدم انطباق هش به دلیل فاصله‌های خالی یا تغییر ترتیب کلیدها، قبل از امضا، شیء JSON به صورت **Canonical (الفبایی و بدون فاصله با `ksort` و `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`)** فرمت‌بندی می‌شود.

### ۳.۱. مدل داده خام JSON (Payload JSON):

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": [
    "activation_id",
    "domains",
    "expiry",
    "grace_period_days",
    "issued_at",
    "issuer",
    "nonce",
    "plan",
    "quota"
  ],
  "properties": {
    "issuer": { "type": "string", "enum": ["https://portal.backupino.com"] },
    "activation_id": { "type": "string", "pattern": "^act_[a-f0-9]{16,32}$" },
    "plan": { "type": "string", "enum": ["trial", "personal", "freelancer", "agency"] },
    "domains": {
      "type": "array",
      "items": { "type": "string", "pattern": "^[a-f0-9]{64}$" },
      "description": "لیست هش دامنه‌های مجاز تحت این لایسنس (sha256 دامنه‌های قانونی)"
    },
    "quota": {
      "type": "object",
      "required": ["max_domains", "cloud_storage_bytes", "retention_versions", "features"],
      "properties": {
        "max_domains": { "type": "integer", "minimum": 1 },
        "cloud_storage_bytes": { "type": "integer", "minimum": 0 },
        "retention_versions": { "type": "integer", "minimum": 1 },
        "features": {
          "type": "array",
          "items": { "type": "string" },
          "description": "امکانات مجاز مانند: multisite, whitelabel, restore_in_place, cloud_s3, cloud_ftp, incremental, rescue_agent"
        }
      }
    },
    "issued_at": { "type": "integer", "description": "زمان صدور بر حسب ثانیه یونیکس (UTC)" },
    "expiry": { "type": "integer", "description": "زمان پایان اشتراک بر حسب ثانیه یونیکس (UTC)" },
    "grace_period_days": { "type": "integer", "default": 7 },
    "nonce": { "type": "string", "pattern": "^[a-f0-9]{32}$" }
  }
}
```

---

## ۴. قالب نهایی رشته توکن لایسنس (Compact License Blob Format)

سند لایسنس در قالب یک رشته فشرده، امن برای عبور از URL و دیتابیس (مشابه استاندارد JWS) به شکل زیر تولید می‌شود:

$$\text{LicenseBlob} = \text{Base64Url}(\text{CanonicalPayloadJSON}) \,+\, \text{"."} \,+\, \text{Base64Url}(\text{SignatureBytes})$$

### نمونه نمایشی توکن لایسنس:
```text
eyJpc3N1ZXIiOiJodHRwczovL3BvcnRhbC5iYWNrdXBpbm8uY29tIiwiYWN0aXZhdGlvbl9pZCI6ImFjdF84OWZhMTJjZDAxIiwicGxhbiI6ImZyZWVsYW5jZXIiLCJkb21haW5zIjpbIjRlYTkyNGM4ZDA4YmY0OTFkMjRkNDUyZjBkMmQ4YmMwMWQ5YTM5NGQwOTk2ODNlZjgyNzg1MzBhM2U1MjI5NzMiXSwicXVvdGEiOnsibWF4X2RvbWFpbnMiOjEwLCJjbG91ZF9zdG9yYWdlX2J5dGVzIjozMjIxMjI1NDcyMCwicmV0ZW50aW9uX3ZlcnNpb25zIjo1LCJmZWF0dXJlcyI6WyJtdWx0aXNpdGUiLCJyZXN0b3JlX2luX3BsYWNlIiwiY2xvdWRfczMiLCJjbG91ZF9mdHAiLCJpbmNyZW1lbnRhbCJdfSwiaXNzdWVkX2F0IjoxNzMwNzM2MDAwLCJleHBpcnkiOjE3NjIyNzIwMDAsImdyYWNlX3BlcmlvZF9kYXlzIjo3LCJub25jZSI6ImU5OTM0MWYyODgwMmIxNGRkYjcxMjFhMmQ5YTA1NTQyIn0.9Y8u6a7B8c7D6e5F4g3H2i1J0k9L8m7N6o5P4q3R2s1T0u9V8w7X6y5Z4a3B2c1D0e9F8g7H6i5J4k3L2m1N0o9P8q7R6s5T4u3V2w1X0y9Z==
```

---

## ۵. ماتریس سازگاری و استراتژی Fallback در نسخه‌های PHP (7.4 تا 8.4)

افزونهٔ Pro بر روی انواع هاست‌های اشتراکی با سطوح دسترسی گوناگون اجرا خواهد شد؛ بنابراین فرآیند Verify باید دارای ۳ لایه لغزش خودکار (Fallback) باشد:

| لایه | موتور اعتبارسنجی | پیش‌نیاز سیستم | سطح کارایی | پوشش پلتفرم |
|---|---|---|---|---|
| **لایه ۱ (اصلی)** | `ext-sodium` بومی | توابع `sodium_crypto_sign_verify_detached()` | حداکثر سرعت (< ۰.۳ میلی‌ثانیه) | فعال به صورت پیش‌فرض در ۹۸٪ هاست‌های PHP 7.4 تا 8.4 |
| **لایه ۲ (پشتیبان مدرن)** | `OpenSSL` | تابع `openssl_verify()` با الگوریتم Ed25519 | بسیار سریع (< ۰.۸ میلی‌ثانیه) | هاست‌های دارای PHP 8.1+ و نسخه OpenSSL 1.1.1+ در صورت غیرفعال بودن سدیم |
| **لایه ۳ (نجات اضطراری)** | Pure-PHP Ed25519 Engine | پیاده‌سازی خالص PHP بدون اکستنشن کامپایل‌شده | متوسط (~ ۶ میلی‌ثانیه) | ۱۰۰٪ محیط‌ها حتی هاست‌های به شدت قفل‌شده و محدود بدون هیچ اکستنشن اضافه |

### شبه‌کد مدیریت چندلایه‌ای موتور امضا در کلاس `LicenseVerifier`:

```php
namespace Backupino\Pro\License;

final class LicenseVerifier {

    public static function verify( string $message, string $signature, string $publicKey ): bool {
        // ۱. بررسی لایه سدیم بومی
        if ( function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
            return sodium_crypto_sign_verify_detached( $signature, $message, $publicKey );
        }

        // ۲. بررسی لایه OpenSSL در صورت وجود Ed25519
        if ( defined( 'OPENSSL_KEYTYPE_ED25519' ) && function_exists( 'openssl_verify' ) ) {
            $spki_key = self::convert_raw_to_spki( $publicKey );
            $result   = openssl_verify( $message, $signature, $spki_key, OPENSSL_ALGO_SHA512 );
            if ( 1 === $result ) {
                return true;
            }
        }

        // ۳. فال‌بک به موتور مستقل Pure-PHP
        return PureEd25519::verify( $message, $signature, $publicKey );
    }
}
```

---

## ۶. الگوریتم چرخه بررسی لایسنس در افزونه Pro (Verification Flowchart)

```text
               +-----------------------------------+
               |  دریافت سند لایسنس (License Blob)   |
               +-----------------------------------+
                                 |
                                 v
               +-----------------------------------+
               | تفکیک رشته به Payload و Signature  |
               +-----------------------------------+
                                 |
                                 v
               +-----------------------------------+
               | دیکود Base64Url هر دو بخش         |
               +-----------------------------------+
                                 |
                                 v
               +-----------------------------------+
               | اعتبارسنجی ریاضی امضای Ed25519    |
               | با کلید عمومی ۳۲ بایتی پرتال      |
               +-----------------------------------+
                     /                       \
             [نامعتبر]                       [معتبر]
                   /                           \
                  v                             v
+-------------------------------+ +------------------------------------+
| خطای INVALID_SIGNATURE         | | بررسی ساختار فیلدهای ضروری JSON    |
| لایسنس فوراً رد می‌شود        | +------------------------------------+
+-------------------------------+                  |
                                                   v
                                  +------------------------------------+
                                  | تطبیق هش دامنه سایت فعلی           |
                                  | با لیست دامنه‌ها یا لیست سفید استیجینگ|
                                  +------------------------------------+
                                        /                        \
                                [عدم تطبیق]                    [تطبیق کامل]
                                      /                            \
                                     v                              v
                   +---------------------------+  +-------------------------------+
                   | خطای DOMAIN_MISMATCH      |  | سنجش زمان:                    |
                   | غیرفعال‌سازی امکانات Pro   |  | time() <= expiry ?           |
                   +---------------------------+         /                 \
                                                       [بله]               [خیر]
                                                         /                   \
                                                        v                     v
                                      +--------------------+  +-------------------------------+
                                      | وضعیت: ACTIVE      |  | سنجش دوره مهلت:               |
                                      | ذخیره در کش آفلاین |  | time() <= expiry + grace_days |
                                      +--------------------+         /                 \
                                                                   [بله]               [خیر]
                                                                     /                   \
                                                                    v                     v
                                                  +--------------------+  +-------------------------+
                                                  | وضعیت: GRACE       |  | وضعیت: EXPIRED          |
                                                  | اخطار به ادمین     |  | بازگشت به حالت رایگان   |
                                                  +--------------------+  | دسترسی خواندنی بکاپ‌ها  |
                                                                          +-------------------------+
```

---

## ۷. استثنائات محیط‌های استیجینگ و توسعه محلی (`D-051`)

طبق تصمیم معماری `D-051`، دامنه‌های توسعه و تست **هرگز از سهمیه لایسنس مشتری کسر نمی‌کنند**.

در فرآیند اعتبارسنجی سند لایسنس:
اگر `canonical_domain` با یکی از الگوهای زیر تطابق داشته باشد:
* `localhost`
* `127.0.0.1` یا هر آدرس در رنج محلی `10.0.0.0/8`, `192.168.0.0/16`
* پسوندهای آزمایشی: `*.test`, `*.local`, `*.internal`, `*.invalid`
* زیردامنه‌های توسعه رایج: `staging.*`, `dev.*`, `test.*`, `stage.*`

بررسی هش دامنه نادیده گرفته شده و تا زمانی که اصل امضای لایسنس معتبر و متعلق به یک لایسنس فعال باشد، مجوز اجرای امکانات Pro روی محیط تست صادر می‌گردد.

---

## ۸. تست‌های مورد نیاز برای این قرارداد (`C2-T12`)

در فاز دوم (تسک `C2-T12`) و فاز چهارم (`P4-T18`) تست‌های زیر پیاده‌سازی و ارزیابی می‌شوند:
1. `test_valid_ed25519_signature_passes`: تست امضای سالم تولیدشده با کلید خصوصی نمونه.
2. `test_tampered_payload_fails`: تغییر حتی ۱ بایت در Payload باید منجر به شکست قطعی اعتبارسنجی شود.
3. `test_tampered_signature_fails`: تست امضای دستکاری‌شده یا تصادفی.
4. `test_expired_license_enters_grace_or_free`: تست تغییر وضعیت به Grace Period و سپس بازگشت به حالت رایگان خواندنی.
5. `test_staging_domains_bypass_domain_restriction`: تست فعالیت صحیح روی `test.site.local`.
6. `test_all_three_verifier_engines`: تست یکسان بودن نتیجه تایید روی هر ۳ موتور سدیم، اپن‌اس‌اس‌ال و پیور پی‌اچ‌پی.

</div>
