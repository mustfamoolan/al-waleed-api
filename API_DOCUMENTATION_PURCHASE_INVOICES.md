# API Documentation - Purchase Invoices & Inventory System

## نظرة عامة

هذا الـ API مخصص لإدارة فواتير الشراء والمخزون مع نظام توزيع تكاليف النقل:
- إدارة فواتير الشراء (Purchase Invoices)
- توزيع كراء السائق والعمال على المواد
- حساب سعر التكلفة بعد الشراء
- ربط الفواتير بالمنتجات والمخزون
- تتبع آخر شراء لكل مادة

## Base URL

```
https://maktabalwaleed.com/api
```

## Authentication

جميع الـ endpoints تحتاج إلى:
- Token صالح (Manager authentication)
- Header: `Authorization: Bearer {token}`

---

## 1. Purchase Invoices Management

### 1.1 قائمة فواتير الشراء

**GET** `/api/purchase-invoices`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `supplier_id` (integer) - فلترة حسب المورد
- `status` (string) - فلترة حسب الحالة: `draft`, `pending`, `paid`, `partial`, `returned`, `cancelled`
- `from_date` (date) - تاريخ البداية
- `to_date` (date) - تاريخ النهاية
- `search` (string) - بحث في رقم الفاتورة أو اسم المورد

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "invoice_id": 1,
      "supplier_id": 1,
      "supplier": {
        "supplier_id": 1,
        "company_name": "شركة المورد الأول"
      },
      "invoice_number": "PUR-2026-0001",
      "invoice_date": "2026-01-15",
      "due_date": "2026-02-15",
      "subtotal": 10000.00,
      "tax_amount": 500.00,
      "discount_amount": 200.00,
      "total_amount": 10300.00,
      "paid_amount": 5000.00,
      "remaining_amount": 5300.00,
      "driver_cost": 200.00,
      "worker_cost": 300.00,
      "total_transport_cost": 500.00,
      "status": "partial",
      "notes": "ملاحظات",
      "items": [
        {
          "item_id": 1,
          "product_id": 1,
          "product": {
            "product_id": 1,
            "product_name": "جهاز كمبيوتر محمول",
            "sku": "LAPTOP-001"
          },
          "product_name": "جهاز كمبيوتر محمول",
          "product_code": "LAPTOP-001",
          "category_name": "أجهزة إلكترونية",
          "quantity": 5,
          "unit_price": 2000.00,
          "cost_after_purchase": 2100.00,
          "transport_cost_share": 100.00,
          "retail_price": 2500.00,
          "wholesale_price": 2200.00,
          "discount_percentage": 0,
          "tax_percentage": 5,
          "total_price": 10500.00,
          "notes": null
        }
      ],
      "created_by": 1,
      "created_at": "2026-01-15T10:00:00.000000Z",
      "updated_at": "2026-01-15T10:00:00.000000Z"
    }
  ]
}
```

### 1.2 إضافة فاتورة شراء

**POST** `/api/purchase-invoices`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "supplier_id": 1,
  "invoice_number": "PUR-2026-0001",
  "invoice_date": "2026-01-15",
  "due_date": "2026-02-15",
  "subtotal": 10000.00,
  "tax_amount": 500.00,
  "discount_amount": 200.00,
  "total_amount": 10300.00,
  "driver_cost": 200.00,
  "worker_cost": 300.00,
  "notes": "ملاحظات",
  "items": [
    {
      "product_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "product_code": "LAPTOP-001",
      "quantity": 5,
      "unit_price": 2000.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    },
    {
      "product_id": 2,
      "product_name": "طابعة",
      "product_code": "PRINT-001",
      "quantity": 3,
      "unit_price": 1000.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    }
  ]
}
```

**ملاحظات مهمة:**
- `driver_cost` و `worker_cost` اختياريان (يمكن أن يكونا 0)
- عند إضافة الفاتورة، يتم حساب `cost_after_purchase` و `transport_cost_share` تلقائياً
- توزيع الكراوي: مجموع الكراوي (driver_cost + worker_cost) يقسم على عدد الكراتين الإجمالي
- لكل مادة: `transport_cost_share = quantity × cost_per_carton`
- `cost_after_purchase = unit_price + cost_per_carton`
- إذا تم إرسال `product_id`، يتم جلب `retail_price`, `wholesale_price`, و `category_name` تلقائياً

**مثال على التوزيع:**
```
إجمالي الكراتين = 5 + 3 = 8 كراتين
مجموع الكراوي = 200 + 300 = 500
نصيب كل كرتون = 500 / 8 = 62.5

المادة 1 (5 كراتين):
  transport_cost_share = 5 × 62.5 = 312.5
  cost_after_purchase = 2000 + 62.5 = 2062.5

المادة 2 (3 كراتين):
  transport_cost_share = 3 × 62.5 = 187.5
  cost_after_purchase = 1000 + 62.5 = 1062.5
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice created successfully",
  "data": {
    "invoice_id": 1,
    "supplier_id": 1,
    "invoice_number": "PUR-2026-0001",
    "invoice_date": "2026-01-15",
    "driver_cost": 200.00,
    "worker_cost": 300.00,
    "total_transport_cost": 500.00,
    "status": "draft",
    "items": [
      {
        "item_id": 1,
        "product_id": 1,
        "quantity": 5,
        "unit_price": 2000.00,
        "cost_after_purchase": 2062.50,
        "transport_cost_share": 312.50,
        "retail_price": 2500.00,
        "wholesale_price": 2200.00,
        "category_name": "أجهزة إلكترونية"
      }
    ]
  }
}
```

### 1.3 عرض فاتورة شراء

**GET** `/api/purchase-invoices/{id}`

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "invoice_id": 1,
    "supplier_id": 1,
    "invoice_number": "PUR-2026-0001",
    "invoice_date": "2026-01-15",
    "driver_cost": 200.00,
    "worker_cost": 300.00,
    "total_transport_cost": 500.00,
    "status": "draft",
    "items": [...]
  }
}
```

### 1.4 تحديث فاتورة شراء

**PATCH** `/api/purchase-invoices/{id}`

**ملاحظات:**
- يمكن تحديث الفواتير في حالة `draft` فقط
- عند تحديث `driver_cost` أو `worker_cost` أو `items`، يتم إعادة حساب التوزيع تلقائياً

**Request Body:**
```json
{
  "driver_cost": 250.00,
  "worker_cost": 350.00,
  "items": [
    {
      "product_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "quantity": 6,
      "unit_price": 2100.00
    }
  ]
}
```

### 1.5 حذف فاتورة شراء

**DELETE** `/api/purchase-invoices/{id}`

**ملاحظات:**
- يمكن حذف الفواتير في حالة `draft` فقط

### 1.6 نسخ فاتورة شراء

**POST** `/api/purchase-invoices/{id}/duplicate`

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice duplicated successfully",
  "data": {
    "invoice_id": 2,
    "invoice_number": "PUR-2026-0002",
    "invoice_date": "2026-01-15",
    "status": "draft",
    ...
  }
}
```

### 1.7 تأكيد فاتورة شراء (Post)

**POST** `/api/purchase-invoices/{id}/post`

**ملاحظات:**
- تغيير حالة الفاتورة من `draft` إلى `pending`
- عند التأكيد:
  - يتم تحديث المخزون للمنتجات المرتبطة
  - يتم إنشاء `InventoryMovement` لكل مادة
  - يتم تحديث `last_purchase_date` للمنتج
  - يتم إعادة حساب `cost_after_purchase` و `transport_cost_share`
  - يتم حفظ `retail_price`, `wholesale_price`, `category_name` وقت الشراء

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase invoice posted successfully",
  "data": {
    "invoice_id": 1,
    "status": "pending",
    "items": [
      {
        "item_id": 1,
        "product_id": 1,
        "cost_after_purchase": 2062.50,
        "transport_cost_share": 312.50,
        "inventory_movement_id": 1
      }
    ]
  }
}
```

---

## 2. توزيع تكاليف النقل (Transport Cost Distribution)

### 2.1 كيفية التوزيع

عند إضافة أو تحديث فاتورة شراء، يتم توزيع كراء السائق والعمال على المواد كالتالي:

1. **حساب عدد الكراتين الإجمالي:**
   ```
   total_cartons = sum(quantity لكل مادة)
   ```

2. **حساب نصيب كل كرتون:**
   ```
   cost_per_carton = (driver_cost + worker_cost) / total_cartons
   ```

3. **لكل مادة في الفاتورة:**
   ```
   transport_cost_share = quantity × cost_per_carton
   cost_after_purchase = unit_price + cost_per_carton
   ```

### 2.2 مثال عملي

**البيانات:**
- المادة 1: 5 كراتين × 2000 = 10000
- المادة 2: 3 كراتين × 1000 = 3000
- كراء السائق: 200
- كراء العمال: 300

**الحساب:**
```
إجمالي الكراتين = 5 + 3 = 8
مجموع الكراوي = 200 + 300 = 500
نصيب كل كرتون = 500 / 8 = 62.5

المادة 1:
  transport_cost_share = 5 × 62.5 = 312.5
  cost_after_purchase = 2000 + 62.5 = 2062.5

المادة 2:
  transport_cost_share = 3 × 62.5 = 187.5
  cost_after_purchase = 1000 + 62.5 = 1062.5
```

---

## 3. تتبع آخر شراء

### 3.1 معلومات آخر شراء في الفاتورة

كل مادة في الفاتورة تحتوي على:
- `cost_after_purchase`: سعر التكلفة بعد إضافة نصيب الكراوي
- `transport_cost_share`: نصيب المادة من الكراوي
- `retail_price`: سعر البيع المفرد وقت الشراء
- `wholesale_price`: سعر البيع الجملة وقت الشراء
- `category_name`: اسم الفئة وقت الشراء

### 3.2 تحديث المنتج

عند تأكيد الفاتورة (Post):
- يتم تحديث `last_purchase_date` للمنتج
- يتم تحديث `purchase_price` للمنتج
- يتم تحديث `current_stock` للمنتج

---

## 4. Flutter/Dart Examples

### 4.1 إنشاء فاتورة شراء

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> createPurchaseInvoice({
  required String token,
  required int supplierId,
  required String invoiceNumber,
  required String invoiceDate,
  required double subtotal,
  required double totalAmount,
  double? driverCost,
  double? workerCost,
  required List<Map<String, dynamic>> items,
}) async {
  final url = Uri.parse('https://maktabalwaleed.com/api/purchase-invoices');
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'supplier_id': supplierId,
      'invoice_number': invoiceNumber,
      'invoice_date': invoiceDate,
      'subtotal': subtotal,
      'total_amount': totalAmount,
      'driver_cost': driverCost ?? 0,
      'worker_cost': workerCost ?? 0,
      'items': items,
    }),
  );

  if (response.statusCode == 201) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to create purchase invoice');
  }
}

// مثال على الاستخدام
final invoice = await createPurchaseInvoice(
  token: 'your_token',
  supplierId: 1,
  invoiceNumber: 'PUR-2026-0001',
  invoiceDate: '2026-01-15',
  subtotal: 10000.00,
  totalAmount: 10300.00,
  driverCost: 200.00,
  workerCost: 300.00,
  items: [
    {
      'product_id': 1,
      'product_name': 'جهاز كمبيوتر محمول',
      'product_code': 'LAPTOP-001',
      'quantity': 5,
      'unit_price': 2000.00,
    },
    {
      'product_id': 2,
      'product_name': 'طابعة',
      'product_code': 'PRINT-001',
      'quantity': 3,
      'unit_price': 1000.00,
    },
  ],
);
```

### 4.2 تأكيد فاتورة شراء

```dart
Future<Map<String, dynamic>> postPurchaseInvoice({
  required String token,
  required int invoiceId,
}) async {
  final url = Uri.parse(
    'https://maktabalwaleed.com/api/purchase-invoices/$invoiceId/post'
  );
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to post purchase invoice');
  }
}
```

### 4.3 قائمة فواتير الشراء

```dart
Future<List<Map<String, dynamic>>> getPurchaseInvoices({
  required String token,
  int? supplierId,
  String? status,
  String? fromDate,
  String? toDate,
}) async {
  final queryParams = <String, String>{};
  if (supplierId != null) queryParams['supplier_id'] = supplierId.toString();
  if (status != null) queryParams['status'] = status;
  if (fromDate != null) queryParams['from_date'] = fromDate;
  if (toDate != null) queryParams['to_date'] = toDate;

  final uri = Uri.parse('https://maktabalwaleed.com/api/purchase-invoices')
      .replace(queryParameters: queryParams);
  
  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return List<Map<String, dynamic>>.from(data['data']);
  } else {
    throw Exception('Failed to get purchase invoices');
  }
}
```

---

## 5. cURL Examples

### 5.1 إنشاء فاتورة شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-invoices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "supplier_id": 1,
    "invoice_number": "PUR-2026-0001",
    "invoice_date": "2026-01-15",
    "due_date": "2026-02-15",
    "subtotal": 10000.00,
    "tax_amount": 500.00,
    "discount_amount": 200.00,
    "total_amount": 10300.00,
    "driver_cost": 200.00,
    "worker_cost": 300.00,
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "product_code": "LAPTOP-001",
        "quantity": 5,
        "unit_price": 2000.00,
        "discount_percentage": 0,
        "tax_percentage": 5
      }
    ]
  }'
```

### 5.2 تأكيد فاتورة شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-invoices/1/post \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 5.3 قائمة فواتير الشراء

```bash
curl -X GET "https://maktabalwaleed.com/api/purchase-invoices?supplier_id=1&status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## 6. Error Responses

### 6.1 Validation Error (422)

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "supplier_id": ["The supplier id field is required."],
    "items": ["The items field is required."]
  }
}
```

### 6.2 Unauthorized (401)

```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

### 6.3 Not Found (404)

```json
{
  "status": "error",
  "message": "Purchase invoice not found"
}
```

### 6.4 Cannot Update/Delete (422)

```json
{
  "status": "error",
  "message": "Only draft invoices can be updated"
}
```

---

## 7. Important Notes

1. **حالة الفاتورة:**
   - `draft`: مسودة - يمكن التعديل والحذف
   - `pending`: معلقة - تم التأكيد، في انتظار الدفع
   - `paid`: مدفوعة - تم دفع المبلغ بالكامل
   - `partial`: مدفوعة جزئياً - تم دفع جزء من المبلغ
   - `returned`: مرتجعة
   - `cancelled`: ملغاة

2. **توزيع الكراوي:**
   - يتم التوزيع تلقائياً عند إنشاء أو تحديث الفاتورة
   - التوزيع يتم بالتساوي على كل كرتون
   - `cost_after_purchase` = سعر الشراء + نصيب الكرتون من الكراوي

3. **ربط المنتجات:**
   - يُفضل إرسال `product_id` عند إضافة المواد
   - إذا تم إرسال `product_id`، يتم جلب الأسعار والفئة تلقائياً
   - عند التأكيد (Post)، يتم تحديث المخزون تلقائياً

4. **تتبع آخر شراء:**
   - يتم حفظ جميع تفاصيل الشراء في `purchase_invoice_items`
   - يتم تحديث `last_purchase_date` في المنتج عند التأكيد
   - يمكن تتبع جميع المشتريات من خلال `InventoryMovement`

---

## 8. Integration with Products System

عند تأكيد فاتورة الشراء (Post):
1. يتم البحث عن المنتج بواسطة `product_id` أو `product_code` أو `product_name`
2. يتم تحديث `current_stock` للمنتج
3. يتم تحديث `purchase_price` و `last_purchase_date` للمنتج
4. يتم إنشاء `InventoryMovement` لتسجيل الحركة
5. يتم ربط `PurchaseInvoiceItem` بالمنتج والحركة

---

## 9. Reports & Analytics

يمكن استخدام endpoints التقارير الموجودة في:
- `/api/reports/suppliers/{supplier_id}/purchases` - تقرير مشتريات المورد
- `/api/reports/products/{product_id}/purchases` - تقرير مشتريات المنتج
- `/api/inventory-movements` - حركات المخزون

---

## 10. Support

للمساعدة أو الاستفسارات، يرجى التواصل مع فريق التطوير.

