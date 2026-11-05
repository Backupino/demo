<div align="right" dir="rtl">

# سند مشخصات تست‌های قرارداد و موک‌های لایسنس — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه تسک:** `C2-T12` · **فاز:** فاز ۲ (قرارداد مشترک «Gate»)  
> **عامل مسئول:** `AG-QA` (تضمین کیفیت و طراحی آزمون‌های قرارداد)  
> **تصمیمات مرتبط:** `D-020`، `D-031`، `D-032`، `D-042`، `D-046`، `D-047`، `D-049`، `D-051` در [`DECISIONS.md`](DECISIONS.md)  
> **پیشنیازها:** اسناد مصوب [`API-CONTRACT-V1.md`](API-CONTRACT-V1.md)، [`LICENSE-CONTRACT-ED25519.md`](LICENSE-CONTRACT-ED25519.md)، [`HYBRID-LICENSE-MODEL-B.md`](HYBRID-LICENSE-MODEL-B.md)، [`RESCUE-AGENT-CONTRACT.md`](RESCUE-AGENT-CONTRACT.md) و [`VERSION-COMPATIBILITY-MATRIX.md`](VERSION-COMPATIBILITY-MATRIX.md).  
> **دامنه:** این سند مشخصات فنی، معماری موک سرور (`FakeLicenseServer`) و ماتریس سناریوهای آزمون‌های قرارداد را تعیین می‌کند. پیاده‌سازی و اجرای این تست‌ها در `tests/Pro/` در فاز ۴ انجام خواهد شد تا در فاز ۲ هیچ کدی تغییر نکرده و مرجع ۱۵۲ تست سبز پروژه پابرجا بماند (`D-046` و `D-047`).

---

## ۱. اهداف و معماری آزمون‌های قرارداد (Contract Testing)

در معماری ماژولار بکاپینو، افزونه Pro و پورتال مرکزی به صورت مستقل توسعه می‌یابند. برای تضمین اینکه این دو بخش بدون وابستگی به یک محیط زنده یکدیگر را درک کنند، آزمون‌های مبتنی بر قرارداد (Consumer-Driven Contract Tests) طراحی شده‌اند:
1. **صحت کامل ریاضی و رمزنگاری:** اطمینان از سازگاری توکن‌های امضاشده با کلیدهای `Ed25519` و عملکرد بی‌نقص ماتریس ۳ لایه‌ای Fallback.
2. **انعطاف‌پذیری در قطعی شبکه:** سنجش رفتار صحیح ماشین وضعیت لایسنس و کش آفلاین ۷ روزه بدون تماس به اینترنت (`D-020`).
3. **امنیت در برابر حملات متداول:** ارزیابی مقاومت کلاینت و سرور در برابر حملات Replay، جعل امضا، دامنه‌های نامعتبر و سوءاستفاده از اسلات‌ها.
4. **رفتار پیش‌بینی‌پذیر در ناسازگاری:** راستی‌آزمایی ورود به حالت Safe Read-Only در صورت عدم انطباق نسخه‌ها.

---

## ۲. معماری شبیه‌ساز سرور لایسنس (`FakeLicenseServer`)

کلاس شبیه‌ساز `FakeLicenseServer` به عنوان یک سرور مجازی درون‌حافظه‌ای، نقش پورتال بکاپینو را در محیط تست بازی می‌کند.

### ۲.۱. کلیدهای آزمایشی قطعی (Deterministic Test Keypair)

برای جلوگیری از تفاوت خروجی در اجراهای مختلف CI، یک جفت کلید ثابت Ed25519 با Seed مشخص برای محیط تست تعریف می‌شود:

```php
namespace Backupino\Tests\Support;

final class FakeLicenseServer {
    // کلید خصوصی ۳۲ بایتی تست (Base64Url)
    public const TEST_PRIVATE_KEY_B64 = '4ccd089b28ff96da9db6c346ec114e0f5b8a319f35aba624da8cf6ed4fb8a6fb';
    // کلید عمومی ۳۲ بایتی متناظر (Base64Url)
    public const TEST_PUBLIC_KEY_B64 = '3d4017c3e843895a92b70aa74d1b7ebc9c982ccf2ec4968cc0cd55f12af4660c';

    /**
     * صدور توکن لایسنس معتبر با امضای معتبر Ed25519
     */
    public static function issue_valid_token( array $custom_payload = array() ): string;

    /**
     * صدور توکن با امضای دستکاری‌شده یا نامعتبر (Tampered Signature)
     */
    public static function issue_tampered_token( array $payload = array() ): string;

    /**
     * صدور پاسخ خطای ساختاریافته مطابق API-CONTRACT-V1
     */
    public static function make_error_response( string $error_code, string $message, int $http_status = 400 ): array;
}
```

---

## ۳. ماتریس جامع سناریوهای آزمون قرارداد (Contract Test Scenarios)

| ردیف | نام سناریو / تست | ورودی آزمون | نتیجه مورد انتظار | قرارداد مرجع |
|---|---|---|---|---|
| ۱ | `test_ed25519_valid_signature_verifies` | توکن امضاشده معتبر از `FakeLicenseServer` | تأیید موفقیت‌آمیز، خروجی Payload دیکودشده بدون خطا | `LICENSE-CONTRACT-ED25519.md` |
| ۲ | `test_ed25519_tampered_payload_rejected` | توکن معتبر با تغییر حتی ۱ بایت در بخش Payload | پرتاب استثنای `ERR_INVALID_SIGNATURE` و ابطال لایسنس | `LICENSE-CONTRACT-ED25519.md` |
| ۳ | `test_ed25519_tampered_signature_rejected` | توکن با امضای مخدوش (بیت تصادفی معکوس) | رد قطعی امضا و عدم فعال‌سازی Pro | `LICENSE-CONTRACT-ED25519.md` |
| ۴ | `test_offline_cache_grace_period_7_days` | قطع کامل پاسخ شبکه به مدت ۶ روز و ۲۳ ساعت | ادامه کار Pro بر پایه کش `_backupino_pro_license_cache` | `HYBRID-LICENSE-MODEL-B.md` |
| ۵ | `test_offline_cache_expired_after_7_days` | قطعی شبکه بیش از ۷ روز (۱۶۸ ساعت) | تنزل به وضعیت `DOWNGRADE` و توقف بکاپ‌های خودکار | `HYBRID-LICENSE-MODEL-B.md` |
| ۶ | `test_read_only_access_on_expired_license` | لایسنس منقضی‌شده با وضعیت `READ_ONLY_ACCESS` | بکاپ جدید ممنوع، دانلود بکاپ‌های محلی آزاد (`D-015`) | `HYBRID-LICENSE-MODEL-B.md` |
| ۷ | `test_domain_mismatch_rejected` | توکن صادرشده برای دامنه A روی دامنه B | خطای `ERR_DOMAIN_MISMATCH` و عدم فعال‌سازی | `API-CONTRACT-V1.md` |
| ۸ | `test_staging_domain_whitelist_allowed` | دامنه تستی (مانند `dev.mysite.local` یا `staging.test`) | فعال‌سازی بدون کسر سهمیه لایسنس (`D-051`) | `STAGING-WHITELIST-RULES.md` |
| ۹ | `test_replay_attack_rejected` | ارسال درخواست با `X-Backupino-Nonce` تکراری | رد درخواست با کد HTTP 409 و خطای `ERR_REPLAY_DETECTED` | `API-CONTRACT-V1.md` |
| ۱۰ | `test_timestamp_drift_rejected` | ارسال درخواست با اختلاف ساعت بیش از ۳۰۰ ثانیه | رد درخواست با کد HTTP 400 و خطای `ERR_TIMESTAMP_DRIFT` | `API-CONTRACT-V1.md` |
| ۱۱ | `test_idempotent_activation_requests` | ارسال همزمان ۲ درخواست فعال‌سازی یکسان | پاسخ موفق یکسان بدون ثبت دو رکورد فعال‌سازی در دیتابیس | `API-CONTRACT-V1.md` |
| ۱۲ | `test_version_mismatch_safe_dormant` | هسته قدیمی ۱.۰.۰ با پرو ۱.۱.۰ | ورود به حالت Safe Read-Only بدون Fatal Error وردپرس | `VERSION-COMPATIBILITY-MATRIX.md` |
| ۱۳ | `test_rescue_agent_token_authentication` | توکن معتبر با `hash_equals` در برابر توکن اشتباه | اجازه دسترسی به توکن درست، خطای ۴۰۳ به توکن اشتباه | `RESCUE-AGENT-CONTRACT.md` |
| ۱۴ | `test_rescue_agent_signed_command_execution` | دستور `COMMIT_RESTORE` با امضای سرور پورتال | اجرای مرحله کامیت ریستور و خودتخریبی فایل نجات | `RESCUE-AGENT-CONTRACT.md` |

---

## ۴. ساختار پوشه‌ها و نام‌گذاری فایل‌های تست Pro (طراحی برای فاز ۴)

در فاز ۴، پس از آغاز توسعه افزودنی تجاری، فایل‌های آزمون زیر در مسیر `tests/Pro/` اضافه خواهند شد:

```
tests/
├── Pro/
│   ├── S3SignerTest.php                # تست موجود ساینر S3 (تطابق کامل AWS v4)
│   ├── LicenseSignatureTest.php        # سناریوهای ۱ تا ۳ (امضا و رمزنگاری Ed25519)
│   ├── LicenseLifecycleTest.php        # سناریوهای ۴ تا ۶ (ماشین وضعیت و کش آفلاین)
│   ├── ApiContractTest.php             # سناریوهای ۷ تا ۱۱ (Replay، تایم‌استمپ و اندپوینت‌ها)
│   ├── VersionMatrixTest.php           # سناریوی ۱۲ (تطابق نسخه‌ها و تنزل امن)
│   └── RescueAgentContractTest.php     # سناریوهای ۱۳ و ۱۴ (عامل نجات و بازگردانی)
└── Support/
    └── FakeLicenseServer.php           # کلاس کمکی شبیه‌ساز سرور لایسنس
```

---

## ۵. سیاست اجرا در CI/CD و پایبندی به قانون صفر

1. **حفظ پایداری در فاز ۲:** هیچ فایل تستی در این فاز به مخزن اضافه نمی‌شود تا شمارشگر تست‌ها روی **۱۵۲ تست سبز** باقی بماند (`D-047`).
2. **فعال‌سازی مشروط در فاز ۴:** رانر تست پروژه (`tests/run.php`) با اضافه شدن فایل‌های جدید در `tests/Pro/` به صورت خودکار آنها را شناسایی و اجرا خواهد کرد. برای تفکیک محیط‌ها، متغیر محیطی `BACKUPINO_RUN_PRO_TESTS=1` در گیت‌هاب اکشنز فاز ۴ تعریف خواهد شد.

</div>
