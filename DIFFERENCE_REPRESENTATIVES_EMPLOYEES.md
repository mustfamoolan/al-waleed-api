# الفرق بين المندوبين (Representatives) والموظفين (Employees) - دليل شامل

## 📋 نظرة عامة

هذا الدليل يوضح الفروقات الأساسية بين **المندوبين (Representatives)** و**الموظفين (Employees)** في النظام، وكيفية استخدام كل نوع بشكل صحيح.

---

## 🔑 الفروقات الأساسية

### 1. **المندوبين (Representatives)**

| الخاصية | القيمة |
|---------|--------|
| **النوع** | مستخدم مستقل - لديه API منفصل تماماً |
| **الموديل** | `Representative` |
| **الجدول** | `representatives` |
| **المفتاح الأساسي** | `rep_id` |
| **الحقول** | `full_name`, `phone_number`, `password_hash`, `profile_image` |
| **هل يحتوي `job_role`؟** | ❌ **لا** |
| **API الخاص** | `/representative-api/*` (API منفصل للمندوبين) |
| **API الإدارة** | `/api/representatives/*` (للمدير فقط) |

### 2. **الموظفين (Employees) - بما فيهم السائقين**

| الخاصية | القيمة |
|---------|--------|
| **النوع** | موظف داخل النظام |
| **الموديل** | `Employee` |
| **الجدول** | `employees` |
| **المفتاح الأساسي** | `emp_id` |
| **الحقول** | `full_name`, `phone_number`, `password_hash`, `job_role`, `profile_image` |
| **هل يحتوي `job_role`؟** | ✅ **نعم** (مثال: "سائق", "محاسب", "مدقق", "IT") |
| **API الخاص** | ❌ لا يوجد API منفصل (يستخدم Manager API) |
| **API الإدارة** | `/api/employees/*` (للمدير فقط) |

---

## 🎯 الفرق الرئيسي: `job_role`

### المندوبين (Representatives)
```json
{
  "rep_id": 1,
  "full_name": "أحمد المندوب",
  "phone_number": "0501111111",
  "profile_image": null,
  "profile_image_url": null,
  "created_at": "2026-01-08T10:00:00.000000Z",
  "updated_at": "2026-01-08T10:00:00.000000Z"
}
```
⚠️ **لاحظ:** لا يوجد حقل `job_role` ❌

### الموظفين (Employees) - السائق مثال
```json
{
  "emp_id": 1,
  "full_name": "محمد السائق",
  "phone_number": "0502222222",
  "job_role": "سائق",  // ✅ هنا الفرق
  "profile_image": null,
  "profile_image_url": null,
  "created_at": "2026-01-08T10:00:00.000000Z",
  "updated_at": "2026-01-08T10:00:00.000000Z"
}
```
✅ **لاحظ:** يوجد حقل `job_role` = "سائق"

---

## 📡 Endpoints الصحيحة

### 🔵 للمندوبين (Representatives)

#### 1. عرض قائمة المندوبين
```
GET /api/representatives
```
**Response:**
- يحتوي `rep_id` (وليس `emp_id`)
- **لا يحتوي** `job_role`
- الجدول: `representatives`

#### 2. إنشاء مندوب جديد
```
POST /api/representatives
```
**Request Body:**
```json
{
  "full_name": "مندوب جديد",
  "phone_number": "0503333333",
  "password": "password123",
  "profile_image": null
}
```
⚠️ **مهم:** لا ترسل `job_role` - المندوبين لا يحتاجونه!

#### 3. API المندوبين (للتطبيق الخاص بهم)
```
POST /representative-api/auth/login
GET /representative-api/auth/me
POST /representative-api/auth/logout
```

---

### 🟢 للموظفين (Employees) - السائقين

#### 1. عرض قائمة الموظفين (جميع الموظفين)
```
GET /api/employees
```
**Response:**
- يحتوي `emp_id` (وليس `rep_id`)
- **يحتوي** `job_role` (مثال: "سائق", "محاسب", "مدقق")

#### 2. فلترة السائقين فقط (في Frontend)
```dart
// Flutter/Dart Example
final allEmployees = await getEmployees();
final drivers = allEmployees.where((emp) => emp.job_role == "سائق").toList();
```

#### 3. إنشاء موظف جديد (سائق)
```
POST /api/employees
```
**Request Body:**
```json
{
  "full_name": "سائق جديد",
  "phone_number": "0504444444",
  "password": "password123",
  "job_role": "سائق",  // ✅ مطلوب!
  "profile_image": null
}
```
✅ **مهم:** يجب إرسال `job_role` = "سائق" للموظفين!

---

## ❌ أخطاء شائعة - احذر منها!

### خطأ 1: استخدام endpoint خاطئ
```dart
// ❌ خطأ - في صفحة المندوبين
GET /api/employees  // هذا للموظفين، ليس للمندوبين!

// ✅ صحيح - في صفحة المندوبين
GET /api/representatives  // هذا للمندوبين
```

### خطأ 2: البحث عن `job_role` في المندوبين
```dart
// ❌ خطأ - المندوبين ليس لديهم job_role
representatives.where((rep) => rep.job_role == "سائق")

// ✅ صحيح - استخدم employees مع job_role
employees.where((emp) => emp.job_role == "سائق")
```

### خطأ 3: الخلط بين `rep_id` و `emp_id`
```dart
// ❌ خطأ
if (user.rep_id && user.job_role == "سائق") { ... }

// ✅ صحيح
if (user.rep_id) { /* هذا مندوب */ }
if (user.emp_id && user.job_role == "سائق") { /* هذا سائق */ }
```

### خطأ 4: إرسال `job_role` عند إنشاء مندوب
```json
// ❌ خطأ - لا ترسل job_role للمندوبين
{
  "full_name": "مندوب",
  "phone_number": "0501111111",
  "password": "password123",
  "job_role": "سائق"  // ❌ خطأ! المندوبين لا يحتاجونه
}

// ✅ صحيح
{
  "full_name": "مندوب",
  "phone_number": "0501111111",
  "password": "password123"
  // بدون job_role
}
```

---

## 🔍 كيفية التمييز في الكود

### في Flutter/Dart

```dart
// مثال: التحقق من نوع المستخدم
void checkUserType(dynamic user) {
  // المندوب (Representative)
  if (user.containsKey('rep_id')) {
    print('هذا مندوب');
    print('ID: ${user['rep_id']}');
    // لا يوجد job_role للمندوبين
  }
  
  // الموظف (Employee) - قد يكون سائق
  if (user.containsKey('emp_id')) {
    print('هذا موظف');
    print('ID: ${user['emp_id']}');
    print('الوظيفة: ${user['job_role']}');
    
    if (user['job_role'] == 'سائق') {
      print('هذا سائق');
    }
  }
}
```

### في Response JSON

#### Response المندوب:
```json
{
  "status": "success",
  "data": {
    "rep_id": 1,           // ✅ rep_id
    "full_name": "مندوب",
    "phone_number": "0501111111",
    // ❌ لا يوجد job_role
    "profile_image": null
  }
}
```

#### Response الموظف (سائق):
```json
{
  "status": "success",
  "data": {
    "emp_id": 1,           // ✅ emp_id
    "full_name": "سائق",
    "phone_number": "0502222222",
    "job_role": "سائق",   // ✅ job_role موجود
    "profile_image": null
  }
}
```

---

## 📊 جدول المقارنة السريع

| الخاصية | المندوبين (Representatives) | الموظفين - السائقين (Employees) |
|---------|---------------------------|--------------------------------|
| **API منفصل** | ✅ `/representative-api/*` | ❌ يستخدم Manager API |
| **API الإدارة** | `/api/representatives/*` | `/api/employees/*` |
| **المفتاح الأساسي** | `rep_id` | `emp_id` |
| **الحقل `job_role`** | ❌ **لا يوجد** | ✅ **موجود** ("سائق", "محاسب", إلخ) |
| **التسجيل الدخول** | `/representative-api/auth/login` | يستخدم Manager Auth |
| **الجدول في DB** | `representatives` | `employees` |
| **الموديل** | `App\Models\Representative` | `App\Models\Employee` |

---

## 🎯 ملخص القواعد الذهبية

### ✅ للمندوبين:
1. استخدم `/api/representatives` (للمدير)
2. استخدم `/representative-api/*` (للتطبيق الخاص بالمندوبين)
3. استخدم `rep_id` (وليس `emp_id`)
4. **لا يوجد** `job_role` ❌

### ✅ للموظفين (السائقين):
1. استخدم `/api/employees` (للمدير)
2. استخدم `emp_id` (وليس `rep_id`)
3. **يوجد** `job_role` = "سائق" ✅
4. فلتر السائقين: `employees.where((emp) => emp.job_role == "سائق")`

---

## 🐛 حل المشاكل الشائعة

### المشكلة: السائقين يظهرون في صفحة المندوبين

**السبب:** استخدام endpoint خاطئ في Frontend

**الحل:**
```dart
// ❌ خطأ
final response = await dio.get('/api/employees');  // هذا للموظفين!

// ✅ صحيح - في صفحة المندوبين
final response = await dio.get('/api/representatives');  // هذا للمندوبين
```

### المشكلة: البحث عن `job_role` في المندوبين

**السبب:** المندوبين ليس لديهم `job_role`

**الحل:**
- للمندوبين: استخدم `/api/representatives` مباشرة (لا تحتاج فلترة)
- للسائقين: استخدم `/api/employees` ثم فلتر `job_role == "سائق"`

### المشكلة: خلط البيانات في Frontend

**الحل:**
```dart
// افصل البيانات حسب النوع
class UserService {
  // للمندوبين
  Future<List<Representative>> getRepresentatives() async {
    final response = await dio.get('/api/representatives');
    return (response.data['data'] as List)
        .map((json) => Representative.fromJson(json))
        .toList();
  }
  
  // للموظفين - السائقين فقط
  Future<List<Employee>> getDrivers() async {
    final response = await dio.get('/api/employees');
    final allEmployees = (response.data['data'] as List)
        .map((json) => Employee.fromJson(json))
        .toList();
    // فلتر السائقين فقط
    return allEmployees.where((emp) => emp.jobRole == "سائق").toList();
  }
}
```

---

## 📝 أمثلة استخدام صحيحة

### مثال 1: جلب المندوبين في صفحة المندوبين
```dart
Future<List<Representative>> fetchRepresentatives() async {
  final response = await dio.get(
    '/api/representatives',
    options: Options(
      headers: {'Authorization': 'Bearer $token'},
    ),
  );
  
  return (response.data['data'] as List)
      .map((json) => Representative.fromJson(json))
      .toList();
}
```

### مثال 2: جلب السائقين فقط في صفحة السائقين
```dart
Future<List<Employee>> fetchDrivers() async {
  final response = await dio.get(
    '/api/employees',
    options: Options(
      headers: {'Authorization': 'Bearer $token'},
    ),
  );
  
  final allEmployees = (response.data['data'] as List)
      .map((json) => Employee.fromJson(json))
      .toList();
  
  // فلتر السائقين فقط
  return allEmployees.where((emp) => emp.jobRole == "سائق").toList();
}
```

### مثال 3: إنشاء مندوب جديد
```dart
Future<Representative> createRepresentative({
  required String fullName,
  required String phoneNumber,
  required String password,
}) async {
  final response = await dio.post(
    '/api/representatives',
    data: {
      'full_name': fullName,
      'phone_number': phoneNumber,
      'password': password,
      // ❌ لا ترسل job_role
    },
    options: Options(
      headers: {'Authorization': 'Bearer $token'},
    ),
  );
  
  return Representative.fromJson(response.data['data']);
}
```

### مثال 4: إنشاء موظف سائق جديد
```dart
Future<Employee> createDriver({
  required String fullName,
  required String phoneNumber,
  required String password,
}) async {
  final response = await dio.post(
    '/api/employees',
    data: {
      'full_name': fullName,
      'phone_number': phoneNumber,
      'password': password,
      'job_role': 'سائق',  // ✅ مطلوب!
    },
    options: Options(
      headers: {'Authorization': 'Bearer $token'},
    ),
  );
  
  return Employee.fromJson(response.data['data']);
}
```

---

## ✅ قائمة التحقق

عند العمل مع المندوبين:
- [ ] استخدام `/api/representatives` (وليس `/api/employees`)
- [ ] استخدام `rep_id` (وليس `emp_id`)
- [ ] عدم البحث عن `job_role`
- [ ] عدم إرسال `job_role` عند الإنشاء

عند العمل مع السائقين:
- [ ] استخدام `/api/employees` (وليس `/api/representatives`)
- [ ] استخدام `emp_id` (وليس `rep_id`)
- [ ] التحقق من `job_role == "سائق"`
- [ ] إرسال `job_role = "سائق"` عند الإنشاء

---

## 📚 مراجع

- **دوكمنت المندوبين:** `API_DOCUMENTATION_REPRESENTATIVE.md`
- **دوكمنت الموظفين:** `API_DOCUMENTATION_MANAGER_EMPLOYEE.md` (الأقسام 10-14)

---

**آخر تحديث:** 2026-01-15

