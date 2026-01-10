# خطوات تشغيل deploy.sh على VPS - دليل سريع

## 📋 الخطوات الأساسية

### 1️⃣ اتصل بالـ VPS
```bash
ssh root@your-server-ip
# أو إذا كنت تستخدم مستخدم آخر
ssh username@your-server-ip
```

### 2️⃣ تأكد من تثبيت Docker و Git
```bash
# تحقق من Docker
docker --version
docker compose version

# تحقق من Git
git --version

# إذا لم يكن مثبتاً، راجع ملف DEPLOYMENT.md
```

### 3️⃣ استنسخ المشروع (إذا لم يكن موجوداً)
```bash
# انتقل لمجلد الويب
cd /var/www

# استنسخ المشروع
git clone https://github.com/your-username/al-waleed-api.git

# انتقل للمجلد
cd al-waleed-api
```

### 4️⃣ أعد إعداد ملف .env
```bash
# انسخ ملف البيئة
cp env.docker.example .env

# عدّله بالإعدادات المناسبة
nano .env
```

**تأكد من:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST=mysql` (في حالة Docker)
- إضافة كلمات مرور قوية

### 5️⃣ شغّل سكريبت الـ Deployment
```bash
# اجعل السكريبت قابل للتنفيذ وشغّله
chmod +x deploy.sh && ./deploy.sh
```

أو مع `sudo`:
```bash
chmod +x deploy.sh && sudo ./deploy.sh
```

---

## 🔄 التحديثات المستقبلية

بعد الإعداد الأول، للتحديث فقط شغّل:
```bash
cd /var/www/al-waleed-api
./deploy.sh
```

---

## 🎯 ماذا يفعل السكريبت؟

السكريبت يقوم بالتالي تلقائياً:
1. ✅ سحب آخر تحديثات من Git
2. 🐳 إعادة بناء Docker images
3. 🚀 تشغيل الحاويات
4. 📦 تشغيل Migrations
5. 🌱 تشغيل Seeders
6. 🧹 مسح الكاش
7. ✨ تحسين الأداء

---

## ⚠️ حل المشاكل الشائعة

### المشكلة: "Permission denied"
```bash
# استخدم sudo
sudo chmod +x deploy.sh && sudo ./deploy.sh
```

### المشكلة: "docker: command not found"
```bash
# ثبّت Docker أولاً (راجع DEPLOYMENT.md)
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
```

### المشكلة: "No such file or directory"
```bash
# تأكد أنك في المجلد الصحيح
cd /var/www/al-waleed-api
pwd
ls -la deploy.sh
```

### المشكلة: البورت 8000 مستخدم
```bash
# أوقف العملية على البورت
sudo lsof -ti:8000 | xargs kill -9

# أو عدّل البورت في docker-compose.yml
```

---

## 🔐 ملاحظات أمنية

1. **لا تشارك ملف .env** - يحتوي على معلومات حساسة
2. **استخدم كلمات مرور قوية** للـ Database
3. **فعّل Firewall** (راجع DEPLOYMENT.md)
4. **استخدم HTTPS** في الإنتاج مع Let's Encrypt

---

## 📱 التواصل بعد الـ Deployment

بعد التشغيل بنجاح، التطبيق سيعمل على:
```
http://your-server-ip:8000
```

للتحقق:
```bash
curl http://localhost:8000
```

---

## 📊 مراقبة التطبيق

```bash
# عرض حالة الحاويات
docker compose ps

# عرض السجلات
docker compose logs -f app

# عرض استهلاك الموارد
docker stats
```

---

## 🛑 إيقاف التطبيق

```bash
cd /var/www/al-waleed-api
docker compose stop
```

## 🔄 إعادة التشغيل

```bash
cd /var/www/al-waleed-api
docker compose restart
```

---

**تم! 🎉** التطبيق الآن يعمل على VPS

