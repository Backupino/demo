<div align="right" dir="rtl">

# فاز ۲ — قرارداد مشترک، لایسنس و حریم خصوصی «Gate»

> **Status:** 🔄 in-progress · **Progress:** ۱ از ۱۵ تسک (۷٪) · **Last updated:** 2026-11-04
> **پیشنیاز:** اصلاح وضعیت فاز ۱ و ثبت `P1-T12` انجام شده باشد.
> **دامنه:** در این شاخه فقط **طراحی و ثبت قرارداد**؛ هیچ کدی در `free/` یا `pro/` نوشته نمیشود.
> **عامل مسئول فاز:** `AG-ORCH` «طراح قرارداد» · **نگهداشت سند:** `AG-DOCS` · **همکاران طراح:** `AG-CORE` / `AG-PRO` / `AG-PORTAL` / `AG-UI` / `AG-QA` · **قرارداد تخصیص:** [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md)

## هدف

پیش از آنکه سایت رسمی یا افزونهٔ Pro شروع شوند، مشخص شود این دو دقیقاً چه چیزی ردوبدل میکنند، لایسنس چگونه امضا و بهصورت آفلاین بررسی میشود، هستهٔ رایگان کدام نقاط اتصال را بدون هیچ کد تجاری در اختیار میگذارد، و دادهٔ کاربر چگونه هرگز از سایتش خارج نمیشود.

## تسکها

| ID | عامل | وضعیت | تسک | خروجی / معیار پذیرش | وابستگی |
|---|---|---|---|---|---|
| `C2-T01` | `AG-DOCS` | ✅ done | ثبت تصمیمات `D-031`…`D-053` و اصلاح `D-009` / `D-002` / `SPEC.md` §2 و §10 | `docs/Dev/DECISIONS.md`، `PRODUCT-PLANS.md`، `SPEC.md`، `ARCHITECTURE.md` | — |
| `C2-T02` | `AG-ORCH` | ⏳ todo | قرارداد API `v1` افزونه ↔ پنل | `activate`، `validate`، `heartbeat`، `deactivate`، `plans`، `usage`، `backup-index`، `download-url`، `feature-flags`، `version-matrix`، `command-queue`؛ JSON Schema، کد خطا، idempotency، ضد replay، نرخمحدود، نسخهبندی `/v1` | `C2-T01` |
| `C2-T03` | `AG-ORCH` | ⏳ todo | قرارداد سند لایسنس `Ed25519` | payload امضاشده: `plan`، `quota`، `domains`، `activation_id`، `expiry`، `issued_at`، `nonce`، `signature`؛ کلید خصوصی فقط پنل، کلید عمومی `۳۲ بایت` در Pro، امضای `۶۴ بایت`؛ ماتریس fallback روی PHP 7.4–8.4 با/بدون `ext-sodium` | `C2-T01` |
| `C2-T04` | `AG-ORCH` | ⏳ todo | مدل `B` هیبرید pull-first | سند امضاشده + کش آفلاین ۷ روزه + heartbeat **اختیاری** + `فعال → T-3d اخطار → انقضا → رایگان خواندنی`؛ هیچ تماس اجباری از `free/` | `C2-T03` |
| `C2-T05` | `AG-CORE` | ⏳ todo | نقاط اتصال هستهٔ رایگان «طراحی، نه پیادهسازی» | هوکهای واقعی فعلی: `backupino_manage_capability`، `backupino_storage_drivers`، `backupino_schedule_intervals`، `backupino_schedule_intervals_labels`، `backupino_job_completed`. هوکهای برنامهریزیشده: `backupino_stages`، `backupino_limits`، `backupino_pro_features`، `backupino_restore_pipeline`، `backupino_design_tokens`، `backupino_download_page_styles`، `backupino_package_published` | `C2-T01` |
| `C2-T06` | `AG-CORE` | ⏳ todo | قرارداد `Restore` محلی خالص | اینترفیس `RestoreStep` + فیلتر `backupino_restore_pipeline`؛ هسته همان کلاسهای `Installer` را بهعنوان مراحل پیشفرض ثبت کند؛ Pro فقط از طریق فیلتر/اینترفیس، بدون `use Backupino\Installer\...` مستقیم «قاعدهٔ جدا نشدن `D-022`» | `C2-T05` |
| `C2-T07` | `AG-PRO` | ⏳ todo | قرارداد عامل نجات و دستور امضاشده | `backupino-rescue.php` در ریشهٔ سایت، خارج از `wp-content/` «همان قفل ۴۰۳»، توکن یکبارمصرف، `hash_equals`، `noindex/no-store`، **فقط درخواست خروجی HTTPS**؛ پنل هرگز inbound نمیشود؛ دستور مقید به `instance_id` + `domain_hash` + `backup_hash` + `expiry` + `nonce` | `C2-T02`، `C2-T03` |
| `C2-T08` | `AG-QA` | ⏳ todo | ماتریس سازگاری نسخهها | `free >= 1.0.1` ↔ `pro 1.x` ↔ `portal 1.x`؛ حداقل نسخه، اعلام ناسازگاری، حالت read-only هنگام mismatch؛ آپدیت رایگان از WordPress.org و آپدیت Pro از پنل امضاشده | `C2-T02` |
| `C2-T09` | `AG-UI` | ⏳ todo | قرارداد `en_US` / `fa_IR` و توکنهای طراحی | `locale` وردپرس + `hreflang` + RTL/LTR کامل + ۲ فونت محلی جدا از Elementor Pro Custom Fonts + لایهٔ توکن واحد شامل ایمیلها و `PublicDownload`؛ یکدستسازی fallback لوکال نصاب و `locale` بسته | `D-035`، `D-036` |
| `C2-T10` | `AG-QA` | ⏳ todo | خطمشی حریم خصوصی و WordPress.org | جدول دادههای مجاز/ممنوع، `domain_hash` بهجای دامنهٔ خام، عدم ذخیرهٔ IP پایدار، عدم تلهٔمتری، افشای نشدن سرویس جدید در `readme.txt` رایگان، `D-020` و `D-002` پابرجا | `C2-T04` |
| `C2-T11` | `AG-PORTAL` | ⏳ todo | رزرو فیلدهای ابر اختصاصی بدون پیادهسازی | `plan.cloud_quota`، `cloud_versions`، `usage`، `signed-upload-url`، `zero_knowledge`، سیاست `۳ اخطار / ۶۰ روز` فقط بهصورت داده؛ جلوگیری از migration در فاز ۷ | `D-041` |
| `C2-T12` | `AG-QA` | ⏳ todo | تستهای قرارداد و mockها | `FakeLicenseServer`، تست امضای دستکاریشده، انقضا، کش آفلاین، دامنهٔ اشتباه، replay، idempotency، نسخهٔ ناسازگار؛ افزودن `tests/Pro/*` به CI «اجرا در فاز ۴، طراحی در این فاز» | `C2-T02`…`C2-T08` |
| `C2-T13` | `AG-ORCH` | ⏳ todo | قرارداد آپدیت خودکار Pro | API متادیتای نسخه جدید + URL دانلود موقت امضاشده (`signed_download_url`) برای هوک‌های رسمی وردپرس (`pre_set_site_transient_update_plugins` و `plugins_api`) `D-049` | `C2-T02` |
| `C2-T14` | `AG-ORCH` | ⏳ todo | قواعد لیست سفید دامنه‌های استیجینگ/لوکال | رجکس و قوانین دامنه برای محیط‌های تستی بدون کسر سهمیه لایسنس (`localhost`, `127.0.0.1`, `*.test`, `*.local`, `staging.*`, `dev.*`, `test.*`) `D-051` | `C2-T03` |
| `C2-T15` | `AG-CORE` | ⏳ todo | قرارداد مانیفست و بسته‌های دلتای بکاپ افزایشی | ساختار داده‌ای `manifest.json` (شامل سایز، mtime و sha256 فایل‌ها)، ژورنال حذفیات و نحوه ادغام با درایورهای FTP و S3 `D-048`، `D-050` | `C2-T05` |

---

## تخصیص عاملها در این فاز

> در فاز ۲ عاملها **طراح / امضاءکنندهٔ قرارداد**اند، نه پیادهساز. هیچ کدی در `free/` یا `pro/` نوشته نمیشود.

| عامل | تسکها | دامنهٔ مجاز در این فاز | خروجی تحویلی | تحویل به |
|---|---|---|---|---|
| `AG-DOCS` | `C2-T01` | `docs/Dev/` | `D-031`…`D-047` + اصلاح `SPEC.md` §2/§10 | `AG-ORCH` |
| `AG-ORCH` | `C2-T02`, `C2-T03`, `C2-T04` | `roadmaps/`, `DECISIONS.md` | API `v1`، سند `Ed25519`، مدل `B` | `AG-PRO` / `AG-PORTAL` |
| `AG-CORE` | `C2-T05`, `C2-T06` | «طراحی هوکها، بدون کد» | قرارداد فیلترها + `RestoreStep` | `AG-PRO` |
| `AG-PRO` | `C2-T07` | «طراحی عامل rescue» | دستور امضاشدهٔ pull-only | `AG-QA` |
| `AG-UI` | `C2-T09` | توکنها / فونت / لوکال | `en_US` + `fa_IR` + ۲ فونت محلی | `AG-PORTAL` |
| `AG-PORTAL` | `C2-T11` | «رزرو فیلدها» | شمای ابر فاز ۷ بدون migration | `AG-CLOUD` |
| `AG-QA` | `C2-T08`, `C2-T10`, `C2-T12` | ماتریس نسخه، حریم خصوصی، mockها | گزارش تست قرارداد | `AG-ORCH` |

### ورودی / خروجی فاز «Handoff»

- **ورودی:** فاز ۱ اصلاحشده + `D-031`…`D-047`.
- **خروجی:** **قرارداد قفلشدهٔ Gate** «API، لایسنس، هوکها، Restore، Rescue، لوکال، حریم خصوصی».
- **گیرنده:** `AG-PORTAL` فاز ۳ و `AG-PRO` فاز ۴ «موازی».
- **گزارش تحویل:** قالب بند ۲ [`AGENT-CONTRACT.md`](AGENT-CONTRACT.md).

> ✅ **معیار پذیرش فاز:** `D-046` پابرجا؛ `free/` و `pro/` دستنخورده؛ `C2-T02` / `C2-T07` قبل از آغاز ۳ و ۴ تأیید `AG-QA` گرفته باشند.

## تصمیمات مرتبط

`D-001`، `D-002`، `D-003`…`D-008`، `D-015`، `D-020`، `D-022`، `D-026`، `D-031`…`D-053` در [`docs/Dev/DECISIONS.md`](../docs/Dev/DECISIONS.md).

## تعریف «انجام شد»

- [ ] قرارداد API، لایسنس، Restore، Rescue، نسخهها و حریم خصوصی همگی سند شده و `D-031+` ثبت شدهاند.
- [ ] `free/` همچنان بدون کد لایسنس/ابری/پنلی و بدون تماس اجباری خارجی است.
- [ ] `readme.txt` رایگان نیازی به افشای سرویس جدید پیدا نکرده است.
- [ ] `npm test` همچنان ۱۵۲ تست سبز دارد «چون سورس تغییر نکرده».
- [ ] `roadmaps/README.md` و فاز ۲ به `done` بهروز شدهاند.

## خطرها

- **SLA:** از لحظهای که پنل وجود دارد، قطعی پنل = قطعی محصول مشتری؛ کش ۷ روزه و طراحی pull-first اجباری است.
- **انطباق:** کوچکترین تماس اجباری از `free/` به پنل، `readme.txt` §External Services و چکلیست PCP را نقض میکند.
- **پیچیدگی امضا:** fallback نادرست `sodium`/`openssl` میتواند لایسنس را روی هاست اشتراکی غیرقابلverify کند؛ ماتریس `C2-T03` الزامی است.

</div>
