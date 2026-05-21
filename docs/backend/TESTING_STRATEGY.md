# Testing Strategy & Test Plan

Dokumentasi ini menjelaskan strategi pengujian otomatis (automated testing) pada backend FlowDo dengan berfokus pada **Feature Tests** menggunakan **PHPUnit/Pest** untuk menjamin kestabilan dan kepatuhan setiap endpoint terhadap kontrak API frontend.

---

## 1. Setup Lingkungan Pengujian (Testing Environment)

Pengujian dilakukan menggunakan database in-memory **SQLite** agar proses testing berjalan cepat tanpa merusak data development.

### 1.1 Konfigurasi `phpunit.xml`
Pastikan environment berikut aktif saat testing dijalankan:
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="SESSION_DRIVER" value="array"/>
</php>
```

---

## 2. Definisi Model Factories

Factories digunakan untuk menghasilkan mock data secara dinamis selama proses pengujian.

### 2.1 `UserFactory.php`
```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
        ];
    }
}
```

### 2.2 `TaskFactory.php`
```php
namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_date' => fake()->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::TO_DO,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::DONE,
            'completed_at' => now(),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => today()->format('Y-m-d'),
        ]);
    }
}
```

### 2.3 `TagFactory.php`
```php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
```

---

## 3. Matriks Skenario Test (Test Cases Matrix)

Setiap endpoint diuji dengan skenario-skenario berikut guna mendeteksi bug regresi:

### 3.1 Autentikasi (`tests/Feature/AuthTest.php`)

| Skenario Test | Method / Endpoint | Input | Status Yang Diharapkan |
|---------------|-------------------|-------|------------------------|
| Registrasi Berhasil | `POST /api/register` | Kredensial Lengkap & Valid | `201 Created` + User JSON + Default Tags Created |
| Registrasi Gagal (Email Duplikat) | `POST /api/register` | Email yang sudah terdaftar | `422 Unprocessable Content` |
| Login Berhasil | `POST /api/login` | Email & Password Benar | `200 OK` + Session Cookie |
| Login Gagal (Wrong Pass) | `POST /api/login` | Password Salah | `422 Unprocessable Content` |
| Logout Berhasil | `POST /api/logout` | Session Aktif | `204 No Content` |
| Get User Aktif | `GET /api/user` | Session Aktif | `200 OK` + User JSON |
| Get User Gagal (Guest) | `GET /api/user` | Tanpa Cookie Sesi | `401 Unauthenticated` |

```php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Hari Saputra',
            'email' => 'hari@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Hari Saputra');

        $this->assertDatabaseHas('users', ['email' => 'hari@gmail.com']);
        // Pastikan default tags otomatis dibuat untuk user baru ini
        $this->assertDatabaseHas('tags', ['user_id' => 1, 'name' => 'Work']);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'hari@gmail.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'hari@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }
}
```

---

### 3.2 Task Management (`tests/Feature/TaskTest.php`)

| Skenario Test | Method / Endpoint | Input | Status Yang Diharapkan |
|---------------|-------------------|-------|------------------------|
| Ambil Task Milik Sendiri | `GET /api/tasks` | Session Valid | `200 OK` + List Task |
| Tidak Bisa Ambil Task User Lain | `GET /api/tasks` | Ada task milik user lain di DB | `200 OK` (Hanya me-return task milik user bersangkutan) |
| Membuat Task Berhasil | `POST /api/tasks` | Title, Date, Priority Valid | `201 Created` + Task JSON |
| Validasi Task Form Gagal | `POST /api/tasks` | Tanpa Title / Format Date Salah | `422 Unprocessable Content` |
| Detail Task Milik Sendiri | `GET /api/tasks/{id}` | ID Task Valid | `200 OK` |
| Detail Task Gagal (Milik Orang Lain) | `GET /api/tasks/{id}`| ID Task User Lain | `403 Forbidden` |
| Update Task Berhasil | `PUT /api/tasks/{id}` | Data Update | `200 OK` |
| Toggle Status Cycle | `PATCH /api/tasks/{id}/toggle` | Keadaan Awal `to-do` | `200 OK` + status: `in-progress` |
| Toggle Status Done mencatat completed_at | `PATCH /api/tasks/{id}/toggle` | Keadaan Awal `in-progress` | `200 OK` + status: `done` + completed_at != null |
| Hapus Task Berhasil | `DELETE /api/tasks/{id}` | ID Task Valid | `204 No Content` |

```php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_access_their_own_tasks(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Task::factory()->count(3)->create(['user_id' => $userA->id]);
        Task::factory()->count(2)->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_toggle_task_status(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => TaskStatus::TO_DO,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/tasks/{$task->id}/toggle");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in-progress');
            
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null
        ]);
    }
}
```

---

### 3.3 Tag Management (`tests/Feature/TagTest.php`)

| Skenario Test | Method / Endpoint | Input | Status Yang Diharapkan |
|---------------|-------------------|-------|------------------------|
| Ambil List Tag | `GET /api/tags` | Session Valid | `200 OK` + list tag |
| Create Tag Kustom | `POST /api/tags` | Name & Color Valid | `201 Created` |
| Create Tag Gagal (Duplicate Name) | `POST /api/tags` | Name sama dengan tag yang sudah ada | `422 Unprocessable Content` |
| Update Default Tag Ditolak | `PUT /api/tags/{id}` | Mengubah warna default tag | `403 Forbidden` |
| Delete Tag Kustom | `DELETE /api/tags/{id}` | ID Tag kustom | `204 No Content` + Asosiasi tag pada task terhapus |
| Delete Default Tag Ditolak | `DELETE /api/tags/{id}` | ID Default Tag | `403 Forbidden` |

---

## 4. Cara Menjalankan Automated Test

Jalankan perintah Artisan di terminal project:

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan test dengan PHPUnit langsung
vendor/bin/phpunit

# Menjalankan test file tertentu
php artisan test --filter=TaskTest
```

---

## 5. Integrasi CI/CD (GitHub Actions Workflow)

Pipeline berikut dapat dipasang di `.github/workflows/ci.yml` untuk memvalidasi setiap push ke branch `main` atau `develop`:

```yaml
name: CI Backend Test

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  laravel-tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: flowdo_test
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: password
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, xml, ctype, iconv, pdo-sqlite, pdo-pgsql
        coverage: xdebug

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Run Tests
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: ":memory:"
      run: vendor/bin/phpunit
```
