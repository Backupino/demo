<div align="right" dir="rtl">

# قرارداد چندزبانه (i18n)، فونت‌های محلی و توکن‌های طراحی واحد — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه تسک:** `C2-T09` · **فاز:** فاز ۲ (قرارداد مشترک «Gate»)  
> **عامل مسئول:** `AG-UI` (طراح تجربه کاربری و استایل)  
> **تصمیمات مرتبط:** `D-013`، `D-020`، `D-035`، `D-036`، `D-046` در [`DECISIONS.md`](DECISIONS.md)  
> **دامنه:** این سند قرارداد معماری طراحی بصری، بین‌المللی‌سازی و تایپوگرافی را در تمام اجزای اکوسیستم بکاپینو (هسته رایگان، افزودنی پرو، نصاب مستقل و پورتال رسمی) تعیین می‌کند. هیچ کدی در `free/` یا `pro/` بدون ثبت تسک تغییر نمی‌یابد.

---

## ۱. اهداف و اصول راهبردی

1. **دو زبانه بودن هم‌سطح و بدون تبعیض (`en_US` و `fa_IR`):** زبان پیش‌فرض اکوسیستم برای رعایت استانداردهای جهانی مخزن وردپرس `en_US` و زبان بومی رسمی برای پورتال و افزونه با پوشش ۱۰۰٪ ترجمه `fa_IR` است (`D-035`).
2. **خودکفایی ۱۰۰٪ و عدم وابستگی به فونت‌های ریموت (Self-contained & Zero-CDN):** هیچ‌یک از اجزای بکاپینو (صفحه دانلود پابلیک، نصاب مستقل، پنل ادمین و ایمیل‌ها) مجاز به فراخوانی Google Fonts، FontAwesome CDN یا هر ارائه‌دهنده ریموت دیگری نیستند (`D-020`).
3. **تفکیک فونت‌های محلی از Elementor Pro Custom Fonts:** دو خانواده فونت محلی استاندارد به صورت محلی در پوسته و افزونه تعبیه می‌شوند تا نیازی به ماژول تجاری Custom Fonts المنتور پرو یا بارگذاری خارجی نباشد (`D-035`).
4. **تک‌منبع حقیقت برای توکن‌های طراحی (Single Source of Truth):** هوک‌های فیلتر توکن (`backupino_design_tokens`) و متغیرهای CSS واحد باید ظاهر صفحه فرود دانلود عمومی (`PublicDownload`)، نصاب مستقل (`Installer`)، قالب ایمیل‌های تراکنشی و بخش‌های پورتال را با پالت رنگی و تایپوگرافی سازگار هماهنگ سازند (`D-036`).
5. **همگام‌سازی لوکال پکیج و نصاب مستقل:** نصاب خودکار باید زبان محیط مبدا بکاپ را تشخیص داده و تجربه کاربری (RTL/LTR و متون) را بلافاصله منطبق کند و در صورت تغییر کاربر، دیتابیس مقصد را به‌درستی پیکربندی نماید.

---

## ۲. معماری بین‌المللی‌سازی (i18n & l10n)

### ۲.۱. ساختار Textdomain و بارگذاری ترجمه‌ها

```
backupino/
├── languages/
│   ├── backupino.pot              # قالب اصلی استخراج‌شده از سورس
│   ├── backupino-fa_IR.po          # فایل ترجمه فارسی
│   └── backupino-fa_IR.mo          # باینری کامپایل‌شده ترجمه فارسی
```

- **نام Textdomain:** `backupino`
- **بارگذاری:** تابع استاندارد وردپرس `load_plugin_textdomain( 'backupino', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' )` در هوک `plugins_loaded`.
- **انطباق با GlotPress:** قالب `backupino.pot` استانداردهای مخزن وردپرس را دنبال کرده و برای ترجمه عمومی در translate.wordpress.org بدون هشدارهای PHPCS آماده است.

### ۲.۲. مسیریابی چندزبانه در پورتال رسمی و سئو (hreflang)

در پورتال رسمی بکاپینو (طراحی‌شده در فاز ۳ بر پایه وردپرس و ووکامرس):
- **دامنه انگلیسی (پیش‌فرض بین‌المللی):** `https://backupino.com/` (با `lang="en-US"` و `dir="ltr"`)
- **بخش فارسی:** `https://backupino.com/fa/` (با `lang="fa-IR"` و `dir="rtl"`)
- **تگ‌های استاندارد `hreflang` در هدر تمام صفحات:**
  ```html
  <link rel="alternate" hreflang="en" href="https://backupino.com/" />
  <link rel="alternate" hreflang="fa" href="https://backupino.com/fa/" />
  <link rel="alternate" hreflang="x-default" href="https://backupino.com/" />
  ```
- **سیاست ژئولوکیشن بدون ردیابی تهاجمی (`D-038`):** تغییر زبان و سوئیچ محتوا بر اساس درخواست صریح کاربر یا هدر `Accept-Language` مرورگر انجام می‌شود، نه ردیابی IP یا موقعیت جغرافیایی تحمیلی.

---

## ۳. سیاست فونت‌های محلی و تایپوگرافی (Local Fonts Policy)

### ۳.۱. عدم وابستگی به شبکه و افزونه‌های تجاری

طبق تصمیمات `D-020` و `D-035`:
- هیچ استایلی حق ندارد از `@import url('https://fonts.googleapis.com/...')` استفاده کند.
- فونت‌ها منحصراً از دو لایه تامین می‌شوند:
  1. **فونت‌های تعبیه‌شده در بسته/پوسته (Self-hosted Webfonts):** فرمت‌های مدرن `woff2` و `woff` با فشرده‌سازی حداکثری و `font-display: swap`.
  2. **زنجیره فونت‌های سیستمی و محلی (System Font Stack):** بهره‌گیری از فونت‌های استاندارد نصب‌شده در سیستم‌عامل کاربر بدون ایجاد سربار دانلود شبکه.

### ۳.۲. تعریف دو خانواده فونت محلی مستقل

| کاربرد | خانواده فونت اولویت‌دار | زنجیره فال‌بک لوکال (System Fallback) |
|---|---|---|
| **فارسی (RTL)** | `Arad` / `Vazirmatn` (وب‌فونت سبک محلی) | `local("Arad")`, `local("B Arad")`, `local("Vazirmatn")`, `"Segoe UI"`, `Tahoma`, `system-ui`, `sans-serif` |
| **انگلیسی/لاتین (LTR)** | `Inter` / `System-UI` | `system-ui`, `-apple-system`, `BlinkMacSystemFont`, `"Segoe UI"`, `Roboto`, `"Helvetica Neue"`, `Arial`, `sans-serif` |

### ۳.۳. پیاده‌سازی `@font-face` امن و سبک

در نصاب مستقل و صفحات اختصاصی، اعلام فونت محلی به فرمت زیر انجام می‌پذیرد تا در صورت آفلاین بودن کامل کلاینت نیز زیباترین تایپوگرافی محلی بدون شکست نمایش داده شود:

```css
@font-face {
    font-family: "Arad";
    src: local("Arad"), local("B Arad"), local("Arad-Regular");
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: "Arad";
    src: local("Arad Bold"), local("B Arad Bold"), local("Arad-Bold");
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

/* زنجیره فونت هوشمند بر اساس جهت صفحه */
html[dir="rtl"] body {
    font-family: "Arad", "Vazirmatn", "Segoe UI", Tahoma, system-ui, sans-serif;
}

html[dir="ltr"] body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", "Helvetica Neue", Arial, sans-serif;
}
```

---

## ۴. لایه توکن‌های طراحی واحد (Unified Design Tokens Contract)

### ۴.۱. منبع واحد حقیقت (Single Source of Truth)

مطابق `D-036` و هوک‌های ارائه‌شده در `P1-T12`، کلیه اجزای فرانت‌اند و صفحات ایزوله از یک نگاشت ثابت متغیرها تغذیه می‌کنند:

```php
$default_tokens = array(
    'primary_color'   => '#0284c7', // رنگ اصلی برند (Sky 600 / Backupino Blue)
    'primary_hover'   => '#0369a1', // حالت هاور رنگ اصلی (Sky 700)
    'secondary_color' => '#0f172a', // رنگ تیره پس‌زمینه/کنتراست (Slate 900)
    'secondary_hover' => '#1e293b', // حالت هاور رنگ مکمل (Slate 800)
    'both_color'      => '#16a34a', // رنگ وضعیت موفقیت/عملیات کامل (Green 600)
    'both_hover'      => '#15803d', // حالت هاور موفقیت (Green 700)
    'bg_color'        => '#f1f5f9', // پس‌زمینه تمیز (Slate 100)
    'card_bg'         => '#ffffff', // پس‌زمینه کارت محتوا
    'text_color'      => '#1e293b', // رنگ متن اصلی (Slate 800)
    'font_family'     => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
);
```

### ۴.۲. نگاشت متغیرهای CSS (`:root`)

در خروجی HTML تمامی صفحات ایزوله (صفحه دانلود پابلیک، نصاب، ایمیل و پنل)، این توکن‌ها به متغیرهای سی‌اس‌اس استاندارد تبدیل می‌شوند:

```css
:root {
    --bk-primary: #0284c7;
    --bk-primary-hover: #0369a1;
    --bk-secondary: #0f172a;
    --bk-secondary-hover: #1e293b;
    --bk-both: #16a34a;
    --bk-both-hover: #15803d;
    --bk-bg: #f1f5f9;
    --bk-card-bg: #ffffff;
    --bk-text: #1e293b;
    --bk-muted: #64748b;
    --bk-border: #e2e8f0;
    --bk-radius: 12px;
    --bk-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
```

### ۴.۳. کاربرد توکن‌ها در ایمیل‌های ووکامرس و تراکنش‌های پرتال

ایمیل‌های ارسالی برای خرید لایسنس، هشدار انقضا، یا اعلان بکاپ از همین پالت رنگی و استایل تیتر/دکمه پیروی می‌کنند:
- **دکمه فراخوان (CTA Button):** پس‌زمینه `--bk-primary` با رنگ متن `#ffffff` و گوشه‌های گرد `8px`.
- **کارت اطلاعات لایسنس:** کادر با رنگ `--bk-border` و پس‌زمینه سفید روی بک‌گراند `--bk-bg`.
- **ایمیل دوزبانه:** ساختار ایمیل‌ها بر اساس زبان انتخابی کاربر در ووکامرس (فارسی RTL با فونت محلی یا انگلیسی LTR) رندر می‌شود.

---

## ۵. یکدست‌سازی لوکال نصاب مستقل و پکیج بکاپ (Installer & Locale Sync)

### ۵.۱. چرخه انتقال و تشخیص زبان

در زمان تولید پکیج بکاپ در سایت مبدا:
1. زبان سایت مبدا (`get_locale()`) درون آرایه متادیتای `backupino_payload['locale']` ذخیره می‌شود (مثلاً `fa_IR` یا `en_US`).
2. هنگام اجرای نصاب مستقل (`backupino-installer.php`):
   - اگر مقدار `locale` با پیشوند `fa` باشد، نصاب در حالت پیش‌فرض با `currentLang = "fa"` و ویژگی `dir="rtl"` لود می‌شود.
   - در غیر این صورت با `currentLang = "en"` و `dir="ltr"` رندر می‌گردد.
3. کاربر در بالای صفحه نصاب با کلیک روی کلیدهای `.lang-switch` می‌تواند بدون بارگذاری مجدد صفحه، زبان نصاب را بین انگلیسی و فارسی جابجا کند.

### ۵.۲. اعمال قطعی زبان روی دیتابیس مقصد در مرحله نهایی (Finalize)

نصاب مستقل موظف است در فاز بازنویسی و نهایی‌سازی، زبان مورد نظر را در دیتابیس وردپرس بازگردانی‌شده تثبیت کند:
- گزینه `WPLANG` در جدول `wp_options`:
  ```sql
  REPLACE INTO `wp_options` (`option_name`, `option_value`, `autoload`) 
  VALUES ('WPLANG', 'fa_IR', 'yes');
  ```
- متای `locale` کاربران در جدول `wp_usermeta`:
  ```sql
  UPDATE `wp_usermeta` SET meta_value = 'fa_IR' WHERE meta_key = 'locale';
  ```

---

## ۶. انطباق با قوانین مخزن و چک‌لیست امنیتی

1. **بدون ردیابی بیرونی:** هیچ اسکریپت یا فونتی از خارج سرور کاربر لود نمی‌شود.
2. **کد ایزوله و تمیز:** همه متغیرها با تابع `sanitize_hex_color()` و فیلترهای امنیتی اعتبارسنجی می‌شوند تا مانع تزریق CSS یا کد مخرب گردند.
3. **عدم وابستگی به افزونه‌های تجاری:** کاربر بدون نیاز به هیچ افزونه جانبی (مانند WPML، Polylang یا Elementor Pro) تجربه بصری و زبانی کامل را دریافت می‌کند.

</div>
