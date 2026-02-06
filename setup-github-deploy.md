# إعداد النشر التلقائي من GitHub إلى VPS

هذا الدليل يشرح كيفية إعداد النشر التلقائي (CI/CD) من GitHub إلى VPS الخاص بك.

---

## 📋 المتطلبات

- VPS مع Docker مثبت
- Git repository على GitHub
- وصول SSH إلى VPS
- معرفة أساسية بـ GitHub Actions

---

## 🔑 الخطوة 1: إنشاء SSH Key على VPS

اتصل بـ VPS وأنشئ SSH key جديد للـ deployment:

```bash
# الاتصال بـ VPS
ssh root@your-server-ip

# إنشاء SSH key جديد
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy_key -N ""

# عرض المفتاح العام (سنحتاجه لاحقاً)
cat ~/.ssh/github_deploy_key.pub

# إضافة المفتاح العام إلى authorized_keys
cat ~/.ssh/github_deploy_key.pub >> ~/.ssh/authorized_keys
```

**احفظ المفتاح الخاص** - سنحتاجه في GitHub Secrets:

```bash
cat ~/.ssh/github_deploy_key
```

انسخ المحتوى بالكامل (يبدأ بـ `-----BEGIN OPENSSH PRIVATE KEY-----`)

---

## 🔐 الخطوة 2: إعداد GitHub Secrets

1. اذهب إلى GitHub repository
2. اضغط على **Settings** → **Secrets and variables** → **Actions**
3. اضغط على **New repository secret**

أضف الأسرار التالية:

### 1. VPS_SSH_KEY
- **Name:** `VPS_SSH_KEY`
- **Value:** المفتاح الخاص الذي نسخته من VPS (المحتوى الكامل)

### 2. VPS_HOST
- **Name:** `VPS_HOST`
- **Value:** IP address أو domain name لـ VPS
  - مثال: `123.45.67.89` أو `api.yourdomain.com`

### 3. VPS_USER
- **Name:** `VPS_USER`
- **Value:** اسم المستخدم للاتصال بـ VPS
  - مثال: `root` أو `deploy`

### 4. VPS_PORT (اختياري)
- **Name:** `VPS_PORT`
- **Value:** منفذ SSH (افتراضي: `22`)
  - إذا كان SSH على منفذ مختلف، غيّره هنا

### 5. DEPLOY_PATH (اختياري)
- **Name:** `DEPLOY_PATH`
- **Value:** مسار المشروع على VPS
  - افتراضي: `/var/www/al-waleed-api`
  - غيّره إذا كان المسار مختلف

---

## 📁 الخطوة 3: إعداد المشروع على VPS

### 3.1: رفع المشروع إلى VPS

```bash
# الاتصال بـ VPS
ssh root@your-server-ip

# الانتقال إلى مجلد الويب
cd /var/www

# استنساخ المشروع من GitHub
git clone https://github.com/your-username/al-waleed-api.git
# أو إذا كان المشروع خاص:
# git clone git@github.com:your-username/al-waleed-api.git

cd al-waleed-api
```

### 3.2: إعداد ملف .env

```bash
# نسخ ملف الإعدادات
cp env.docker.example .env

# تعديل ملف .env
nano .env
```

عدّل الإعدادات المطلوبة:
- `APP_URL` - domain name أو IP
- `DB_PASSWORD` - كلمة مرور قوية
- `DB_ROOT_PASSWORD` - كلمة مرور root قوية

### 3.3: جعل deploy.sh قابل للتنفيذ

```bash
chmod +x deploy.sh
```

---

## 🚀 الخطوة 4: اختبار النشر

### اختبار يدوي:

```bash
# على VPS
cd /var/www/al-waleed-api
./deploy.sh
```

### اختبار من GitHub:

1. اذهب إلى GitHub repository
2. اضغط على **Actions** tab
3. اضغط على **Deploy to VPS** workflow
4. اضغط على **Run workflow** → **Run workflow**

أو ببساطة:
- اعمل push إلى branch `main` أو `master`
- سيتم النشر تلقائياً!

---

## 🔄 الخطوة 5: إعداد Git على VPS (للمرة الأولى)

إذا كان المشروع جديد على VPS، قم بإعداد Git:

```bash
cd /var/www/al-waleed-api

# إعداد Git (إذا لم يكن موجوداً)
git config --global user.name "Deploy Bot"
git config --global user.email "deploy@yourdomain.com"

# التأكد من أن branch صحيح
git checkout main
# أو
git checkout master
```

---

## 📝 الخطوة 6: إعداد Branch Protection (اختياري)

لحماية branch الرئيسي:

1. اذهب إلى **Settings** → **Branches**
2. اضغط على **Add rule**
3. اختر branch `main` أو `master`
4. فعّل **Require pull request reviews** (اختياري)

---

## 🛠️ استكشاف الأخطاء

### المشكلة: "Permission denied (publickey)"

**الحل:**
- تأكد من أن `VPS_SSH_KEY` في GitHub Secrets صحيح
- تأكد من أن المفتاح العام موجود في `~/.ssh/authorized_keys` على VPS

### المشكلة: "Host key verification failed"

**الحل:**
- GitHub Actions تضيف host key تلقائياً
- إذا استمرت المشكلة، أضف host key يدوياً في workflow

### المشكلة: "Git pull failed"

**الحل:**
```bash
# على VPS
cd /var/www/al-waleed-api
git config --global --add safe.directory /var/www/al-waleed-api
```

### المشكلة: "Docker compose command not found"

**الحل:**
- تأكد من تثبيت Docker Compose على VPS
- استخدم `docker compose` (بدون شرطة) بدلاً من `docker-compose`

### المشكلة: "Container failed to start"

**الحل:**
```bash
# على VPS
cd /var/www/al-waleed-api
docker compose logs
# فحص السجلات لمعرفة المشكلة
```

---

## 🔒 الأمان

### نصائح أمنية:

1. **استخدم SSH key منفصل للـ deployment**
   - لا تستخدم SSH key الشخصي
   - استخدم key مخصص للـ CI/CD فقط

2. **اقصر صلاحيات SSH key**
   - يمكنك تقييد SSH key لتنفيذ أوامر محددة فقط
   - أضف في `~/.ssh/authorized_keys`:
     ```
     command="/var/www/al-waleed-api/deploy.sh",no-port-forwarding,no-X11-forwarding,no-agent-forwarding ssh-ed25519 ...
     ```

3. **استخدم مستخدم منفصل**
   - أنشئ مستخدم `deploy` بدلاً من `root`
   ```bash
   adduser deploy
   usermod -aG docker deploy
   ```

4. **احمِ ملف .env**
   - تأكد من أن `.env` في `.gitignore`
   - لا ترفع معلومات حساسة إلى GitHub

---

## 📊 مراقبة النشر

### عرض سجلات GitHub Actions:

1. اذهب إلى **Actions** tab في GitHub
2. اضغط على workflow run
3. اضغط على job لرؤية السجلات

### عرض سجلات على VPS:

```bash
# سجلات Docker
cd /var/www/al-waleed-api
docker compose logs -f

# سجلات Laravel
docker compose exec app tail -f storage/logs/laravel.log
```

---

## 🔄 التحديثات المستقبلية

بعد إعداد كل شيء:

1. **اعمل تغييرات في الكود محلياً**
2. **اعمل commit و push إلى GitHub**
3. **سيتم النشر تلقائياً على VPS!**

```bash
git add .
git commit -m "Update code"
git push origin main
```

---

## ✅ التحقق من النشر

بعد النشر، تحقق من:

1. **الحاويات تعمل:**
   ```bash
   docker compose ps
   ```

2. **التطبيق يعمل:**
   ```bash
   curl http://your-domain.com/api/health
   ```

3. **السجلات:**
   ```bash
   docker compose logs app
   ```

---

## 📚 مراجع مفيدة

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)

---

**تم إعداد النشر التلقائي بنجاح! 🎉**

الآن كل push إلى `main` أو `master` سيتم نشره تلقائياً على VPS.

