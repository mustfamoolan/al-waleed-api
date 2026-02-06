# دليل إعداد ملفات البيئة (.env)

هذا الدليل يشرح كيفية إعداد ملفات البيئة للعمل محلياً وعلى السيرفر.

---

## 📋 الفرق بين البيئات

### 1. البيئة المحلية (Local - بدون Docker)

- MySQL موجود على `localhost` أو `127.0.0.1`
- Username: `root`
- Password: فارغ (بدون كلمة مرور)
- تستخدم Laravel مباشرة بدون Docker

### 2. البيئة مع Docker (Local أو Server)

- MySQL موجود في حاوية Docker
- DB_HOST: `mysql` (اسم الخدمة في docker-compose.yml)
- تحتاج إلى username و password
- كل شيء يعمل داخل Docker

---

## 🏠 الإعداد للبيئة المحلية (بدون Docker)

### الخطوة 1: إنشاء ملف `.env`

```bash
# نسخ ملف المثال
cp .env.local.example .env
```

### الخطوة 2: تعديل ملف `.env`

افتح ملف `.env` وعدّل إعدادات قاعدة البيانات:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=al_waleed_db
DB_USERNAME=root
DB_PASSWORD=
```

**ملاحظات:**
- `DB_HOST=127.0.0.1` أو `localhost` - لأن MySQL على جهازك
- `DB_USERNAME=root` - المستخدم الافتراضي
- `DB_PASSWORD=` - فارغ (بدون كلمة مرور)

### الخطوة 3: إنشاء قاعدة البيانات

```bash
# الدخول إلى MySQL
mysql -u root

# إنشاء قاعدة البيانات
CREATE DATABASE al_waleed_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# الخروج
EXIT;
```

### الخطوة 4: تشغيل المشروع

```bash
# تثبيت التبعيات
composer install
npm install

# توليد مفتاح التطبيق
php artisan key:generate

# تشغيل Migrations
php artisan migrate

# تشغيل السيرفر
php artisan serve
```

---

## 🐳 الإعداد مع Docker (محلي أو سيرفر)

### الخطوة 1: إنشاء ملف `.env`

```bash
# نسخ ملف المثال
cp env.docker.example .env
```

### الخطوة 2: تعديل ملف `.env`

افتح ملف `.env` وعدّل إعدادات قاعدة البيانات:

```env
# إعدادات قاعدة البيانات
DB_CONNECTION=mysql
DB_HOST=mysql          # مهم: هذا اسم الخدمة في docker-compose.yml
DB_PORT=3306
DB_DATABASE=al_waleed_db
DB_USERNAME=al_waleed_user
DB_PASSWORD=your_strong_password_here

# كلمة مرور root (لإدارة قاعدة البيانات)
DB_ROOT_PASSWORD=your_root_password_here
```

**ملاحظات مهمة:**
- `DB_HOST=mysql` - **يجب أن يكون "mysql"** لأن هذا اسم الخدمة في docker-compose.yml
- في Docker، الحاويات تتواصل مع بعضها عبر أسماء الخدمات
- يمكنك تغيير `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` حسب رغبتك

### الخطوة 3: تعديل docker-compose.yml (اختياري)

إذا أردت تغيير إعدادات قاعدة البيانات، يمكنك تعديل `docker-compose.yml`:

```yaml
services:
  mysql:
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-al_waleed_db}
      MYSQL_USER: ${DB_USERNAME:-al_waleed_user}
      MYSQL_PASSWORD: ${DB_PASSWORD:-al_waleed_password}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root_password}
```

أو يمكنك تعيين القيم مباشرة في ملف `.env`.

### الخطوة 4: تشغيل Docker

```bash
# بناء وتشغيل الحاويات
docker compose up -d

# عرض السجلات
docker compose logs -f

# تشغيل Migrations
docker compose exec app php artisan migrate
```

---

## 🖥️ الإعداد على السيرفر (VPS)

### الخيار 1: استخدام Docker (مُوصى به)

اتبع نفس خطوات "الإعداد مع Docker" أعلاه.

**ملاحظة:** على السيرفر، قد تحتاج إلى:
- تغيير `APP_URL` إلى domain name الخاص بك
- استخدام كلمات مرور قوية
- تعيين `APP_ENV=production` و `APP_DEBUG=false`

### الخيار 2: بدون Docker (MySQL منفصل)

إذا كان لديك MySQL مثبت على السيرفر (خارج Docker):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1      # أو IP السيرفر
DB_PORT=3306
DB_DATABASE=al_waleed_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 🔄 التبديل بين البيئات

### من Local (بدون Docker) إلى Docker:

1. أوقف Laravel: `Ctrl+C`
2. أنشئ `.env` من `env.docker.example`
3. عدّل `DB_HOST=mysql`
4. شغّل: `docker compose up -d`

### من Docker إلى Local (بدون Docker):

1. أوقف Docker: `docker compose down`
2. أنشئ `.env` من `.env.local.example`
3. عدّل `DB_HOST=127.0.0.1` و `DB_USERNAME=root` و `DB_PASSWORD=`
4. شغّل: `php artisan serve`

---

## ✅ التحقق من الإعدادات

### اختبار الاتصال بقاعدة البيانات (بدون Docker):

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

### اختبار الاتصال (مع Docker):

```bash
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### عرض إعدادات قاعدة البيانات:

```bash
# بدون Docker
php artisan config:show database

# مع Docker
docker compose exec app php artisan config:show database
```

---

## 🛠️ استكشاف الأخطاء

### المشكلة: "SQLSTATE[HY000] [2002] Connection refused"

**الحل:**
- تأكد من أن MySQL يعمل
- تحقق من `DB_HOST` (يجب أن يكون `mysql` في Docker أو `127.0.0.1` بدون Docker)
- تحقق من `DB_PORT` (يجب أن يكون `3306`)

### المشكلة: "Access denied for user"

**الحل:**
- تحقق من `DB_USERNAME` و `DB_PASSWORD`
- في Docker، تأكد من تطابق الإعدادات في `.env` و `docker-compose.yml`

### المشكلة: "Unknown database"

**الحل:**
- أنشئ قاعدة البيانات أولاً
- في Docker، قاعدة البيانات تُنشأ تلقائياً، لكن تأكد من تطابق الاسم

---

## 📝 ملخص الإعدادات

| البيئة | DB_HOST | DB_USERNAME | DB_PASSWORD | ملف المثال |
|--------|---------|-------------|-------------|------------|
| Local (بدون Docker) | `127.0.0.1` | `root` | فارغ | `.env.local.example` |
| Docker (محلي/سيرفر) | `mysql` | `al_waleed_user` | كلمة مرور | `env.docker.example` |
| سيرفر (MySQL منفصل) | `127.0.0.1` | حسب الإعداد | كلمة مرور | `env.docker.example` |

---

**نصيحة:** استخدم `.env.local.example` للعمل محلياً بدون Docker، و `env.docker.example` عند استخدام Docker.

