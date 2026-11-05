<div align="right" dir="rtl">

# قرارداد آپدیت خودکار درون‌پیشخوان Pro — Backupino

> **وضعیت سند:** ✅ نهایی و مصوب (Approved)  
> **شناسه تسک:** `C2-T13` · **فاز:** فاز ۲ (قرارداد مشترک «Gate»)  
> **عامل مسئول:** `AG-ORCH` (طراح قرارداد و معماری سیستم)  
> **تصمیمات مرتبط:** `D-001`، `D-002`، `D-015`، `D-022`، `D-029`، `D-046`، `D-049` در [`DECISIONS.md`](DECISIONS.md)  
> **پیشنیازها:** اسناد مصوب [`API-CONTRACT-V1.md`](API-CONTRACT-V1.md)، [`HYBRID-LICENSE-MODEL-B.md`](HYBRID-LICENSE-MODEL-B.md) و [`VERSION-COMPATIBILITY-MATRIX.md`](VERSION-COMPATIBILITY-MATRIX.md).  
> **دامنه:** طراحی معماری به‌روزرسانی یکپارچه و امن با یک کلیک (One-Click Auto-Update) برای افزودنی تجاری Pro در صفحه مدیریت افزونه‌های وردپرس (`plugins.php`) بدون نقض قوانین مخزن وردپرس و با حفظ امنیت پکیج‌های تجاری.

---

## ۱. اهداف و اصول حاکم بر چرخه به‌روزرسانی

1. **تفکیک کانال‌های انتشار (`D-001`):** هسته رایگان (`free/`) منحصراً از مخزن رسمی WordPress.org به‌روزرسانی می‌شود. افزونه تجاری (`pro/`) به صورت مستقل میزبانی شده و کانال آپدیت اختصاصی خود را از طریق پورتال مرکزی مدیریت می‌کند (`D-049`).
2. **تجربه کاربری بومی وردپرس (Native UX):** فرآیند بررسی، نمایش نشانگر نسخه جدید و ارتقای Pro باید دقیقاً از طریق رابط پیش‌فرض پیشخوان وردپرس و با کلاس استاندارد `Plugin_Upgrader` انجام گیرد.
3. **امنیت پکیج تجاری و توکن‌های موقت امضاشده:** لینک‌های دانلود فایل zip پکیج پرو نباید عمومی یا دائمی باشند؛ هر لینک منحصراً پس از اعتبارسنجی لایسنس فعال، به صورت موقت (۱۵ دقیقه) و امضاشده (`signed_download_url`) صادر می‌شود.
4. **تضمین یکپارچگی و رول‌بک خودکار:** سنجش اثرانگشت SHA-256 پیش از جایگزینی فایل‌ها و بازگردانی خودکار نسخه قبلی در صورت بروز خطا.

---

## ۲. هوک‌های استاندارد هسته وردپرس (WordPress Core Hooks Integration)

افزودنی Pro در زمان اجرا، دو هوک استاندارد وردپرس را برای هدایت چرخه آپدیت فیلتر می‌کند:

### ۲.۱. فیلتر `pre_set_site_transient_update_plugins`

این فیلتر هنگام بررسی وضعیت نسخه‌های افزونه‌ها توسط کرون وردپرس یا مراجعه مدیر به برگه افزونه‌ها فراخوانی می‌شود:

```php
add_filter( 'pre_set_site_transient_update_plugins', 'backupino_pro_check_for_update' );

/**
 * Inject Pro update metadata into WordPress update transient.
 *
 * @param object $transient Transient data.
 * @return object Modified transient data.
 */
function backupino_pro_check_for_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $update_info = backupino_pro_get_remote_update_info();
    if ( ! $update_info || empty( $update_info['has_update'] ) ) {
        return $transient;
    }

    $plugin_file = 'backupino-pro/backupino-pro.php';
    $item = (object) array(
        'id'            => 'backupino.com/pro',
        'slug'          => 'backupino-pro',
        'plugin'        => $plugin_file,
        'new_version'   => $update_info['new_version'],
        'url'           => $update_info['changelog_url'],
        'package'       => $update_info['package_url'], // لینک موقت امضاشده یا خالی در صورت انقضا
        'icons'         => array(
            '1x' => 'https://backupino.com/assets/icon-128x128.png',
            '2x' => 'https://backupino.com/assets/icon-256x256.png',
        ),
        'banners'       => array(
            'low'  => 'https://backupino.com/assets/banner-772x250.png',
            'high' => 'https://backupino.com/assets/banner-1544x500.png',
        ),
        'requires'      => $update_info['requires_wp'],
        'requires_php'  => $update_info['requires_php'],
        'tested'        => $update_info['tested_wp'],
    );

    $transient->response[ $plugin_file ] = $item;
    return $transient;
}
```

### ۲.۲. فیلتر `plugins_api` (اکشن `plugin_information`)

این فیلتر زمانی فراخوانی می‌شود که مدیر روی پیوند «مشاهده جزئیات نسخه X» کلیک می‌کند تا پنجره مودال پیشخوان باز شود:

```php
add_filter( 'plugins_api', 'backupino_pro_plugin_information', 20, 3 );

/**
 * Provide detailed plugin popup information.
 *
 * @param false|object|array $result Result.
 * @param string             $action Action name.
 * @param object             $args   Arguments.
 * @return false|object Plugin information object.
 */
function backupino_pro_plugin_information( $result, string $action, $args ) {
    if ( 'plugin_information' !== $action || 'backupino-pro' !== ( $args->slug ?? '' ) ) {
        return $result;
    }

    $info = backupino_pro_get_remote_plugin_details();
    if ( ! $info ) {
        return $result;
    }

    $res = new stdClass();
    $res->name           = 'Backupino Pro';
    $res->slug           = 'backupino-pro';
    $res->version        = $info['version'];
    $res->tested         = $info['tested'];
    $res->requires       = $info['requires'];
    $res->requires_php   = $info['requires_php'];
    $res->author         = '<a href="https://backupino.com">Backupino Team</a>';
    $res->homepage       = 'https://backupino.com/pro/';
    $res->download_link  = $info['download_link'];
    $res->sections       = array(
        'description'  => $info['description_html'],
        'changelog'    => $info['changelog_html'],
        'installation' => $info['installation_html'],
    );

    return $res;
}
```

---

## ۳. قرارداد اندپوینت‌های پورتال برای آپدیت Pro

### ۳.۱. اندپوینت استعلام متادیتا (`GET /api/v1/updates/check`)

کلاینت وضعیت نسخه را استعلام می‌نماید (با کش ۱۲ ساعته در ترنزینت وردپرس):

- **پارامترهای ارسالی در Query:**
  - `license_key`: رشته لایسنس
  - `domain_hash`: هش ۶۴ کاراکتری دامنه سایت
  - `current_pro_version`: نسخه فعلی نصب‌شده (مثلاً `1.0.2`)
  - `current_free_version`: نسخه فعلی هسته رایگان (مثلاً `1.0.2`)
  - `php_version`: نسخه پی‌اچ‌پی (مثلاً `8.3.0`)
  - `wp_version`: نسخه وردپرس (مثلاً `6.7.0`)
- **پاسخ موفق پورتال (HTTP 200):**
  ```json
  {
    "has_update": true,
    "new_version": "1.1.0",
    "release_date": "2026-11-15T10:00:00Z",
    "requires_wp": "6.0",
    "tested_wp": "6.7",
    "requires_php": "7.4",
    "changelog_url": "https://backupino.com/changelog/pro/1-1-0/",
    "package_sha256": "8f4e2...e3b",
    "package_url": "https://backupino.com/api/v1/updates/download?token=eyJhbG...&expires=1730000000&sig=..."
  }
  ```

### ۳.۲. تولید و اعتبارسنجی Signed Download URL

لینک ارائه‌شده در فیلد `package_url` دارای ویژگی‌های امنیتی زیر است:
- **مدت اعتبار کوتاه (TTL):** حداکثر ۹۰۰ ثانیه (۱۵ دقیقه).
- **امضای رمزنگاری‌شده:** شامل هش مجوز، هش دامنه، نسخه هدف و زمان انقضا با امضای سرور پورتال.
- **اعتبارسنجی لحظه دانلود:** پورتال هنگام دریافت درخواست دانلود، مجدداً وضعیت لایسنس دامنه را ارزیابی می‌کند. در صورتی که لایسنس مسدود یا باطل شده باشد، کد خطای HTTP 403 بازگردانده می‌شود.

---

## ۴. سیاست مواجهه با لایسنس منقضی‌شده (`DOWNGRADE` و `READ_ONLY_ACCESS`)

مطابق اصول تصمیم `D-015`:
1. **نمایش اعلان نسخه جدید:** در صورت وجود نسخه جدیدتر، کاربر همچنان وجود نسخه جدید و تغییرات آن را مشاهده می‌کند تا از وصله‌های امنیتی آگاه گردد.
2. **غیرفعال‌سازی لینک دانلود پکیج:** فیلد `package` در آبجکت ترنزینت وردپرس خالی گذاشته می‌شود (`package = ''`).
3. **پیام راهنما در ردیف افزونه (`in_plugin_update_message`):**
   با استفاده از هوک `in_plugin_update_message-backupino-pro/backupino-pro.php`، دکمه پیش‌فرض «به‌روزرسانی خودکار» با پیام زیر جایگزین می‌شود:
   > *«نسخه جدید Backupino Pro (1.1.0) در دسترس است. اشتراک لایسنس شما منقضی شده است. برای دریافت به‌روزرسانی‌های خودکار و پشتیبانی، لطفاً [لایسنس خود را تمدید کنید](https://backupino.com/my-account/).»*

---

## ۵. امنیت فرآیند نصب، بررسی هش و رول‌بک خودکار (Safety & Rollback)

برای جلوگیری از سفید شدن صفحه (WSOD) در زمان ارتقا:
1. **بررسی هش SHA-256 پیش از استخراج:** فایل zip دانلودشده بلافاصله پس از انتقال به پوشه موقت وردپرس اعتبارسنجی هش می‌شود. در صورت عدم تطابق با `package_sha256`، بسته بدون اکسترکت شدن حذف می‌شود.
2. **پشتیبان‌گیری موقت نسخه جاری:** نسخه قبلی پوشه `backupino-pro` پیش از رونویسی به `backupino-pro-backup-temp/` منتقل می‌شود.
3. **بررسی سلامت بعد از استخراج:** اسکریپت سلامت با فراخوانی `token_get_all()` و تست syntax اولیه، سلامت فایل‌های PHP را تایید می‌کند. در صورت وجود Fatal Error، نسخه موقت قبلی در کسری از ثانیه بازگردانی می‌شود.

</div>
