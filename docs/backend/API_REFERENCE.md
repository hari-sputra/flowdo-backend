# RESTful API Reference

Dokumentasi ini mendefinisikan seluruh RESTful API endpoint yang tersedia pada backend FlowDo. Semua input dan output menggunakan format **JSON**.

---

## 1. Global Specifications

- **Base URL:** `http://localhost:8000` (atau sesuai konfigurasi env `APP_URL`)
- **Headers Wajib (untuk request mutasi & data):**
  ```http
  Accept: application/json
  Content-Type: application/json
  X-Requested-With: XMLHttpRequest
  ```
- **CSRF Token:** Setiap request mutasi (`POST`, `PUT`, `PATCH`, `DELETE`) pada SPA wajib menyertakan cookie `XSRF-TOKEN` dan header `X-XSRF-TOKEN` (secara otomatis ditangani oleh Axios jika `withCredentials = true`).

---

## 2. Authentication Endpoints

Semua endpoint autentikasi bersifat stateful menggunakan Laravel Sanctum session cookies.

### 2.1 Inisialisasi Cookie CSRF (`GET /sanctum/csrf-cookie`)
Digunakan sebelum melakukan login/register untuk menetapkan token CSRF pada browser client.

- **Auth Required:** No
- **Response:** `204 No Content`
- **cURL Example:**
  ```bash
  curl -X GET http://localhost:8000/sanctum/csrf-cookie -i
  ```

---

### 2.2 Pendaftaran Akun (`POST /api/register`)
Mendaftarkan user baru, secara otomatis login ke sistem, menginisialisasi default tags, dan mengembalikan user data.

- **Auth Required:** No
- **Request Body:**
  ```json
  {
    "name": "Hari Saputra",
    "email": "hari.saputra.dev@gmail.com",
    "password": "supersecurepassword123",
    "password_confirmation": "supersecurepassword123"
  }
  ```
- **Validation Rules:**
  - `name`: Wajib, String, Maks 255 karakter.
  - `email`: Wajib, String, Format Email valid, Unik dalam tabel `users`.
  - `password`: Wajib, String, Min 8 karakter, Wajib dikonfirmasi via `password_confirmation`.
- **Response (201 Created):**
  ```json
  {
    "data": {
      "id": "2",
      "name": "Hari Saputra",
      "email": "hari.saputra.dev@gmail.com",
      "avatarUrl": null
    }
  }
  ```
- **cURL Example:**
  ```bash
  curl -X POST http://localhost:8000/api/register \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"name":"Hari Saputra","email":"hari@gmail.com","password":"password123","password_confirmation":"password123"}'
  ```

---

### 2.3 Masuk Log Sesi (`POST /api/login`)
Melakukan autentikasi kredensial pengguna dan memulai session cookie.

- **Auth Required:** No
- **Request Body:**
  ```json
  {
    "email": "hari.saputra.dev@gmail.com",
    "password": "supersecurepassword123"
  }
  ```
- **Validation Rules:**
  - `email`: Wajib, String, Format email.
  - `password`: Wajib, String.
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": "1",
      "name": "Hari Saputra",
      "email": "hari.saputra.dev@gmail.com",
      "avatarUrl": "https://www.gravatar.com/avatar/..."
    }
  }
  ```

---

### 2.4 Keluar Log Sesi (`POST /api/logout`)
Menghapus session autentikasi server-side dan membersihkan cookie di client.

- **Auth Required:** Yes (Sanctum)
- **Response:** `204 No Content`

---

### 2.5 Informasi User Aktif (`GET /api/user`)
Mendapatkan profil data user yang saat ini sedang login.

- **Auth Required:** Yes (Sanctum)
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": "1",
      "name": "Hari Saputra",
      "email": "hari.saputra.dev@gmail.com",
      "avatarUrl": null
    }
  }
  ```

---

## 3. Task Management Endpoints

Semua task endpoint mewajibkan user login. Query hanya akan memproses task milik user yang terotentikasi.

### 3.1 Daftar Semua Task (`GET /api/tasks`)
Mengambil seluruh daftar task milik pengguna aktif. Mendukung parameter sorting dan pemfilteran opsional.

- **Auth Required:** Yes
- **Query Parameters:**
  - `sort_by` (optional): `dueDate`, `title`, `priority` (default: `dueDate`)
  - `sort_direction` (optional): `asc`, `desc` (default: `asc`)
  - `status` (optional): `todo`, `inprogress`, `completed` (memfilter berdasarkan status tertentu)
  - `tag` (optional): memfilter task yang memiliki tag tertentu (berdasarkan nama tag)
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": "1",
        "title": "Market Research",
        "description": "Conduct market research and user analysis for the grocery shopping application.",
        "status": "done",
        "dueDate": "2026-05-21",
        "priority": "medium",
        "tags": [
          {
            "id": "1",
            "name": "Work",
            "color": "#8764FF"
          }
        ]
      },
      {
        "id": "2",
        "title": "Competitive Analysis",
        "description": "Analyze competitors in the grocery shopping space.",
        "status": "in-progress",
        "dueDate": "2026-05-21",
        "priority": "high",
        "tags": [
          {
            "id": "1",
            "name": "Work",
            "color": "#8764FF"
          }
        ]
      }
    ]
  }
  ```

---

### 3.2 Membuat Task Baru (`POST /api/tasks`)
Menambahkan task baru untuk user aktif.

- **Auth Required:** Yes
- **Request Body (`StoreTaskRequest`):**
  ```json
  {
    "title": "Create Low-fidelity Wireframe",
    "description": "Sketch out initial layout and basic structures.",
    "dueDate": "2026-05-21",
    "priority": "urgent",
    "status": "to-do",
    "tags": ["Personal"]
  }
  ```
- **Validation Rules:**
  - `title`: Wajib, String, Maks 255 karakter.
  - `description`: Opsional, String.
  - `dueDate`: Wajib, Format tanggal `YYYY-MM-DD`.
  - `priority`: Wajib, Enum (`low`, `medium`, `high`, `urgent`).
  - `status`: Opsional, Enum (`to-do`, `in-progress`, `done`). Default: `to-do`.
  - `tags`: Opsional, Array berisi nama-nama tag (`string[]`). Tag yang dilempar harus merupakan tag yang dimiliki oleh user tersebut.
- **Response (201 Created):**
  ```json
  {
    "data": {
      "id": "3",
      "title": "Create Low-fidelity Wireframe",
      "description": "Sketch out initial layout and basic structures.",
      "status": "to-do",
      "dueDate": "2026-05-21",
      "priority": "urgent",
      "tags": [
        {
          "id": "2",
          "name": "Personal",
          "color": "#FF7D53"
        }
      ]
    }
  }
  ```

---

### 3.3 Mendapatkan Detail Task (`GET /api/tasks/{id}`)
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": "3",
      "title": "Create Low-fidelity Wireframe",
      "description": "Sketch out initial layout and basic structures.",
      "status": "to-do",
      "dueDate": "2026-05-21",
      "priority": "urgent",
      "tags": [
        {
          "id": "2",
          "name": "Personal",
          "color": "#FF7D53"
        }
      ]
    }
  }
  ```

---

### 3.4 Mengubah Task (`PUT /api/tasks/{id}`)
Memperbarui atribut dari task tertentu. Mendukung partial update.

- **Auth Required:** Yes
- **Request Body (`UpdateTaskRequest`):**
  ```json
  {
    "title": "Create Low-fidelity Wireframe Updated",
    "status": "in-progress"
  }
  ```
- **Validation Rules:** Sama dengan `StoreTaskRequest` namun seluruh field opsional.
- **Response (200 OK):** Mengembalikan data task terupdate lengkap.

---

### 3.5 Menghapus Task (`DELETE /api/tasks/{id}`)
Menghapus task secara permanen.

- **Auth Required:** Yes
- **Response:** `204 No Content`

---

### 3.6 Mengganti Status Task (`PATCH /api/tasks/{id}/toggle`)
Endpoint khusus untuk mempercepat siklus toggle status dari frontend. Siklus status adalah:
`to-do` ➔ `in-progress` ➔ `done` ➔ `to-do`.
Jika status berubah menjadi `done`, maka backend secara otomatis mencatat timestamp `completed_at` saat ini.

- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": "3",
      "title": "Create Low-fidelity Wireframe",
      "status": "in-progress",
      "dueDate": "2026-05-21",
      "priority": "urgent",
      "tags": [...]
    }
  }
  ```

---

### 3.7 Daftar Task Jatuh Tempo Hari Ini (`GET /api/tasks/due-today`)
Mengambil list task dengan `due_date` hari ini milik user aktif yang belum berkategori `done`.

- **Auth Required:** Yes
- **Response (200 OK):** Sama seperti `GET /api/tasks` namun dengan filter default.

---

## 4. Tag Management Endpoints

Mengelola daftar tag kustom per pengguna.

### 4.1 Daftar Tag (`GET /api/tags`)
Mengambil semua tag milik user aktif (termasuk default tag).

- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "data": [
      { "id": "1", "name": "Work", "color": "#8764FF", "isDefault": true },
      { "id": "2", "name": "Personal", "color": "#FF7D53", "isDefault": true },
      { "id": "5", "name": "Side Project", "color": "#34D399", "isDefault": false }
    ]
  }
  ```

### 4.2 Membuat Tag Baru (`POST /api/tags`)
- **Auth Required:** Yes
- **Request Body:**
  ```json
  {
    "name": "Side Project",
    "color": "#34D399"
  }
  ```
- **Validation Rules:**
  - `name`: Wajib, String, Maks 50 karakter, Unik untuk user aktif.
  - `color`: Wajib, format Hex color code (`#ffffff` / `#FFF`).
- **Response (201 Created):**
  ```json
  {
    "data": {
      "id": "5",
      "name": "Side Project",
      "color": "#34D399",
      "isDefault": false
    }
  }
  ```

### 4.3 Mengubah Tag (`PUT /api/tags/{id}`)
- **Auth Required:** Yes
- **Note:** Default tag (`isDefault: true`) **tidak dapat diubah** namanya untuk menjaga konsistensi. Jika dicoba, API merespon `403 Forbidden`.
- **Response (200 OK):** Mengembalikan data tag terupdate.

### 4.4 Menghapus Tag (`DELETE /api/tags/{id}`)
Menghapus tag dari database. 
- **Note:** Default tag (`isDefault: true`) **tidak dapat dihapus** (akan merespon `403 Forbidden`).
- **Cascade effect:** Menghapus data asosiasi tag ini pada semua task pengguna (`task_tag` pivot row didelete otomatis, tanpa mendelete task-nya sendiri).
- **Response:** `204 No Content`

---

## 5. Web Push Notification Endpoints (Fase Akhir)

Mengelola push subscription di browser client.

### 5.1 Menyimpan Subscription (`POST /api/push-subscriptions`)
- **Auth Required:** Yes
- **Request Body:**
  ```json
  {
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "BLAn...",
      "auth": "Wv8..."
    }
  }
  ```
- **Response (201 Created):** `{"success": true}`

### 5.2 Menghapus Subscription (`DELETE /api/push-subscriptions`)
- **Auth Required:** Yes
- **Request Body:**
  ```json
  {
    "endpoint": "https://fcm.googleapis.com/fcm/send/..."
  }
  ```
- **Response:** `204 No Content`

---

## 6. Standar HTTP Error Responses

### 401 Unauthenticated (User belum login)
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden (Mencoba mengakses task/tag user lain, atau memodifikasi default tag)
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found (Resource tidak ada)
```json
{
  "message": "Resource not found."
}
```

### 429 Too Many Requests (Rate limit terlampaui)
```json
{
  "message": "Too Many Attempts."
}
```
