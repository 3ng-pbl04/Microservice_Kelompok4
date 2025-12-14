# Product Service - Microservice API Documentation

## Deskripsi

Product Service adalah microservice yang mengelola data produk dalam sistem. Service ini menyediakan REST API untuk operasi CRUD (Create, Read, Update, Delete) produk dengan validasi input, error handling terstruktur, serta logging berbasis **Correlation ID** untuk kebutuhan distributed tracing.

Service ini dirancang **independen**, memiliki database sendiri, dan hanya dapat diakses oleh service lain melalui HTTP API.

---

## Fitur Utama

* CRUD Product (Create, Read, Update, Delete)
* Validasi input dengan custom error messages
* Error handling konsisten berbasis HTTP status code
* Middleware Correlation ID untuk request tracking
* Structured JSON logging (siap untuk observability)
* API versioning (`/api/v1`)

---

## Service Responsibility & Boundary

### Tanggung Jawab

Product Service bertanggung jawab penuh atas:

* Penyimpanan dan validasi data produk
* Operasi CRUD produk
* Penyediaan data produk ke service lain melalui REST API

### Bukan Tanggung Jawab

Product Service **tidak menangani**:

* Transaksi penjualan
* Pembayaran
* Autentikasi / manajemen user
* Sinkronisasi stok lintas gudang

Service lain (misalnya Order Service atau Transaction Service) **dilarang mengakses database secara langsung** dan wajib berkomunikasi melalui API Product Service.

---

## Architecture Overview

Product Service merupakan bagian dari arsitektur microservice dan berjalan secara independen pada port **8003**.

Komunikasi antar service menggunakan **HTTP REST API (JSON)** dengan **Correlation ID** untuk keperluan tracing.

Contoh alur:

```
Client / API Gateway
        ↓
 Product Service (8003)
        ↓
     Database
```

---

## Teknologi

* PHP >= 8.2
* Laravel 11.x
* Database: SQLite / MySQL / MariaDB
* Composer

---

## Setup & Running

### System Requirements

* PHP >= 8.2
* Composer
* Database (SQLite atau MySQL)

### Quick Start

```bash
cd product_service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8003
```

Service berjalan di:

```
http://localhost:8003
```

---

## API Base URL

```
http://localhost:8003/api/v1
```

Semua endpoint menggunakan versioning `v1` untuk menjaga backward compatibility.

---

## Health Check

### GET /health

Digunakan untuk memastikan service dalam kondisi aktif.

**Response (200 OK)**

```json
{
  "status": "UP",
  "service": "product_service",
  "time": "2025-12-10T06:35:15.003670Z"
}
```

---

## API Endpoints

### 1. GET /products

Mengambil seluruh data produk.

**Method**: GET
**URL**: `/api/v1/products`

**Headers**:

```
X-Correlation-ID: (optional)
Content-Type: application/json
```

**Response (200 OK)**

```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": []
}
```

---

### 2. GET /products/{id}

Mengambil detail produk berdasarkan ID.

**Method**: GET
**URL**: `/api/v1/products/{id}`

**Response (200 OK)**

```json
{
  "success": true,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "Keyboard Mechanical",
    "price": 300000,
    "stock": 12,
    "description": "RGB Blue Switch"
  }
}
```

**Error**:

* 400: Invalid product ID format
* 404: Product not found

---

### 3. POST /products

Membuat produk baru.

**Method**: POST
**URL**: `/api/v1/products`

**Request Body**

```json
{
  "name": "Test Product",
  "price": 50000,
  "stock": 10,
  "description": "Test"
}
```

**Response (201 Created)**

```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 5,
    "name": "Test Product",
    "price": 50000,
    "stock": 10,
    "description": "Test"
  }
}
```

**Validation Error (422)**

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "name": ["Nama produk wajib diisi"]
  }
}
```

---

### 4. PUT /products/{id}

Mengubah data produk (partial update diperbolehkan).

**Method**: PUT
**URL**: `/api/v1/products/{id}`

**Response (200 OK)**

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 3,
    "name": "edit Keyboard Mechanical",
    "price": 20000,
    "stock": 18
  }
}
```

---

### 5. DELETE /products/{id}

Menghapus produk.

**Method**: DELETE
**URL**: `/api/v1/products/{id}`

**Response (200 OK)**

```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

**Error**:

* 404: Product not found
* 409: Conflict (data masih direferensikan)

---

## HTTP Status Codes

| Code | Description              |
| ---- | ------------------------ |
| 200  | Request berhasil         |
| 201  | Resource berhasil dibuat |
| 400  | Bad request              |
| 404  | Resource tidak ditemukan |
| 409  | Conflict                 |
| 422  | Validation error         |
| 500  | Internal server error    |

---

## Correlation ID & Distributed Tracing

Setiap request dapat menyertakan header:

```
X-Correlation-ID
```

Behavior:

* Jika dikirim client → digunakan
* Jika tidak → digenerate otomatis
* Dikembalikan di response header
* Dicatat di semua log

---

## Structured Logging

Semua log disimpan dalam format JSON di:

```
storage/logs/laravel.log
```

Contoh log:

```json
{
  "message": "Product Created",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "id": 5
  }
}
```

---

## Database Schema

```sql
CREATE TABLE products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  price DECIMAL(15,2) NOT NULL,
  stock INT NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
```

---

## Security Considerations

* Validasi input di semua endpoint
* Proteksi SQL Injection (Eloquent ORM)
* Tidak menampilkan error sensitif di production
* Logging terstruktur untuk audit trail

---

## Troubleshooting

**Service tidak berjalan**

```bash
php artisan serve --port=8003
```

**Database error**

```bash
php artisan migrate --refresh
```

**Debug request**
Gunakan `correlation_id` untuk menelusuri log di `storage/logs/laravel.log`.

---

### Status

Product Service siap diintegrasikan dengan API Gateway dan service lain dalam arsitektur microservice.
