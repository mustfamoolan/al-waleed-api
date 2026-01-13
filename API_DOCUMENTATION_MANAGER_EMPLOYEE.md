# API Documentation - Manager & Employee

## نظرة عامة

هذا الـ API مخصص للمديرين والموظفين. المدير والموظف يستخدمان نفس الـ API لتسجيل الدخول، ولكن فقط المدير يمكنه إدارة الحسابات (إنشاء، تعديل، حذف).

## Base URL

```
http://your-domain.com/api
```

## Health Check

للتحقق من أن الـ API يعمل بشكل صحيح، افتح الرابط التالي في المتصفح:

```
GET http://your-domain.com/api/manager-employee/health
```

**Response:**
```json
{
  "status": "success",
  "api": "Manager & Employee API",
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

**GET** `/api/manager-employee/health`

التحقق من حالة الـ API.

**Response:**
```json
{
  "status": "success",
  "api": "Manager & Employee API",
  "message": "API is running and healthy",
  "database": "connected",
  "timestamp": "2026-01-08 12:00:00",
  "version": "1.0.0"
}
```

---

### 2. تسجيل الدخول (Login)

**POST** `/api/manager-auth/login`

تسجيل الدخول للمدير أو الموظف.

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
      "manager_id": 1,
      "full_name": "أحمد محمد",
      "phone_number": "0501234567",
      "profile_image": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    },
    "user_type": "manager",
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

**GET** `/api/manager-auth/me`

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
      "manager_id": 1,
      "full_name": "أحمد محمد",
      "phone_number": "0501234567",
      "profile_image": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    },
    "user_type": "manager"
  }
}
```

---

### 4. تسجيل الخروج (Logout)

**POST** `/api/manager-auth/logout`

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

## Manager Only Endpoints

جميع الـ endpoints التالية تتطلب:
1. تسجيل الدخول كمدير (Manager)
2. إرسال token في header
3. فقط المدير يمكنه الوصول لهذه الـ endpoints

---

### 5. عرض قائمة المديرين

**GET** `/api/managers`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "manager_id": 1,
      "full_name": "أحمد محمد",
      "phone_number": "0501234567",
      "profile_image": null,
      "profile_image_url": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    },
    {
      "manager_id": 2,
      "full_name": "محمد علي",
      "phone_number": "0507654321",
      "profile_image": "managers/abc123.jpg",
      "profile_image_url": "https://maktabalwaleed.com/storage/managers/abc123.jpg",
      "created_at": "2026-01-08T11:00:00.000000Z",
      "updated_at": "2026-01-08T11:00:00.000000Z"
    }
  ]
}
```

---

### 6. إنشاء مدير جديد

**POST** `/api/managers`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "سالم أحمد",
  "phone_number": "0501111111",
  "password": "password123",
  "profile_image": "https://example.com/image.jpg"
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Manager created successfully",
  "data": {
    "manager_id": 3,
    "full_name": "سالم أحمد",
    "phone_number": "0501111111",
    "profile_image": "https://example.com/image.jpg",
    "created_at": "2026-01-08T12:00:00.000000Z",
    "updated_at": "2026-01-08T12:00:00.000000Z"
  }
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "phone_number": ["The phone number has already been taken."]
  }
}
```

---

### 7. عرض مدير محدد

**GET** `/api/managers/{manager_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "manager_id": 1,
    "full_name": "أحمد محمد",
    "phone_number": "0501234567",
    "profile_image": null,
    "created_at": "2026-01-08T10:00:00.000000Z",
    "updated_at": "2026-01-08T10:00:00.000000Z"
  }
}
```

---

### 8. تحديث مدير

**PUT/PATCH** `/api/managers/{manager_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "أحمد محمد المحدث",
  "phone_number": "0501234567",
  "password": "newpassword123",
  "profile_image": "https://example.com/new-image.jpg"
}
```

**ملاحظة:** جميع الحقول اختيارية. فقط الحقول المرسلة سيتم تحديثها.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Manager updated successfully",
  "data": {
    "manager_id": 1,
    "full_name": "أحمد محمد المحدث",
    "phone_number": "0501234567",
    "profile_image": "https://example.com/new-image.jpg",
    "created_at": "2026-01-08T10:00:00.000000Z",
    "updated_at": "2026-01-08T12:30:00.000000Z"
  }
}
```

---

### 9. حذف مدير

**DELETE** `/api/managers/{manager_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Manager deleted successfully",
  "data": null
}
```

---

### 10. عرض قائمة الموظفين

**GET** `/api/employees`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "emp_id": 1,
      "full_name": "خالد سعيد",
      "phone_number": "0502222222",
      "job_role": "محاسب",
      "profile_image": null,
      "profile_image_url": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    },
    {
      "emp_id": 2,
      "full_name": "فاطمة علي",
      "phone_number": "0503333333",
      "job_role": "مدقق",
      "profile_image": "employees/xyz789.jpg",
      "profile_image_url": "https://maktabalwaleed.com/storage/employees/xyz789.jpg",
      "created_at": "2026-01-08T11:00:00.000000Z",
      "updated_at": "2026-01-08T11:00:00.000000Z"
    }
  ]
}
```

---

### 11. إنشاء موظف جديد

**POST** `/api/employees`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "علي حسن",
  "phone_number": "0504444444",
  "password": "password123",
  "job_role": "IT",
  "profile_image": "https://example.com/image.jpg"
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Employee created successfully",
  "data": {
    "emp_id": 3,
    "full_name": "علي حسن",
    "phone_number": "0504444444",
    "job_role": "IT",
    "profile_image": "https://example.com/image.jpg",
    "created_at": "2026-01-08T12:00:00.000000Z",
    "updated_at": "2026-01-08T12:00:00.000000Z"
  }
}
```

---

### 12. عرض موظف محدد

**GET** `/api/employees/{emp_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "emp_id": 1,
    "full_name": "خالد سعيد",
    "phone_number": "0502222222",
    "job_role": "محاسب",
    "profile_image": null,
    "created_at": "2026-01-08T10:00:00.000000Z",
    "updated_at": "2026-01-08T10:00:00.000000Z"
  }
}
```

---

### 13. تحديث موظف

**PUT/PATCH** `/api/employees/{emp_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "خالد سعيد المحدث",
  "phone_number": "0502222222",
  "password": "newpassword123",
  "job_role": "مدير محاسبة",
  "profile_image": "https://example.com/new-image.jpg"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Employee updated successfully",
  "data": {
    "emp_id": 1,
    "full_name": "خالد سعيد المحدث",
    "phone_number": "0502222222",
    "job_role": "مدير محاسبة",
    "profile_image": "https://example.com/new-image.jpg",
    "created_at": "2026-01-08T10:00:00.000000Z",
    "updated_at": "2026-01-08T12:30:00.000000Z"
  }
}
```

---

### 14. حذف موظف

**DELETE** `/api/employees/{emp_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Employee deleted successfully",
  "data": null
}
```

---

### 15. عرض قائمة الممثلين

**GET** `/api/representatives`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "rep_id": 1,
      "full_name": "ممثل 1",
      "phone_number": "0505555555",
      "profile_image": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    }
  ]
}
```

---

### 16. إنشاء ممثل جديد

**POST** `/api/representatives`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "ممثل جديد",
  "phone_number": "0506666666",
  "password": "password123",
  "profile_image": "https://example.com/image.jpg"
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Representative created successfully",
  "data": {
    "rep_id": 2,
    "full_name": "ممثل جديد",
    "phone_number": "0506666666",
    "profile_image": "https://example.com/image.jpg",
    "created_at": "2026-01-08T12:00:00.000000Z",
    "updated_at": "2026-01-08T12:00:00.000000Z"
  }
}
```

---

### 17. عرض ممثل محدد

**GET** `/api/representatives/{rep_id}`

**Headers:**
```
Authorization: Bearer {token}
```

---

### 18. تحديث ممثل

**PUT/PATCH** `/api/representatives/{rep_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

### 19. حذف ممثل

**DELETE** `/api/representatives/{rep_id}`

**Headers:**
```
Authorization: Bearer {token}
```

---

### 20. عرض قائمة المنتقيين

**GET** `/api/pickers`

**Headers:**
```
Authorization: Bearer {token}
```

---

### 21. إنشاء منتقي جديد

**POST** `/api/pickers`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "منتقي جديد",
  "phone_number": "0507777777",
  "password": "password123",
  "profile_image": "https://example.com/image.jpg"
}
```

---

### 22. عرض منتقي محدد

**GET** `/api/pickers/{picker_id}`

**Headers:**
```
Authorization: Bearer {token}
```

---

### 23. تحديث منتقي

**PUT/PATCH** `/api/pickers/{picker_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

### 24. حذف منتقي

**DELETE** `/api/pickers/{picker_id}`

**Headers:**
```
Authorization: Bearer {token}
```

---

## Image Upload Endpoints (رفع الصور)

جميع المستخدمين الآن يمكنهم رفع صور شخصية عبر endpoints منفصلة.

### 25. رفع صورة مدير

**POST** `/api/managers/{manager_id}/upload-image`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
- `profile_image` (file) - مطلوب - ملف الصورة

**المتطلبات:**
- أنواع الملفات المدعومة: jpeg, jpg, png, gif, webp
- الحجم الأقصى: 2MB

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "managers/abc123xyz.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/managers/abc123xyz.jpg"
  }
}
```

**Response (Error - 422 - Validation):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "profile_image": [
      "The profile image must be an image.",
      "The profile image must not be greater than 2048 kilobytes."
    ]
  }
}
```

**Response (Error - 413 - File Too Large):**
```html
<html>
<head><title>413 Request Entity Too Large</title></head>
<body>
<center><h1>413 Request Entity Too Large</h1></center>
<hr><center>nginx</center>
</body>
</html>
```

**ملاحظة:** إذا واجهت خطأ 413، فهذا يعني أن nginx يرفض الملف. راجع قسم "Troubleshooting" أدناه.

**مثال Flutter/Dart:**
```dart
import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';

Future<void> uploadManagerImage(int managerId, File imageFile) async {
  try {
    FormData formData = FormData.fromMap({
      'profile_image': await MultipartFile.fromFile(
        imageFile.path,
        filename: 'manager_$managerId.jpg',
      ),
    });

    final response = await dio.post(
      '/managers/$managerId/upload-image',
      data: formData,
    );
    
    print('✅ Image uploaded: ${response.data['data']['profile_image_url']}');
  } on DioException catch (e) {
    if (e.response?.statusCode == 422) {
      print('❌ Validation error: ${e.response?.data}');
    } else if (e.response?.statusCode == 413) {
      print('❌ Image too large. Please compress the image.');
    } else {
      print('❌ Upload failed: ${e.message}');
    }
  }
}
```

---

### 26. رفع صورة موظف

**POST** `/api/employees/{emp_id}/upload-image`

نفس التفاصيل أعلاه، لكن للموظفين. الصور تُحفظ في `storage/app/public/employees/`.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "employees/xyz789abc.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/employees/xyz789abc.jpg"
  }
}
```

**مثال cURL:**
```bash
curl -X POST https://maktabalwaleed.com/api/employees/1/upload-image \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "profile_image=@/path/to/image.jpg"
```

---

### 27. رفع صورة ممثل

**POST** `/api/representatives/{rep_id}/upload-image`

نفس التفاصيل أعلاه، لكن للممثلين. الصور تُحفظ في `storage/app/public/representatives/`.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "representatives/def456ghi.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/representatives/def456ghi.jpg"
  }
}
```

---

### 28. رفع صورة عامل تجهيز

**POST** `/api/pickers/{picker_id}/upload-image`

نفس التفاصيل أعلاه، لكن لعمال التجهيز. الصور تُحفظ في `storage/app/public/pickers/`.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "pickers/jkl012mno.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/pickers/jkl012mno.jpg"
  }
}
```

---

## Troubleshooting

### مشكلة خطأ 413 - Request Entity Too Large

إذا واجهت خطأ 413 عند رفع الصور، فهذا يعني أن nginx يحدد حجم الرفع. الحلول:

**الحل 1: زيادة حد nginx (للسيرفر)**
```nginx
# في ملف nginx config
client_max_body_size 10M;
```

**الحل 2: ضغط الصورة من Flutter**
```dart
import 'package:flutter_image_compress/flutter_image_compress.dart';

Future<File?> compressImage(File file) async {
  final dir = await getTemporaryDirectory();
  final targetPath = '${dir.absolute.path}/temp_${DateTime.now().millisecondsSinceEpoch}.jpg';
  
  var result = await FlutterImageCompress.compressAndGetFile(
    file.absolute.path,
    targetPath,
    quality: 70,
    minWidth: 800,
    minHeight: 800,
  );
  
  return result != null ? File(result.path) : null;
}
```

---

## Status Codes

- `200` - Success
- `201` - Created Successfully
- `401` - Unauthenticated (لم يتم تسجيل الدخول أو token غير صحيح)
- `403` - Forbidden (ليس لديك صلاحية للوصول - فقط المدير)
- `404` - Not Found (الموارد المطلوبة غير موجودة)
- `413` - Request Entity Too Large (الصورة كبيرة جداً - يحتاج تعديل nginx)
- `422` - Validation Error (خطأ في البيانات المرسلة)
- `500` - Server Error

## أمثلة على الاستخدام

### مثال 1: تسجيل الدخول والحصول على Token

```bash
curl -X POST http://your-domain.com/api/manager-auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0501234567",
    "password": "password123"
  }'
```

### مثال 2: إنشاء موظف جديد (يتطلب token المدير)

```bash
curl -X POST http://your-domain.com/api/employees \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "علي حسن",
    "phone_number": "0504444444",
    "password": "password123",
    "job_role": "IT",
    "profile_image": null
  }'
```

### مثال 3: عرض قائمة الموظفين (يتطلب token المدير)

```bash
curl -X GET http://your-domain.com/api/employees \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

---

## ملاحظات مهمة

1. **الصلاحيات:** فقط المدير (Manager) يمكنه الوصول لـ endpoints إدارة الحسابات (CRUD operations)
2. **التوثيق:** جميع الـ requests يجب أن تحتوي على header `Content-Type: application/json`
3. **الـ Token:** يجب إرسال الـ token في header `Authorization: Bearer {token}` لكل request محمي
4. **الـ Phone Number:** يجب أن يكون فريد (unique) لكل نوع مستخدم
5. **كلمة المرور:** يجب أن تكون على الأقل 6 أحرف
6. **رفع الصور:** جميع endpoints رفع الصور تحذف الصورة القديمة تلقائياً عند رفع صورة جديدة
7. **URL الصور:** جميع الـ responses الآن تُرجع `profile_image` (المسار) و `profile_image_url` (URL كامل)

