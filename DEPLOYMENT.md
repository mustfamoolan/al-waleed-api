# دليل النشر على VPS Hostinger

هذا الدليل يشرح كيفية نشر مشروع Al-Waleed API على VPS من Hostinger باستخدام Docker.

## المتطلبات الأساسية

- VPS من Hostinger (Ubuntu 20.04 أو أحدث)
- وصول SSH إلى السيرفر
- معرفة أساسية بـ Linux commands
- Domain name (اختياري)

---

## الخطوة 1: الاتصال بـ VPS

اتصل بالسيرفر عبر SSH:

```bash
ssh root@your-server-ip
# أو
ssh username@your-server-ip
```

---

## الخطوة 2: تحديث النظام

```bash
# تحديث قائمة الحزم
sudo apt update

# ترقية النظام
sudo apt upgrade -y

# إعادة التشغيل (إذا لزم الأمر)
sudo reboot
```

---

## الخطوة 3: تثبيت Docker

### تثبيت Docker Engine

```bash
# إزالة أي إصدارات قديمة
sudo apt remove docker docker-engine docker.io containerd runc

# تثبيت المتطلبات
sudo apt install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# إضافة Docker's official GPG key
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# إضافة Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# تثبيت Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# التحقق من التثبيت
sudo docker --version
sudo docker compose version
```

### إضافة المستخدم الحالي إلى مجموعة docker (اختياري)

```bash
sudo usermod -aG docker $USER
# تسجيل الخروج والدخول مرة أخرى لتطبيق التغييرات
```

---

## الخطوة 4: رفع المشروع إلى VPS

### الطريقة 1: استخدام Git (مُوصى بها)

```bash
# تثبيت Git (إذا لم يكن مثبتاً)
sudo apt install -y git

# الانتقال إلى مجلد الويب
cd /var/www

# استنساخ المشروع
git clone https://github.com/your-username/al-waleed-api.git
# أو إذا كان المشروع خاص، استخدم SSH
# git clone git@github.com:your-username/al-waleed-api.git

cd al-waleed-api
```

### الطريقة 2: استخدام SCP (من جهازك المحلي)

```bash
# من جهازك المحلي
scp -r /path/to/al-waleed-api root@your-server-ip:/var/www/
```

### الطريقة 3: استخدام FTP/SFTP

استخدم FileZilla أو أي عميل FTP لرفع الملفات إلى `/var/www/al-waleed-api`

---

## الخطوة 5: إعداد ملف البيئة (.env)

```bash
cd /var/www/al-waleed-api

# نسخ ملف الإعدادات
cp env.docker.example .env

# أو إنشاء ملف .env جديد
nano .env
```

### تعديل ملف .env:

```env
APP_NAME="Al-Waleed API"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://your-domain.com
# أو http://your-server-ip:8000

# Database Configuration
# مهم: في Docker، DB_HOST يجب أن يكون "mysql" (اسم الخدمة في docker-compose.yml)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=al_waleed_db
DB_USERNAME=al_waleed_user
DB_PASSWORD=your_strong_password_here

# تغيير كلمة مرور MySQL root
DB_ROOT_PASSWORD=your_root_password_here
```

**مهم**: 
- استخدم كلمات مرور قوية!
- `DB_HOST=mysql` - هذا اسم الخدمة في docker-compose.yml، لا تغيره إلا إذا غيرت اسم الخدمة
- في Docker، الحاويات تتواصل مع بعضها عبر أسماء الخدمات، لذا `mysql` يشير إلى حاوية MySQL

### إذا كنت تستخدم MySQL خارج Docker:

إذا كان لديك MySQL مثبت على السيرفر (خارج Docker)، غيّر الإعدادات:

```env
DB_HOST=127.0.0.1
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

ثم علّق أو احذف خدمة MySQL من `docker-compose.yml`.

---

## الخطوة 6: بناء وتشغيل الحاويات

```bash
# بناء الصور
sudo docker compose build

# تشغيل الحاويات
sudo docker compose up -d

# عرض حالة الحاويات
sudo docker compose ps

# عرض السجلات
sudo docker compose logs -f
```

---

## الخطوة 7: إعدادات الأمان

### إعداد Firewall (UFW)

```bash
# تثبيت UFW (إذا لم يكن مثبتاً)
sudo apt install -y ufw

# السماح بـ SSH
sudo ufw allow 22/tcp

# السماح بـ HTTP
sudo ufw allow 80/tcp

# السماح بـ HTTPS
sudo ufw allow 443/tcp

# السماح بـ منفذ التطبيق (إذا كنت تستخدم منفذ مخصص)
sudo ufw allow 8000/tcp

# تفعيل Firewall
sudo ufw enable

# عرض حالة Firewall
sudo ufw status
```

### إعدادات إضافية للأمان

```bash
# تعطيل تسجيل الدخول كـ root (اختياري)
# قم بإنشاء مستخدم جديد أولاً
sudo adduser deploy
sudo usermod -aG sudo deploy
sudo usermod -aG docker deploy

# ثم تعطيل root login
sudo nano /etc/ssh/sshd_config
# غيّر: PermitRootLogin no
sudo systemctl restart sshd
```

---

## الخطوة 8: إعداد Domain Name (اختياري)

### ربط Domain بـ VPS

1. اذهب إلى إعدادات DNS في مزود Domain
2. أضف A Record يشير إلى IP السيرفر:
   ```
   Type: A
   Name: @ (أو api)
   Value: your-server-ip
   TTL: 3600
   ```

### إعداد Nginx كـ Reverse Proxy (مُوصى به للإنتاج)

```bash
# تثبيت Nginx
sudo apt install -y nginx

# إنشاء ملف إعدادات
sudo nano /etc/nginx/sites-available/al-waleed-api
```

أضف المحتوى التالي:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# تفعيل الموقع
sudo ln -s /etc/nginx/sites-available/al-waleed-api /etc/nginx/sites-enabled/

# اختبار الإعدادات
sudo nginx -t

# إعادة تشغيل Nginx
sudo systemctl restart nginx
```

### إعداد SSL مع Let's Encrypt

```bash
# تثبيت Certbot
sudo apt install -y certbot python3-certbot-nginx

# الحصول على شهادة SSL
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Certbot سيقوم بتحديث إعدادات Nginx تلقائياً
```

---

## الخطوة 9: إدارة المشروع

### أوامر مفيدة

```bash
# عرض الحاويات النشطة
sudo docker compose ps

# عرض السجلات
sudo docker compose logs -f app
sudo docker compose logs -f mysql

# إيقاف الحاويات
sudo docker compose stop

# بدء الحاويات
sudo docker compose start

# إعادة تشغيل الحاويات
sudo docker compose restart

# إيقاف وحذف الحاويات
sudo docker compose down

# إعادة بناء الحاويات بعد تحديث الكود
sudo docker compose up -d --build

# تنفيذ أوامر داخل حاوية Laravel
sudo docker compose exec app php artisan migrate
sudo docker compose exec app php artisan cache:clear
sudo docker compose exec app composer install
```

### تحديث المشروع

```bash
cd /var/www/al-waleed-api

# سحب التحديثات من Git
git pull origin main

# إعادة بناء الحاويات
sudo docker compose up -d --build

# تشغيل Migrations الجديدة
sudo docker compose exec app php artisan migrate

# مسح الكاش
sudo docker compose exec app php artisan config:clear
sudo docker compose exec app php artisan cache:clear
```

---

## الخطوة 10: النسخ الاحتياطي

### نسخ احتياطي لقاعدة البيانات

```bash
# إنشاء مجلد للنسخ الاحتياطي
mkdir -p /var/backups/al-waleed-api

# نسخ احتياطي لقاعدة البيانات
sudo docker compose exec mysql mysqldump -u root -p${DB_ROOT_PASSWORD} al_waleed_db > /var/backups/al-waleed-api/db_$(date +%Y%m%d_%H%M%S).sql

# ضغط النسخة الاحتياطية
gzip /var/backups/al-waleed-api/db_*.sql
```

### سكريبت نسخ احتياطي تلقائي

أنشئ ملف `/var/www/al-waleed-api/backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/al-waleed-api"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="al_waleed_db"
DB_ROOT_PASSWORD="your_root_password"

mkdir -p $BACKUP_DIR

# نسخ احتياطي لقاعدة البيانات
docker compose exec -T mysql mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} | gzip > ${BACKUP_DIR}/db_${DATE}.sql.gz

# حذف النسخ الاحتياطية الأقدم من 7 أيام
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_${DATE}.sql.gz"
```

```bash
# جعل السكريبت قابل للتنفيذ
chmod +x /var/www/al-waleed-api/backup.sh

# إضافة إلى cron (نسخ احتياطي يومي في الساعة 2 صباحاً)
crontab -e
# أضف السطر التالي:
0 2 * * * /var/www/al-waleed-api/backup.sh
```

---

## استكشاف الأخطاء

### المشكلة: الحاويات لا تبدأ

```bash
# فحص السجلات
sudo docker compose logs

# فحص حالة الحاويات
sudo docker compose ps -a

# إعادة بناء من الصفر
sudo docker compose down -v
sudo docker compose up -d --build
```

### المشكلة: خطأ في الاتصال بقاعدة البيانات

```bash
# فحص حالة MySQL
sudo docker compose exec mysql mysqladmin ping -h localhost -u root -p

# فحص السجلات
sudo docker compose logs mysql

# إعادة تشغيل MySQL
sudo docker compose restart mysql
```

### المشكلة: مشاكل في الصلاحيات

```bash
# إصلاح صلاحيات storage
sudo docker compose exec app chmod -R 775 storage bootstrap/cache
sudo docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### المشكلة: التطبيق بطيء

```bash
# مسح الكاش
sudo docker compose exec app php artisan config:clear
sudo docker compose exec app php artisan cache:clear
sudo docker compose exec app php artisan route:clear
sudo docker compose exec app php artisan view:clear

# إعادة بناء الكاش
sudo docker compose exec app php artisan config:cache
sudo docker compose exec app php artisan route:cache
sudo docker compose exec app php artisan view:cache
```

---

## إضافة خدمات جديدة (Node.js مثال)

لإضافة Node.js أو أي خدمة أخرى، عدّل `docker-compose.yml`:

```yaml
services:
  # ... الخدمات الموجودة ...
  
  node:
    image: node:20
    container_name: al-waleed-node
    working_dir: /app
    volumes:
      - ./:/app
    command: npm run dev
    networks:
      - al-waleed-network
```

ثم:

```bash
sudo docker compose up -d node
```

---

## المراجع المفيدة

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Documentation](https://laravel.com/docs)
- [Hostinger VPS Documentation](https://www.hostinger.com/tutorials/vps)

---

## الدعم

إذا واجهت أي مشاكل، تحقق من:
1. سجلات Docker: `sudo docker compose logs`
2. سجلات Laravel: `storage/logs/laravel.log`
3. سجلات Apache: داخل الحاوية `docker compose exec app tail -f /var/log/apache2/error.log`

---

**تم إنشاء هذا الدليل بواسطة فريق تطوير Al-Waleed API**

