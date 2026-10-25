# نشر KFS Runners على Hostinger

المشروع يتكون من واجهة React/Vite وواجهة Laravel API. في باقة Web/Shared على حساب `elatiky.com`، انشر الواجهة على `runners.elatiky.com` والـ API على `api-runners.elatiky.com`. بهذه الطريقة يظل الموقع الأساسي كما هو.

## قبل النشر

- اربط الدومين بباقة Hostinger وأنشئ قاعدة MySQL.
- اضبط PHP للموقع على 8.3 أو أحدث؛ `backend/composer.json` يتطلب PHP 8.3.
- أنشئ `runners.elatiky.com` و`api-runners.elatiky.com` كـ **موقعين مستقلين** من Websites → Create website، وليس كمجلدين داخل WordPress. خطة Business الحالية تعرض 6 مواقع من أصل 100.
- أسماء النطاقات الفرعية تختارها هذه التعليمات كافتراضي؛ إذا اخترت أسماء أخرى، حدّث رابط API و`APP_URL` و`FRONTEND_URL` وcallback قبل البناء.
- فعّل SSL لكلا النطاقين الفرعيين.
- لا ترفع ملف `.env` الموجود على جهاز التطوير، ولا تضع مفاتيح EasyKash في React أو GitHub.

## بناء واجهة React

من PowerShell في مجلد المشروع:

```powershell
.\scripts\build-hostinger-frontend.ps1 -ApiBaseUrl "https://api-runners.elatiky.com"
```

السكربت يستخدم إصدارات `package-lock.json`، ويبني React على `dist/` مع عنوان API، ويضيف إعداد إعادة توجيه مسارات React إلى `index.html`. إذا لم تكن مكتبات الواجهة مثبتة، قد تحتاج لإيقاف خادم Vite المحلي قبل تثبيتها.

ارفع محتويات ملف `kfs-runners-hostinger-frontend.zip` إلى `public_html` للنطاق `runners.elatiky.com`. لا ترفع مجلد المشروع كاملًا إلى `public_html`. عند استخدام تطبيق Node.js في خطة Hostinger تدعمه، يمكن ربط GitHub واستخدام:

- Build command: `npm run build`
- Output directory: `dist`
- Build-time variable: `VITE_API_BASE_URL=https://api-runners.elatiky.com`

في أي طريقة بناء، قيمة `VITE_API_BASE_URL` هي أصل API فقط، من دون `/api` في نهايتها.

## نشر Laravel API بأمان

استخدم مسارين منفصلين للنطاق الفرعي `api-runners.elatiky.com`:

```text
domains/api-runners.elatiky.com/kfs-api/          Laravel app, .env, vendor, storage
domains/api-runners.elatiky.com/public_html/      Laravel public files and index.php
```

ارفع محتويات مجلد `backend/` إلى `kfs-api/` من خلال **Access all files** أو SSH. لا ترفع `.env` المحلي. ثبّت الاعتماديات على Hostinger من SSH:

```bash
cd ~/domains/api-runners.elatiky.com/kfs-api
composer2 install --no-dev --optimize-autoloader
```

قبل تشغيل Artisan, أنشئ مجلدات التخزين المؤقت والسجلات إذا لم تكن موجودة بعد الرفع:

```bash
mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

ملف `kfs-runners-hostinger-api.zip` يحتوي مجلدي `kfs-api/` و`public_html/`. ارفعه إلى مجلد النطاق `api-runners.elatiky.com` وفكّه هناك. يحتوي `public_html/index.php` على مسار Laravel المعدل لقراءة التطبيق من `../kfs-api`.

تأكد أن مجلد التطبيق اسمه `kfs-api`. بهذه البنية تبقى ملفات `.env` و`vendor` و`storage` خارج `public_html`.

## إعداد Laravel على الخادم

أنشئ `kfs-api/.env` على Hostinger، واضبط على الأقل:

```env
APP_NAME="KFS Runners API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api-runners.elatiky.com
FRONTEND_URL=https://runners.elatiky.com
CORS_ALLOWED_ORIGINS=https://runners.elatiky.com
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

ولّد `APP_KEY` على الخادم، ثم نفّذ إعداد قاعدة البيانات لأول مرة فقط:

```bash
cd ~/domains/api-runners.elatiky.com/kfs-api
php artisan key:generate
php artisan migrate --seed --force
php artisan config:cache
```

تحقق من نسخة PHP في SSH أيضًا؛ يجب أن تكون 8.3 أو أحدث. لا تضع التطبيق كاملًا داخل `public_html` حتى لا تصبح ملفات Laravel أو `.env` قابلة للوصول عبر الويب.

`--seed` ينشئ حساب المدير وسباقات البداية. لا تعِد تشغيل seeder على قاعدة بيانات حية إذا كنت لا تريد إعادة أسعار السباقات الافتراضية. أعطِ PHP صلاحية الكتابة إلى `storage/` و`bootstrap/cache/`.

في إعدادات EasyKash، اضبط callback على:

```text
https://api-runners.elatiky.com/api/payments/easykash/callback
```

الواجهة ترجع بعد الدفع إلى `https://runners.elatiky.com/register`. لا تعتبر إعادة التوجيه تأكيد دفع؛ التأكيد يحصل عبر callback موقّع فقط.

## فحص النشر

- افتح `https://api-runners.elatiky.com/up`؛ Laravel يجب أن يرجع استجابة الصحة.
- افتح `https://api-runners.elatiky.com/api/races`؛ يجب أن تظهر فئات السباق والأسعار النشطة.
- افتح `https://runners.elatiky.com/register` و`/admin/login` مباشرة للتأكد من عمل إعادة توجيه SPA.
- سجّل الدخول إلى لوحة الإدارة بحساب المدير الذي ضبطته في `.env`.
- استخدم EasyKash في الإنتاج فقط بعد مراجعة مفاتيح الحساب ورابط callback وHTTPS.

## ملاحظات Hostinger

- بناء React محليًا يعني أن الخادم لا يحتاج Node.js لتشغيل الواجهة الثابتة.
- بعض باقات Hostinger توفر نشر Node.js من GitHub. هذا خيار آخر لبناء React، لكنه لا يغني عن نشر Laravel API وقاعدة MySQL.
- إذا لم يعمل Composer بسبب اختلاف نسخة PHP في SSH، استخدم `composer2` وراجع نسخة PHP الخاصة بسطر الأوامر. إعداد PHP للموقع في hPanel قد يختلف عن نسخة PHP في SSH.
- في بعض خطط Web/Cloud لا يمكن تغيير جذر الموقع عن `public_html`؛ لذلك نبقي تطبيق Laravel خارجه ونضع ملفات `public/` فقط داخل جذر نطاق API.
- بيانات EasyKash التي أُرسلت في المحادثة السابقة يجب تدويرها قبل النشر الحي، ثم تحفظ المفاتيح الجديدة في `.env` على الخادم فقط.
