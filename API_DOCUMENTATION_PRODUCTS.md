# API Documentation - Products & Inventory System

## نظرة عامة

هذا الـ API مخصص لإدارة المنتجات والمخزون مع تتبع المبيعات والربح:
- إدارة الفئات (Categories)
- إدارة المنتجات (Products) مع المخزون
- تتبع حركات المخزون (Inventory Movements)
- سجل المبيعات (Product Sales)
- التقارير والتحليلات

## Base URL

```
https://maktabalwaleed.com/api
```

## Authentication

جميع الـ endpoints تحتاج إلى:
- Token صالح (Manager authentication)
- Header: `Authorization: Bearer {token}`

---

## 1. Categories Management

### 1.1 قائمة الفئات

**GET** `/api/categories`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `is_active` (boolean) - فلترة حسب الحالة
- `search` (string) - بحث في اسم الفئة
- `with_products_count` (boolean) - إضافة عدد المنتجات

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "category_id": 1,
      "category_name": "أجهزة إلكترونية",
      "description": "جميع الأجهزة الإلكترونية",
      "is_active": true,
      "products_count": 25,
      "created_at": "2026-01-13T10:00:00.000000Z",
      "updated_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

### 1.2 إضافة فئة

**POST** `/api/categories`

**Body:**
```json
{
  "category_name": "أجهزة إلكترونية",
  "description": "جميع الأجهزة الإلكترونية",
  "is_active": true
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Category created successfully",
  "data": {
    "category_id": 1,
    "category_name": "أجهزة إلكترونية",
    "description": "جميع الأجهزة الإلكترونية",
    "is_active": true,
    "created_at": "2026-01-13T10:00:00.000000Z",
    "updated_at": "2026-01-13T10:00:00.000000Z"
  }
}
```

### 1.3 عرض فئة

**GET** `/api/categories/{id}`

**Query Parameters:**
- `with_products` (boolean) - إضافة المنتجات

### 1.4 تحديث فئة

**PATCH** `/api/categories/{id}`

**Body:**
```json
{
  "category_name": "أجهزة إلكترونية محدثة",
  "description": "وصف محدث"
}
```

### 1.5 حذف فئة

**DELETE** `/api/categories/{id}`

**Note:** لا يمكن حذف فئة تحتوي على منتجات

### 1.6 منتجات الفئة

**GET** `/api/categories/{id}/products`

---

## 2. Products Management

### 2.1 قائمة المنتجات

**GET** `/api/products`

**Query Parameters:**
- `category_id` (integer) - فلترة حسب الفئة
- `supplier_id` (integer) - فلترة حسب المورد
- `is_active` (boolean) - فلترة حسب الحالة
- `low_stock` (boolean) - منتجات مخزون منخفض
- `low_stock_threshold` (integer) - حد المخزون المنخفض (افتراضي: 10)
- `search` (string) - بحث في الاسم أو SKU
- `sort_by` (string) - ترتيب حسب (افتراضي: product_name)
- `sort_order` (string) - ترتيب (asc/desc)
- `per_page` (integer) - عدد النتائج في الصفحة (افتراضي: 15)

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "product_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "sku": "LAPTOP-001",
      "product_image": "products/laptop.jpg",
      "product_image_url": "https://maktabalwaleed.com/storage/products/laptop.jpg",
      "category": {
        "category_id": 1,
        "category_name": "أجهزة إلكترونية"
      },
      "category_id": 1,
      "supplier": {
        "supplier_id": 1,
        "company_name": "شركة المورد الأول"
      },
      "supplier_id": 1,
      "unit_type": "piece",
      "pieces_per_carton": null,
      "piece_weight": 2.5,
      "weight_unit": "kg",
      "carton_weight": null,
      "current_stock": 50,
      "purchase_price": 1000.00,
      "wholesale_price": 1200.00,
      "retail_price": 1500.00,
      "last_purchase_date": "2026-01-10",
      "last_sale_date": "2026-01-12",
      "is_active": true,
      "notes": "ملاحظات",
      "is_low_stock": false,
      "created_at": "2026-01-13T10:00:00.000000Z",
      "updated_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

### 2.2 إضافة منتج

**POST** `/api/products`

**Body:**
```json
{
  "product_name": "جهاز كمبيوتر محمول",
  "sku": "LAPTOP-001",
  "category_id": 1,
  "supplier_id": 1,
  "unit_type": "carton",
  "pieces_per_carton": 24,
  "piece_weight": 500,
  "weight_unit": "gram",
  "current_stock": 10,
  "purchase_price": 1000.00,
  "wholesale_price": 1200.00,
  "retail_price": 1500.00,
  "last_purchase_date": "2026-01-10",
  "is_active": true,
  "notes": "ملاحظات"
}
```

**ملاحظات مهمة:**
- **`sku`** (اختياري): كود المنتج - يجب أن يكون فريداً إذا تم إدخاله، ويمكن تصويره من الكاميرا (Barcode Scanner)
- **`unit_type`**: يجب أن يكون `carton` دائماً (التعامل فقط بالكارتون)
- **`pieces_per_carton`** (مطلوب): عدد القطع في الكارتون الواحد
- **`piece_weight`** (مطلوب): وزن/حجم القطعة الواحدة (غرام أو ملم)
- **`weight_unit`**: وحدة قياس وزن/حجم القطعة الواحدة - يمكن أن يكون: `gram` (للوزن) أو `ml` (للسوائل)
- **`carton_weight`**: يُحسب تلقائياً من (pieces_per_carton × piece_weight) ويعرض الوزن/الحجم الكامل للكارتون
- **`current_stock`** (اختياري): الكمية المتوفرة عند إنشاء المنتج - إذا لم يتم إدخاله، القيمة الافتراضية `0`
- **`last_sale_date`**: يتم تحديثه تلقائياً عند البيع

**مثال على حساب الوزن:**
- كارتون يحتوي على 24 قطعة
- وزن/حجم القطعة الواحدة: 500 غرام (أو 500 ملم)
- `carton_weight` التلقائي = 24 × 500 = 12000 غرام (أو 12000 ملم)

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Product created successfully",
  "data": {
    "product_id": 1,
    "product_name": "جهاز كمبيوتر محمول",
    "sku": "LAPTOP-001",
    ...
  }
}
```

### 2.3 عرض منتج

**GET** `/api/products/{id}`

### 2.4 تحديث منتج

**PATCH** `/api/products/{id}`

### 2.5 حذف منتج

**DELETE** `/api/products/{id}`

**Note:** لا يمكن حذف منتج له حركات مخزون

### 2.6 رفع صورة المنتج

**POST** `/api/products/{id}/upload-image`

**Content-Type:** `multipart/form-data`

**Body:**
```
product_image: (file) - صورة المنتج (max: 2MB)
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "product_image": "products/laptop.jpg",
    "product_image_url": "https://maktabalwaleed.com/storage/products/laptop.jpg"
  }
}
```

### 2.7 حالة المخزون

**GET** `/api/products/{id}/stock`

**Response:**
```json
{
  "status": "success",
  "data": {
    "product_id": 1,
    "product_name": "جهاز كمبيوتر محمول",
    "current_stock": 50,
    "is_low_stock": false,
    "last_movement": {
      "movement_id": 10,
      "movement_type": "purchase",
      "quantity": 20,
      "created_at": "2026-01-13T10:00:00.000000Z"
    }
  }
}
```

### 2.8 حركات المخزون

**GET** `/api/products/{id}/movements`

**Query Parameters:**
- `per_page` (integer) - عدد النتائج في الصفحة

### 2.9 سجل المبيعات

**GET** `/api/products/{id}/sales`

**Query Parameters:**
- `per_page` (integer) - عدد النتائج في الصفحة

### 2.10 حساب الربح

**GET** `/api/products/{id}/profit`

**Query Parameters:**
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية

**Response:**
```json
{
  "status": "success",
  "data": {
    "product_id": 1,
    "product_name": "جهاز كمبيوتر محمول",
    "total_profit": 5000.00,
    "total_sales_quantity": 10,
    "average_profit": 500.00,
    "sales_count": 10
  }
}
```

### 2.11 تعديل المخزون يدوياً

**POST** `/api/products/{id}/adjust-stock`

**Body:**
```json
{
  "quantity": 10,
  "movement_type": "adjustment",
  "notes": "تعديل يدوي"
}
```

**ملاحظات:**
- `quantity` موجب لإضافة، سالب لخصم
- `movement_type`: `adjustment` أو `transfer`

### 2.12 منتجات مخزون منخفض

**GET** `/api/products/low-stock`

**Query Parameters:**
- `threshold` (integer) - حد المخزون (افتراضي: 10)

### 2.13 تقرير المخزون

**GET** `/api/products/stock-report`

**Query Parameters:**
- `category_id` (integer) - فلترة حسب الفئة
- `supplier_id` (integer) - فلترة حسب المورد

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_products": 100,
    "total_stock_value": 500000.00,
    "low_stock_count": 15,
    "out_of_stock_count": 5
  }
}
```

### 2.14 تقرير الربح

**GET** `/api/products/profit-report`

**Query Parameters:**
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية

### 2.15 تقرير المبيعات

**GET** `/api/products/sales-report`

**Query Parameters:**
- `product_id` (integer) - فلترة حسب المنتج
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية

---

## 3. Inventory Movements

### 3.1 قائمة حركات المخزون

**GET** `/api/inventory-movements`

**Query Parameters:**
- `product_id` (integer) - فلترة حسب المنتج
- `movement_type` (string) - فلترة حسب نوع الحركة (purchase, return, sale, adjustment, transfer)
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية
- `per_page` (integer) - عدد النتائج في الصفحة

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "movement_id": 1,
      "product": {
        "product_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "sku": "LAPTOP-001"
      },
      "product_id": 1,
      "movement_type": "purchase",
      "movement_type_label": "شراء",
      "reference_type": "purchase_invoice",
      "reference_id": 5,
      "quantity": 20,
      "stock_before": 30,
      "stock_after": 50,
      "unit_price": 1000.00,
      "notes": "From invoice: PUR-2026-0005",
      "creator": {
        "manager_id": 1,
        "full_name": "أحمد محمد"
      },
      "created_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

---

## 4. Product Sales

### 4.1 قائمة المبيعات

**GET** `/api/product-sales`

**Query Parameters:**
- `product_id` (integer) - فلترة حسب المنتج
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية
- `per_page` (integer) - عدد النتائج في الصفحة

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "sale_id": 1,
      "product": {
        "product_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "sku": "LAPTOP-001"
      },
      "product_id": 1,
      "sale_invoice_id": null,
      "sale_date": "2026-01-13",
      "quantity": 2,
      "unit_price": 1500.00,
      "total_price": 3000.00,
      "purchase_price_at_sale": 1000.00,
      "profit_amount": 1000.00,
      "profit_percentage": 50.00,
      "notes": "بيع مباشر",
      "creator": {
        "manager_id": 1,
        "full_name": "أحمد محمد"
      },
      "created_at": "2026-01-13T10:00:00.000000Z",
      "updated_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

### 4.2 تسجيل مبيعة

**POST** `/api/product-sales`

**Body:**
```json
{
  "product_id": 1,
  "sale_date": "2026-01-13",
  "quantity": 2,
  "unit_price": 1500.00,
  "notes": "بيع مباشر"
}
```

**ملاحظات:**
- يتم التحقق من توفر المخزون تلقائياً
- يتم تحديث المخزون تلقائياً
- يتم حساب الربح تلقائياً بناءً على سعر الشراء الحالي

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Product sale recorded successfully",
  "data": {
    "sale_id": 1,
    "product_id": 1,
    "sale_date": "2026-01-13",
    "quantity": 2,
    "unit_price": 1500.00,
    "total_price": 3000.00,
    "purchase_price_at_sale": 1000.00,
    "profit_amount": 1000.00,
    "profit_percentage": 50.00,
    ...
  }
}
```

### 4.3 عرض مبيعة

**GET** `/api/product-sales/{id}`

### 4.4 تقرير الربح

**GET** `/api/product-sales/profit-report`

**Query Parameters:**
- `product_id` (integer) - فلترة حسب المنتج
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_profit": 50000.00,
    "total_sales_quantity": 100,
    "total_revenue": 150000.00,
    "sales_count": 50,
    "average_profit": 1000.00,
    "profit_margin": 33.33
  }
}
```

---

## 5. التكامل مع فواتير الشراء

عند تأكيد فاتورة شراء (Post Invoice):

1. يتم البحث عن المنتج بالـ SKU أولاً
2. إذا لم يُوجد، يتم البحث بالاسم والمورد
3. إذا وُجد المنتج:
   - يتم ربط عنصر الفاتورة بالمنتج
   - يتم إضافة الكمية للمخزون
   - يتم إنشاء حركة مخزون (type: purchase)
   - يتم تحديث `last_purchase_date`
   - يتم تحديث `purchase_price` إذا تغير

**مثال:**
```json
{
  "invoice_id": 5,
  "items": [
    {
      "product_code": "LAPTOP-001",
      "product_name": "جهاز كمبيوتر محمول",
      "quantity": 20,
      "unit_price": 1000.00
    }
  ]
}
```

عند تأكيد الفاتورة، سيتم:
- البحث عن المنتج بـ SKU: "LAPTOP-001"
- إضافة 20 قطعة للمخزون
- إنشاء حركة مخزون
- تحديث سعر الشراء

---

## 6. التكامل مع فواتير المرتجع

عند تأكيد فاتورة مرتجع (Post Return):

1. يتم البحث عن المنتج بالـ SKU أو الاسم
2. إذا وُجد المنتج:
   - يتم ربط عنصر المرتجع بالمنتج
   - يتم خصم الكمية من المخزون
   - يتم إنشاء حركة مخزون (type: return)

---

## 7. أمثلة Flutter/Dart

### 7.1 إضافة منتج

```dart
import 'package:dio/dio.dart';

Future<void> createProduct() async {
  final dio = Dio();
  dio.options.headers['Authorization'] = 'Bearer $token';
  
  final response = await dio.post(
    'https://maktabalwaleed.com/api/products',
    data: {
      'product_name': 'جهاز كمبيوتر محمول',
      'sku': 'LAPTOP-001', // اختياري
      'category_id': 1,
      'supplier_id': 1,
      'unit_type': 'carton',
      'pieces_per_carton': 24,
      'piece_weight': 500,
      'weight_unit': 'gram',
      'current_stock': 10, // اختياري - القيمة الافتراضية: 0
      'purchase_price': 1000.00,
      'wholesale_price': 1200.00,
      'retail_price': 1500.00,
      'is_active': true,
    },
  );
  
  print(response.data);
}
```

### 7.2 رفع صورة المنتج

```dart
import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

Future<void> uploadProductImage(int productId) async {
  final dio = Dio();
  dio.options.headers['Authorization'] = 'Bearer $token';
  
  final picker = ImagePicker();
  final image = await picker.pickImage(source: ImageSource.camera);
  
  if (image != null) {
    final formData = FormData.fromMap({
      'product_image': await MultipartFile.fromFile(
        image.path,
        filename: 'product.jpg',
      ),
    });
    
    final response = await dio.post(
      'https://maktabalwaleed.com/api/products/$productId/upload-image',
      data: formData,
    );
    
    print(response.data);
  }
}
```

### 7.3 تسجيل مبيعة

```dart
Future<void> recordSale() async {
  final dio = Dio();
  dio.options.headers['Authorization'] = 'Bearer $token';
  
  final response = await dio.post(
    'https://maktabalwaleed.com/api/product-sales',
    data: {
      'product_id': 1,
      'sale_date': '2026-01-13',
      'quantity': 2,
      'unit_price': 1500.00,
      'notes': 'بيع مباشر',
    },
  );
  
  print(response.data);
}
```

### 7.4 قائمة المنتجات مع فلترة

```dart
Future<void> getProducts() async {
  final dio = Dio();
  dio.options.headers['Authorization'] = 'Bearer $token';
  
  final response = await dio.get(
    'https://maktabalwaleed.com/api/products',
    queryParameters: {
      'category_id': 1,
      'is_active': true,
      'low_stock': false,
      'search': 'laptop',
      'sort_by': 'product_name',
      'sort_order': 'asc',
      'per_page': 15,
    },
  );
  
  print(response.data);
}
```

---

## 8. Error Responses

### 8.1 Validation Error (422)

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "sku": ["The sku has already been taken."],
    "purchase_price": ["The purchase price field is required."],
    "pieces_per_carton": ["The pieces per carton field is required when unit type is carton."]
  }
}
```

### 8.2 Insufficient Stock (422)

```json
{
  "status": "error",
  "message": "Insufficient stock available"
}
```

### 8.3 Not Found (404)

```json
{
  "status": "error",
  "message": "Product not found"
}
```

### 8.4 Unauthorized (401)

```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

---

## 9. ملاحظات مهمة

1. **SKU:** اختياري - يجب أن يكون فريداً إذا تم إدخاله، ويمكن تصويره من الكاميرا (Barcode Scanner)
2. **current_stock:** يمكن إدخال الكمية المتوفرة عند إنشاء المنتج (اختياري - القيمة الافتراضية: 0)
3. **المخزون:** يتم تحديثه تلقائياً من فواتير الشراء والمرتجعات والمبيعات، ويمكن إدخال الكمية الأولية عند إنشاء المنتج
4. **الربح:** يُحسب عند البيع بناءً على سعر الشراء وقت الشراء
5. **الوزن:** وزن الكارتون يُحسب تلقائياً من (عدد القطع × وزن القطعة الواحدة)
6. **حركات المخزون:** يتم إنشاؤها تلقائياً عند:
   - تأكيد فاتورة شراء
   - تأكيد فاتورة مرتجع
   - تسجيل مبيعة
   - تعديل المخزون يدوياً

---

## 10. Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

**تم إنشاء التوثيق بنجاح!** ✅

