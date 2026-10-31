<div align="right" dir="rtl">

# releases/

> **Status:** active · **Last updated:** 2026-11-01

خروجی ساخت اینجا قرار میگیرد و **در گیت ذخیره نمیشود** (بهجز این فایل).

| فایل | ساختهشده با | توضیح |
|---|---|---|
| `backupino-<version>.zip` | `php bin/build-release.php` | بستهی آمادهی نصب در وردپرس (ریشهی zip = `backupino/`) |
| `installer.php` | `php bin/build-installer.php` | اینستالر مستقل برای بررسی محلی |

## مشخصات انتشار پایدار v1.0.0

| ویژگی | مقدار |
|---|---|
| نسخه انتشار | `1.0.0` |
| تاریخ ایجاد | 2026-11-01 |
| بسته انتشار | `backupino-1.0.0.zip` (134,252 بایت، 92 فایل) |
| چک‌سام SHA-256 بسته | `0691e52dbd6f26ef0a605d599ad1290a009c739431db0913c2e0168c34cecdae` |
| فایل اینستالر مستقل | `installer.php` (75,045 بایت) |
| وضعیت اعتبارسنجی | آزمون‌های واحد 142/142 پاس، ممیزی امنیتی تأییدشده |

## محتویات zip انتشار

```
backupino/
  backupino.php
  uninstall.php
  readme.txt
  src/  templates/  assets/  languages/
```

**حذفشدهها:** `pro/`, `tests/`, `docs/`, `tools/`, `bin/`, `roadmaps/`, `.github/`, `package*.json`, `composer.json`, `phpcs.xml.dist`.

## قاعده

هر فایل در این پوشه باید از یک کامیت مشخص و با نسخهی ثبتشده در `CHANGELOG.md` ساخته شده باشد.

</div>
