<div align="right" dir="rtl">

# releases/

> **Status:** active · **Last updated:** 2026-10-31

خروجی ساخت اینجا قرار میگیرد و **در گیت ذخیره نمیشود** (بهجز این فایل).

| فایل | ساختهشده با | توضیح |
|---|---|---|
| `backupino-<version>.zip` | `php bin/build-release.php` | بستهی آمادهی نصب در وردپرس (ریشهی zip = `backupino/`) |
| `installer.php` | `php bin/build-installer.php` | اینستالر مستقل برای بررسی محلی |

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
