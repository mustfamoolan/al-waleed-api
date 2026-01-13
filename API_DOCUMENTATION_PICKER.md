# API Documentation - Picker

## نظرة عامة

هذا الـ API مخصص للمنتقيين (Pickers). المنتقيون لديهم API منفصل تماماً عن API المدير والموظف و API الممثلين.

## Base URL

```
http://your-domain.com/picker-api
```

## Health Check

للتحقق من أن الـ API يعمل بشكل صحيح، افتح الرابط التالي في المتصفح:

```
GET http://your-domain.com/picker-api/health
```

**Response:**
```json
{
  "status": "success",
  "api": "Picker API",
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

**GET** `/picker-api/health`

التحقق من حالة الـ API.

**Response:**
```json
{
  "status": "success",
  "api": "Picker API",
  "message": "API is running and healthy",
  "database": "connected",
  "timestamp": "2026-01-08 12:00:00",
  "version": "1.0.0"
}
```

---

### 2. تسجيل الدخول (Login)

**POST** `/picker-api/auth/login`

تسجيل الدخول للمنتقي.

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
      "picker_id": 1,
      "full_name": "منتقي 1",
      "phone_number": "0501234567",
      "profile_image": null,
      "profile_image_url": null,
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

**GET** `/picker-api/auth/me`

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
      "picker_id": 1,
      "full_name": "منتقي 1",
      "phone_number": "0501234567",
      "profile_image": null,
      "profile_image_url": null,
      "created_at": "2026-01-08T10:00:00.000000Z",
      "updated_at": "2026-01-08T10:00:00.000000Z"
    }
  }
}
```

---

### 4. تسجيل الخروج (Logout)

**POST** `/picker-api/auth/logout`

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

### 5. رفع صورة الملف الشخصي

**POST** `/api/pickers/{picker_id}/upload-image`

**ملاحظة مهمة:** رفع الصور لعمال التجهيز يتم عبر Manager API وليس Picker API. يجب على المدير رفع الصورة نيابة عن العامل.

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
- `profile_image` (file) - مطلوب - ملف الصورة

**المتطلبات:**
- أنواع الملفات المدعومة: jpeg, jpg, png, gif, webp
- الحجم الأقصى: 2MB
- يجب أن يكون المستخدم مدير (Manager)

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "pickers/xyz789abc.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/pickers/xyz789abc.jpg"
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

**مثال Flutter/Dart:**
```dart
import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';

// اختيار صورة من المعرض
Future<File?> pickImage() async {
  final ImagePicker picker = ImagePicker();
  final XFile? image = await picker.pickImage(
    source: ImageSource.gallery,
    maxWidth: 1024,
    maxHeight: 1024,
    imageQuality: 85,
  );
  
  if (image != null) {
    return File(image.path);
  }
  return null;
}

// رفع الصورة
Future<void> uploadPickerImage(int pickerId, File imageFile) async {
  try {
    FormData formData = FormData.fromMap({
      'profile_image': await MultipartFile.fromFile(
        imageFile.path,
        filename: 'picker_$pickerId.jpg',
      ),
    });

    final response = await dio.post(
      '/pickers/$pickerId/upload-image',
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

// استخدام كامل
Future<void> changePickerProfilePicture(int pickerId) async {
  final imageFile = await pickImage();
  
  if (imageFile != null) {
    await uploadPickerImage(pickerId, imageFile);
  }
}
```

**مثال cURL:**
```bash
curl -X POST https://maktabalwaleed.com/api/pickers/1/upload-image \
  -H "Authorization: Bearer MANAGER_TOKEN" \
  -F "profile_image=@/path/to/image.jpg"
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

ثم إعادة تشغيل nginx:
```bash
sudo systemctl restart nginx
```

**الحل 2: ضغط الصورة من Flutter**
```dart
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:path_provider/path_provider.dart';

Future<File?> compressImage(File file) async {
  final dir = await getTemporaryDirectory();
  final targetPath = '${dir.absolute.path}/temp_${DateTime.now().millisecondsSinceEpoch}.jpg';
  
  var result = await FlutterImageCompress.compressAndGetFile(
    file.absolute.path,
    targetPath,
    quality: 70,        // جودة 70%
    minWidth: 800,      // عرض أقصى
    minHeight: 800,     // ارتفاع أقصى
  );
  
  return result != null ? File(result.path) : null;
}
```

**أضف للـ pubspec.yaml:**
```yaml
dependencies:
  flutter_image_compress: ^2.1.0
  path_provider: ^2.1.0
  image_picker: ^1.0.0
```

---

## Status Codes

- `200` - Success
- `401` - Unauthenticated (لم يتم تسجيل الدخول أو token غير صحيح)
- `413` - Request Entity Too Large (الصورة كبيرة جداً - يحتاج تعديل nginx)
- `422` - Validation Error (خطأ في البيانات المرسلة)
- `500` - Server Error

## أمثلة على الاستخدام

### مثال 1: تسجيل الدخول والحصول على Token

```bash
curl -X POST http://your-domain.com/picker-api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0501234567",
    "password": "password123"
  }'
```

### مثال 2: الحصول على معلومات المستخدم الحالي

```bash
curl -X GET http://your-domain.com/picker-api/auth/me \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

### مثال 3: تسجيل الخروج

```bash
curl -X POST http://your-domain.com/picker-api/auth/logout \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

---

## ملاحظات مهمة

1. **API منفصل:** هذا الـ API منفصل تماماً عن API المدير والموظف و API الممثلين
2. **التوثيق:** جميع الـ requests يجب أن تحتوي على header `Content-Type: application/json`
3. **الـ Token:** يجب إرسال الـ token في header `Authorization: Bearer {token}` لكل request محمي
4. **الـ Phone Number:** يجب أن يكون فريد (unique) للمنتقيين
5. **كلمة المرور:** يجب أن تكون على الأقل 6 أحرف
6. **إدارة الحسابات:** المنتقيون لا يمكنهم إدارة حساباتهم - فقط المدير يمكنه ذلك من خلال API المدير والموظف
7. **رفع الصور:** يتم رفع الصور عبر Manager API (endpoint مختلف)، ويُرجع `profile_image_url` مع كل response

---

## اختبار الـ API

### اختبار Health Check

افتح الرابط التالي في المتصفح:
```
http://your-domain.com/picker-api/health
```

إذا رأيت JSON response يحتوي على `"status": "success"`، فإن الـ API يعمل بشكل صحيح.

### اختبار تسجيل الدخول

يمكنك استخدام Postman أو أي أداة مشابهة لاختبار تسجيل الدخول:

1. افتح Postman
2. اختر POST
3. أدخل URL: `http://your-domain.com/picker-api/auth/login`
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

