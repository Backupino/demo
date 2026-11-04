<div align="right" dir="rtl">

# فاز ۰ — زیرساخت و اسکلت پروژه

> **Status:** ✅ done · **Progress:** ۱۰۰٪ · **Completed:** 2026-10-31
> **عامل مسئول فاز:** `AG-CORE` / `AG-QA` / `AG-DOCS` «تاریخی، انجامشده» · **هماهنگ:** `AG-ORCH` · **قرارداد تخصیص:** [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md)

## هدف

ساخت پایهای که همهی فازهای بعدی روی آن سوار میشوند: موتور بکاپ، اینستالر مستقل، لایهی ذخیرهسازی، رابطها، تست و CI.

## تسکها

| ID | عامل | وضعیت | تسک | یادداشت / معیار پذیرش |
|---|---|---|---|---|
| P0-T01 | AG-CORE | ✅ done | اسکلت افزونه، autoloader، `Plugin` container | بدون وابستگی Composer در زمان اجرا |
| P0-T02 | AG-CORE | ✅ done | `Settings`, `Paths`, `Schema`, `Activator`, `Compatibility` | تنظیمات dot-notation با sanitize بازگشتی |
| P0-T03 | AG-CORE | ✅ done | موتور Job (Repository/Runner/Pipeline/Stage/Deadline) | مقاوم به timeout، ریاستارتپذیر |
| P0-T04 | AG-CORE | ✅ done | اسکن فایل + مانیفست NDJSON + فیلترها | پشتهی صریح، بدون بازگشت |
| P0-T05 | AG-CORE | ✅ done | آرشیو ZIP قابل ادامه + بستهبندی | بازکردن مجدد زیپ در هر tick |
| P0-T06 | AG-CORE | ✅ done | دامپ دیتابیس با keyset pagination | `MySqlDumper` + `SqlWriter` |
| P0-T07 | AG-CORE | ✅ done | اینستالر مستقل (فلتنشده از کلاسهای واقعی) | ~۷۲KB، بدون وردپرس |
| P0-T08 | AG-CORE | ✅ done | Replace آگاه به serialize/JSON | پارسر بازگشتی، نه regex |
| P0-T09 | AG-CORE | ✅ done | لایهی ذخیرهسازی (قرارداد + local) | درایور S3 ساخته شد و بعد به `pro/` منتقل شد |
| P0-T10 | AG-CORE | ✅ done | پیشخوان، REST، WP-CLI، Cron، لاگ |  |
| P0-T11 | AG-QA | ✅ done | مجموعه تست بدون وابستگی (۸۶ تست) | اجرا در php-wasm |
| P0-T12 | AG-QA | ✅ done | CI (PHP 7.4–8.4، PHPCS، ساخت اینستالر) | `.github/workflows/ci.yml` |
| P0-T13 | AG-DOCS | ✅ done | مستندات پایه (Spec، Architecture) | `docs/Dev/` |
| P0-T14 | AG-ORCH | ✅ done | پارتیشنبندی مخزن (`free/`, `pro/`, `docs/`, `tests/`, `roadmaps/`, `releases/`) | 2026-10-31 |
| P0-T15 | AG-DOCS | ✅ done | قوانین توسعه و باکس وضعیت | `docs/Dev/DEVELOPMENT-RULES.md` |

---

## تخصیص عاملها در این فاز

| عامل | تسکها | دامنهٔ مجاز در این فاز | خروجی تحویلی | تحویل به |
|---|---|---|---|---|
| `AG-CORE` | `P0-T01`…`P0-T10` | `free/`, `bin/`, `tools/` | اسکلت، موتور Job، ZIP، دامپ، نصاب، REST | `AG-QA` |
| `AG-QA` | `P0-T11`, `P0-T12` | `tests/`, `.github/workflows/` | ۸۶ تست + CI روی PHP 7.4–8.4 | `AG-ORCH` |
| `AG-DOCS` | `P0-T13`, `P0-T15` | `docs/Dev/` | `SPEC.md`, `ARCHITECTURE.md`, `DEVELOPMENT-RULES.md` | `AG-ORCH` |
| `AG-ORCH` | `P0-T14` | ساختار مخزن | پارتیشنبندی `free/ pro/ docs/ tests/ roadmaps/ releases/` | `AG-CORE` «فاز ۱» |

### ورودی / خروجی فاز «Handoff»

- **ورودی:** هیچ «فاز آغازین».
- **خروجی:** پایهٔ ۸۶ تستی، نصاب مستقل، CI و مستندات پایه.
- **گیرنده:** `AG-CORE` در فاز ۱.
- **گزارش تحویل:** قالب بند ۲ [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md).

> ✅ **معیار پذیرش فاز:** همهٔ ۱۵ ردیف `✅ done` و دارای `عامل` مشخصاند. این فاز تاریخی است و نیازی به اجرای مجدد ندارد.

## نتیجه

- `npm test` → **۸۶ تست، ۰ خطا** روی PHP 8.3 (و ۷.۴/۸.۴)
- `php bin/build-installer.php` → اینستالر معتبر ۷۲٬۰۰۳ بایتی
- نسخهی رایگان دیگر هیچ کد ابری ندارد (`StorageManager` فقط `local` را میشناسد؛ بقیه با فیلتر `backupino_storage_drivers`)

## نکات برای فاز بعد

- `pro/src/Storage/*` فعلاً پارک شده است و از `pro/autoload.php` فقط در تست لود میشود.
- پس از پایان فاز ۱، `bin/build-release.php` باید پوشههای `pro/`, `tests/`, `docs/`, `tools/` را از zip حذف کند.

</div>
