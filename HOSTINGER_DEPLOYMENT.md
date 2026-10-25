# نشر KFS Runners على Hostinger Shared Hosting

المشروع أصبح تطبيق Laravel واحدًا بصفحات Blade؛ لا تحتاج إلى موقع React أو خادم Node منفصل. أنشئ موقعًا مستقلًا على نطاق فرعي جديد مثل `runners.elatiky.com` حتى لا تستبدل ملفات `elatiky.com` الحالية.

## 1) جهّز موقع Hostinger

1. من **Websites → Add website / Create website** أنشئ موقعًا مستقلًا للنطاق الفرعي الذي اخترته، مثل `runners.elatiky.com`.
2. أنشئ قاعدة MySQL من hPanel وسجّل اسم القاعدة والمستخدم وكلمة المرور.
3. اختر PHP 8.3 أو أحدث، ثم فعّل SSL للنطاق الفرعي.
4. لا ترفع أي شيء إلى `public_html` الخاص بالموقع الأساسي.

## 2) أنشئ ملف الرفع

من PowerShell داخل جذر المشروع:

```powershell
.\scripts\package-hostinger-laravel.ps1
```

ينشئ السكربت `kfs-runners-hostinger-laravel.zip` ببنية آمنة:

```text
kfs-app/       ملفات Laravel ومجلدات storage، من دون .env أو vendor
public_html/   الملفات العامة وindex.php المعدل ليشغّل ../kfs-app
```

ارفع الملف إلى مجلد النطاق الفرعي، ثم فك الضغط. تأكد أن `kfs-app` و`public_html` بجوار بعضهما، وأن `public_html` هو جذر الموقع للنطاق الفرعي.

## 3) ثبّت Laravel على الخادم

من SSH في Hostinger:

```bash
cd ~/domains/runners.elatiky.com/kfs-app
composer2 install --no-dev --optimize-autoloader
```

أنشئ الملف `kfs-app/.env` على الخادم. ابدأ من `kfs-app/.env.example`، ثم اضبط القيم التالية:

```env
APP_NAME="KFS Runners"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://runners.elatiky.com
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=HOSTINGER_DATABASE_NAME
DB_USERNAME=HOSTINGER_DATABASE_USER
DB_PASSWORD=HOSTINGER_DATABASE_PASSWORD

KFS_ADMIN_NAME="KFS Administrator"
KFS_ADMIN_EMAIL=ADMIN_EMAIL
KFS_ADMIN_PASSWORD=LONG_UNIQUE_ADMIN_PASSWORD

EASYKASH_PAY_API_URL=https://back.easykash.net/api/directpayv1/pay
EASYKASH_REDIRECT_URL=https://runners.elatiky.com/register
EASYKASH_API_KEY=ROTATED_EASYKASH_API_KEY
EASYKASH_HMAC_SECRET=ROTATED_EASYKASH_HMAC_SECRET
EASYKASH_CASH_EXPIRY=48
```

استخدم بيانات MySQL التي أنشأتها في hPanel. لا تضع `.env` في `public_html` ولا ترفع مفاتيح EasyKash إلى GitHub. مفاتيح EasyKash التي أُرسلت سابقًا في المحادثة يجب تدويرها قبل النشر.

أنشئ مجلدات الكتابة وشغّل إعداد Laravel لأول مرة:

```bash
mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan key:generate
php artisan migrate --seed --force
php artisan config:cache
```

`--seed` ينشئ المستخدم الإداري وسباقات البداية. لا تعِد تشغيل seed على قاعدة الإنتاج إلا إذا كنت تريد تطبيق بيانات البداية مرة أخرى. تأكد أن `storage/` و`bootstrap/cache/` قابلان للكتابة من PHP.

## 4) إعداد EasyKash والتحقق

في إعدادات EasyKash، اضبط Callback URL على:

```text
https://runners.elatiky.com/api/payments/easykash/callback
```

يعود المتسابق إلى صفحة `/register` بعد الدفع. لا تعتبر إعادة التوجيه نجاحًا للدفع؛ Laravel يغيّر حالة الدفع فقط بعد التحقق من callback الموقّع.

تحقق بعد النشر من:

- `https://runners.elatiky.com/` — الموقع العام.
- `https://runners.elatiky.com/register` — نموذج التسجيل.
- `https://runners.elatiky.com/admin/login` — لوحة الإدارة.
- `https://runners.elatiky.com/up` — فحص Laravel.
- `https://runners.elatiky.com/api/races` — السباقات والأسعار النشطة.

## ملاحظات

- لا يحتاج الموقع الحي إلى Node.js؛ ملفات CSS وExcelJS موجودة ضمن ملفات Laravel العامة.
- بعض خطط Hostinger تثبّت جذر الموقع على `public_html`. هذا الإعداد يضع كود Laravel خارج مجلد الويب ويعرض فقط محتويات `public/`.
- نسخة PHP في SSH قد تختلف عن نسخة PHP المحددة للموقع. تأكد من استخدام PHP 8.3+ للطرفية وhPanel.
