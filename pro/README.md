<div align="center">

# 🔒 Backupino Pro (مخزن اختصاصی افزونه حرفه‌ای)
### ماژول‌های اینترپرایز، یکپارچه‌سازی ابری و درایورهای اختصاصی بکاپینو

[![Repository](https://img.shields.io/badge/Repository-Private-red.svg?style=for-the-badge)](#)
[![Edition](https://img.shields.io/badge/Edition-Pro%20Commercial-blueviolet.svg?style=for-the-badge)](#)
[![Core Compatibility](https://img.shields.io/badge/Backupino%20Core->=1.0.0-green.svg?style=for-the-badge)](https://github.com/Backupino/plugin)
[![PHP](https://img.shields.io/badge/PHP-7.4%20--%208.4-777bb4.svg?style=for-the-badge&logo=php)](https://php.net)

</div>

---

<div align="right" dir="rtl">

## ⚠️ محرمانگی و خط‌مشی دسترسی

این مخزن **کاملاً خصوصی (Strictly Private)** بوده و منحصراً جهت توسعه، نگهداری کدهای نسخه تجاری (Pro) و افزودنی‌های پیشرفته بکاپینو استفاده می‌شود.

> [!IMPORTANT]
> کدهای موجود در این مخزن **هرگز نباید** در مخزن عمومی `plugin` یا در بسته ارسالی به مخزن رسمی `WordPress.org` قرار گیرند (مطابق با بند ۵ و ۶ قوانین رسمی افزونه‌های وردپرس). این نسخه به صورت یک افزونه مجزا (`backupino-pro`) توزیع شده و از طریق فیلترها و هوک‌های اکستنشن به هسته رایگان متصل می‌شود.

---

## 🎯 قابلیت‌ها و اهداف نسخه Pro

1. **یکپارچه‌سازی ذخیره‌سازهای ابری (Cloud Destinations):**
   * پشتیبانی بومی از پروتکل Amazon S3 و استانداردهای AWS SigV4
   * سازگار با ArvanCloud S3، MinIO، DigitalOcean Spaces، Wasabi و Cloudflare R2
   * پشتیبانی از Google Drive و Dropbox با سیستم OAuth 2.0
2. **زمانبندی پیشرفته:**
   * امکان تعریف زمانبندی‌های ساعتی، روزانه، هفتگی و ماهانه سفارشی
   * اجرای پایپ‌لاین زنجیره‌ای: «پشتیبان‌گیری → آپلود مستقیم ابری → پاکسازی خودکار دیسک محلی»
3. **رمزنگاری امن در سطح نظامی:**
   * رمزگذاری بسته‌ها با استاندارد AES-256 قبل از خروج از سرور
4. **وردپرس شبکه (Multisite Network Support):**
   * استخراج و کلون یک زیرسایت از شبکه وردپرس به صورت یک سایت تکی مستقل
5. **سیستم لایسنسینگ و به‌روزرسانی اختصاصی:**
   * اعتبارسنجی لایسنس و تحویل خودکار به‌روزرسانی‌ها از پورتال مشتریان

---

## 📂 ساختار دایرکتوری مخزن

```text
pro/
├── README.md                      ← شناسنامه و راهنمای مخزن اختصاصی
├── .gitignore                     ← فایل‌های مستثنی‌شده از گیت
├── autoload.php                   ← لودر کلاس‌های افزونه حرفه‌ای
└── src/                           ← کدهای منبع ماژول‌ها
    └── Storage/                   ← درایورهای ذخیره‌سازی ابری
        ├── S3Storage.php          ← پیاده‌سازی ذخیره‌ساز S3
        └── S3/                    ← کلاینت و امضاکننده احراز هویت
            ├── S3Client.php       ← کلاینت ارتباط با S3 API
            └── S3Signer.php       ← محاسبات هشینگ SHA256 و SigV4
```

---

## 🔌 نقاط اتصال و هوک‌ها به هسته رایگان (Extension Hooks)

نسخه Pro بدون دستکاری فایل‌های هسته، از طریق فیلترهای استاندارد با هسته بکاپینو جفت می‌شود:

| فیلتر اکستنشن | هدف و کاربرد |
|---|---|
| `backupino_storage_drivers` | ثبت مقاصد ذخیره‌سازی ابری جدید (S3, Drive, FTP) |
| `backupino_stages` | تزریق مراحل جدید به پایپ‌لاین (رمزنگاری، آپلود ابری) |
| `backupino_schedules` | تعریف دوره‌های زمانبندی ساعتی و روزانه سفارشی |

</div>