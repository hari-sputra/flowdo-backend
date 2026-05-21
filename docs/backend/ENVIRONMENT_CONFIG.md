# Environment Configuration & Deployment Setup

Dokumentasi ini menjelaskan konfigurasi file `.env`, pengaturan CORS, integrasi Laravel Sanctum, konfigurasi Docker Compose untuk local development, serta deployment Docker untuk production.

---

## 1. File Konfigurasi `.env.example`

Berikut adalah parameter environment wajib untuk menjalankan FlowDo backend:

```env
APP_NAME=FlowDo
APP_ENV=local
APP_KEY=SomeRandomStringWith32Characters
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost:8000

# Pengaturan Domain Stateful (Sangat Penting untuk Cookie SPA)
# Domain tempat frontend Vue.js berjalan
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
# Domain sesi cookie (opsional di local, wajib di prod)
SESSION_DOMAIN=localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Konfigurasi Database PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowdo
DB_USERNAME=postgres
DB_PASSWORD=secret

# Drivers
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync
CACHE_STORE=database

# Web Push Notification Keys (Fase Akhir)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:hari.saputra.dev@gmail.com
```

---

## 2. Konfigurasi CORS & Cookie Sesi

Agar Vue.js SPA dapat melakukan request API serta bertukar cookie sesi dengan backend Laravel, CORS dan Session configuration harus dikonfigurasi sebagai berikut:

### 2.1 Konfigurasi CORS (`config/cors.php`)
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')], // URL Vue SPA
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Wajib bernilai TRUE agar cookie terkirim
];
```

### 2.2 Konfigurasi Session (`config/session.php`)
Pastikan cookie dikonfigurasi secara aman untuk menghindari serangan hijacking:
```php
return [
    'driver' => env('SESSION_DRIVER', 'cookie'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', false), // TRUE di HTTPS Production
    'http_only' => true, // Menghindari pembacaan cookie oleh JavaScript (XSS)
    'same_site' => 'lax',
];
```

---

## 3. Kontainerisasi dengan Docker (Local & Production)

### 3.1 Docker Compose (`docker-compose.yml`)
Menjalankan full-stack development environment yang terdiri dari database PostgreSQL, PHP-FPM, dan Nginx web server.

```yaml
services:
  # 1. Database PostgreSQL
  db:
    image: postgres:16-alpine
    container_name: flowdo_db
    restart: unless-stopped
    environment:
      POSTGRES_DB: flowdo
      POSTGRES_USER: flowdo_user
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - pgdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U flowdo_user -d flowdo"]
      interval: 5s
      timeout: 5s
      retries: 5

  # 2. PHP-FPM Backend Application
  api:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: flowdo_api
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    environment:
      DB_CONNECTION: pgsql
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: flowdo
      DB_USERNAME: flowdo_user
      DB_PASSWORD: ${DB_PASSWORD:-secret}
      APP_ENV: local
      APP_DEBUG: 'true'
    volumes:
      - .:/var/www/html
      - api-storage:/var/www/html/storage

  # 3. Nginx Web Server / Reverse Proxy
  webserver:
    image: nginx:alpine
    container_name: flowdo_webserver
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - api

volumes:
  pgdata:
  api-storage:
```

### 3.2 Backend Dockerfile (`Dockerfile`)
Membangun image PHP 8.3 dengan ekstensi PostgreSQL yang dioptimasi untuk menjalankan Laravel.

```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies & PostgreSQL PHP extension
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Salin source code
COPY . .

# Run composer install (untuk production)
# RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]
```

### 3.3 Konfigurasi Nginx (`nginx/default.conf`)
Konfigurasi Nginx untuk mengarahkan request API ke PHP-FPM dan mendukung parsing URL Laravel.

```nginx
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/html/public;

    # CORS Headers (fallback jika tidak terhandle php)
    add_header 'Access-Control-Allow-Credentials' 'true' always;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass api:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
```

---

## 4. Perbedaan Konfigurasi Dev vs Production

| Parameter | Local Development | Production Environment |
|-----------|-------------------|------------------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost:8000` | `https://api.flowdo.com` |
| `SESSION_SECURE_COOKIE` | `false` | `true` (Wajib SSL/HTTPS) |
| `DB_HOST` | `127.0.0.1` / `db` | IP Server DB Internal / Cloud DB Cluster |
| `CACHE_STORE` | `database` / `array` | `redis` / `memcached` |
| `QUEUE_CONNECTION` | `sync` | `database` / `redis` (dengan Worker run) |
| Optimization | - | Jalankan `php artisan config:cache`, `route:cache`, `view:cache` |
