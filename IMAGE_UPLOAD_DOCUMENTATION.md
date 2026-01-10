# 📸 توثيق API رفع الصور

## نظرة عامة

تم إضافة نظام رفع الصور لجميع أنواع المستخدمين (Managers, Employees, Representatives, Pickers).

---

## 🔧 التعديلات التي تمت

### 1. إصلاح مشكلة الـ Validation
تم إصلاح خطأ في جميع ملفات UpdateRequest حيث كان يتسبب في خطأ 500 عند التحديث.

**الملفات المعدلة:**
- `app/Http/Requests/Employee/UpdateEmployeeRequest.php`
- `app/Http/Requests/Manager/UpdateManagerRequest.php`
- `app/Http/Requests/Representative/UpdateRepresentativeRequest.php`
- `app/Http/Requests/Picker/UpdatePickerRequest.php`

### 2. إضافة دوال رفع الصور
تم إضافة دالة `uploadImage()` في جميع Controllers:
- `EmployeeController`
- `ManagerController`
- `RepresentativeController`
- `PickerController`

### 3. تحديث Resources
تم إضافة حقل `profile_image_url` في جميع Resources لإرجاع URL كامل للصورة.

### 4. إضافة Routes
تم إضافة routes جديدة لرفع الصور في `routes/api.php`.

---

## 📋 API Endpoints

### رفع صورة موظف (Employee)
**POST** `/api/employees/{emp_id}/upload-image`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
- `profile_image` (file) - صورة (jpeg, png, jpg, gif, webp) - حجم أقصى: 2MB

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "employees/xYz123.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/employees/xYz123.jpg"
  }
}
```

---

### رفع صورة مدير (Manager)
**POST** `/api/managers/{manager_id}/upload-image`

نفس الطريقة أعلاه، لكن يتم حفظ الصور في مجلد `managers/`

---

### رفع صورة ممثل (Representative)
**POST** `/api/representatives/{rep_id}/upload-image`

نفس الطريقة أعلاه، لكن يتم حفظ الصور في مجلد `representatives/`

---

### رفع صورة عامل (Picker)
**POST** `/api/pickers/{picker_id}/upload-image`

نفس الطريقة أعلاه، لكن يتم حفظ الصور في مجلد `pickers/`

---

## 📱 استخدام من Flutter

### مثال: رفع صورة موظف

```dart
import 'package:dio/dio.dart';
import 'dart:io';

Future<void> uploadEmployeeImage(int empId, File imageFile) async {
  try {
    // إنشاء FormData لرفع الملف
    FormData formData = FormData.fromMap({
      'profile_image': await MultipartFile.fromFile(
        imageFile.path,
        filename: 'employee_$empId.jpg',
      ),
    });

    // رفع الصورة
    final response = await dio.post(
      '/employees/$empId/upload-image',
      data: formData,
      options: Options(
        headers: {
          'Authorization': 'Bearer $token',
        },
      ),
    );
    
    print('✅ Image uploaded successfully');
    print('Image URL: ${response.data['data']['profile_image_url']}');
    
  } on DioException catch (e) {
    if (e.response?.statusCode == 422) {
      print('❌ Validation error: ${e.response?.data}');
    } else {
      print('❌ Upload failed: ${e.message}');
    }
  }
}
```

### مثال: تحديث بيانات موظف (بدون صورة)

```dart
Future<void> updateEmployee(int empId) async {
  try {
    final response = await dio.patch(
      '/employees/$empId',
      data: {
        'full_name': 'علي حسين',
        'phone_number': '07742209252',
        'job_role': 'محاسب',
      },
    );
    
    print('✅ Employee updated successfully');
    
  } catch (e) {
    print('❌ Update failed: $e');
  }
}
```

### مثال كامل: اختيار وتحميل صورة

```dart
import 'package:image_picker/image_picker.dart';

class EmployeeService {
  final Dio dio;
  
  EmployeeService(this.dio);

  // اختيار صورة من المعرض أو الكاميرا
  Future<File?> pickImage({required ImageSource source}) async {
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(
      source: source,
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
  Future<Map<String, dynamic>?> uploadEmployeeImage(
    int empId, 
    File imageFile
  ) async {
    try {
      FormData formData = FormData.fromMap({
        'profile_image': await MultipartFile.fromFile(
          imageFile.path,
          filename: 'employee_${empId}_${DateTime.now().millisecondsSinceEpoch}.jpg',
        ),
      });

      final response = await dio.post(
        '/employees/$empId/upload-image',
        data: formData,
      );
      
      return response.data['data'];
      
    } on DioException catch (e) {
      print('Upload error: ${e.response?.data}');
      rethrow;
    }
  }

  // استخدام كامل
  Future<void> changeEmployeeProfilePicture(int empId) async {
    // اختيار الصورة
    final imageFile = await pickImage(source: ImageSource.gallery);
    
    if (imageFile != null) {
      // رفع الصورة
      final result = await uploadEmployeeImage(empId, imageFile);
      
      if (result != null) {
        print('✅ Image URL: ${result['profile_image_url']}');
      }
    }
  }
}
```

---

## 🗂️ تنظيم الملفات

يتم حفظ الصور في المجلدات التالية:
- **Employees:** `storage/app/public/employees/`
- **Managers:** `storage/app/public/managers/`
- **Representatives:** `storage/app/public/representatives/`
- **Pickers:** `storage/app/public/pickers/`

الصور متاحة عبر:
```
https://maktabalwaleed.com/storage/{folder}/{filename}
```

---

## ⚠️ ملاحظات مهمة

### 1. الصلاحيات
- جميع endpoints رفع الصور تحتاج إلى:
  - Token صالح
  - المستخدم يجب أن يكون Manager

### 2. أنواع الملفات المدعومة
- jpeg
- jpg
- png
- gif
- webp

### 3. حجم الملف الأقصى
- 2MB (2048 KB)

### 4. حذف الصورة القديمة
- عند رفع صورة جديدة، يتم حذف الصورة القديمة تلقائياً

### 5. Response Format
جميع الـ Resources الآن تُرجع:
```json
{
  "profile_image": "employees/abc123.jpg",
  "profile_image_url": "https://maktabalwaleed.com/storage/employees/abc123.jpg"
}
```

---

## 🐛 معالجة الأخطاء

### خطأ 422 - Validation Error
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

### خطأ 401 - Unauthorized
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

### خطأ 404 - Not Found
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

### خطأ 500 - Server Error
```json
{
  "status": "error",
  "message": "Failed to upload image"
}
```

---

## ✅ التأكد من عمل النظام

تم تنفيذ:
1. ✅ إصلاح validation في جميع UpdateRequests
2. ✅ إضافة دوال uploadImage في جميع Controllers
3. ✅ إضافة routes لرفع الصور
4. ✅ تحديث Resources لإرجاع profile_image_url
5. ✅ إنشاء symbolic link للـ storage

الآن يمكنك:
- تحديث بيانات الموظفين بنجاح ✅
- رفع الصور لجميع أنواع المستخدمين ✅
- الحصول على URL كامل للصور ✅

---

## 🚀 مثال سريع

```bash
# رفع صورة موظف باستخدام cURL
curl -X POST \
  https://maktabalwaleed.com/api/employees/1/upload-image \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "profile_image=@/path/to/image.jpg"
```

---

**تم بواسطة:** AI Assistant  
**التاريخ:** 2026-01-10  
**الإصدار:** 1.0.0

