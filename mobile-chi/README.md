# موبایل چی (Mobile Chi) — پوسته وردپرس

نسخه: **1.2.2** — بازسازی‌شده از تحلیل دموی `https://demo-serverha.ir/woodmart/tikakala/`
(رنگ اصلی `#435fcb`، فونت یکان‌بخ/ایران‌سنس، گرید ۴ستونه، RTL).

## دو حالت اجرا

| حالت | شرط | رفتار |
|---|---|---|
| **فرزند WoodMart** | قالب مادر `woodmart` در `wp-content/themes` نصب باشد | استایل و قالب‌های WoodMart + سفارشی‌سازی‌های این پوسته؛ خط `Template: woodmart` به‌صورت خودکار در هدر style.css قرار می‌گیرد |
| **مستقل (Standalone)** | WoodMart نصب نباشد | پوسته یک پوستهٔ کامل مستقل است؛ قالب‌های جایگزین داخلی (خانه، نوشته، برگه، بایگانی، جستجو، فروشگاه ووکامرس) از `uploads/mobile-chi-fallback` سرو می‌شوند |

> مهم: فایل بسته هیچ‌گاه با خط `Template:` ارائه نمی‌شود، بنابراین وردپرس هرگز آن را در فهرست
> **«پوسته‌های خراب»** نمایش نمی‌دهد. پس از نصب WoodMart، تابع `mobile_chi_sync_header_with_reality()`
> خودکار حالت فرزند را برمی‌گرداند.

## رفع خطای «پوستهٔ مادر در دسترس نیست»

1. پوسته را نصب و فعال کنید — دیگر «پوسته‌های خراب» نخواهد شد.
2. اگر می‌خواهید حتماً به‌عنوان فرزند WoodMart کار کند:
   - WoodMart را از **نمایش > پوسته‌ها > افزودن پوسته** نصب کنید، سپس یک‌بار صفحه را تازه‌سازی کنید؛ یا
   - روی دکمهٔ «تبدیل به حالت فرزند WoodMart» در نوتیس مدیریتی کلیک کنید (لینک‌ها nonce-safe هستند).
3. تعویض دستی حالت (برای ادمین):
   - `wp-admin/themes.php?mc-mode=child&mc-nonce=…`
   - `wp-admin/themes.php?mc-mode=standalone&mc-nonce=…`
   (دکمه‌های آماده داخل نوتیس «موبایل چی» در پیشخوان وجود دارد.)

## بخش‌های موجود در حالت مستقل

- `header/footer` با منوی اصلی، نوار بالا و فوتر چندستونه
- `index/page/singular/single/archive` برای نوشته‌ها و برگه‌ها
- `search.php` با فرم جستجو و صفحه‌بندی
- `archive-product.php` و `taxonomy-product_cat.php` برای ووکامرس (گرید ۴ستونه + دسته‌بندی‌ها)
- ویجت‌های Elementor (`inc/elementor-widgets.php`) و بلوک‌های فوتر (`inc/footer-widgets.php`)
- تنظیمات Customizer: رنگ اصلی، ارقام فارسی قیمت‌ها، اجرای مستقل

## ساخت بسته نصبی

```bash
cd /workspace && rm -f mobile-chi.zip && zip -r mobile-chi.zip mobile-chi -x "*.git*"
```

