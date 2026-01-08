# API Documentation - Representative

## نظرة عامة

هذا الـ API مخصص للممثلين (Representatives). الممثلون لديهم API منفصل تماماً عن API المدير والموظف.

## Base URL

```
http://your-domain.com/representative-api
```

## Health Check

للتحقق من أن الـ API يعمل بشكل صحيح، افتح الرابط التالي في المتصفح:

```
GET http://your-domain.com/representative-api/health
```

**Response:**
```json
{
  "status": "success",
  "api": "Representative API",
  "message": "API is running and healthy",
  "database": "connected",
  "timestamp": "2026-01-08 12:00:00",
  "version": "1.0.0"
}
```

## Authentication

يستخدم هذا الـ API Laravel Sanctum للـ authentication. بعد تسجيل الدخول، ستحصل على token يجب إرساله في header كل request.

### Headers المطلوبة للـ Protected Routes

```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

---

## Endpoints

### 1. Health Check

**GET** `/representative-api/health`

التحقق من حالة الـ API.

**Response:**
```json
{
  "status": "success",
  "api": "Representative API",
  "message": "API is running and healthy",
  "database": "connected",
  "timestamp": "2026-01-08 12:00:00",
  "version": "1.0.0"
}
```

---

### 2. تسجيل الدخول (Login)

**POST** `/representative-api/auth/login`

تسجيل الدخول للممثل.

**Request Body:**
```json
{
  "phone_number": "0501234567",
  "password": "password123"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "rep_id": 1,
      "full_name": "ممثل 1",
      "phone_number": "0501234567",
      "profile_image": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "phone_number": ["The provided credentials are incorrect."]
  }
}
```

---

### 3. الحصول على معلومات المستخدم الحالي (Me)

**GET** `/representative-api/auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "user": {
      "rep_id": 1,
      "full_name": "ممثل 1",
      "phone_number": "0501234567",
      "profile_image": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    }
  }
}
```

---

### 4. تسجيل الخروج (Logout)

**POST** `/representative-api/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Logged out successfully",
  "data": null
}
```

---

## Status Codes

- `200` - Success
- `401` - Unauthenticated (لم يتم تسجيل الدخول أو token غير صحيح)
- `422` - Validation Error (خطأ في البيانات المرسلة)
- `500` - Server Error

## أمثلة على الاستخدام

### مثال 1: تسجيل الدخول والحصول على Token

```bash
curl -X POST http://your-domain.com/representative-api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0501234567",
    "password": "password123"
  }'
```

### مثال 2: الحصول على معلومات المستخدم الحالي

```bash
curl -X GET http://your-domain.com/representative-api/auth/me \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

### مثال 3: تسجيل الخروج

```bash
curl -X POST http://your-domain.com/representative-api/auth/logout \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

---

## ملاحظات مهمة

1. **API منفصل:** هذا الـ API منفصل تماماً عن API المدير والموظف
2. **التوثيق:** جميع الـ requests يجب أن تحتوي على header `Content-Type: application/json`
3. **الـ Token:** يجب إرسال الـ token في header `Authorization: Bearer {token}` لكل request محمي
4. **الـ Phone Number:** يجب أن يكون فريد (unique) للممثلين
5. **كلمة المرور:** يجب أن تكون على الأقل 6 أحرف
6. **إدارة الحسابات:** الممثلون لا يمكنهم إدارة حساباتهم - فقط المدير يمكنه ذلك من خلال API المدير والموظف

---

## اختبار الـ API

### اختبار Health Check

افتح الرابط التالي في المتصفح:
```
http://your-domain.com/representative-api/health
```

إذا رأيت JSON response يحتوي على `"status": "success"`، فإن الـ API يعمل بشكل صحيح.

### اختبار تسجيل الدخول

يمكنك استخدام Postman أو أي أداة مشابهة لاختبار تسجيل الدخول:

1. افتح Postman
2. اختر POST
3. أدخل URL: `http://your-domain.com/representative-api/auth/login`
4. في Headers، أضف: `Content-Type: application/json`
5. في Body، اختر raw و JSON، ثم أدخل:
```json
{
  "phone_number": "0501234567",
  "password": "password123"
}
```
6. اضغط Send

إذا كان الحساب موجود وكلمة المرور صحيحة، ستحصل على token في الـ response.

