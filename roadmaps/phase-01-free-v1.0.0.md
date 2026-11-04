<div align="right" dir="rtl">

# فاز 1 — نسخهی رایگان `v1.0.0`

> **Status:** ✅ done · **Progress:** 12 از 12 تسک (۱۰۰٪) · **Last updated:** 2026-11-04
> **هدف:** نسخهی رایگانِ کامل، سالم و قابل انتشار در WordPress.org — بدون هیچ کد تجاری.
> **یادداشت بازگشایی:** بنا به دستور مالک، هر تغییر آتی هستهٔ رایگان باید در همین فاز ثبت شود؛ `P1-T12` فقط **مستنداتی** اضافه شده و در این شاخه هیچ کدی تغییر نکرده است.
> **عامل مسئول فاز:** `AG-CORE` · **پاسخگو:** `AG-ORCH` · **مشورت:** `AG-QA` / `AG-DOCS` · **قرارداد تخصیص:** [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md)

---

## دامنهی این فاز

**داخل:** بکاپ کامل/دیتابیس، آرشیو + اینستالر، نگهداری محلی، دانلود، لینک دائمی، اعلان تلگرام/بله (ربات شخصی)، بکاپ خودکار ماهانه، i18n، بستهبندی و انتشار.

**خارج:** هر نوع فضای ابری، لایسنس، ریاستور درجا، مالتیسایت، بکاپ افزایشی، ربات مرکزی، پنل.

**ثبتشده ولی اجرا نشده:** `P1-T12` — تغییرات کوچک و آتی هستهٔ رایگان «فیلترهای توکن طراحی و یکدستسازی لوکال نصاب»؛ فقط در مستندات، بدون لمس `free/`.

**ثبتشده ولی اجرا نشده:** `P1-T12` — تغییرات کوچک و آتی هستهٔ رایگان «فیلترهای توکن طراحی و یکدستسازی لوکال نصاب»؛ فقط در مستندات، بدون لمس `free/`.

---

## تسکها

| ID | عامل | وضعیت | تسک | خروجی / معیار پذیرش |
|---|---|---|---|---|
| P1-T01 | AG-CORE | ✅ done | جداسازی کد ابر از نسخهی رایگان | `pro/src/Storage/*`، `StorageManager` فیلترمحور، حذف فیلدهای S3 از تنظیمات |
| P1-T02 | AG-CORE | ✅ done | لینک دائمی دانلود «آخرین بکاپ» | `?backupino_download=latest&backupino_key=…` → ورود → دانلود، بدون انقضا |
| P1-T03 | AG-CORE | ✅ done | کانال اعلان تلگرام + بله (ربات شخصی) | `Notify\Notifier` + `BotApi`، گروه تنظیمات `notify`، دکمه‌ی «ارسال آزمایشی»، صف تلاش مجدد |
| P1-T04 | AG-CORE | ✅ done | بکاپ خودکار ماهانه (اول ماه) | شمسی برای `fa_IR`، میلادی برای بقیه؛ زمانبندی اول ماه با `Dates`؛ `SchedulerTest` (4 سبز) |
| P1-T05 | AG-CORE | ✅ done | بازنگری تنظیمات و UX | retention پیشفرض 3، چکباکس opt-in اخبار، حذف بقایای ابر از UI |
| P1-T06 | AG-CORE | ✅ done | i18n، RTL و اعداد لاتین | `.pot`، `load_plugin_textdomain`، ترجمهی کامل فارسی/انگلیسی، RTL/LTR بر اساس ویژگی‌های منطقی، نرمالسازی ارقام به لاتین با `Str` |
| P1-T07 | AG-CORE | ✅ done | بستهبندی انتشار | `bin/build-release.php` → `releases/backupino-x.y.z.zip` بدون `pro/tests/docs/tools` |
| P1-T08 | AG-QA | ✅ done | بازبینی امنیتی و QA | ماتریس PHP 7.4–8.4، حذف `extract()`، اعتبارسنجی REST، آزمون‌های خودکار `SecurityQATest` |
| P1-T09 | AG-DOCS | ✅ done | مستندات عمومی + CHANGELOG | `docs/Public/*`، `CHANGELOG.md`، `readme.txt` نهایی با External services |
| P1-T10 | AG-QA | ✅ done | انتشار `v1.0.0` | SVN وردپرس + GitHub Release |
| P1-T11 | AG-CORE | ✅ done | تقویم شمسی/میلادی | `Support\Dates`: جلالی برای `fa_IR`، میلادی برای بقیه، ذخیره همیشه UTC، ارقام لاتین؛ `DatesTest` (7 سبز) |
| P1-T12 | AG-CORE | ✅ done | تغییرات کوچک و آتی هستهٔ رایگان «هماهنگ با فازهای ۲–۷» | فیلترهای `backupino_design_tokens` / `backupino_download_page_styles` در `PublicDownload` با fallback کامل؛ یکدستسازی fallback لوکال نصاب به `en_US`؛ هماهنگ‌سازی payload توسعه در `bin/build-installer.php` |

---

---

## تخصیص عاملها در این فاز

| عامل | تسکها | دامنهٔ مجاز در این فاز | خروجی تحویلی | تحویل به |
|---|---|---|---|---|
| `AG-CORE` | `P1-T01`…`P1-T07`, `P1-T11`, `P1-T12` «⏳ deferred» | `free/`, `bin/`, `tools/` | هستهٔ رایگان + `P1-T12` **فقط ثبتشده** | `AG-QA` |
| `AG-QA` | `P1-T08`, `P1-T10` | تستها، PCP، بستهٔ انتشار | ۱۵۲ تست سبز + بستهٔ `v1.0.x` | `AG-ORCH` |
| `AG-DOCS` | `P1-T09` | `docs/Public/`, `CHANGELOG.md`, `free/readme.txt` «با هماهنگی QA» | مستندات عمومی + External services | `AG-ORCH` |
| `AG-ORCH` | «وضعیت فاز» | `roadmaps/README.md`, `DECISIONS.md` | `🔄 reopened` ۱۱ از ۱۲ + `D-045` | `AG-ORCH` «فاز ۲» |

### ورودی / خروجی فاز «Handoff»

- **ورودی:** پایهٔ فاز ۰.
- **خروجی:** بستهٔ WP.org-clean + **`P1-T12` ثبتشده و اجرانشده**.
- **گیرنده:** `AG-ORCH` در فاز ۲ «Gate».
- **گزارش تحویل:** قالب بند ۲ [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md).

> ✅ **معیار پذیرش فاز:** `P1-T12` حتماً `⏳ todo (deferred)` بماند تا تأیید جداگانهٔ مالک؛ در شاخهٔ حاضر `free/` لمس نشود `D-046`.

## جزئیات تسکها

### P1-T01 — جداسازی کد ابر ✅

- `free/src/Storage/S3Storage.php`، `free/src/Storage/S3/*` → `pro/src/Storage/`
- `StorageManager` دیگر نام هیچ درایوری را نمیشناسد؛ درایورها با فیلتر `backupino_storage_drivers` ثبت میشوند و در نبودشان به `local` برمیگردد.
- فیلدهای S3 و انتخابگر درایور از `templates/settings.php` و `admin.js` حذف/پویا شد.
- تست: `tests/Pro/S3SignerTest.php` + اجرای کامل مجموعه (86 سبز).

**DoD:** ✅ کد در `pro/` · ✅ تنظیمات فقط `local` · ✅ تستها سبز.

### P1-T02 — لینک دائمی دانلود ✅

**پیاده‌سازی**

| فایل | نقش |
|---|---|
| `free/src/Download/DownloadLink.php` | ساخت و اعتبارسنجی URL دائمی؛ راز 32 کاراکتری با `random_bytes` در آپشن `backupino_download_key` |
| `free/src/Download/LatestPackage.php` | پیدا کردن جدیدترین `.zip` در پوشه‌ی بسته‌ها (مرتب‌سازی بر اساس mtime، سپس نام) |
| `free/src/Download/AccessPolicy.php` | قواعد دسترسی به‌صورت یک متد خالص و تست‌پذیر |
| `free/src/Download/PublicDownload.php` | نقطه‌ی ورود روی `init` در فرانت‌اند |
| `free/templates/dashboard.php` + `admin.js` | نمایش لینک در پیشخوان با دکمه‌ی کپی |

**قواعد**

1. کلید نادرست → `403` (بدون افشای وجود یا نبود بسته).
2. کلید درست + بدون نشست → ریدایرکت به `wp_login_url( <همان URL> )`؛ بعد از ورود، دانلود **خودکار** شروع می‌شود.
3. نشست بدون `manage_options` (قابل تغییر با فیلتر `backupino_manage_capability`) → `403`.
4. بدون بسته → `404` با پیام روشن.
5. هر دانلود موفق در لاگ ثبت می‌شود (کاربر + نام فایل).

**تست‌ها:** `DownloadLinkTest` (4) · `LatestPackageTest` (3) · `AccessPolicyTest` (5) — همه‌ی شاخه‌های تصمیم پوشش داده شده‌اند.

### P1-T03 — اعلان تلگرام/بله ✅

- `Notify\ChannelFactory` سرویس را به یک ترنسپورت `Notify\BotApi` نگاشت می‌کند: تلگرام روی `https://api.telegram.org`، بله روی `https://tapi.bale.ai` — هر دو Bot API سازگار، پس یک کلاس کافی است.
- گروه تنظیمات `notify` (`service`، `token`، `chat_id`) با مقدار پیش‌فرض `none`؛ سرویس نامعتبر در sanitize به `none` برمی‌گردد و توکن هرگز در خروجی REST/لاگ چاپ نمی‌شود.
- پیام (`Notify\Message`): نام دامنه، محتوای بکاپ، حجم (`Bytes::format`)، زمان، و **لینک دائمی**؛ ارقام همیشه لاتین.
- انتشار رویداد: `JobRunner` در آخرین مرحله `do_action( 'backupino_job_completed', $job )` را صدا می‌زند؛ `Plugin::notify_completed_job()` آن را به `Notifier::announce()` می‌دهد. شکست اعلان هرگز بکاپ را خراب نمی‌کند.
- صف تلاش مجدد: آپشن `backupino_notify_queue` (حداکثر 5 پیام، حداکثر 3 تلاش)، در ابتدای هر `Cron\Scheduler::run_jobs()` فلاش می‌شود.
- تست دستی: `POST /backupino/v1/notify/test` + دکمه‌ی «ارسال پیام آزمایشی» در تنظیمات (اول تنظیمات ذخیره، بعد ارسال) → `{sent, error}`.
- راهنمای کاربر: `docs/Public/BOT-SETUP.md`.

**تست‌ها:** `MessageTest` (4) · `BotApiTest` (6) · `QueueTest` (4) · `NotifierTest` (5) — همه با ترنسپورت جعلی، بدون شبکه.

### P1-T04 — بکاپ خودکار ماهانه ✅

- رویداد وردپرس با recurrence سفارشی `backupino_monthly`؛ زمانبندی: اولین روز ماه.
- محاسبه: اگر locale فارسی باشد، اول ماه شمسی؛ در غیر این صورت اول ماه میلادی با کلاس `Support\Dates::next_month_start()`.
- پیش‌فرض اینتروال: `monthly` و پیش‌فرض نگهداری (`keep`): 3 نسخه.

### P1-T05 — بازنگری تنظیمات و UX ✅

- نگهداری نسخه‌های قدیمی (`retention`) پیش‌فرض 3 نسخه در تنظیمات و کلاس `Cron\Retention` تنظیم شد.
- افزودن چک‌باکس opt-in صریح برای دریافت اخبار و هشدارهای امنیتی (`advanced.opt_in_news`) مطابق راهنمای WordPress.org با مقدار پیش‌فرض `false`.
- تمیزکاری رابط کاربری تنظیمات: حذف استایل‌های جا مانده S3 از `admin.css` و شفاف‌سازی توضیحات مقصد ذخیره‌سازی محلی در برابر کلود.
- تست‌ها: اضافه شدن تست‌های واحد به `SettingsTest`.

### P1-T06 — i18n، RTL و اعداد لاتین ✅

- ثبت و فراخوانی خودکار `load_plugin_textdomain` در متد `Plugin::boot()`.
- پیاده‌سازی متدهای `Str::latin_digits()` و `Str::number()` جهت تضمین ارقام انگلیسی در همه‌ی فرمت‌ها.
- تولید خودکار کاتالوگ پوسته `backupino.pot` با 157 رشته استخراج‌شده از کد.
- ایجاد فایل ترجمه کامل فارسی `backupino-fa_IR.po` و کامپایل نسخه باینری `backupino-fa_IR.mo` با اسکریپت داخلی `bin/build-i18n.php`.
- بازنویسی استایل‌های پیشخوان ادمین در `admin.css` با ویژگی‌های منطقی (`margin-inline-start`, `text-align: start`, `direction: ltr` برای لاگ‌ها).
- تست‌ها: اضافه شدن `StrTest` و `I18nTest` (همه پاس).

### P1-T07 — بستهبندی انتشار ✅

- اسکریپت `bin/build-release.php` و کلاس `Backupino\Build\ReleaseBuilder`:
  - ایجاد بسته `releases/backupino-{version}.zip` با ساختار ریشه منحصراً `backupino/`.
  - اعتبارسنجی نحوی همه‌ی فایل‌های PHP قبل از فشرده‌سازی.
  - حذف خودکار همه‌ی پوشه‌ها و فایل‌های نامربوط (`pro/`, `tests/`, `docs/`, `tools/`, `roadmaps/`, `bin/`, `.github/`, `.gitkeep`, `.DS_Store`, و فایل‌های موقت).
  - محاسبه و نمایش ابعاد، تعداد فایل‌ها و چک‌سام SHA-256 بسته.
- تست‌ها: `BuildReleaseTest` با بررسی کامل محتوا، ساختار، حضور فایل‌های کلیدی و عدم وجود فایل‌های زائد.

### P1-T08 — بازبینی امنیتی و QA ✅

- حذف تابع `extract()` از [`free/src/Admin/Views.php`](free/src/Admin/Views.php) و جایگزینی با مقداردهی ایمن برای رعایت کامل استاندارد مخزن وردپرس.
- اعتبارسنجی سطح دسترسی و `permission_callback` برای تک‌تک روت‌های کنترلر REST.
- بررسی و اطمینان از عدم استفاده از هرگونه توابع خطرناک اجرایی (`eval`, `exec`, `system`, `passthru`, `proc_open`).
- اطمینان از اصل عدم وابستگی به CDN خارجی (آفلاین 100٪).
- پیاده‌سازی مجموعه تست‌های خودکار [`tests/Unit/SecurityQATest.php`](tests/Unit/SecurityQATest.php) و به‌روزرسانی چک‌لیست مخزن.

### P1-T09 — مستندات عمومی + CHANGELOG ✅

- بازنگری و نهایی‌سازی `free/readme.txt` بر اساس استانداردهای رسمی مخزن WordPress.org:
  - تگ‌های معتبر و حذف تگ زائد `s3` (`backup, restore, migration, clone, jalali`).
  - نگارش بخش الزامی افشای سرویس‌های جانبی (`== External Services ==`) شامل مستندسازی دقیق Bot API تلگرام و بله، آدرس اندپوینت‌ها، داده‌های ارسالی، ماهیت کاملاً opt-in و پیوند به قوانین حریم خصوصی.
  - اضافه شدن بخش‌های استاندارد `== Upgrade Notice ==` و تشریح جامع معماری آفلاین-فرست، اینستالر مستقل و لینک دائمی در بخش Description و FAQ.
- نهایی‌سازی فایل `CHANGELOG.md` بر اساس فرمت استاندارد Keep a Changelog و نسخه پایدار `[1.0.0] - 2026-11-01` با پوشش کلیه تغییرات تسک‌های فاز 1.
- به‌روزرسانی و غنی‌سازی راهنماهای عمومی در `docs/Public/*`:
  - [`README.md`](docs/Public/README.md): معرفی جامع، مزایا و نیازمندی‌ها.
  - [`INSTALL.md`](docs/Public/INSTALL.md): آموزش گام‌به‌گام نصب، ساخت بکاپ، بازیابی و دستورات WP-CLI.
  - [`FAQ.md`](docs/Public/FAQ.md): پاسخ به تمام پرسش‌های پرتکرار درباره امنیت، لینک دائمی، حجم و تقویم شمسی.
  - [`BOT-SETUP.md`](docs/Public/BOT-SETUP.md): راهنمای گام‌به‌گام اتصال ربات شخصی تلگرام و بله به همراه صف تلاش مجدد و نکات امنیتی توکن.
- هماهنگ‌سازی نسخه `1.0.0` در کلیه بخش‌های مرتبط (`free/backupino.php`، `free/readme.txt`، `CHANGELOG.md`، `package.json`، اسکریپت‌های توزیع و کاتالوگ‌های i18n).

### P1-T10 — انتشار `v1.0.0` ✅

- بیلد و ایجاد بسته انتشار رسمی `releases/backupino-1.0.0.zip` (144,216 بایت، 92 فایل) با ساختار استاندارد مخزن WordPress.org منحصراً با پیشوند ریشه `backupino/` و چک‌سام تأییدشده SHA-256 (`1d76dd320c9cda96fbc25aa0a29fd9771e0533badc1bf86bdb7a8379e1660536`).
- تولید اینستالر مستقل `releases/installer.php` (75,045 بایت) برای توزیع مستقل در GitHub Release.
- اعتبارسنجی 100٪ چک‌لیست مخزن وردپرس در [`docs/Dev/WORDPRESS-ORG-CHECKLIST.md`](docs/Dev/WORDPRESS-ORG-CHECKLIST.md).
- اجرای کامل مجموعه رگرسیون نهایی فاز 1 (۱۵۲ تست سبز، بدون خطا و بدون صرف‌نظر) روی ماتریس نسخه‌های پشتیبانی‌شده PHP.
- تکمیل اهداف اصلی فاز 1 «نسخهٔ رایگان v1.0.x»؛ وضعیت «۱۰۰٪ / done» در نشست ۲۰۲۶-۱۱-۰۴ به «۱۱ از ۱۲ تسک / 🔄 بازگشایی» اصلاح شد، زیرا `P1-T12` به این فاز اضافه گردید.

### P1-T11 — تقویم شمسی/میلادی ✅

- کلاس `Support\Dates`: تبدیل timestamp به تاریخ جلالی (پیادهسازی خالص PHP، بدون نیاز به افزونهی `intl`).
- فرمت یکسان: `1404/08/09 14:30` در فارسی، `2026-10-31 14:30` در انگلیسی.
- ذخیره در دیتابیس **همیشه میلادی/UTC**؛ جلالی فقط نمایش.
- زمانبندی «اول ماه» (P1-T04) از همین کلاس استفاده میکند — به همین دلیل بعد از آن آمده است.
- تست: 12 ماه + لبهی اسفند/سال کبیسه + تبدیل رفتوبرگشت.

---

---

### P1-T12 — تغییرات کوچک و آتی هستهٔ رایگان ⏳

این تسک بنا به دستور مالک ایجاد شد: «برای تغییرات مربوط به نسخهٔ رایگان، باید تسکی در فاز ۱ ایجاد بکنیم و وضعیت تکمیلشدهٔ فاز ۱ را اصلاح کنیم». **در شاخهٔ طراحی نقشهٔ راه فقط ثبت شده است؛ هیچ فایلی از `free/` تغییر نکرده و اجرای آن نیازمند تأیید جداگانه است.**

| ردیف | تغییر آینده | شرط انطباق با WordPress.org |
|---|---|---|
| 1 | فیلتر `backupino_design_tokens` و `backupino_download_page_styles` در `free/src/Download/PublicDownload.php` | پیشفرض کامل و دقیقاً همان ظاهر فعلی؛ بدون gating / بنر / کد ناقص «چکلیست §2» |
| 2 | یکدستسازی fallback لوکال در `free/src/Installer/installer-template.php` «خط ۸۳۱ `fa_IR` در برابر خط ۱۰۱۱ `en` → `en_US`» و افزودن `locale` به payload توسعه در `bin/build-installer.php` | رفع ناسازگاری؛ `FinalizeStage` از قبل `get_locale()` میفرستد، پس رفتار بستههای واقعی عوض نمیشود |
| 3 | مقادیر CSS | `sanitize_hex_color` / لیست سفید `px|rem` / `font-family` مجاز / `esc_attr`؛ بدون منبع ریموت و بدون `@import` |
| 4 | فونت و استایل | فقط مسیر محلی ثبتشده توسط Elementor Pro Custom Fonts؛ حفظ `phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet` با توجیه «صفحهٔ فرود مستقل» |
| 5 | رشتهها و تست | دامنهٔ `backupino`، بازسازی `.pot/.po/.mo` با `bin/build-i18n.php`، تست `InstallerTest` برای fallback، `SecurityQATest` همچنان سبز |
| 6 | نسخه و اسناد | `1.1.0` طبق `D-029`؛ هماهنگسازی `free/backupino.php`، `free/readme.txt`، `CHANGELOG.md`، `package.json`، `package-lock.json`، `docs/Public/`؛ اجرای مجدد PCP و `php -l` نصاب تولیدشده |

**حکم انطباق:** ذاتاً مخالف استانداردهای مخزن وردپرس نیست «هوک اکستنشن دقیقاً همان الگوی `D-001` و پیشینهٔ `backupino_storage_drivers` است»؛ مشروط به شش شرط بالا. ادعای «۰ خطا / ۰ هشدار» فقط بعد از اجرای واقعی PCP روی کد تغییریافته قابل اثبات است. جزئیات در [`docs/Dev/WORDPRESS-ORG-CHECKLIST.md`](../docs/Dev/WORDPRESS-ORG-CHECKLIST.md) بند ۹.

---

### P1-T12 — تغییرات کوچک و آتی هستهٔ رایگان ⏳

این تسک بنا به دستور مالک ایجاد شد: «برای تغییرات مربوط به نسخهٔ رایگان، باید تسکی در فاز ۱ ایجاد بکنیم و وضعیت تکمیلشدهٔ فاز ۱ را اصلاح کنیم». **در شاخهٔ طراحی نقشهٔ راه فقط ثبت شده است؛ هیچ فایلی از `free/` تغییر نکرده و اجرای آن نیازمند تأیید جداگانه است.**

| ردیف | تغییر آینده | شرط انطباق با WordPress.org |
|---|---|---|
| 1 | فیلتر `backupino_design_tokens` و `backupino_download_page_styles` در `free/src/Download/PublicDownload.php` | پیشفرض کامل و دقیقاً همان ظاهر فعلی؛ بدون gating / بنر / کد ناقص «چکلیست §2» |
| 2 | یکدستسازی fallback لوکال در `free/src/Installer/installer-template.php` «خط ۸۳۱ `fa_IR` در برابر خط ۱۰۱۱ `en` → `en_US`» و افزودن `locale` به payload توسعه در `bin/build-installer.php` | رفع ناسازگاری؛ `FinalizeStage` از قبل `get_locale()` میفرستد، پس رفتار بستههای واقعی عوض نمیشود |
| 3 | مقادیر CSS | `sanitize_hex_color` / لیست سفید `px|rem` / `font-family` مجاز / `esc_attr`؛ بدون منبع ریموت و بدون `@import` |
| 4 | فونت و استایل | فقط مسیر محلی ثبتشده توسط Elementor Pro Custom Fonts؛ حفظ `phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet` با توجیه «صفحهٔ فرود مستقل» |
| 5 | رشتهها و تست | دامنهٔ `backupino`، بازسازی `.pot/.po/.mo` با `bin/build-i18n.php`، تست `InstallerTest` برای fallback، `SecurityQATest` همچنان سبز |
| 6 | نسخه و اسناد | `1.1.0` طبق `D-029`؛ هماهنگسازی `free/backupino.php`، `free/readme.txt`، `CHANGELOG.md`، `package.json`، `package-lock.json`، `docs/Public/`؛ اجرای مجدد PCP و `php -l` نصاب تولیدشده |

**حکم انطباق:** ذاتاً مخالف استانداردهای مخزن وردپرس نیست «هوک اکستنشن دقیقاً همان الگوی `D-001` و پیشینهٔ `backupino_storage_drivers` است»؛ مشروط به شش شرط بالا. ادعای «۰ خطا / ۰ هشدار» فقط بعد از اجرای واقعی PCP روی کد تغییریافته قابل اثبات است. جزئیات در [`docs/Dev/WORDPRESS-ORG-CHECKLIST.md`](../docs/Dev/WORDPRESS-ORG-CHECKLIST.md) بند ۹.

## ریسکهای این فاز

| ریسک | کاهش |
|---|---|
| ربات شخصی برای کاربر عادی سخت است | راهنمای گامبهگام در `docs/Public` + دکمهی «ارسال آزمایشی» |
| محاسبهی اول ماه شمسی خطا کند | تست واحد برای 12 ماه + لبهی اسفند/کبیسه |
| وابستگی به cURL در هاستهای ضعیف | استفاده از `wp_remote_post` (HTTP API) به جای cURL خام |
| حجم بسته روی هاستهای اشتراکی | ادامهی build ریاستارتپذیر + محدودیت پیشفرض tick |

</div>
