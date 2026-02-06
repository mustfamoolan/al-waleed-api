# 🚀 دليل سريع - رفع الصور

## ✅ المشكلة التي تم حلها

**المشكلة الأصلية:** خطأ 500 عند تحديث بيانات الموظف
```
❌ ERROR[500] => PATH: /employees/1
```

**السبب:** خطأ في validation rules في ملفات UpdateRequest

**الحل:** ✅ تم إصلاح الخطأ وإضافة نظام كامل لرفع الصور

---

## 📱 الاستخدام من Flutter

### 1️⃣ تحديث بيانات موظف (بدون صورة)

الآن سيعمل بدون مشاكل! ✅

```dart
await dio.patch(
  '/employees/1',
  data: {
    'full_name': 'علي حسين',
    'phone_number': '07742209252',
    'job_role': 'محاسب',
  },
);
```

### 2️⃣ رفع صورة موظف

```dart
// اختيار الصورة
final ImagePicker picker = ImagePicker();
final XFile? image = await picker.pickImage(source: ImageSource.gallery);

if (image != null) {
  // رفع الصورة
  FormData formData = FormData.fromMap({
    'profile_image': await MultipartFile.fromFile(image.path),
  });
  
  await dio.post(
    '/employees/1/upload-image',
    data: formData,
  );
}
```

---

## 🔗 Endpoints الجديدة

| المستخدم | Endpoint |
|---------|----------|
| موظف | `POST /api/employees/{id}/upload-image` |
| مدير | `POST /api/managers/{id}/upload-image` |
| ممثل | `POST /api/representatives/{id}/upload-image` |
| عامل | `POST /api/pickers/{id}/upload-image` |

---

## 📊 Response الجديد

الآن جميع الـ API تُرجع `profile_image_url`:

```json
{
  "status": "success",
  "data": {
    "emp_id": 1,
    "full_name": "علي حسين",
    "phone_number": "07742209252",
    "job_role": "محاسب",
    "profile_image": "employees/abc123.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/employees/abc123.jpg"
  }
}
```

---

## ⚡ مثال كامل - Widget Flutter

```dart
class EmployeeProfilePicker extends StatelessWidget {
  final int employeeId;
  final Dio dio;
  
  const EmployeeProfilePicker({
    required this.employeeId,
    required this.dio,
  });

  Future<void> _pickAndUploadImage() async {
    // 1. اختيار الصورة
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1024,
      maxHeight: 1024,
      imageQuality: 85,
    );
    
    if (image == null) return;
    
    try {
      // 2. رفع الصورة
      FormData formData = FormData.fromMap({
        'profile_image': await MultipartFile.fromFile(
          image.path,
          filename: 'employee_$employeeId.jpg',
        ),
      });
      
      final response = await dio.post(
        '/employees/$employeeId/upload-image',
        data: formData,
      );
      
      // 3. النجاح ✅
      final imageUrl = response.data['data']['profile_image_url'];
      print('✅ تم رفع الصورة: $imageUrl');
      
      // تحديث UI هنا
      
    } on DioException catch (e) {
      // 4. معالجة الأخطاء
      if (e.response?.statusCode == 422) {
        print('❌ خطأ في الصورة: ${e.response?.data['errors']}');
      } else {
        print('❌ فشل الرفع: ${e.message}');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      icon: Icon(Icons.camera_alt),
      onPressed: _pickAndUploadImage,
    );
  }
}
```

---

## 📝 ملاحظات

### الصور المدعومة
- أنواع: jpeg, jpg, png, gif, webp
- حجم أقصى: 2MB

### الصلاحيات المطلوبة
- ✅ Token صالح (Manager فقط)

### حذف الصورة القديمة
- يتم تلقائياً عند رفع صورة جديدة

---

## 🎯 الخلاصة

| الميزة | الحالة |
|-------|--------|
| تحديث البيانات بدون صورة | ✅ يعمل |
| رفع صورة جديدة | ✅ يعمل |
| حذف الصورة القديمة تلقائياً | ✅ يعمل |
| إرجاع URL كامل للصورة | ✅ يعمل |
| دعم جميع أنواع المستخدمين | ✅ يعمل |

---

**جاهز للاستخدام! 🚀**

للمزيد من التفاصيل، راجع: `IMAGE_UPLOAD_DOCUMENTATION.md`

