<div align="right" dir="rtl">

# فاز ۰ — زیرساخت و اسکلت پروژه

> **Status:** ✅ done · **Progress:** ۱۰۰٪ · **Completed:** 2026-10-31

## هدف

ساخت پایهای که همهی فازهای بعدی روی آن سوار میشوند: موتور بکاپ، اینستالر مستقل، لایهی ذخیرهسازی، رابطها، تست و CI.

## تسکها

| ID | تسک | وضعیت | یادداشت |
|---|---|---|---|
| P0-T01 | اسکلت افزونه، autoloader، `Plugin` container | ✅ done | بدون وابستگی Composer در زمان اجرا |
| P0-T02 | `Settings`, `Paths`, `Schema`, `Activator`, `Compatibility` | ✅ done | تنظیمات dot-notation با sanitize بازگشتی |
| P0-T03 | موتور Job (Repository/Runner/Pipeline/Stage/Deadline) | ✅ done | مقاوم به timeout، ریاستارتپذیر |
| P0-T04 | اسکن فایل + مانیفست NDJSON + فیلترها | ✅ done | پشتهی صریح، بدون بازگشت |
| P0-T05 | آرشیو ZIP قابل ادامه + بستهبندی | ✅ done | بازکردن مجدد زیپ در هر tick |
| P0-T06 | دامپ دیتابیس با keyset pagination | ✅ done | `MySqlDumper` + `SqlWriter` |
| P0-T07 | اینستالر مستقل (فلتنشده از کلاسهای واقعی) | ✅ done | ~۷۲KB، بدون وردپرس |
| P0-T08 | Replace آگاه به serialize/JSON | ✅ done | پارسر بازگشتی، نه regex |
| P0-T09 | لایهی ذخیرهسازی (قرارداد + local) | ✅ done | درایور S3 ساخته شد و بعد به `pro/` منتقل شد |
| P0-T10 | پیشخوان، REST، WP-CLI، Cron، لاگ | ✅ done | |
| P0-T11 | مجموعه تست بدون وابستگی (۸۶ تست) | ✅ done | اجرا در php-wasm |
| P0-T12 | CI (PHP 7.4–8.4، PHPCS، ساخت اینستالر) | ✅ done | `.github/workflows/ci.yml` |
| P0-T13 | مستندات پایه (Spec، Architecture) | ✅ done | `docs/Dev/` |
| P0-T14 | پارتیشنبندی مخزن (`free/`, `pro/`, `docs/`, `tests/`, `roadmaps/`, `releases/`) | ✅ done | 2026-10-31 |
| P0-T15 | قوانین توسعه و باکس وضعیت | ✅ done | `docs/Dev/DEVELOPMENT-RULES.md` |

## نتیجه

- `npm test` → **۸۶ تست، ۰ خطا** روی PHP 8.3 (و ۷.۴/۸.۴)
- `php bin/build-installer.php` → اینستالر معتبر ۷۲٬۰۰۳ بایتی
- نسخهی رایگان دیگر هیچ کد ابری ندارد (`StorageManager` فقط `local` را میشناسد؛ بقیه با فیلتر `backupino_storage_drivers`)

## نکات برای فاز بعد

- `pro/src/Storage/*` فعلاً پارک شده است و از `pro/autoload.php` فقط در تست لود میشود.
- پس از پایان فاز ۱، `bin/build-release.php` باید پوشههای `pro/`, `tests/`, `docs/`, `tools/` را از zip حذف کند.

</div>
