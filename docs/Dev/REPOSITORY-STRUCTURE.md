<div align="right" dir="rtl">

# ساختار مخزن — Repository Structure

> **Status:** active · **Last updated:** 2026-10-31

## درخت

```
/
├── free/                     ← افزونهی رایگان؛ همان چیزی که در WordPress.org منتشر میشود
│   ├── backupino.php         ← فایل اصلی افزونه (Plugin Header)
│   ├── uninstall.php
│   ├── readme.txt            ← readme استاندارد WordPress.org
│   ├── src/                  ← کد PHP (namespace Backupino\)
│   │   ├── Backup/           ← اسکن، مانیفست، آرشیو، SQL، مراحل خط لوله، ساخت اینستالر
│   │   ├── Core/             ← Settings, Paths, Schema, Activator, Db
│   │   ├── Jobs/             ← Job, Repository, Runner, Pipeline, Stage
│   │   ├── Storage/          ← قرارداد Storage + LocalStorage (فقط!)
│   │   ├── Replace/          ← جستوجو و جایگذاری آگاه به serialize/JSON
│   │   ├── Installer/        ← سورس اینستالر مستقل (فلتنشده)
│   │   ├── Admin/  Cli/  Cron/  Support/
│   ├── templates/            ← قالبهای پیشخوان
│   ├── assets/               ← css/js
│   └── languages/            ← فایلهای ترجمه (.pot/.po/.mo)
│
├── pro/                      ← کد تجاری؛ هرگز در بستهی رایگان نمیرود
│   ├── autoload.php          ← فقط برای توسعه و تستهای Pro
│   ├── README.md
│   └── src/Storage/…         ← فعلاً فقط درایور S3 (بعداً: Drive، ابر اختصاصی، لایسنس)
│
├── tests/                    ← مجموعه تست بدون وابستگی
│   ├── bootstrap.php         ← شبیهساز توابع وردپرس + بارگذاری autoload
│   ├── run.php               ← اجراکننده (کشف خودکار)
│   ├── Harness/  Support/
│   ├── Unit/                 ← تستهای رایگان
│   ├── Integration/
│   └── Pro/                  ← تستهای کد تجاری
│
├── docs/
│   ├── Public/               ← مستندات کاربر نهایی
│   └── Dev/                  ← مستندات توسعه (قوانین، معماری، تصمیمها، پلنها)
│
├── roadmaps/                 ← فازها، تسکها، وضعیت
│   └── README.md             ← باکس وضعیت کلی
│
├── releases/                 ← خروجی ساخت (zip، installer) — در گیت ذخیره نمیشود
├── tools/                    ← run-php.mjs (php-wasm)، اسکریپتهای ساخت
├── bin/                      ← اسکریپتهای CLI توسعه (build-installer، build-release)
└── .github/workflows/        ← CI
```

## قواعد جایگذاری فایل

| اگر فایل این است… | جای آن اینجاست |
|---|---|
| کلاس PHP که با لایسنس کار میکند (ابر، لایسنس، ریاستور، مالتیسایت) | `pro/src/` |
| کلاس PHP که همیشه در دسترس است | `free/src/` |
| تست هر کلاس `free/` | `tests/Unit/` یا `tests/Integration/` |
| تست هر کلاس `pro/` | `tests/Pro/` |
| سند کاربری | `docs/Public/` |
| سند فنی/تصمیم/قانون | `docs/Dev/` |
| تسک یا فاز | `roadmaps/` |
| خروجی ساخت | `releases/` (gitignored) |

## قواعد مهم

1. **`free/` باید همیشه قابل انتشار در WordPress.org باشد.** اگر کدی قفلشدنی/تجاری در آن دیده شد، به `pro/` منتقل میشود.
2. **`free/` و `pro/` هرگز به فایلهای یکدیگر import مستقیم نمیزنند**؛ اتصال فقط از طریق فیلترها/اینترفیسهای `free/`.
3. مسیر `free/` در بستهی نهایی به `backupino/` تغییر نام میدهد (اسکریپت `bin/build-release.php`).
4. مسیرها در تستها نسبیاند (`dirname( __DIR__ )`)؛ مسیر مطلق هاردکد نمیشود.

</div>
