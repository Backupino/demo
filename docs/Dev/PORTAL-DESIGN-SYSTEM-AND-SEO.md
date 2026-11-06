<div align="right" dir="rtl">

# سند مرجع سیستم طراحی و راهبرد سئو پورتال — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه سند:** `PORTAL-DESIGN-SYSTEM-AND-SEO` · **فاز:** فاز ۳ (پورتال رسمی، سایت و فروشگاه)  
> **عاملان مسئول:** `AG-UI` (طراحی، توکن‌ها و تایپوگرافی) + `AG-PORTAL` (معماری، سئو و ساختار صفحات)  
> **تصمیمات مرتبط:** `D-034`، `D-035`، `D-036`، `D-038` در [`DECISIONS.md`](DECISIONS.md)  
> **دامنه:** این سند تنها منبع حقیقت (Single Source of Truth) برای تمام صفحات المنتور، پوسته‌ها، کامپوننت‌های UI، ایمیل‌ها، پالت رنگ لایت/دارک، فاصله‌گذاری و استراتژی سئو دوزبانه پورتال رسمی بکاپینو است.

---

# بخش اول: سیستم طراحی یکپارچه (Design System)

## ۱. توکن‌های طراحی و پالت رنگی لایت و دارک (Color Tokens)

رنگ‌های پورتال بکاپینو با کنتراست استاندارد **WCAG 2.2 AA** (نسبت کنتراست حداقل ۴.۵ به ۱ برای متون عادی و ۳ به ۱ برای تیترهای درشت) مهندسی شده‌اند:

```css
/* ==========================================================================
   پالت پیش‌فرض: حالت روشن (Light Mode - Default)
   ========================================================================== */
:root, [data-theme="light"] {
    /* رنگ‌های هویت بصری برند (Brand Identity) */
    --bk-primary: #0284c7;          /* Sky 600 - آبی اصلی بکاپینو */
    --bk-primary-hover: #0369a1;    /* Sky 700 */
    --bk-primary-light: #e0f2fe;    /* Sky 100 - پس‌زمینه نشان‌ها و المان‌های روشن */
    --bk-primary-glow: rgba(2, 132, 199, 0.15);

    /* رنگ‌های مکمل تیره (Secondary) */
    --bk-secondary: #0f172a;        /* Slate 900 */
    --bk-secondary-hover: #1e293b;  /* Slate 800 */

    /* رنگ‌های وضعیت و عملیات (Status & Functional) */
    --bk-success: #16a34a;          /* Green 600 - وضعیت فعال / تایید */
    --bk-success-hover: #15803d;    /* Green 700 */
    --bk-success-bg: #dcfce7;       /* Green 100 */
    --bk-warning: #d97706;          /* Amber 600 - اخطار / مهلت */
    --bk-warning-bg: #fef3c7;       /* Amber 100 */
    --bk-danger: #dc2626;           /* Red 600 - خطای بحرانی / انقضا */
    --bk-danger-bg: #fee2e2;        /* Red 100 */

    /* سطوح و پس‌زمینه‌ها (Surfaces & Backgrounds) */
    --bk-bg: #f8fafc;               /* Slate 50 - پس‌زمینه تمیز کلی */
    --bk-card-bg: #ffffff;          /* سفید خالص برای کارت‌ها */
    --bk-glass-bg: rgba(255, 255, 255, 0.75); /* پس‌زمینه شیشه‌ای نیمه‌شفاف */
    --bk-glass-border: rgba(226, 232, 240, 0.8);

    /* متون و تایپوگرافی (Text & Ink) */
    --bk-text: #0f172a;             /* Slate 900 - متن تیره و خوانا */
    --bk-text-muted: #64748b;       /* Slate 500 - متون فرعی و توضیحات */
    --bk-border: #e2e8f0;           /* Slate 200 - کادربندی ملایم */
    --bk-line: #cbd5e1;             /* Slate 300 - جداکننده‌ها */

    /* سایه‌ها (Shadows) */
    --bk-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --bk-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --bk-shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
}

/* ==========================================================================
   پالت حالت تاریک (Dark Mode)
   ========================================================================== */
[data-theme="dark"] {
    /* رنگ‌های هویت بصری برند در دارک‌مود (درخشان‌تر برای کنتراست عالی) */
    --bk-primary: #38bdf8;          /* Sky 400 */
    --bk-primary-hover: #0ea5e9;    /* Sky 500 */
    --bk-primary-light: rgba(56, 189, 248, 0.12);
    --bk-primary-glow: rgba(56, 189, 248, 0.2);

    --bk-secondary: #f8fafc;
    --bk-secondary-hover: #e2e8f0;

    --bk-success: #22c55e;          /* Green 500 */
    --bk-success-hover: #16a34a;
    --bk-success-bg: rgba(34, 197, 94, 0.15);
    --bk-warning: #f59e0b;          /* Amber 500 */
    --bk-warning-bg: rgba(245, 158, 11, 0.15);
    --bk-danger: #ef4444;           /* Red 500 */
    --bk-danger-bg: rgba(239, 68, 68, 0.15);

    /* سطوح و پس‌زمینه‌ها در تم تاریک (تیره نرم، بدون مشکی مطلق) */
    --bk-bg: #090d16;               /* سرمه‌ای/مشکی فوق‌العاده عمیق و شیک */
    --bk-card-bg: #111827;          /* Gray 900 - کارت‌ها و ماژول‌ها */
    --bk-glass-bg: rgba(17, 24, 39, 0.7);
    --bk-glass-border: rgba(255, 255, 255, 0.08);

    /* متون در دارک‌مود */
    --bk-text: #f1f5f9;             /* Slate 100 */
    --bk-text-muted: #94a3b8;       /* Slate 400 */
    --bk-border: #1f2937;           /* Gray 800 */
    --bk-line: #374151;             /* Gray 700 */

    /* سایه‌ها در دارک‌مود */
    --bk-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
    --bk-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
    --bk-shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.7);
}
```

---

## ۲. قوانین طراحی شیشه‌ای مدرن (Glassmorphism Invariant)

طبق تصمیمات معماری، استفاده افراطی از شیشه باعث کندی گرافیکی و پرش مرورگر (Jank) در سیستم‌های ضعیف می‌شود. بنابراین:
1. **محدوده مجاز:** فقط در سطوح شناور کوچک و کلیدی (هدر چسبان `Sticky Navbar`، کارت‌های قیمت‌گذاری، پنجره‌های مودال و منوهای کشویی).
2. **کد استاندارد شیشه‌ای:**
   ```css
   .bk-glass-card {
       background: var(--bk-glass-bg);
       backdrop-filter: blur(12px);
       -webkit-backdrop-filter: blur(12px);
       border: 1px solid var(--bk-glass-border);
       box-shadow: var(--bk-shadow-lg);
   }
   ```
3. **پشتیبان قطعی (Fallback):** در مرورگرهایی که از `backdrop-filter` پشتیبانی نمی‌کنند، رنگ پس‌زمینه خودکار به `var(--bk-card-bg)` توپر سوییچ می‌شود.

---

## ۳. سیستم فواصل و شبکه استاندارد (8px Spacing Grid)

تمام پدینگ‌ها، مارجین‌ها و گپ‌های کانتینر در المنتور و قالب‌های اختصاصی، باید منحصراً مضربی از ۸ پیکسل باشند:

| اندازه توکن | مقدار پیکسل | موارد کاربرد استاندارد |
|---|---|---|
| `--bk-space-1` | `4px` | فاصله بین آیکون و متن، پدینگ داخلی تگ‌های کوچک |
| `--bk-space-2` | `8px` | فاصله عمودی فرم‌ها، گپ افقی دکمه‌های متوالی |
| `--bk-space-3` | `12px` | پدینگ داخلی دکمه‌های استاندارد و سلول‌های جدول |
| `--bk-space-4` | `16px` | پدینگ پیش‌فرض کارت‌ها در نمای موبایل، فاصله پاراگراف‌ها |
| `--bk-space-6` | `24px` | پدینگ داخلی کارت‌های دسکتاپ، گپ گرید ستون‌ها |
| `--bk-space-8` | `32px` | فاصله بین سکشن‌های کوچک، پدینگ کانتینرهای اصلی |
| `--bk-space-12` | `48px` | فاصله عمودی بین بخش‌های لندینگ در موبایل |
| `--bk-space-16` | `64px` | فاصله عمودی بین بخش‌های اصلی لندینگ در دسکتاپ |
| `--bk-space-24` | `96px` | فاصله بین سکشن Hero و سکشن‌های بعدی |

### کانتینرهای استاندارد (Container Max Widths):
- **لندینگ پیج و معرفی محصولات:** `max-width: 1200px` (با `padding-inline: 24px`)
- **صفحات فرم، حساب کاربری و چکاوت:** `max-width: 760px`
- **کادر مطالعه مستندات:** `max-width: 860px`

### شعاع گردی گوشه‌ها (Border Radius):
- دکمه‌ها و فیلدهای ورودی: `--bk-radius-md: 8px`
- کارت‌ها، جداول و مودال‌ها: `--bk-radius-lg: 14px`
- نشان‌ها و برچسب‌های وضعیت: `--bk-radius-full: 9999px`

---

## ۴. مقیاس تایپوگرافی و جهت‌گیری (Typography Scale & Fonts)

### ۴.۱. فونت‌ها بدون CDN و کاملاً محلی (`D-020` و `D-035`)
- **زبان انگلیسی (LTR):** خانواده فونت `Inter, system-ui, -apple-system, sans-serif`
- **زبان فارسی (RTL):** خانواده فونت سبک و استاندارد `Arad, Vazirmatn, Segoe UI, Tahoma, sans-serif`

### ۴.۲. مقیاس سلسله‌مراتبی اندازه فونت‌ها (Type Scale)

| سطح | سایز دسکتاپ | سایز موبایل | وزن (Weight) | ارتفاع خط (Line Height) | موارد استفاده |
|---|---|---|---|---|---|
| **H1 (Hero)** | `42px` | `28px` | ۸۰۰ (ExtraBold) | ۱.۲ | عنوان اصلی صفحه لندینگ در بخش Hero |
| **H2 (Section)** | `30px` | `24px` | ۷۰۰ (Bold) | ۱.۳ | تیتر سکشن‌ها (ویژگی‌ها، مقایسه، پلن‌ها) |
| **H3 (Sub-Section)** | `22px` | `18px` | ۶۰۰ (SemiBold) | ۱.۴ | تیتر کارت‌ها و نام پلن‌های قیمتی |
| **H4 / Card Title** | `18px` | `16px` | ۶۰۰ (SemiBold) | ۱.۴ | عنوان ویجت‌ها، پرسش‌های متداول (FAQ) |
| **Body (متن بدنه)** | `15.5px` | `14.5px` | ۴۰۰ (Regular) | ۱.۷۵ | متن توضیحات، پاراگراف‌ها، مقالات |
| **Small / Meta** | `13px` | `12px` | ۵۰۰ (Medium) | ۱.۵ | تاریخ، نسخه، متادیتای لایسنس، کپشن |
| **Badge / Label** | `11.5px` | `11px` | ۷۰۰ (Bold) | ۱.۰ | برچسب‌های Pro، تخفیف، وضعیت |

---

# بخش دوم: استراتژی جامع سئو و کلیدواژه‌ها (SEO Master Strategy)

## ۱. ساختار URL و مسیریابی سئو

```
https://backupino.com/                 <-- نسخه پیش‌فرض بین‌المللی (انگلیسی LTR)
https://backupino.com/fa/              <-- نسخه رسمی بومی (فارسی RTL)
```

- **عدم تغییر زبان بدون اجازه:** انتخاب زبان منحصراً توسط کاربر یا از روی کوکی او ذخیره و اعمال می‌شود (هیچ تغییر اجباری و خودکار بر اساس IP انجام نمی‌شود تا بات‌های خزش گوگل آزادانه هر دو نسخه را پیمایش کنند).
- **کنترل پرملینک‌ها:** اسلاگ‌ها در انگلیسی کوتاه، معنادار و بدون کاراکترهای اضافه (Kebab-case) و در فارسی اسلاگ‌های انگلیسی استاندارد معادل خواهند بود (برای حفظ خوانایی در کپی پیوندها).

---

## ۲. ماتریس جامع کلیدواژه‌های هدف (Keyword Matrix)

| صفحه | کلیدواژه‌های اصلی انگلیسی (Primary EN) | کلیدواژه‌های فرعی انگلیسی (Secondary EN) | کلیدواژه‌های اصلی فارسی (Primary FA) | کلیدواژه‌های فرعی فارسی (Secondary FA) |
|---|---|---|---|---|
| **صفحه اصلی (Home)** | • WordPress Backup Plugin<br>• WordPress Migration Plugin<br>• Clone WordPress Site | • Zero-Downtime Migration<br>• Standalone Restore Tool<br>• Self-hosted WP Backup | • افزونه بکاپ وردپرس<br>• افزونه انتقال وردپرس<br>• مهاجرت سایت وردپرس | • پشتیبان‌گیری خودکار وردپرس<br>• نصاب مستقل و بازگردانی سریع<br>• بکاپ کم‌حجم و سبک |
| **قیمت‌ها (Pricing)** | • Backupino Pricing<br>• WordPress Backup Pro License<br>• Lifetime WordPress Backup | • Unlimited Site Backup Plan<br>• Agency Backup Solution | • خرید لایسنس بکاپینو<br>• قیمت افزونه بکاپینو پرو<br>• لایسنس مادام‌العمر بکاپ | • پلن فریلنسری و سازمانی بکاپ<br>• خرید افزونه پشتیبان‌گیری وردپرس |
| **امکانات (Features)** | • Incremental WordPress Backup<br>• S3 Cloud Backup for WordPress<br>• Local Restore WordPress | • FTP Download Host Backup<br>• Deep Search & Replace<br>• ArvanCloud S3 Backup | • بکاپ افزایشی وردپرس<br>• اتصال بکاپ به هاست دانلود<br>• پشتیبان‌گیری ابری S3 | • سازگار با ابر آروان و پارس‌پک<br>• بازنویسی لینک‌های المنتور<br>• بازیابی بدون نیاز به ورود وردپرس |
| **مقایسه (vs Competitors)** | • Backupino vs UpdraftPlus<br>• Backupino vs Duplicator Pro<br>• Best WordPress Backup 2026 | • Lightweight Alternative to Duplicator<br>• Reliable Migration Tool | • مقایسه بکاپینو و داپلیکیتور<br>• بهترین افزونه بکاپ وردپرس<br>• جایگزین آپ‌درفت‌پلاس | • افزونه بکاپ بدون کندی هاست<br>• مقایسه سرعت مهاجرت وردپرس |
| **مستندات (Docs)** | • Backupino Documentation<br>• How to Restore WordPress<br>• Backupino CLI Guide | • Rescue Agent Setup<br>• S3 Storage Integration Guide | • راهنمای کار با بکاپینو<br>• آموزش بازگردانی بکاپ وردپرس<br>• اتصال بکاپینو به FTP | • آموزش عامل نجات بکاپینو<br>• رفع خطای بازگردانی در هاست اشتراکی |

---

## ۳. الگوی متاتگ‌های سئو و شبکه‌های اجتماعی (Meta Tags Blueprint)

### ۳.۱. فرمت تایتل و توضیحات (Title & Description):
- **انگلیسی:**
  - `Title`: `Backupino — Fastest WordPress Backup & Migration Plugin (Zero-Downtime)`
  - `Description`: `Reliable, lightweight WordPress backup, clone & migration plugin. Self-contained, incremental backups, S3 & FTP destinations with one-click restore.`
- **فارسی:**
  - `Title`: `بکاپینو — سریع‌ترین افزونه بکاپ، کلون و مهاجرت وردپرس بدون قطعی`
  - `Description`: `افزونه حرفه‌ای و سبک پشتیبان‌گیری و انتقال وردپرس. بکاپ افزایشی، اتصال به هاست دانلود و ابر S3 با بازگردانی مستقل و بدون نیاز به پیشخوان.`

### ۳.۲. تگ‌های شبکه‌های اجتماعی (OpenGraph & Twitter Card):
```html
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Backupino" />
<meta property="og:title" content="{{page_title}}" />
<meta property="og:description" content="{{page_description}}" />
<meta property="og:image" content="https://backupino.com/assets/og-cover.png" />
<meta name="twitter:card" content="summary_large_image" />
```

---

## ۴. داده‌های ساختاریافته استاندارد (JSON-LD Schemas)

در صفحه اصلی و صفحات محصول، کدهای اسکیمای معتبر زیر تزریق می‌شوند:

### ۴.۱. اسکیمای نرم‌افزار (`SoftwareApplication`):
```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Backupino",
  "operatingSystem": "WordPress 6.0+",
  "applicationCategory": "UtilitiesApplication",
  "softwareVersion": "1.0.2",
  "inLanguage": "en-US",
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "USD",
    "lowPrice": "0",
    "highPrice": "199"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "5.0",
    "reviewCount": "48"
  }
}
```

### ۴.۲. اسکیمای سوالات متداول (`FAQPage`):
سکشن FAQ لندینگ با اسکیمای دقیق جهت نمایش به صورت ریچ‌اسنیپت (Rich Snippet) در نتایج گوگل کدگذاری می‌شود.

---

## ۵. بهینه‌سازی برای موتورهای مولد هوش مصنوعی (GEO & LLM Readiness)

طبق تصمیم `D-038`:
1. **فایل `llms.txt` در ریشه دامنه:**
   یک فایل متنی در مسیر `https://backupino.com/llms.txt` حاوی مشخصات فنی صریح، قابلیت‌های مقایسه‌ای و حقایق مستند درباره بکاپینو جهت خوانش موتورهای جستجوی AI نظیر Perplexity، ChatGPT و Gemini.
2. **بیان حقایق بدون مبالغه:** حذف ادعاهای مبهم بازاریابی و جایگزینی با آمارهای فنی دقیق (مانند: "۱۵۲ تست واحد"، "پشتیبانی از PHP 7.4 تا 8.4"، "بدون وابستگی به cURL یا Exec").

</div>
