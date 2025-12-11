# Product Service - Microservice API Documentation

## Deskripsi
Product Service adalah microservice yang mengelola data produk dalam sistem. Service ini menyediakan REST API untuk operasi CRUD (Create, Read, Update, Delete) produk dengan validasi input, error handling, dan logging terstruktur dengan correlation ID.

## Fitur Utama
- CRUD Product (Create, Read, Update, Delete)
- Validasi Input dengan custom error messages
- Error Handling yang comprehensive
- Middleware Correlation ID untuk tracking
- JSON Structured Logging dengan correlation_id
- HTTP Status Code yang sesuai standard

---

## Setup & Running

### System Requirements
- PHP >= 8.2
- Laravel 11.x
- MySQL/MariaDB
- Composer

### Quick Start

```bash
cd product_service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8003
```

Server akan berjalan di: `http://localhost:8003`

---

## API Base URL
```
http://localhost:8003/api
```

---

## API Endpoints

### 1. GET /products - Retrieve All Products

**Description**: Mengambil daftar lengkap semua produk

**Method**: GET
**URL**: `/api/products`

**Request Headers**:
```
X-Correlation-ID: (optional - UUID untuk tracking)
Content-Type: application/json
```

**Request Example**:
```bash
curl -X GET "http://localhost:8003/api/products" \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json"
```

**Success Response (200 OK)**:
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Keyboard Mechanical",
      "price": 300000,
      "stock": 12,
      "description": "RGB Blue Switch",
      "created_at": "2025-12-10T05:29:57.000000Z",
      "updated_at": "2025-12-10T05:29:57.000000Z"
    },
    {
      "id": 3,
      "name": "edit Keyboard Mechanical",
      "price": 20000,
      "stock": 18,
      "description": "RGB Blue Switch",
      "created_at": "2025-12-10T05:36:57.000000Z",
      "updated_at": "2025-12-10T05:36:57.000000Z"
    },
    {
      "id": 4,
      "name": "Keyboard Mechanical",
      "price": 300000,
      "stock": 10,
      "description": "RGB Blue Switch",
      "created_at": "2025-12-10T05:37:20.000000Z",
      "updated_at": "2025-12-10T05:37:47.000000Z"
    },
    {
      "id": 5,
      "name": "Test Product",
      "price": 50000,
      "stock": 10,
      "description": "Test",
      "created_at": "2025-12-10T06:35:35.000000Z",
      "updated_at": "2025-12-10T06:35:35.000000Z"
    }
  ]
}
```

**Error Response (500 Server Error)**:
```json
{
  "success": false,
  "message": "Failed to retrieve products"
}
```

---

### 2. GET /products/{id} - Retrieve Single Product

**Description**: Mengambil detail satu produk berdasarkan ID

**Method**: GET
**URL**: `/api/products/{id}`

**URL Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID produk (harus > 0) |

**Request Example**:
```bash
curl -X GET "http://localhost:8003/api/products/1" \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json"
```

**Success Response (200 OK)**:
```json
{
  "success": true,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "Keyboard Mechanical",
    "price": 300000,
    "stock": 12,
    "description": "RGB Blue Switch",
    "created_at": "2025-12-10T05:29:57.000000Z",
    "updated_at": "2025-12-10T05:29:57.000000Z"
  }
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Error Response (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Invalid product ID format"
}
```

---

### 3. POST /products - Create Product

**Description**: Membuat produk baru

**Method**: POST
**URL**: `/api/products`

**Request Body**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Nama produk (max 255, unik) |
| price | numeric | Yes | Harga produk (min 0) |
| stock | integer | Yes | Jumlah stok (min 0) |
| description | string | No | Deskripsi produk |

**Request Example**:
```bash
curl -X POST "http://localhost:8003/api/products" \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Product",
    "price": 50000,
    "stock": 10,
    "description": "Test"
  }'
```

**Success Response (201 Created)**:
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 5,
    "name": "Test Product",
    "price": 50000,
    "stock": 10,
    "description": "Test",
    "created_at": "2025-12-10T06:35:35.000000Z",
    "updated_at": "2025-12-10T06:35:35.000000Z"
  }
}
```

**Error Response (422 Validation Error)**:
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "name": ["Nama produk wajib diisi"],
    "price": ["Harga produk wajib diisi"],
    "stock": ["Stok produk wajib diisi"]
  }
}
```

**Error Response (409 Duplicate Name)**:
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "name": ["Nama produk sudah digunakan"]
  }
}
```

**Validation Rules**:
| Field | Rule | Message |
|-------|------|---------|
| name | required, string, max:255, unique | Nama produk wajib diisi / sudah digunakan |
| price | required, numeric, min:0, max:999999999.99 | Harga produk wajib diisi |
| stock | required, integer, min:0 | Stok produk wajib diisi |

---

### 4. PUT /products/{id} - Update Product

**Description**: Mengubah data produk (partial update allowed)

**Method**: PUT
**URL**: `/api/products/{id}`

**URL Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID produk yang akan diubah |

**Request Body** (semua field optional):
| Field | Type | Description |
|-------|------|-------------|
| name | string | Nama produk (max 255, unik) |
| price | numeric | Harga produk (min 0) |
| stock | integer | Jumlah stok (min 0) |
| description | string | Deskripsi produk |

**Request Example**:
```bash
curl -X PUT "http://localhost:8003/api/products/3" \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "edit Keyboard Mechanical",
    "price": 20000,
    "stock": 18
  }'
```

**Success Response (200 OK)**:
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 3,
    "name": "edit Keyboard Mechanical",
    "price": 20000,
    "stock": 18,
    "description": "RGB Blue Switch",
    "created_at": "2025-12-10T05:36:57.000000Z",
    "updated_at": "2025-12-10T05:36:57.000000Z"
  }
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Error Response (422 Validation Error)**:
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "price": ["Harga harus berupa angka"]
  }
}
```

---

### 5. DELETE /products/{id} - Delete Product

**Description**: Menghapus produk dari database

**Method**: DELETE
**URL**: `/api/products/{id}`

**URL Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID produk yang akan dihapus |

**Request Example**:
```bash
curl -X DELETE "http://localhost:8003/api/products/1" \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json"
```

**✅ Response (200 OK)**:
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Error Response (409 Conflict)**:
```json
{
  "success": false,
  "message": "Cannot delete product because it is referenced by other records"
}
```

---

## HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful, data returned |
| 201 | Created | Resource successfully created |
| 400 | Bad Request | Invalid request (ID format, etc) |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Constraint violation (duplicate, FK reference) |
| 422 | Unprocessable Entity | Validation error |
| 500 | Server Error | Internal server error |

---

## Correlation ID untuk Tracking

Setiap request dapat include header X-Correlation-ID untuk distributed tracing across microservices.

**Features**:
- Generate UUID otomatis jika tidak ada
- Include di response header yang sama
- Di-log dalam JSON format untuk audit trail

**Example**:
```bash
# Request dengan custom Correlation ID
curl -X GET "http://localhost:8003/api/products" \
  -H "X-Correlation-ID: my-custom-id-123" \
  -H "Content-Type: application/json"

# Response akan include header yang sama
X-Correlation-ID: my-custom-id-123
```

**Log Output** (JSON format):
```json
{
  "message": "Get Product List",
  "context": {
    "correlation_id": "my-custom-id-123"
  },
  "datetime": "2025-12-10T06:35:15.003670+00:00"
}
```

---

## Structured JSON Logging

Semua logs disimpan dalam format JSON di `storage/logs/laravel.log`.

**Log Entry Example**:
```json
{
  "message": "Product Created",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "id": 5,
    "name": "Test Product"
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "local",
  "datetime": "2025-12-10T06:35:35.190287+00:00"
}
```

**Log Levels**:
- INFO (200): Operasi berhasil
- WARNING (300): Validasi gagal
- ERROR (400): Database error / exceptions

---

## Integration dengan API Gateway

**JavaScript Example**:
```javascript
const correlationId = generateUUID();

const response = await fetch('http://localhost:8003/api/products', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'X-Correlation-ID': correlationId
  }
});

// Response header akan berisi correlation ID yang sama
const returnedId = response.headers.get('X-Correlation-ID');
console.log(returnedId);
```

---

## Database Schema

```sql
CREATE TABLE products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  price DECIMAL(15,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  description LONGTEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
```

---

## Security Features

- Input validation pada semua endpoint
- SQL injection prevention (Eloquent ORM)
- No sensitive data dalam error responses di production
- Structured logging untuk audit trail
- Proper HTTP status codes

---

## Troubleshooting

**Server tidak berjalan**:
```bash
php artisan serve --port=8003
```

**Database error**:
```bash
php artisan migrate --refresh
```

**Debug request**:
Cek `storage/logs/laravel.log` untuk detail error. Search dengan `correlation_id` untuk track request flow.


