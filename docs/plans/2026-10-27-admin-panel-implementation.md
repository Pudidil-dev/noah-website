# Panel Admin + Halaman Dinamis NOAH — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.
> **Aturan repo:** commit tanpa baris `Co-Authored-By`, dan **jangan pernah `git push`** (user push sendiri).

**Goal:** Website NOAH punya database PostgreSQL, panel admin CRUD di `/admin`, form publik yang benar-benar menyimpan data, dan 7 halaman publik `.php` yang membaca isi dari database.

**Architecture:** PHP 8.2 native tanpa framework. Folder `app/` berisi fungsi bersama (koneksi PDO, escape, CSRF, validasi, throttle, query publik, handler form). Panel admin memakai satu CRUD generik yang dibaca dari config `admin/resources.php`, ditambah 3 halaman custom: pesanan tiket, moderasi kiriman fans, dan setlist. Halaman publik tetap memakai HTML/CSS lama. Hanya bagian yang berulang yang diganti loop dari DB. Filter yang sudah ada di `app.js` tetap jalan karena atribut `data-*` ikut dirender.

**Tech Stack:** PHP 8.2 (XAMPP, `pdo_pgsql` + `fileinfo` aktif), PostgreSQL 16 (Docker), PHPUnit 10 (Composer), server dev `php -S`.

**Dokumen terkait:** [desain basis data](2026-10-27-database-draft-design.md) · [desain panel admin](2026-10-27-admin-panel-design.md)

**Keputusan yang menyimpang dari dokumen desain (sudah final):**
1. Filter toko, diskografi, dan galeri tetap client-side lewat `app.js` dengan atribut `data-*`, bukan filter GET di server. Hasilnya sama dan jumlah kodenya lebih sedikit.
2. Skema direvisi supaya semua isi HTML punya kolom (Task 2).
3. `posts.admin_id` dan `tracks.album_id` boleh NULL. Seed bisa jalan sebelum ada admin, dan lagu di setlist tidak harus terikat ke album.
4. Jadwal konser 2026 di HTML sudah lewat (hari ini 27 Okt 2026), jadi seed memakai tanggal 2027. Harga dan kuota tiket di seed hanya contoh.

**⚠ Catatan hosting:** riwayat repo menunjukkan pernah ada CNAME (GitHub Pages). GitHub Pages tidak bisa menjalankan PHP. Setelah Task 19–25, situs harus dijalankan di XAMPP/hosting PHP, tidak bisa lagi di Pages.

---

## Konvensi

- Semua file PHP diawali `<?php declare(strict_types=1);`.
- Semua output ke HTML lewat `e()`. Tidak ada `echo` mentah untuk data DB/user.
- Semua query memakai prepared statement. Nama tabel/kolom hanya dari config dan selalu lewat `ident()`.
- Test: `vendor/bin/phpunit` (unit + integration). DB test bernama `noah_test` dan dibuat ulang otomatis oleh test.
- Jalankan perintah dari root project: `C:\Users\IDK\Documents\kuliah\semester 4\web\week 11`.

## Peta file akhir

```
app/        bootstrap.php db.php helpers.php csrf.php validator.php throttle.php queries.php forms.php
admin/      index.php resources.php admin.css
admin/core/ auth.php crud.php upload.php fields.php controller.php workflow.php
admin/custom/ ticket_orders.php fan_submissions.php setlist.php
admin/views/ layout.php login.php dashboard.php list.php form.php error.php
admin/views/custom/ ticket_orders.php fan_submissions.php setlist.php
actions/    order-ticket.php submit-fan.php subscribe.php
partials/   header.php footer.php flash.php
database/   schema.sql seed.sql create-admin.php
tests/      bootstrap.php DbTestCase.php Unit/* Integration/*
*.php       index cerita-band diskografi tur-event berita-media sahabat-noah toko
docker-compose.yml composer.json phpunit.xml config.example.php
```

---

### Task 1: Lingkungan dev (PostgreSQL, config, Composer, PHPUnit)

**Files:**
- Create: `docker-compose.yml`, `config.example.php`, `composer.json`, `phpunit.xml`, `tests/bootstrap.php`
- Modify: `.gitignore`

**Step 1: Buat `docker-compose.yml`**

```yaml
# PostgreSQL untuk development lokal saja. Password ini bukan untuk produksi.
services:
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_USER: noah
      POSTGRES_PASSWORD: noah_dev
      POSTGRES_DB: noah
    ports:
      - "127.0.0.1:5433:5432"
    volumes:
      - noah_pg:/var/lib/postgresql/data
volumes:
  noah_pg:
```

**Step 2: Buat `config.example.php`**

```php
<?php
declare(strict_types=1);

// Salin file ini menjadi config.php lalu sesuaikan. config.php tidak di-commit.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 5433,
        'name' => 'noah',
        'user' => 'noah',
        'pass' => 'noah_dev',
    ],
    'app' => [
        'upload_dir' => __DIR__ . '/uploads',
        'upload_url' => 'uploads',
    ],
];
```

**Step 3: Tambahkan ke `.gitignore`**

```
# PHP / Composer
/vendor/
/config.php
/uploads/*
!/uploads/.htaccess
.phpunit.cache/
```

**Step 4: Buat `composer.json`**

```json
{
  "name": "pudidil/noah-website",
  "description": "Website fan NOAH dengan panel admin PHP + PostgreSQL",
  "type": "project",
  "require": {
    "php": ">=8.2",
    "ext-pdo_pgsql": "*",
    "ext-fileinfo": "*",
    "ext-mbstring": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5"
  },
  "scripts": {
    "test": "phpunit"
  }
}
```

**Step 5: Buat `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true" cacheDirectory=".phpunit.cache">
  <testsuites>
    <testsuite name="unit">
      <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="integration">
      <directory>tests/Integration</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

**Step 6: Buat `tests/bootstrap.php`**

File yang belum ada dilewati, jadi bootstrap ini tidak perlu diubah di task berikutnya.

```php
<?php
declare(strict_types=1);

putenv('NOAH_DB_NAME=noah_test');
putenv('NOAH_THROTTLE_DIR=' . sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'noah_throttle_test');
$_SESSION = [];

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$files = [
    'app/db.php', 'app/helpers.php', 'app/csrf.php', 'app/validator.php', 'app/throttle.php',
    'app/queries.php', 'app/forms.php',
    'admin/core/crud.php', 'admin/core/upload.php', 'admin/core/auth.php', 'admin/core/workflow.php',
    'tests/DbTestCase.php',
];
foreach ($files as $file) {
    if (is_file($root . '/' . $file)) {
        require_once $root . '/' . $file;
    }
}
```

**Step 7: Jalankan DB, buat config dan DB test, install PHPUnit**

```bash
docker compose up -d
cp config.example.php config.php
docker compose exec db createdb -U noah noah_test
composer install
vendor/bin/phpunit
```

Expected: container `db` jalan. PHPUnit menampilkan `No tests executed!` (belum ada test).

**Penyesuaian saat implementasi:** PostgreSQL Docker diakses melalui `127.0.0.1:5433` karena port host `5432` sudah dipakai PostgreSQL lain di Windows (disetujui user). Port internal container tetap `5432`. Folder `tests/Unit` dan `tests/Integration` disertai `.gitkeep` agar suite PHPUnit tersedia sejak clone awal.

**Step 8: Commit**

```bash
git add docker-compose.yml config.example.php composer.json composer.lock phpunit.xml tests/bootstrap.php tests/Unit/.gitkeep tests/Integration/.gitkeep .gitignore docs/plans/2026-10-27-admin-panel-implementation.md
git commit -m "chore: siapkan PostgreSQL docker, config, dan PHPUnit"
```

---

### Task 2: Revisi skema agar semua konten HTML punya kolom

**Files:**
- Modify: `database/schema.sql` (ganti seluruh isi)
- Modify: `docs/plans/2026-10-27-database-draft-design.md` (bagian ringkasan kolom baru)

**Step 1: Ganti seluruh isi `database/schema.sql`**

Perubahan dibanding draft: `album_type`/`product_category` memakai nilai yang sama dengan `app.js`, ada enum `album_era`, dan kolom baru `milestones.badge`, `albums.era/total_tracks/is_featured`, `tracks.single_year`, `events.description/badge`, `setlist_items.is_encore`, `ticket_orders.notes`, `posts.tag/is_featured`, `media.tag/caption`, `products.badge`, `communities.leader_name`. Selain itu `posts.admin_id`, `tracks.album_id`, `tracks.track_no`, dan `milestones.year` boleh NULL.

```sql
-- NOAH Official Website - Basis Data (PostgreSQL 14+)

CREATE TYPE admin_role        AS ENUM ('super_admin', 'editor');
CREATE TYPE milestone_type    AS ENUM ('history', 'award');
CREATE TYPE album_type        AS ENUM ('studio', 'kompilasi', 'soundtrack');
CREATE TYPE album_era         AS ENUM ('peterpan', 'noah');
CREATE TYPE event_type        AS ENUM ('concert', 'community');
CREATE TYPE publish_status    AS ENUM ('draft', 'published', 'archived');
CREATE TYPE order_status      AS ENUM ('pending', 'paid', 'cancelled');
CREATE TYPE post_category     AS ENUM ('news', 'behind_scenes');
CREATE TYPE media_type        AS ENUM ('photo', 'video', 'press_kit');
CREATE TYPE product_category  AS ENUM ('kaos', 'hoodie', 'aksesoris', 'musik');
CREATE TYPE submission_type   AS ENUM ('story', 'artwork', 'cover');
CREATE TYPE review_status     AS ENUM ('pending', 'approved', 'rejected');

CREATE TABLE admins (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          admin_role   NOT NULL DEFAULT 'editor',
  created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE members (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  position    VARCHAR(50)  NOT NULL,
  bio         TEXT,
  photo_url   VARCHAR(255),
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   BOOLEAN      NOT NULL DEFAULT TRUE
);

-- history: tampil di timeline (pakai year). award: tampil di statistik (pakai badge, mis. "3x").
CREATE TABLE milestones (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  year        SMALLINT       CHECK (year IS NULL OR year BETWEEN 1990 AND 2100),
  badge       VARCHAR(20),
  title       VARCHAR(150)   NOT NULL,
  description TEXT,
  type        milestone_type NOT NULL DEFAULT 'history',
  image_url   VARCHAR(255),
  sort_order  SMALLINT       NOT NULL DEFAULT 0
);

CREATE TABLE albums (
  id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  title           VARCHAR(150) NOT NULL,
  slug            VARCHAR(160) NOT NULL UNIQUE,
  type            album_type   NOT NULL DEFAULT 'studio',
  era             album_era    NOT NULL DEFAULT 'noah',
  release_date    DATE,
  total_tracks    SMALLINT     CHECK (total_tracks > 0),
  cover_url       VARCHAR(255),
  is_featured     BOOLEAN      NOT NULL DEFAULT FALSE,
  description     TEXT,
  streaming_links JSONB        NOT NULL DEFAULT '{}'
);

-- album_id NULL = lagu lepas (mis. hanya muncul di setlist). single_year terisi = tampil di tabel Single.
CREATE TABLE tracks (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  album_id     BIGINT       REFERENCES albums(id) ON DELETE SET NULL,
  track_no     SMALLINT     CHECK (track_no > 0),
  title        VARCHAR(150) NOT NULL,
  duration_sec INTEGER      CHECK (duration_sec > 0),
  single_year  SMALLINT     CHECK (single_year IS NULL OR single_year BETWEEN 1990 AND 2100),
  UNIQUE (album_id, track_no)
);

CREATE TABLE communities (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  city         VARCHAR(80)  NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  leader_name  VARCHAR(100),
  contact_url  VARCHAR(255),
  member_count INTEGER      NOT NULL DEFAULT 0 CHECK (member_count >= 0)
);

CREATE TABLE events (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  community_id BIGINT         REFERENCES communities(id) ON DELETE SET NULL,
  title        VARCHAR(150)   NOT NULL,
  slug         VARCHAR(160)   NOT NULL UNIQUE,
  type         event_type     NOT NULL DEFAULT 'concert',
  badge        VARCHAR(40),
  city         VARCHAR(80)    NOT NULL,
  venue        VARCHAR(150),
  start_at     TIMESTAMPTZ    NOT NULL,
  description  TEXT,
  maps_url     VARCHAR(500),
  poster_url   VARCHAR(255),
  ticket_price NUMERIC(12,2)  CHECK (ticket_price >= 0),
  quota        INTEGER        CHECK (quota >= 0),
  status       publish_status NOT NULL DEFAULT 'draft'
);
CREATE INDEX idx_events_start_at ON events (start_at);

CREATE TABLE setlist_items (
  event_id  BIGINT   NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  position  SMALLINT NOT NULL CHECK (position > 0),
  track_id  BIGINT   NOT NULL REFERENCES tracks(id) ON DELETE RESTRICT,
  is_encore BOOLEAN  NOT NULL DEFAULT FALSE,
  PRIMARY KEY (event_id, position)
);

CREATE TABLE ticket_orders (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  event_id    BIGINT        NOT NULL REFERENCES events(id) ON DELETE RESTRICT,
  full_name   VARCHAR(120)  NOT NULL,
  email       VARCHAR(150)  NOT NULL,
  phone       VARCHAR(20)   NOT NULL,
  quantity    SMALLINT      NOT NULL CHECK (quantity BETWEEN 1 AND 10),
  total_price NUMERIC(12,2) NOT NULL CHECK (total_price >= 0),
  notes       TEXT,
  status      order_status  NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMPTZ   NOT NULL DEFAULT now()
);
CREATE INDEX idx_ticket_orders_event ON ticket_orders (event_id);

CREATE TABLE posts (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  admin_id     BIGINT         REFERENCES admins(id) ON DELETE SET NULL,
  title        VARCHAR(200)   NOT NULL,
  slug         VARCHAR(210)   NOT NULL UNIQUE,
  category     post_category  NOT NULL DEFAULT 'news',
  tag          VARCHAR(30),
  is_featured  BOOLEAN        NOT NULL DEFAULT FALSE,
  excerpt      TEXT,
  body         TEXT           NOT NULL,
  cover_url    VARCHAR(255),
  status       publish_status NOT NULL DEFAULT 'draft',
  published_at TIMESTAMPTZ
);
CREATE INDEX idx_posts_published ON posts (status, published_at DESC);

-- tag: kategori filter galeri (peterpan, noah, konser, studio). caption: alt foto / info file press kit.
CREATE TABLE media (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  post_id    BIGINT       REFERENCES posts(id) ON DELETE SET NULL,
  type       media_type   NOT NULL DEFAULT 'photo',
  tag        VARCHAR(30),
  title      VARCHAR(150) NOT NULL,
  caption    VARCHAR(255),
  file_url   VARCHAR(255) NOT NULL,
  created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE products (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name        VARCHAR(150)     NOT NULL,
  slug        VARCHAR(160)     NOT NULL UNIQUE,
  category    product_category NOT NULL,
  badge       VARCHAR(20),
  price       NUMERIC(12,2)    NOT NULL CHECK (price >= 0),
  stock       INTEGER          NOT NULL DEFAULT 0 CHECK (stock >= 0),
  image_url   VARCHAR(255),
  is_limited  BOOLEAN          NOT NULL DEFAULT FALSE,
  description TEXT,
  created_at  TIMESTAMPTZ      NOT NULL DEFAULT now()
);

CREATE TABLE fan_submissions (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  community_id BIGINT          REFERENCES communities(id) ON DELETE SET NULL,
  reviewed_by  BIGINT          REFERENCES admins(id) ON DELETE SET NULL,
  name         VARCHAR(120)    NOT NULL,
  email        VARCHAR(150)    NOT NULL,
  type         submission_type NOT NULL,
  content      TEXT            NOT NULL,
  file_url     VARCHAR(255),
  status       review_status   NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMPTZ     NOT NULL DEFAULT now()
);
CREATE INDEX idx_fan_submissions_status ON fan_submissions (status);

CREATE TABLE subscribers (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  email      VARCHAR(150) NOT NULL UNIQUE,
  is_active  BOOLEAN      NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);
```

**Step 2: Jalankan di DB dev**

```bash
docker compose exec -T db psql -U noah -d noah -v ON_ERROR_STOP=1 -f - < database/schema.sql
```

Expected: rentetan `CREATE TYPE` / `CREATE TABLE` / `CREATE INDEX` tanpa `ERROR`.

**Step 3: Update dokumen desain**

Di `docs/plans/2026-10-27-database-draft-design.md`, tambahkan bagian `## Revisi 2026-10-27` yang berisi daftar kolom baru dari Step 1 beserta alasannya: setiap kolom ada karena dipakai oleh section HTML tertentu.

**Step 4: Perbarui ERD di FigJam**

Panggil `generate_diagram` (Figma MCP) dengan `fileKey: rjNSexSYa67PhBXSV8zeJ4` dan mermaid `erDiagram` yang memuat kolom baru di atas.

**Step 5: Commit**

```bash
git add database/schema.sql docs/plans/2026-10-27-database-draft-design.md
git commit -m "feat(db): revisi skema agar semua konten halaman punya kolom"
```

---

### Task 3: Helper umum (`app/helpers.php`)

**Files:**
- Create: `app/helpers.php`
- Test: `tests/Unit/HelpersTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscapeHtml(): void
    {
        $this->assertSame('&lt;b&gt;&quot;x&quot;&#039;', e('<b>"x"\''));
        $this->assertSame('', e(null));
    }

    public function testSlugify(): void
    {
        $this->assertSame('keterkaitan-keterikatan-vinyl', slugify('Keterkaitan Keterikatan Vinyl'));
        $this->assertSame('hiatus-refleksi', slugify('  Hiatus & Refleksi! '));
        $this->assertSame('', slugify('***'));
    }

    public function testRupiah(): void
    {
        $this->assertSame('Rp 1.500.000', rupiah('1500000.00'));
        $this->assertSame('Rp 0', rupiah(0));
    }

    public function testTanggalIndonesia(): void
    {
        $this->assertSame('05 September 2024', tanggal_id('2024-09-05 10:00:00+07'));
        $this->assertSame('15 Nov 2012', tanggal_singkat('2012-11-15'));
        $this->assertSame('AGU', bulan_singkat('2027-08-24'));
        $this->assertSame('Oktober', bulan_panjang('2027-10-24'));
        $this->assertSame('', date_part(null, 'Y'));
        $this->assertSame('2019', date_part('2019-01-01', 'Y'));
    }

    public function testDurasi(): void
    {
        $this->assertSame('04:30', durasi(270));
        $this->assertSame('', durasi(null));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Unit/HelpersTest.php`
Expected: FAIL `Call to undefined function e()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

const BULAN_ID = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
    'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))) ?? '';
    return trim($slug, '-');
}

function rupiah(string|int|float $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function date_part(?string $datetime, string $format): string
{
    return $datetime === null || $datetime === '' ? '' : (new DateTimeImmutable($datetime))->format($format);
}

function bulan_panjang(string $datetime): string
{
    return BULAN_ID[(int)date_part($datetime, 'n')];
}

function bulan_singkat(string $datetime): string
{
    return strtoupper(substr(bulan_panjang($datetime), 0, 3));
}

function tanggal_id(string $datetime): string
{
    return date_part($datetime, 'd') . ' ' . bulan_panjang($datetime) . ' ' . date_part($datetime, 'Y');
}

function tanggal_singkat(string $datetime): string
{
    return date_part($datetime, 'd') . ' ' . substr(bulan_panjang($datetime), 0, 3) . ' ' . date_part($datetime, 'Y');
}

function durasi(?int $seconds): string
{
    return $seconds === null ? '' : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Simpan pesan (2 argumen) atau ambil-lalu-hapus pesan (1 argumen). */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Unit/HelpersTest.php`
Expected: PASS (5 tests)

**Step 5: Commit**

```bash
git add app/helpers.php tests/Unit/HelpersTest.php
git commit -m "feat(app): tambah helper escape, slug, rupiah, dan tanggal"
```

---

### Task 4: Koneksi DB (`app/db.php`) + base class test DB

**Files:**
- Create: `app/db.php`, `tests/DbTestCase.php`
- Test: `tests/Integration/DbTest.php`

**Step 1: Tulis `tests/DbTestCase.php`**

Setiap class test membuat ulang skema, dan setiap test dibungkus transaksi yang di-rollback di akhir.

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = db();
        self::$pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
        self::$pdo->exec((string)file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    }

    protected function setUp(): void
    {
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }

    protected function insert(string $sql, array $params = []): int
    {
        $stmt = self::$pdo->prepare($sql . ' RETURNING id');
        $stmt->execute(array_map('db_param', $params));
        return (int)$stmt->fetchColumn();
    }
}
```

**Step 2: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class DbTest extends DbTestCase
{
    public function testConnectsToTestDatabase(): void
    {
        $this->assertSame('noah_test', self::$pdo->query('SELECT current_database()')->fetchColumn());
    }

    public function testTimezoneIsJakarta(): void
    {
        $this->assertSame('Asia/Jakarta', self::$pdo->query('SHOW TIME ZONE')->fetchColumn());
    }

    public function testBooleanParamIsConverted(): void
    {
        $this->assertSame('t', db_param(true));
        $this->assertSame('f', db_param(false));
        $this->assertSame(5, db_param(5));
    }

    public function testNestedTransactionUsesSavepoint(): void
    {
        try {
            with_transaction(self::$pdo, function (PDO $pdo): void {
                $pdo->exec("INSERT INTO subscribers (email) VALUES ('a@example.com')");
                throw new RuntimeException('batal');
            });
        } catch (RuntimeException) {
        }
        $count = self::$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
        $this->assertSame(0, (int)$count);
        $this->assertTrue(self::$pdo->inTransaction());
    }
}
```

**Step 3: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/DbTest.php`
Expected: FAIL `Call to undefined function db()`

**Step 4: Implementasi `app/db.php`**

```php
<?php
declare(strict_types=1);

function config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            throw new RuntimeException('config.php belum dibuat. Salin dari config.example.php.');
        }
        $loaded = require $path;
        $envDb = getenv('NOAH_DB_NAME');
        $config = ($envDb !== false && $envDb !== '')
            ? array_replace_recursive($loaded, ['db' => ['name' => $envDb]])
            : $loaded;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
    }
    return $pdo;
}

/** PDO pgsql mengirim false sebagai '' (tidak valid untuk boolean), jadi ubah ke 't'/'f'. */
function db_param(mixed $value): mixed
{
    return is_bool($value) ? ($value ? 't' : 'f') : $value;
}

function fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('db_param', $params));
    return $stmt->fetchAll();
}

function fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $row = fetch_all($pdo, $sql, $params)[0] ?? null;
    return $row === null ? null : $row;
}

/** Jalankan $fn dalam transaksi. Kalau sudah di dalam transaksi, pakai SAVEPOINT. */
function with_transaction(PDO $pdo, callable $fn): mixed
{
    if ($pdo->inTransaction()) {
        $pdo->exec('SAVEPOINT sp_noah');
        try {
            $result = $fn($pdo);
            $pdo->exec('RELEASE SAVEPOINT sp_noah');
            return $result;
        } catch (Throwable $e) {
            $pdo->exec('ROLLBACK TO SAVEPOINT sp_noah');
            throw $e;
        }
    }
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

**Step 5: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/DbTest.php`
Expected: PASS (4 tests)

**Step 6: Commit**

```bash
git add app/db.php tests/DbTestCase.php tests/Integration/DbTest.php
git commit -m "feat(app): koneksi PDO pgsql dengan helper transaksi"
```

---

### Task 5: CSRF (`app/csrf.php`)

**Files:**
- Create: `app/csrf.php`
- Test: `tests/Unit/CsrfTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableWithinSession(): void
    {
        $this->assertSame(64, strlen(csrf_token()));
        $this->assertSame(csrf_token(), csrf_token());
    }

    public function testValidation(): void
    {
        $token = csrf_token();
        $this->assertTrue(csrf_valid($token));
        $this->assertFalse(csrf_valid('salah'));
        $this->assertFalse(csrf_valid(null));
    }

    public function testFieldContainsEscapedToken(): void
    {
        $this->assertStringContainsString('name="_csrf" value="' . csrf_token() . '"', csrf_field());
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Unit/CsrfTest.php`
Expected: FAIL `Call to undefined function csrf_token()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(mixed $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/** Hentikan request yang bukan POST atau tokennya salah. */
function csrf_require(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_valid($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Unit/CsrfTest.php`
Expected: PASS (3 tests)

**Step 5: Commit**

```bash
git add app/csrf.php tests/Unit/CsrfTest.php
git commit -m "feat(app): token CSRF untuk semua form POST"
```

---

### Task 6: Validator berbasis definisi field (`app/validator.php`)

Dipakai bersama oleh CRUD admin dan form publik.

**Files:**
- Create: `app/validator.php`
- Test: `tests/Unit/ValidatorTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private const FIELDS = [
        'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 10],
        'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'name'],
        'price' => ['type' => 'money', 'label' => 'Harga', 'required' => true],
        'stock' => ['type' => 'int', 'label' => 'Stok', 'min' => 0, 'default' => 0],
        'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['kaos', 'musik']],
        'is_limited' => ['type' => 'bool', 'label' => 'Terbatas'],
        'image_url' => ['type' => 'image', 'label' => 'Gambar'],
    ];

    public function testValidInputIsCleaned(): void
    {
        [$data, $errors] = validate(self::FIELDS, [
            'name' => ' Kaos Hitam ', 'price' => '250000', 'stock' => '', 'category' => 'kaos', 'is_limited' => '1',
        ]);
        $this->assertSame([], $errors);
        $this->assertSame([
            'name' => 'Kaos Hitam', 'slug' => 'kaos-hitam', 'price' => '250000',
            'stock' => 0, 'category' => 'kaos', 'is_limited' => true,
        ], $data);
    }

    public function testErrorsPerField(): void
    {
        [, $errors] = validate(self::FIELDS, [
            'name' => 'Nama yang terlalu panjang', 'price' => '250.000', 'stock' => '-1', 'category' => 'sepatu',
        ]);
        $this->assertSame(['name', 'price', 'stock', 'category'], array_keys($errors));
        $this->assertSame('Nama maksimal 10 karakter.', $errors['name']);
    }

    public function testRequiredMissing(): void
    {
        [, $errors] = validate(self::FIELDS, []);
        $this->assertSame('Nama wajib diisi.', $errors['name']);
        $this->assertArrayHasKey('slug', $errors);
    }

    public function testEmailPhoneDatetime(): void
    {
        $fields = [
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'phone' => ['type' => 'phone', 'label' => 'Telepon', 'required' => true],
            'start_at' => ['type' => 'datetime', 'label' => 'Mulai', 'required' => true],
        ];
        [$data, $errors] = validate($fields, ['email' => 'A@Example.com', 'phone' => '+62 811-2222-3333', 'start_at' => '2027-08-24T19:00']);
        $this->assertSame([], $errors);
        $this->assertSame('a@example.com', $data['email']);
        $this->assertSame('2027-08-24 19:00:00', $data['start_at']);

        [, $bad] = validate($fields, ['email' => 'bukan-email', 'phone' => 'abc', 'start_at' => '2027-13-40T99:00']);
        $this->assertCount(3, $bad);
    }

    public function testLinkRejectsJavascriptScheme(): void
    {
        $f = ['url' => ['type' => 'link', 'label' => 'URL']];
        $this->assertSame([], validate($f, ['url' => 'https://www.google.com/maps?q=x'])[1]);
        $this->assertSame([], validate($f, ['url' => 'images/product-1.jpg'])[1]);
        $this->assertArrayHasKey('url', validate($f, ['url' => 'javascript:alert(1)'])[1]);
    }

    public function testJsonLinksMustBeHttp(): void
    {
        $f = ['streaming_links' => ['type' => 'json', 'label' => 'Link', 'default' => '{}']];
        $this->assertSame('{"spotify":"https://open.spotify.com/x"}',
            validate($f, ['streaming_links' => '{"spotify": "https://open.spotify.com/x"}'])[0]['streaming_links']);
        $this->assertArrayHasKey('streaming_links', validate($f, ['streaming_links' => '{"x": "javascript:1"}'])[1]);
        $this->assertSame('{}', validate($f, [])[0]['streaming_links']);
    }

    public function testPasswordHashedAndOptionalOnEdit(): void
    {
        $f = ['password_hash' => ['type' => 'password', 'label' => 'Password']];
        [$data] = validate($f, ['password_hash' => 'rahasia123']);
        $this->assertTrue(password_verify('rahasia123', $data['password_hash']));
        $this->assertArrayHasKey('password_hash', validate($f, [])[1]);
        $this->assertSame([[], []], validate($f, [], true));
        $this->assertArrayHasKey('password_hash', validate($f, ['password_hash' => 'pendek'])[1]);
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Unit/ValidatorTest.php`
Expected: FAIL `Call to undefined function validate()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

/**
 * Validasi input berdasarkan definisi field.
 * Field bertipe image dilewati (ditangani upload). Password kosong saat edit = tidak diubah.
 *
 * @return array{0: array<string, mixed>, 1: array<string, string>} [data bersih, error per field]
 */
function validate(array $fields, array $input, bool $isEdit = false): array
{
    $clean = [];
    $errors = [];
    foreach ($fields as $name => $rule) {
        [$has, $value, $error] = validate_field($name, $rule, $input, $isEdit);
        if ($error !== null) {
            $errors[$name] = $error;
        } elseif ($has) {
            $clean[$name] = $value;
        }
    }
    return [$clean, $errors];
}

/** @return array{0: bool, 1: mixed, 2: ?string} [punya nilai?, nilai, pesan error] */
function validate_field(string $name, array $rule, array $input, bool $isEdit): array
{
    $type = $rule['type'];
    $label = $rule['label'] ?? $name;
    if ($type === 'image') {
        return [false, null, null];
    }
    if ($type === 'bool') {
        return [true, !empty($input[$name]), null];
    }
    if ($type === 'slug') {
        $own = trim((string)($input[$name] ?? ''));
        $slug = slugify($own !== '' ? $own : (string)($input[$rule['from'] ?? ''] ?? ''));
        return $slug === '' ? [false, null, "$label tidak boleh kosong."] : [true, $slug, null];
    }
    $raw = is_string($input[$name] ?? null) ? trim($input[$name]) : '';
    if ($raw === '') {
        if ($type === 'password' && $isEdit) {
            return [false, null, null];
        }
        if (!empty($rule['required']) || $type === 'password') {
            return [false, null, "$label wajib diisi."];
        }
        return [true, $rule['default'] ?? null, null];
    }
    [$value, $error] = validate_value($type, $raw, $rule, $label);
    return [$error === null, $value, $error];
}

/** @return array{0: mixed, 1: ?string} */
function validate_value(string $type, string $raw, array $rule, string $label): array
{
    return match ($type) {
        'text', 'textarea' => check_length($raw, $rule, $label),
        'email' => filter_var($raw, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($raw) <= 150
            ? [mb_strtolower($raw), null] : [null, "$label tidak valid."],
        'phone' => preg_match('/^\+?[0-9][0-9\s-]{7,18}$/', $raw) === 1 ? [$raw, null] : [null, "$label tidak valid."],
        'link' => check_link($raw, $label),
        'int', 'fk' => check_int($raw, $rule, $label),
        'money' => preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw) === 1
            ? [$raw, null] : [null, "$label harus angka tanpa titik ribuan."],
        'date' => check_datetime($raw, 'Y-m-d', 'Y-m-d', $label),
        'datetime' => check_datetime($raw, 'Y-m-d\TH:i', 'Y-m-d H:i:00', $label),
        'enum' => in_array($raw, $rule['options'], true) ? [$raw, null] : [null, "$label tidak valid."],
        'json' => check_json_links($raw, $label),
        'password' => mb_strlen($raw) >= 8
            ? [password_hash($raw, PASSWORD_DEFAULT), null] : [null, "$label minimal 8 karakter."],
        default => throw new LogicException("Tipe field tidak dikenal: $type"),
    };
}

function check_length(string $raw, array $rule, string $label): array
{
    $max = $rule['max'] ?? null;
    return ($max !== null && mb_strlen($raw) > $max) ? [null, "$label maksimal $max karakter."] : [$raw, null];
}

/** Hanya http(s) atau path lokal tanpa skema (menolak javascript:, data:, dll). */
function check_link(string $raw, string $label): array
{
    $ok = preg_match('#^(https?://\S+|[A-Za-z0-9_\-./]+(\#[A-Za-z0-9_\-]+)?|\#[A-Za-z0-9_\-]+)$#', $raw) === 1;
    return $ok && mb_strlen($raw) <= 500 ? [$raw, null] : [null, "$label harus URL http(s) atau path lokal."];
}

function check_int(string $raw, array $rule, string $label): array
{
    if (preg_match('/^-?\d{1,9}$/', $raw) !== 1) {
        return [null, "$label harus angka bulat."];
    }
    $number = (int)$raw;
    if (isset($rule['min']) && $number < $rule['min']) {
        return [null, "$label minimal {$rule['min']}."];
    }
    if (isset($rule['max']) && $number > $rule['max']) {
        return [null, "$label maksimal {$rule['max']}."];
    }
    return [$number, null];
}

function check_datetime(string $raw, string $inFormat, string $outFormat, string $label): array
{
    $date = DateTimeImmutable::createFromFormat('!' . $inFormat, $raw);
    $warnings = DateTimeImmutable::getLastErrors();
    $invalid = $date === false || ($warnings !== false && ($warnings['warning_count'] + $warnings['error_count']) > 0);
    return $invalid ? [null, "$label tidak valid."] : [$date->format($outFormat), null];
}

/** JSON objek {nama: url}. Setiap url wajib http(s). */
function check_json_links(string $raw, string $label): array
{
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [null, "$label harus JSON objek, contoh {\"spotify\": \"https://...\"}."];
    }
    foreach ($decoded as $url) {
        if (!is_string($url) || preg_match('#^https?://\S+$#', $url) !== 1) {
            return [null, "$label: setiap link harus diawali http:// atau https://."];
        }
    }
    return [json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), null];
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Unit/ValidatorTest.php`
Expected: PASS (7 tests)

**Step 5: Commit**

```bash
git add app/validator.php tests/Unit/ValidatorTest.php
git commit -m "feat(app): validator input berbasis definisi field"
```

---

### Task 7: Pembatas percobaan (`app/throttle.php`)

Dipakai oleh login admin (5 kali gagal / 15 menit) dan form publik (5 kiriman / 10 menit), dengan kunci per IP. Data disimpan sebagai file JSON di folder temp, jadi tetap berlaku walaupun cookie dihapus.

**Files:**
- Create: `app/throttle.php`
- Test: `tests/Unit/ThrottleTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        throttle_clear('uji');
    }

    public function testPruneKeepsOnlyWindow(): void
    {
        $this->assertSame([950, 1000], throttle_prune([100, 950, 1000], 1000, 100));
    }

    public function testExceededAfterMaxHits(): void
    {
        foreach ([1000, 1001, 1002] as $t) {
            $this->assertFalse(throttle_exceeded('uji', 3, 60, $t));
            throttle_record('uji', 60, $t);
        }
        $this->assertTrue(throttle_exceeded('uji', 3, 60, 1003));
        $this->assertFalse(throttle_exceeded('uji', 3, 60, 1100));
    }

    public function testClear(): void
    {
        throttle_record('uji', 60, 1000);
        throttle_clear('uji');
        $this->assertFalse(throttle_exceeded('uji', 1, 60, 1000));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Unit/ThrottleTest.php`
Expected: FAIL `Call to undefined function throttle_clear()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

function throttle_dir(): string
{
    $env = getenv('NOAH_THROTTLE_DIR');
    $dir = ($env !== false && $env !== '') ? $env : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'noah_throttle';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder throttle tidak bisa dibuat.');
    }
    return $dir;
}

function throttle_file(string $key): string
{
    return throttle_dir() . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
}

/** @return list<int> */
function throttle_hits(string $key): array
{
    $file = throttle_file($key);
    $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
    return is_array($data) ? array_map('intval', $data) : [];
}

function throttle_prune(array $hits, int $now, int $window): array
{
    return array_values(array_filter($hits, fn (int $t): bool => $t > $now - $window));
}

function throttle_exceeded(string $key, int $max, int $window, ?int $now = null): bool
{
    return count(throttle_prune(throttle_hits($key), $now ?? time(), $window)) >= $max;
}

function throttle_record(string $key, int $window, ?int $now = null): void
{
    $time = $now ?? time();
    $hits = [...throttle_prune(throttle_hits($key), $time, $window), $time];
    file_put_contents(throttle_file($key), json_encode($hits), LOCK_EX);
}

function throttle_clear(string $key): void
{
    $file = throttle_file($key);
    if (is_file($file)) {
        unlink($file);
    }
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Unit/ThrottleTest.php`
Expected: PASS (3 tests)

**Step 5: Commit**

```bash
git add app/throttle.php tests/Unit/ThrottleTest.php
git commit -m "feat(app): pembatas percobaan berbasis IP"
```

---

### Task 8: Bootstrap aplikasi (`app/bootstrap.php`)

**Files:**
- Create: `app/bootstrap.php`

**Step 1: Implementasi**

`queries.php` dan `forms.php` baru dibuat di Task 16–17. Baris `require_once` untuk keduanya sudah ditulis sekarang, tapi dilindungi `is_file`.

```php
<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

foreach (['db', 'helpers', 'csrf', 'validator', 'throttle', 'queries', 'forms'] as $module) {
    $file = __DIR__ . "/$module.php";
    if (is_file($file)) {
        require_once $file;
    }
}

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
    header('X-Content-Type-Options: nosniff');

    set_exception_handler(function (Throwable $e): void {
        error_log((string)$e);
        http_response_code(500);
        echo 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
    });
}
```

**Step 2: Cek sintaks**

Run: `php -l app/bootstrap.php`
Expected: `No syntax errors detected in app/bootstrap.php`

**Step 3: Commit**

```bash
git add app/bootstrap.php
git commit -m "feat(app): bootstrap session, timezone, dan error handler"
```

---

### Task 9: Data awal (`database/seed.sql`) + pembuat akun admin

**Files:**
- Create: `database/seed.sql`, `database/create-admin.php`

**Step 1: Tulis `database/seed.sql`**

```sql
-- Data awal dari konten HTML statis. Jalankan setelah schema.sql.
-- Tanggal album memakai 1 Januari karena HTML hanya mencantumkan tahun.
BEGIN;

INSERT INTO members (name, position, bio, photo_url, sort_order) VALUES
('Ariel', 'Vokal / Frontman', 'Sang visioner dan penulis lirik utama yang suaranya telah menjadi lagu tema bagi jutaan orang.', 'images/member-ariel.jpg', 1),
('Lukman', 'Gitar', 'Arsitek melodi gitar yang memberikan nuansa atmosferik khas pada setiap lagu NOAH.', 'images/member-lukman.jpg', 2),
('David', 'Keyboard', 'Master harmonisasi yang membawa sentuhan modern lewat permainan keyboard yang elegan.', 'images/member-david.jpg', 3);

INSERT INTO milestones (year, badge, title, description, type, sort_order) VALUES
(1997, NULL, 'Terbentuknya Topi', 'Cikal bakal NOAH yang dibentuk di Bandung, mengawali perjalanan dari kafe ke kafe.', 'history', 1),
(2000, NULL, 'Lahirnya Peterpan', 'Resmi menggunakan nama Peterpan dan mulai merekam demo yang mengubah industri musik.', 'history', 2),
(2004, NULL, 'Bintang di Surga', 'Album fenomenal yang terjual lebih dari 3.2 juta keping, salah satu terlaris sepanjang masa.', 'history', 3),
(2012, NULL, 'Reinkarnasi: NOAH', 'Kembali dengan nama NOAH dan merilis ''Seperti Seharusnya'', membuktikan kekuatan mereka.', 'history', 4),
(2024, NULL, 'Hiatus & Refleksi', 'Mengambil waktu sejenak untuk persiapan karya yang lebih monumental di masa depan.', 'history', 5),
(NULL, '3x', 'AMI Awards', NULL, 'award', 1),
(NULL, '2x', 'MTV Asia', NULL, 'award', 2),
(2007, '2007', 'Asia Song Fest', NULL, 'award', 3),
(NULL, '2', 'MURI Records', NULL, 'award', 4),
(NULL, '9M+', 'Album Terjual', NULL, 'award', 5),
(NULL, 'KCA', 'Nickelodeon', NULL, 'award', 6);

INSERT INTO albums (title, slug, type, era, release_date, total_tracks, cover_url, is_featured, streaming_links) VALUES
('Keterkaitan Keterikatan', 'keterkaitan-keterikatan', 'studio', 'noah', '2019-01-01', 8, 'images/album-1.jpg', FALSE, '{}'),
('Second Chance', 'second-chance', 'studio', 'noah', '2014-01-01', 12, 'images/album-2.jpg', FALSE, '{}'),
('Sings Legends', 'sings-legends', 'kompilasi', 'noah', '2016-01-01', 9, 'images/album-3.jpg', FALSE, '{}'),
('Seperti Seharusnya', 'seperti-seharusnya', 'studio', 'noah', '2012-01-01', 10, 'images/album-cover.jpg', TRUE,
 '{"spotify": "https://open.spotify.com/album/4EneY8crCrt61L7siIXtkS?si=UTO1NEtATPqdLRThsBjRjQ", "ytmusic": "https://music.youtube.com/channel/UCbtHlc5rJCvSb_k0VyUowLA", "apple": "https://music.apple.com/id/artist/noah/766158889?l=id"}'),
('Bintang di Surga', 'bintang-di-surga', 'studio', 'peterpan', '2004-01-01', 10, 'images/gallery-2.jpg', FALSE, '{}'),
('Sebuah Nama, Sebuah Cerita', 'sebuah-nama-sebuah-cerita', 'kompilasi', 'peterpan', '2008-01-01', 11, 'images/gallery-4.jpg', FALSE, '{}');

-- Lagu yang tampil di kartu diskografi, Album Pilihan, tabel Single, dan setlist.
INSERT INTO tracks (album_id, track_no, title, duration_sec, single_year)
SELECT a.id, t.track_no, t.title, t.duration_sec, t.single_year
FROM (VALUES
  ('keterkaitan-keterikatan', 1, 'Wanitaku', NULL, 2019),
  ('keterkaitan-keterikatan', 2, 'Kupeluk Hatimu', NULL, NULL),
  ('keterkaitan-keterikatan', 3, 'Mendekati Lugu', NULL, NULL),
  ('second-chance', 1, 'Hero', NULL, NULL),
  ('second-chance', 2, 'Seperti Kemarin', NULL, NULL),
  ('second-chance', 3, 'Suara Pikiranku', NULL, NULL),
  ('sings-legends', 1, 'Andaikan Kau Datang', NULL, NULL),
  ('sings-legends', 2, 'Sajadah Panjang', NULL, NULL),
  ('sings-legends', 3, 'Biarlah Sendiri', NULL, NULL),
  ('seperti-seharusnya', 1, 'Separuh Aku', 266, 2012),
  ('seperti-seharusnya', 2, 'Hidup Untukmu, Mati Tanpamu', NULL, NULL),
  ('seperti-seharusnya', 3, 'Jika Engkau', 266, NULL),
  ('seperti-seharusnya', 4, 'Raja Negeriku', 270, NULL),
  ('bintang-di-surga', 1, 'Mungkin Nanti', NULL, NULL),
  ('bintang-di-surga', 2, 'Ada Apa Denganmu', NULL, NULL),
  ('bintang-di-surga', 3, 'Kukatakan Dengan Indah', NULL, NULL),
  ('bintang-di-surga', NULL, 'Khayalan Tingkat Tinggi', NULL, 2004),
  ('bintang-di-surga', NULL, 'Bintang di Surga', NULL, NULL),
  ('sebuah-nama-sebuah-cerita', 1, 'Menunggumu', NULL, 2008),
  ('sebuah-nama-sebuah-cerita', 2, 'Tak Bisakah', NULL, 2005),
  ('sebuah-nama-sebuah-cerita', 3, 'Walau Habis Terang', NULL, NULL)
) AS t(slug, track_no, title, duration_sec, single_year)
JOIN albums a ON a.slug = t.slug;

-- Lagu setlist yang albumnya belum dicatat di situs.
INSERT INTO tracks (title) VALUES
('Topeng'), ('Ada Gajah Dibalik Batu (Intro)'), ('Menghapus Jejakmu'), ('Yang Terdalam'), ('Mimpi Yang Sempurna');

INSERT INTO communities (city, name, leader_name, member_count) VALUES
('Jakarta', 'Sahabat NOAH Jakarta', 'Hendry Kusuma', 8420),
('Bandung', 'Sahabat NOAH Bandung', 'Asep Kurnia', 6150),
('Surabaya', 'Sahabat NOAH Surabaya', 'Budi Santoso', 4890),
('Yogyakarta', 'Sahabat NOAH Yogyakarta', 'Rarasati', 3720),
('Medan', 'Sahabat NOAH Medan', 'Iwan Ginting', 2900),
('Makassar', 'Sahabat NOAH Makassar', 'Zulkifli', 2140);

-- Konser mendatang: tanggal digeser ke 2027 (jadwal 2026 di HTML sudah lewat). Harga & kuota hanya contoh.
-- Arsip: tanggal event 2004 dan 2015 perkiraan karena HTML hanya mencantumkan tahun.
INSERT INTO events (community_id, title, slug, type, badge, city, venue, start_at, description, maps_url, ticket_price, quota, status) VALUES
(NULL, 'Malam Karya NOAH Jakarta', 'malam-karya-noah-jakarta-2027', 'concert', NULL, 'Jakarta', 'JIExpo Kemayoran', '2027-08-24 19:00+07',
 'Malam karya NOAH dengan sesi arsip visual, bernyanyi bersama, dan ruang temu Sahabat.',
 'https://www.google.com/maps?q=JIExpo%20Convention%20Centre%20and%20Theatre%20Jakarta&output=embed', 350000, 1000, 'published'),
(NULL, 'Temu Sahabat NOAH Bandung', 'temu-sahabat-noah-bandung-2027', 'concert', NULL, 'Bandung', 'SABUGA', '2027-09-12 19:00+07',
 'Temu Sahabat NOAH Bandung dengan pemutaran arsip konser dan sesi musik akustik.', NULL, 250000, 600, 'published'),
(NULL, 'Malam Tribut NOAH Surabaya', 'malam-tribut-noah-surabaya-2027', 'concert', NULL, 'Surabaya', 'Grand City Convention Hall', '2027-10-03 19:00+07',
 'Malam tribut NOAH bersama komunitas regional dan kurasi lagu lintas era.', NULL, 250000, 800, 'published'),
(NULL, '6 Kota dalam 24 Jam (2004)', '6-kota-dalam-24-jam-2004', 'concert', 'EVENT BERSEJARAH', 'Medan', NULL, '2004-01-01 19:00+07',
 'Medan, Padang, Pekanbaru, Lampung, Semarang, Surabaya', NULL, NULL, NULL, 'published'),
(NULL, '5 Negara dalam 24 Jam', '5-negara-dalam-24-jam-2012', 'concert', NULL, 'Melbourne', NULL, '2012-11-15 19:00+07',
 'Melbourne, Hong Kong, Kuala Lumpur, Singapore, Jakarta', NULL, NULL, NULL, 'published'),
(NULL, 'Tur AS (2015)', 'tur-as-2015', 'concert', 'TUR DUNIA', 'Los Angeles', NULL, '2015-01-01 19:00+07',
 'Los Angeles, San Francisco, Washington DC, New York', NULL, NULL, NULL, 'published'),
(NULL, 'The Great Journey of NOAH - Jakarta (2023)', 'the-great-journey-jakarta-2023', 'concert', NULL, 'Jakarta',
 'Ancol Beach City International Stadium', '2023-12-03 19:00+07', 'Tamu: Reza (Drummer Asli)', NULL, NULL, NULL, 'published'),
((SELECT id FROM communities WHERE city = 'Jakarta'), 'Sahabat NOAH Nasional', 'sahabat-noah-nasional-2027', 'community', 'Gathnas 2027',
 'Jakarta', NULL, '2027-10-24 10:00+07',
 'Pertemuan akbar tahunan seluruh komunitas wilayah di Jakarta. Sesi bermain musik bersama, diskusi, dan kejutan spesial.', NULL, NULL, NULL, 'published'),
((SELECT id FROM communities WHERE city = 'Bandung'), 'Lokakarya Musik & Kopi', 'lokakarya-musik-kopi-2026', 'community', 'Bandung',
 'Bandung', NULL, '2026-11-12 15:00+07',
 'Bedah instrumen lagu-lagu NOAH bersama musisi lokal dan sesi berbagi antar Sahabat.', NULL, NULL, NULL, 'published');

INSERT INTO setlist_items (event_id, position, track_id, is_encore)
SELECT e.id, s.position, t.id, s.is_encore
FROM (VALUES
  (1, 'Topeng', FALSE), (2, 'Ada Gajah Dibalik Batu (Intro)', FALSE), (3, 'Separuh Aku', FALSE),
  (4, 'Mungkin Nanti', FALSE), (5, 'Menghapus Jejakmu', FALSE), (6, 'Tak Bisakah', FALSE),
  (7, 'Yang Terdalam', FALSE), (8, 'Bintang di Surga', FALSE), (9, 'Khayalan Tingkat Tinggi', FALSE),
  (10, 'Wanitaku', FALSE), (11, 'Walau Habis Terang', TRUE), (12, 'Mimpi Yang Sempurna', TRUE)
) AS s(position, title, is_encore)
JOIN tracks t ON t.title = s.title
CROSS JOIN (SELECT id FROM events WHERE slug = 'the-great-journey-jakarta-2023') AS e;

INSERT INTO posts (title, slug, category, tag, is_featured, excerpt, body, cover_url, status, published_at) VALUES
('NOAH Umumkan Hiatus: Apa yang Terjadi Selanjutnya?', 'hiatus', 'news', 'Pengumuman', TRUE,
 'Setelah dekade penuh kesuksesan, Ariel dan kawan-kawan memutuskan untuk mengambil jeda panjang untuk eksplorasi kreatif baru.',
 'Setelah dekade penuh kesuksesan, Ariel dan kawan-kawan memutuskan untuk mengambil jeda panjang untuk eksplorasi kreatif baru.',
 NULL, 'published', '2024-01-15 10:00+07'),
('Kolaborasi Spesial NOAH dengan Orkestra Nasional', 'orkestra', 'news', 'Musik', FALSE,
 'Sebuah mahakarya baru yang menggabungkan elemen rock alternatif dengan kemegahan simfoni klasik.',
 'Sebuah mahakarya baru yang menggabungkan elemen rock alternatif dengan kemegahan simfoni klasik.',
 'images/news-1.jpg', 'published', '2024-01-12 10:00+07'),
('Tiket Konser ''The Final Show'' Terjual Habis dalam 5 Menit', 'final-show', 'news', 'Konser', FALSE,
 'Antusiasme penggemar memuncak menyambut pertunjukan terakhir sebelum masa hiatus resmi dimulai.',
 'Antusiasme penggemar memuncak menyambut pertunjukan terakhir sebelum masa hiatus resmi dimulai.',
 'images/news-2.jpg', 'published', '2024-01-08 10:00+07'),
('Ariel Berbagi Cerita Dibalik Lirik Lagu Teranyar', 'lirik', 'news', 'Pribadi', FALSE,
 'Wawancara eksklusif mengenai proses kreatif dan refleksi perjalanan karir musiknya.',
 'Wawancara eksklusif mengenai proses kreatif dan refleksi perjalanan karir musiknya.',
 'images/news-3.jpg', 'published', '2024-01-05 10:00+07'),
('Dibalik Layar Pembuatan Aransemen Baru "Keterkaitan Keterikatan"', 'aransemen-keterkaitan-keterikatan', 'news', 'Album', FALSE,
 'Eksplorasi sound terbaru NOAH yang menggabungkan elemen orkestra dengan synthesizer modern...',
 'Eksplorasi sound terbaru NOAH yang menggabungkan elemen orkestra dengan synthesizer modern.',
 'images/news-1.jpg', 'published', '2024-09-12 10:00+07'),
('Pengalaman Tak Terlupakan Tur "The Great Journey" di Singapura', 'great-journey-singapura', 'news', 'Tur', FALSE,
 'Ribuan Sahabat NOAH memadati Star Performing Arts Centre dalam malam yang penuh haru...',
 'Ribuan Sahabat NOAH memadati Star Performing Arts Centre dalam malam yang penuh haru.',
 'images/news-2.jpg', 'published', '2024-09-05 10:00+07'),
('Eksklusif: Sesi Bermusik NOAH Bersama Musisi Legendaris Mancanegara', 'sesi-musisi-mancanegara', 'news', 'Media', FALSE,
 'Kolaborasi lintas genre yang memberikan warna baru pada lagu-lagu klasik NOAH...',
 'Kolaborasi lintas genre yang memberikan warna baru pada lagu-lagu klasik NOAH.',
 'images/news-3.jpg', 'published', '2024-08-28 10:00+07'),
('Proses Rekaman Studio', 'proses-rekaman-studio', 'behind_scenes', 'Video', FALSE,
 'Melihat lebih dekat pembuatan album terakhir.', 'Melihat lebih dekat pembuatan album terakhir.',
 'images/gallery-1.jpg', 'published', '2024-01-03 10:00+07'),
('Momen Belakang Panggung', 'momen-belakang-panggung', 'behind_scenes', 'Foto', FALSE,
 'Persiapan sebelum naik ke panggung megah.', 'Persiapan sebelum naik ke panggung megah.',
 'images/gallery-2.jpg', 'published', '2024-01-02 10:00+07'),
('Pembuatan ''Wanitaku''', 'pembuatan-wanitaku', 'behind_scenes', 'Video', FALSE,
 'Eksplorasi desain suara yang mendalam.', 'Eksplorasi desain suara yang mendalam.',
 'images/gallery-3.jpg', 'published', '2024-01-01 10:00+07');

INSERT INTO media (type, tag, title, caption, file_url, created_at) VALUES
('photo', 'studio', 'Rekaman di studio', 'NOAH saat rekaman di studio', 'images/gallery-1.jpg', now() - interval '8 min'),
('photo', 'peterpan', 'Era Peterpan', 'Penampilan pada era Peterpan', 'images/gallery-2.jpg', now() - interval '7 min'),
('photo', 'konser', 'Panggung konser', 'NOAH tampil di panggung konser', 'images/gallery-3.jpg', now() - interval '6 min'),
('photo', 'peterpan', 'Perjalanan Peterpan', 'Dokumentasi perjalanan Peterpan', 'images/gallery-4.jpg', now() - interval '5 min'),
('photo', 'noah', 'NOAH terbaru', 'Dokumentasi NOAH terbaru', 'images/feed-1.jpg', now() - interval '4 min'),
('photo', 'konser', 'Suasana konser', 'Suasana konser NOAH', 'images/feed-2.jpg', now() - interval '3 min'),
('photo', 'studio', 'Kegiatan studio', 'Kegiatan NOAH di studio', 'images/feed-3.jpg', now() - interval '2 min'),
('photo', 'noah', 'Kegiatan anggota', 'Kegiatan para anggota NOAH', 'images/feed-4.jpg', now() - interval '1 min'),
('press_kit', NULL, 'Biografi Resmi', 'PDF • 2.4 MB', '#materi-pers', now() - interval '3 min'),
('press_kit', NULL, 'Paket Foto Resolusi Tinggi', 'ZIP • 145.0 MB', '#materi-pers', now() - interval '2 min'),
('press_kit', NULL, 'Paket Logo', 'SVG/PNG • 12.0 MB', '#materi-pers', now() - interval '1 min');

-- created_at berurutan: produk pertama = terbaru (dipakai urutan "Terbaru" di toko).
INSERT INTO products (name, slug, category, badge, price, stock, is_limited, image_url, description, created_at) VALUES
('NOAH Logo Tee Black', 'noah-logo-tee-black', 'kaos', 'Baru', 250000, 50, FALSE, 'images/product-1.jpg',
 'Kaos katun premium dengan logo NOAH minimalis.', now()),
('NOAH Logo Tee White', 'noah-logo-tee-white', 'kaos', NULL, 250000, 50, FALSE, 'images/product-2.jpg',
 'Varian warna putih ikonik untuk tampilan bersih.', now() - interval '1 day'),
('Bintang di Surga Anniversary Hoodie', 'bintang-di-surga-anniversary-hoodie', 'hoodie', 'Terbatas', 550000, 30, FALSE, 'images/product-3.jpg',
 'Edisi spesial perayaan album legendaris.', now() - interval '2 day'),
('NOAH Snapback Cap', 'noah-snapback-cap', 'aksesoris', NULL, 185000, 40, FALSE, 'images/product-4.jpg',
 'Topi snapback kualitas premium dengan bordir 3D.', now() - interval '3 day'),
('Keterkaitan Keterikatan Vinyl', 'keterkaitan-keterikatan-vinyl', 'musik', NULL, 450000, 20, FALSE, 'images/product-5.jpg',
 'Piringan hitam kualitas audiophile untuk kolektor.', now() - interval '4 day'),
('NOAH Tote Bag', 'noah-tote-bag', 'aksesoris', NULL, 120000, 60, FALSE, 'images/product-6.jpg',
 'Tas kanvas tebal tahan lama untuk kegiatan sehari-hari.', now() - interval '5 day'),
('Box Set Edisi Terbatas', 'box-set-edisi-terbatas', 'musik', NULL, 1500000, 23, TRUE, 'images/limited-1.jpg',
 'Paket eksklusif berisi 3 vinyl, buku foto dokumentasi tur, dan sertifikat keaslian bertandatangan personil.', now() - interval '6 day'),
('Peterpan Legacy Tee', 'peterpan-legacy-tee', 'kaos', NULL, 350000, 12, TRUE, 'images/limited-2.jpg',
 'Kaos edisi khusus mengenang perjalanan awal karir dengan desain grafis arsip original tahun 2003.', now() - interval '7 day');

INSERT INTO fan_submissions (community_id, name, email, type, content, file_url, status, created_at) VALUES
((SELECT id FROM communities WHERE city = 'Jakarta'), 'Andhika Prasetya', 'andhika@example.com', 'story',
 'NOAH mengubah hidup saya. Lirik-liriknya selalu menemani saat saya berada di titik terendah.', 'images/fan-1.jpg', 'approved', now()),
((SELECT id FROM communities WHERE city = 'Bandung'), 'Siti Rahma', 'siti@example.com', 'story',
 'Menemukan keluarga baru di komunitas ini. Sahabat NOAH Bandung sangat suportif dan kreatif.', 'images/fan-2.jpg', 'approved', now() - interval '1 min'),
((SELECT id FROM communities WHERE city = 'Surabaya'), 'Reza Kurniawan', 'reza@example.com', 'story',
 'Koleksi produk resmi saya sudah mencapai 100 item. Semua berawal dari kecintaan pada melodi Ariel.', 'images/fan-3.jpg', 'approved', now() - interval '2 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-1.jpg', 'approved', now() - interval '3 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-2.jpg', 'approved', now() - interval '4 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-3.jpg', 'approved', now() - interval '5 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-cover.jpg', 'approved', now() - interval '6 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/gallery-2.jpg', 'approved', now() - interval '7 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/gallery-4.jpg', 'approved', now() - interval '8 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-1.jpg', 'approved', now() - interval '9 min'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-2.jpg', 'approved', now() - interval '10 min');

COMMIT;
```

**Step 2: Tulis `database/create-admin.php`**

Akun admin dibuat lewat CLI, jadi tidak ada hash atau password yang tersimpan di repo.

```php
<?php
declare(strict_types=1);

// Pemakaian: php database/create-admin.php "Nama Admin" email@contoh.com [super_admin|editor]
if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan dari terminal.\n");
}
require dirname(__DIR__) . '/app/db.php';
require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/validator.php';

[, $name, $email, $role] = $argv + [null, '', '', 'super_admin'];
fwrite(STDOUT, 'Password (min 8 karakter, akan terlihat di layar): ');
$password = trim((string)fgets(STDIN));

[$data, $errors] = validate([
    'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
    'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
    'role' => ['type' => 'enum', 'label' => 'Role', 'required' => true, 'options' => ['super_admin', 'editor']],
    'password_hash' => ['type' => 'password', 'label' => 'Password'],
], ['name' => $name, 'email' => $email, 'role' => $role, 'password_hash' => $password]);

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
db()->prepare('INSERT INTO admins (name, email, role, password_hash) VALUES (:name, :email, :role, :password_hash)')
    ->execute($data);
echo "Admin {$data['email']} berhasil dibuat.\n";
```

**Step 3: Jalankan seed dan buat admin**

```bash
docker compose exec -T db psql -U noah -d noah -v ON_ERROR_STOP=1 -f - < database/seed.sql
php database/create-admin.php "Pudidil" fadilrahmanhakim@gmail.com super_admin
docker compose exec db psql -U noah -d noah -c "SELECT (SELECT count(*) FROM tracks) AS tracks, (SELECT count(*) FROM setlist_items) AS setlist, (SELECT count(*) FROM products) AS products, (SELECT count(*) FROM admins) AS admins;"
```

Expected: `COMMIT`, lalu `Admin ... berhasil dibuat.`, lalu `tracks=26 setlist=12 products=8 admins=1`.

**Step 4: Commit**

```bash
git add database/seed.sql database/create-admin.php
git commit -m "feat(db): seed konten awal dan skrip pembuat admin"
```

---

### Task 10: Repository CRUD generik (`admin/core/crud.php`)

**Files:**
- Create: `admin/core/crud.php`
- Test: `tests/Integration/CrudTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class CrudTest extends DbTestCase
{
    private const RES = [
        'table' => 'products',
        'search' => ['name'],
        'filters' => ['category'],
        'order' => 'id',
        'fields' => [
            'name' => ['type' => 'text'], 'slug' => ['type' => 'slug'], 'category' => ['type' => 'enum'],
            'price' => ['type' => 'money'], 'is_limited' => ['type' => 'bool'],
        ],
    ];

    private function product(string $name, string $category = 'kaos', bool $limited = false): int
    {
        return crud_insert(self::$pdo, self::RES, [
            'name' => $name, 'slug' => slugify($name), 'category' => $category, 'price' => '100000', 'is_limited' => $limited,
        ]);
    }

    public function testInsertFindUpdateDelete(): void
    {
        $id = $this->product('Kaos A');
        $this->assertSame('Kaos A', crud_find(self::$pdo, self::RES, $id)['name']);
        $this->assertFalse(crud_find(self::$pdo, self::RES, $id)['is_limited']);

        crud_update(self::$pdo, self::RES, $id, ['name' => 'Kaos B', 'is_limited' => true]);
        $row = crud_find(self::$pdo, self::RES, $id);
        $this->assertSame('Kaos B', $row['name']);
        $this->assertTrue($row['is_limited']);

        crud_delete(self::$pdo, self::RES, $id);
        $this->assertNull(crud_find(self::$pdo, self::RES, $id));
    }

    public function testUnknownColumnsAreIgnored(): void
    {
        $id = crud_insert(self::$pdo, self::RES, [
            'name' => 'X', 'slug' => 'x', 'category' => 'kaos', 'price' => '1', 'stock' => 999, 'id' => 777,
        ]);
        $this->assertNotSame(777, $id);
        $this->assertSame(0, (int)crud_find(self::$pdo, self::RES, $id)['stock']);
    }

    public function testListSearchFilterPagination(): void
    {
        $this->product('Kaos Hitam');
        $this->product('Kaos 100%');
        $this->product('Vinyl', 'musik');
        $this->assertSame(3, crud_list(self::$pdo, self::RES)['total']);
        $this->assertSame(2, crud_list(self::$pdo, self::RES, 'kaos')['total']);
        $this->assertSame(1, crud_list(self::$pdo, self::RES, '%')['total']);
        $this->assertSame(1, crud_list(self::$pdo, self::RES, '', ['category' => 'musik'])['total']);
        $this->assertSame(1, crud_list(self::$pdo, self::RES, '', [], 99)['page']);
    }

    public function testDuplicateSlugRaisesFriendlyError(): void
    {
        $this->product('Sama');
        $this->expectException(CrudDuplicateError::class);
        $this->product('Sama');
    }

    public function testRestrictedDeleteRaisesConstraintError(): void
    {
        $tracks = ['table' => 'tracks', 'fields' => ['title' => ['type' => 'text']]];
        $trackId = crud_insert(self::$pdo, $tracks, ['title' => 'Lagu']);
        $eventId = $this->insert("INSERT INTO events (title, slug, city, start_at) VALUES ('E', 'e', 'Kota', now())");
        self::$pdo->exec("INSERT INTO setlist_items (event_id, position, track_id) VALUES ($eventId, 1, $trackId)");
        $this->expectException(CrudConstraintError::class);
        crud_delete(self::$pdo, $tracks, $trackId);
    }

    public function testIdentRejectsInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ident('name; DROP TABLE admins');
    }

    public function testFkOptions(): void
    {
        $id = $this->product('Opsi');
        $this->assertSame([$id => 'Opsi'], fk_options(self::$pdo, 'products', 'name'));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/CrudTest.php`
Expected: FAIL `Call to undefined function crud_insert()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

final class CrudConstraintError extends RuntimeException {}
final class CrudDuplicateError extends RuntimeException {}

const CRUD_PER_PAGE = 20;

/** Quote nama tabel/kolom. Hanya huruf kecil, angka, dan underscore yang diizinkan. */
function ident(string $name): string
{
    if (preg_match('/^[a-z_][a-z0-9_]*$/', $name) !== 1) {
        throw new InvalidArgumentException("Nama kolom/tabel tidak valid: $name");
    }
    return '"' . $name . '"';
}

/** Hanya kolom yang terdaftar di config (+ kolom owner) yang boleh ditulis. */
function crud_columns(array $res, array $data): array
{
    $allowed = $res['fields'] + (isset($res['owner']) ? [$res['owner'] => true] : []);
    return array_intersect_key($data, $allowed);
}

/** @return array{0: string, 1: array<string, string>} */
function crud_where(array $res, string $q, array $filters): array
{
    $parts = [];
    $params = [];
    if ($q !== '' && !empty($res['search'])) {
        $likes = [];
        foreach (array_values($res['search']) as $i => $col) {
            $likes[] = ident($col) . "::text ILIKE :q$i";
            $params["q$i"] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $parts[] = '(' . implode(' OR ', $likes) . ')';
    }
    foreach ($res['filters'] ?? [] as $col) {
        $value = $filters[$col] ?? '';
        if (is_string($value) && $value !== '') {
            $parts[] = ident($col) . " = :f_$col";
            $params["f_$col"] = $value;
        }
    }
    return [$parts === [] ? '' : 'WHERE ' . implode(' AND ', $parts), $params];
}

/** @return array{rows: array, total: int, page: int, pages: int} */
function crud_list(PDO $pdo, array $res, string $q = '', array $filters = [], int $page = 1): array
{
    [$where, $params] = crud_where($res, $q, $filters);
    $table = ident($res['table']);
    $total = (int)crud_run($pdo, "SELECT COUNT(*) FROM $table $where", $params)->fetchColumn();
    $pages = max(1, (int)ceil($total / CRUD_PER_PAGE));
    $current = min(max(1, $page), $pages);
    $order = $res['order'] ?? 'id DESC';
    $stmt = $pdo->prepare("SELECT * FROM $table $where ORDER BY $order LIMIT :_limit OFFSET :_offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('_limit', CRUD_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue('_offset', ($current - 1) * CRUD_PER_PAGE, PDO::PARAM_INT);
    $stmt->execute();
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $current, 'pages' => $pages];
}

function crud_find(PDO $pdo, array $res, int $id): ?array
{
    $row = crud_run($pdo, 'SELECT * FROM ' . ident($res['table']) . ' WHERE id = :id', ['id' => $id])->fetch();
    return $row === false ? null : $row;
}

function crud_insert(PDO $pdo, array $res, array $data): int
{
    $cols = crud_columns($res, $data);
    $names = array_keys($cols);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
        ident($res['table']),
        implode(', ', array_map('ident', $names)),
        implode(', ', array_map(fn (string $n): string => ":$n", $names))
    );
    return (int)crud_run($pdo, $sql, $cols)->fetchColumn();
}

function crud_update(PDO $pdo, array $res, int $id, array $data): void
{
    $cols = crud_columns($res, $data);
    if ($cols === []) {
        return;
    }
    $set = implode(', ', array_map(fn (string $n): string => ident($n) . " = :$n", array_keys($cols)));
    crud_run($pdo, sprintf('UPDATE %s SET %s WHERE id = :_id', ident($res['table']), $set), $cols + ['_id' => $id]);
}

function crud_delete(PDO $pdo, array $res, int $id): void
{
    crud_run($pdo, 'DELETE FROM ' . ident($res['table']) . ' WHERE id = :id', ['id' => $id]);
}

/** Jalankan query; ubah error constraint Postgres menjadi pesan yang ramah. */
function crud_run(PDO $pdo, string $sql, array $params = []): PDOStatement
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_map('db_param', $params));
        return $stmt;
    } catch (PDOException $e) {
        throw match ((string)$e->getCode()) {
            '23503' => new CrudConstraintError('Data masih dipakai oleh data lain, jadi tidak bisa dihapus atau diubah.', 0, $e),
            '23505' => new CrudDuplicateError('Sudah ada data dengan nilai unik yang sama (cek slug atau email).', 0, $e),
            default => $e,
        };
    }
}

/** @return array<int, string> id => label */
function fk_options(PDO $pdo, string $table, string $labelCol): array
{
    $label = ident($labelCol);
    $rows = $pdo->query(sprintf('SELECT id, %s AS label FROM %s ORDER BY %s', $label, ident($table), $label))->fetchAll();
    return array_combine(array_map('intval', array_column($rows, 'id')), array_column($rows, 'label'));
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/CrudTest.php`
Expected: PASS (7 tests)

**Step 5: Commit**

```bash
git add admin/core/crud.php tests/Integration/CrudTest.php
git commit -m "feat(admin): repository CRUD generik dengan whitelist kolom"
```

---

### Task 11: Upload gambar (`admin/core/upload.php`)

**Files:**
- Create: `admin/core/upload.php`, `uploads/.htaccess`
- Test: `tests/Unit/UploadTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UploadTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/noah_upload_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    private function file(string $content, int $error = UPLOAD_ERR_OK): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $content);
        return ['tmp_name' => $tmp, 'error' => $error, 'size' => strlen($content), 'name' => 'x.jpg'];
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    public function testStoresValidImageWithRandomName(): void
    {
        [$path, $error] = store_upload($this->file($this->png()), 'products', $this->dir, 'uploads', 'rename');
        $this->assertNull($error);
        $this->assertMatchesRegularExpression('#^uploads/products/[a-f0-9]{16}\.png$#', $path);
        $this->assertFileExists($this->dir . substr($path, strlen('uploads')));
    }

    public function testNoFileIsNotAnError(): void
    {
        $this->assertSame([null, null], store_upload([], 'products', $this->dir, 'uploads', 'rename'));
    }

    public function testRejectsPhpDisguisedAsJpg(): void
    {
        [$path, $error] = store_upload($this->file('<?php echo 1;'), 'products', $this->dir, 'uploads', 'rename');
        $this->assertNull($path);
        $this->assertSame('Format gambar harus JPG, PNG, atau WEBP.', $error);
    }

    public function testRejectsTooLarge(): void
    {
        $file = $this->file($this->png());
        [, $error] = store_upload(['size' => UPLOAD_MAX_BYTES + 1] + $file, 'products', $this->dir, 'uploads', 'rename');
        $this->assertSame('Ukuran gambar maksimal 2 MB.', $error);
    }

    public function testDeleteOnlyInsideUploadDir(): void
    {
        [$path] = store_upload($this->file($this->png()), 'products', $this->dir, 'uploads', 'rename');
        $this->assertFalse(delete_upload('images/product-1.jpg', $this->dir, 'uploads'));
        $this->assertFalse(delete_upload('uploads/../../etc/passwd', $this->dir, 'uploads'));
        $this->assertTrue(delete_upload($path, $this->dir, 'uploads'));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Unit/UploadTest.php`
Expected: FAIL `Call to undefined function store_upload()`

**Step 3: Implementasi `admin/core/upload.php`**

```php
<?php
declare(strict_types=1);

const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;
const UPLOAD_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

/**
 * Simpan file upload ke $baseDir/$folder dengan nama acak.
 * $move hanya diganti di test. Di produksi selalu move_uploaded_file.
 *
 * @return array{0: ?string, 1: ?string} [path relatif untuk disimpan di DB, pesan error]
 */
function store_upload(array $file, string $folder, string $baseDir, string $baseUrl, callable $move = null): array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [null, "Upload gagal (kode $error)."];
    }
    if ((int)($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return [null, 'Ukuran gambar maksimal 2 MB.'];
    }
    $ext = UPLOAD_MIME[(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])] ?? null;
    if ($ext === null) {
        return [null, 'Format gambar harus JPG, PNG, atau WEBP.'];
    }
    if (preg_match('/^[a-z_]+$/', $folder) !== 1) {
        throw new InvalidArgumentException('Folder upload tidak valid.');
    }
    $dir = $baseDir . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'Folder upload tidak bisa dibuat.'];
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $mover = $move ?? 'move_uploaded_file';
    return $mover($file['tmp_name'], "$dir/$name") ? ["$baseUrl/$folder/$name", null] : [null, 'File gagal disimpan.'];
}

/** Hapus file hanya jika berada di dalam folder upload (bukan gambar bawaan di images/). */
function delete_upload(?string $relPath, string $baseDir, string $baseUrl): bool
{
    if ($relPath === null || !str_starts_with($relPath, $baseUrl . '/')) {
        return false;
    }
    $full = realpath($baseDir . substr($relPath, strlen($baseUrl)));
    $root = realpath($baseDir);
    if ($full === false || $root === false || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
        return false;
    }
    return unlink($full);
}
```

**Step 4: Buat `uploads/.htaccess`**

Untuk XAMPP/Apache: matikan eksekusi script di folder upload.

```apache
php_flag engine off
<FilesMatch "\.(php|phtml|phar)$">
  Require all denied
</FilesMatch>
Options -Indexes
```

**Step 5: Jalankan test**

Run: `vendor/bin/phpunit tests/Unit/UploadTest.php`
Expected: PASS (5 tests)

**Step 6: Commit**

```bash
git add admin/core/upload.php uploads/.htaccess tests/Unit/UploadTest.php
git commit -m "feat(admin): upload gambar aman dengan cek MIME dan nama acak"
```

---

### Task 12: Login admin (`admin/core/auth.php`)

**Files:**
- Create: `admin/core/auth.php`
- Test: `tests/Integration/AuthTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class AuthTest extends DbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->insert('INSERT INTO admins (name, email, password_hash, role) VALUES (:n, :e, :h, :r)', [
            'n' => 'Admin', 'e' => 'admin@example.com', 'h' => password_hash('rahasia123', PASSWORD_DEFAULT), 'r' => 'editor',
        ]);
    }

    public function testAttemptLogin(): void
    {
        $admin = attempt_login(self::$pdo, 'ADMIN@example.com', 'rahasia123');
        $this->assertSame(['name' => 'Admin', 'role' => 'editor'], array_intersect_key($admin, ['name' => 1, 'role' => 1]));
        $this->assertArrayNotHasKey('password_hash', $admin);
        $this->assertNull(attempt_login(self::$pdo, 'admin@example.com', 'salah'));
        $this->assertNull(attempt_login(self::$pdo, 'tidakada@example.com', 'rahasia123'));
    }

    public function testSessionIdleTimeout(): void
    {
        login_admin(['id' => 1, 'name' => 'Admin', 'role' => 'editor']);
        $this->assertNotNull(current_admin(time()));
        $this->assertNull(current_admin(time() + SESSION_IDLE_SEC + 1));
        $this->assertArrayNotHasKey('admin', $_SESSION);
    }

    public function testRoles(): void
    {
        $editor = ['id' => 1, 'name' => 'E', 'role' => 'editor'];
        $this->assertTrue(admin_can($editor, []));
        $this->assertFalse(admin_can($editor, ['super_admin']));
        $this->assertTrue(admin_can(['role' => 'super_admin'] + $editor, ['super_admin']));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/AuthTest.php`
Expected: FAIL `Call to undefined function attempt_login()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SEC = 900;
const SESSION_IDLE_SEC = 7200;

/** @return array{id: int, name: string, email: string, role: string}|null */
function attempt_login(PDO $pdo, string $email, string $password): ?array
{
    static $dummyHash = null;
    $row = fetch_one($pdo, 'SELECT id, name, email, role, password_hash FROM admins WHERE email = :email', [
        'email' => mb_strtolower(trim($email)),
    ]);
    // Tetap jalankan password_verify saat email tidak ada, supaya waktunya sama.
    $dummyHash ??= password_hash('dummy-password', PASSWORD_DEFAULT);
    $valid = password_verify($password, $row['password_hash'] ?? $dummyHash);
    if ($row === null || !$valid) {
        return null;
    }
    return ['id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];
}

function login_admin(array $admin): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['admin'] = $admin;
    $_SESSION['admin_seen'] = time();
}

function logout_admin(): void
{
    unset($_SESSION['admin'], $_SESSION['admin_seen']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function current_admin(?int $now = null): ?array
{
    $time = $now ?? time();
    $admin = $_SESSION['admin'] ?? null;
    if (!is_array($admin)) {
        return null;
    }
    if ($time - (int)($_SESSION['admin_seen'] ?? 0) > SESSION_IDLE_SEC) {
        logout_admin();
        return null;
    }
    $_SESSION['admin_seen'] = $time;
    return $admin;
}

function require_admin(): array
{
    return current_admin() ?? redirect('index.php?r=login');
}

function admin_can(array $admin, array $roles): bool
{
    return $roles === [] || in_array($admin['role'], $roles, true);
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/AuthTest.php`
Expected: PASS (3 tests)

**Step 5: Commit**

```bash
git add admin/core/auth.php tests/Integration/AuthTest.php
git commit -m "feat(admin): login, session idle timeout, dan cek role"
```

---

### Task 13: Config resource (`admin/resources.php`)

**Files:**
- Create: `admin/resources.php`
- Test: `tests/Integration/ResourcesSchemaTest.php`

**Step 1: Tulis test yang gagal**

Test ini memastikan setiap kolom di config benar-benar ada di tabelnya.

```php
<?php
declare(strict_types=1);

final class ResourcesSchemaTest extends DbTestCase
{
    public function testEveryConfiguredColumnExists(): void
    {
        $resources = require dirname(__DIR__, 2) . '/admin/resources.php';
        foreach ($resources as $key => $res) {
            if (isset($res['custom'])) {
                continue;
            }
            $existing = array_column(fetch_all(self::$pdo,
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :t",
                ['t' => $res['table']]), 'column_name');
            $expected = array_unique([
                ...array_keys($res['fields']), ...$res['list'], ...($res['search'] ?? []),
                ...($res['filters'] ?? []), ...(isset($res['owner']) ? [$res['owner']] : []),
            ]);
            $this->assertSame([], array_values(array_diff($expected, $existing)), "Kolom tidak ada di resource $key");
        }
    }

    public function testEnumOptionsMatchDatabase(): void
    {
        $resources = require dirname(__DIR__, 2) . '/admin/resources.php';
        $this->assertSame(['kaos', 'hoodie', 'aksesoris', 'musik'], $resources['products']['fields']['category']['options']);
        $dbValues = array_column(fetch_all(self::$pdo, "SELECT unnest(enum_range(NULL::product_category))::text AS v"), 'v');
        $this->assertSame($dbValues, $resources['products']['fields']['category']['options']);
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/ResourcesSchemaTest.php`
Expected: FAIL `Failed opening required '.../admin/resources.php'`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

// Definisi menu panel admin. Tipe field: text, textarea, slug, int, money, bool, date, datetime,
// enum (options), fk (ref, ref_label), image, link, email, json, password.
const STATUS_OPTIONS = ['draft', 'published', 'archived'];

return [
    'members' => [
        'label' => 'Anggota', 'table' => 'members',
        'list' => ['name', 'position', 'sort_order', 'is_active'],
        'search' => ['name', 'position'], 'order' => 'sort_order, id',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
            'position' => ['type' => 'text', 'label' => 'Posisi', 'required' => true, 'max' => 50],
            'bio' => ['type' => 'textarea', 'label' => 'Bio'],
            'photo_url' => ['type' => 'image', 'label' => 'Foto'],
            'sort_order' => ['type' => 'int', 'label' => 'Urutan', 'min' => 0, 'max' => 999, 'default' => 0],
            'is_active' => ['type' => 'bool', 'label' => 'Aktif'],
        ],
    ],
    'milestones' => [
        'label' => 'Sejarah & Penghargaan', 'table' => 'milestones',
        'list' => ['type', 'year', 'badge', 'title', 'sort_order'],
        'search' => ['title'], 'filters' => ['type'], 'order' => 'type, sort_order, id',
        'fields' => [
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['history', 'award']],
            'year' => ['type' => 'int', 'label' => 'Tahun (sejarah)', 'min' => 1990, 'max' => 2100],
            'badge' => ['type' => 'text', 'label' => 'Angka statistik (penghargaan, mis. 3x)', 'max' => 20],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'image_url' => ['type' => 'image', 'label' => 'Gambar'],
            'sort_order' => ['type' => 'int', 'label' => 'Urutan', 'min' => 0, 'max' => 999, 'default' => 0],
        ],
    ],
    'albums' => [
        'label' => 'Album', 'table' => 'albums',
        'list' => ['title', 'type', 'era', 'release_date', 'is_featured'],
        'search' => ['title'], 'filters' => ['era', 'type'], 'order' => 'release_date DESC NULLS LAST, id',
        'links' => ['Lagu' => 'tracks&album_id={id}'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'type' => ['type' => 'enum', 'label' => 'Tipe', 'required' => true, 'options' => ['studio', 'kompilasi', 'soundtrack']],
            'era' => ['type' => 'enum', 'label' => 'Era', 'required' => true, 'options' => ['peterpan', 'noah']],
            'release_date' => ['type' => 'date', 'label' => 'Tanggal rilis'],
            'total_tracks' => ['type' => 'int', 'label' => 'Jumlah lagu', 'min' => 1, 'max' => 99],
            'cover_url' => ['type' => 'image', 'label' => 'Cover'],
            'is_featured' => ['type' => 'bool', 'label' => 'Album Pilihan di beranda'],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'streaming_links' => ['type' => 'json', 'label' => 'Link streaming (JSON)', 'default' => '{}'],
        ],
    ],
    'tracks' => [
        'label' => 'Lagu', 'table' => 'tracks',
        'list' => ['album_id', 'track_no', 'title', 'duration_sec', 'single_year'],
        'search' => ['title'], 'filters' => ['album_id'], 'order' => 'album_id NULLS LAST, track_no NULLS LAST, title',
        'fields' => [
            'album_id' => ['type' => 'fk', 'label' => 'Album', 'ref' => 'albums', 'ref_label' => 'title'],
            'track_no' => ['type' => 'int', 'label' => 'Nomor lagu', 'min' => 1, 'max' => 99],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'duration_sec' => ['type' => 'int', 'label' => 'Durasi (detik)', 'min' => 1, 'max' => 3600],
            'single_year' => ['type' => 'int', 'label' => 'Tahun single (kosongkan jika bukan single)', 'min' => 1990, 'max' => 2100],
        ],
    ],
    'communities' => [
        'label' => 'Komunitas', 'table' => 'communities',
        'list' => ['city', 'name', 'leader_name', 'member_count'],
        'search' => ['city', 'name'], 'order' => 'member_count DESC, city',
        'fields' => [
            'city' => ['type' => 'text', 'label' => 'Kota', 'required' => true, 'max' => 80],
            'name' => ['type' => 'text', 'label' => 'Nama komunitas', 'required' => true, 'max' => 120],
            'leader_name' => ['type' => 'text', 'label' => 'Ketua', 'max' => 100],
            'contact_url' => ['type' => 'link', 'label' => 'Link kontak'],
            'member_count' => ['type' => 'int', 'label' => 'Jumlah anggota', 'min' => 0, 'default' => 0],
        ],
    ],
    'events' => [
        'label' => 'Event', 'table' => 'events',
        'list' => ['title', 'type', 'city', 'start_at', 'status'],
        'search' => ['title', 'city', 'venue'], 'filters' => ['type', 'status'], 'order' => 'start_at DESC',
        'links' => ['Setlist' => 'setlist&event_id={id}', 'Pesanan' => 'ticket_orders&event_id={id}'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['concert', 'community']],
            'community_id' => ['type' => 'fk', 'label' => 'Komunitas penyelenggara', 'ref' => 'communities', 'ref_label' => 'city'],
            'badge' => ['type' => 'text', 'label' => 'Label (mis. TUR DUNIA)', 'max' => 40],
            'city' => ['type' => 'text', 'label' => 'Kota', 'required' => true, 'max' => 80],
            'venue' => ['type' => 'text', 'label' => 'Venue', 'max' => 150],
            'start_at' => ['type' => 'datetime', 'label' => 'Waktu mulai', 'required' => true],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'maps_url' => ['type' => 'link', 'label' => 'URL embed Google Maps'],
            'poster_url' => ['type' => 'image', 'label' => 'Poster'],
            'ticket_price' => ['type' => 'money', 'label' => 'Harga tiket (kosong = tidak dijual)'],
            'quota' => ['type' => 'int', 'label' => 'Kuota tiket', 'min' => 0],
            'status' => ['type' => 'enum', 'label' => 'Status', 'required' => true, 'options' => STATUS_OPTIONS],
        ],
    ],
    'posts' => [
        'label' => 'Berita', 'table' => 'posts', 'owner' => 'admin_id',
        'list' => ['title', 'category', 'tag', 'status', 'published_at'],
        'search' => ['title', 'excerpt'], 'filters' => ['category', 'status'], 'order' => 'published_at DESC NULLS FIRST',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 200],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['news', 'behind_scenes']],
            'tag' => ['type' => 'text', 'label' => 'Label pill (mis. Konser, Video)', 'max' => 30],
            'is_featured' => ['type' => 'bool', 'label' => 'Jadikan headline halaman Media'],
            'excerpt' => ['type' => 'textarea', 'label' => 'Ringkasan', 'max' => 300],
            'body' => ['type' => 'textarea', 'label' => 'Isi', 'required' => true],
            'cover_url' => ['type' => 'image', 'label' => 'Gambar sampul'],
            'status' => ['type' => 'enum', 'label' => 'Status', 'required' => true, 'options' => STATUS_OPTIONS],
            'published_at' => ['type' => 'datetime', 'label' => 'Tanggal terbit'],
        ],
    ],
    'media' => [
        'label' => 'Galeri & Press Kit', 'table' => 'media',
        'list' => ['type', 'tag', 'title', 'caption'],
        'search' => ['title', 'caption'], 'filters' => ['type'], 'order' => 'type, created_at',
        'fields' => [
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['photo', 'video', 'press_kit']],
            'tag' => ['type' => 'enum', 'label' => 'Filter galeri', 'options' => ['peterpan', 'noah', 'konser', 'studio']],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'caption' => ['type' => 'text', 'label' => 'Keterangan / info file', 'max' => 255],
            'file_url' => ['type' => 'image', 'label' => 'File (gambar, atau isi link untuk press kit)', 'required' => true],
            'post_id' => ['type' => 'fk', 'label' => 'Terkait berita', 'ref' => 'posts', 'ref_label' => 'title'],
        ],
    ],
    'products' => [
        'label' => 'Produk', 'table' => 'products',
        'list' => ['name', 'category', 'price', 'stock', 'is_limited'],
        'search' => ['name'], 'filters' => ['category'], 'order' => 'created_at DESC',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'name'],
            'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['kaos', 'hoodie', 'aksesoris', 'musik']],
            'badge' => ['type' => 'enum', 'label' => 'Badge', 'options' => ['Baru', 'Terbatas']],
            'price' => ['type' => 'money', 'label' => 'Harga', 'required' => true],
            'stock' => ['type' => 'int', 'label' => 'Stok', 'min' => 0, 'default' => 0],
            'is_limited' => ['type' => 'bool', 'label' => 'Tampil di Edisi Terbatas'],
            'image_url' => ['type' => 'image', 'label' => 'Gambar'],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi', 'max' => 500],
        ],
    ],
    'subscribers' => [
        'label' => 'Subscriber', 'table' => 'subscribers',
        'list' => ['email', 'is_active', 'created_at'],
        'search' => ['email'], 'order' => 'created_at DESC',
        'fields' => [
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'is_active' => ['type' => 'bool', 'label' => 'Aktif'],
        ],
    ],
    'admins' => [
        'label' => 'Admin', 'table' => 'admins', 'roles' => ['super_admin'],
        'list' => ['name', 'email', 'role', 'created_at'],
        'search' => ['name', 'email'], 'order' => 'id',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'role' => ['type' => 'enum', 'label' => 'Role', 'required' => true, 'options' => ['editor', 'super_admin']],
            'password_hash' => ['type' => 'password', 'label' => 'Password (kosongkan saat edit jika tidak diganti)'],
        ],
    ],
    'ticket_orders' => ['label' => 'Pesanan Tiket', 'custom' => 'ticket_orders'],
    'fan_submissions' => ['label' => 'Kiriman Fans', 'custom' => 'fan_submissions'],
    'setlist' => ['label' => 'Setlist', 'custom' => 'setlist'],
];
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/ResourcesSchemaTest.php`
Expected: PASS (2 tests)

**Step 5: Commit**

```bash
git add admin/resources.php tests/Integration/ResourcesSchemaTest.php
git commit -m "feat(admin): definisi resource CRUD untuk 11 tabel"
```

---

### Task 14: Router, controller, view, dan CSS panel admin

**Files:**
- Create: `admin/index.php`, `admin/core/controller.php`, `admin/core/fields.php`, `admin/admin.css`
- Create: `admin/views/layout.php`, `login.php`, `dashboard.php`, `list.php`, `form.php`, `error.php`

**Step 1: `admin/index.php` (router)**

```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
foreach (['auth', 'crud', 'upload', 'fields', 'workflow', 'controller'] as $module) {
    require_once __DIR__ . "/core/$module.php";
}

header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$resources = require __DIR__ . '/resources.php';
$route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'dashboard';
$action = is_string($_GET['a'] ?? null) ? $_GET['a'] : 'list';

if ($route === 'login') {
    handle_login();
}
if ($route === 'logout') {
    csrf_require();
    logout_admin();
    redirect('index.php?r=login');
}

$admin = require_admin();
if ($route === 'dashboard') {
    render('dashboard', ['title' => 'Dashboard', 'stats' => dashboard_stats(db())]);
    exit;
}

$resource = $resources[$route] ?? null;
if ($resource === null) {
    not_found();
}
if (!admin_can($admin, $resource['roles'] ?? [])) {
    http_response_code(403);
    render('error', ['title' => 'Akses ditolak', 'message' => 'Menu ini hanya untuk super admin.']);
    exit;
}
if (isset($resource['custom'])) {
    require __DIR__ . '/custom/' . $resource['custom'] . '.php';
    exit;
}
handle_crud($route, $resource, $action, $admin);
```

**Step 2: `admin/core/controller.php`**

```php
<?php
declare(strict_types=1);

function render(string $view, array $vars = []): void
{
    $admin = array_key_exists('admin', $vars) ? $vars['admin'] : current_admin();
    $resources = $GLOBALS['resources'] ?? [];
    extract($vars, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/views/' . $view . '.php';
    $content = (string)ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}

function not_found(): never
{
    http_response_code(404);
    render('error', ['title' => 'Tidak ditemukan', 'message' => 'Halaman atau data tidak ditemukan.']);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function filter_query(array $filters): string
{
    $clean = array_filter($filters, fn ($v): bool => is_string($v) && $v !== '');
    return $clean === [] ? '' : '&' . http_build_query($clean);
}

function remove_upload(?string $path): void
{
    $cfg = config()['app'];
    delete_upload($path, $cfg['upload_dir'], $cfg['upload_url']);
}

function handle_login(): never
{
    if (current_admin() !== null) {
        redirect('index.php');
    }
    $error = null;
    $email = '';
    if (is_post()) {
        csrf_require();
        $email = trim((string)($_POST['email'] ?? ''));
        $key = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (throttle_exceeded($key, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_SEC)) {
            $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.';
        } else {
            $found = attempt_login(db(), $email, (string)($_POST['password'] ?? ''));
            if ($found !== null) {
                throttle_clear($key);
                login_admin($found);
                redirect('index.php');
            }
            throttle_record($key, LOGIN_WINDOW_SEC);
            $error = 'Email atau password salah.';
        }
    }
    render('login', ['title' => 'Masuk', 'error' => $error, 'email' => $email, 'admin' => null]);
    exit;
}

function dashboard_stats(PDO $pdo): array
{
    return [
        'orders_pending' => (int)$pdo->query("SELECT COUNT(*) FROM ticket_orders WHERE status = 'pending'")->fetchColumn(),
        'submissions_pending' => (int)$pdo->query("SELECT COUNT(*) FROM fan_submissions WHERE status = 'pending'")->fetchColumn(),
        'upcoming' => $pdo->query('SELECT id, title, city, start_at FROM events WHERE start_at >= now() ORDER BY start_at LIMIT 3')->fetchAll(),
        'low_stock' => $pdo->query('SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock, name')->fetchAll(),
    ];
}

function handle_crud(string $key, array $res, string $action, array $admin): void
{
    $pdo = db();
    $id = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : null;
    match ($action) {
        'list' => crud_page_list($pdo, $key, $res),
        'create' => crud_page_form($pdo, $key, $res, null, $admin),
        'edit' => $id === null ? not_found() : crud_page_form($pdo, $key, $res, $id, $admin),
        'delete' => $id === null ? not_found() : crud_page_delete($pdo, $key, $res, $id, $admin),
        default => not_found(),
    };
}

function fk_maps(PDO $pdo, array $res): array
{
    $maps = [];
    foreach ($res['fields'] as $name => $rule) {
        if ($rule['type'] === 'fk') {
            $maps[$name] = fk_options($pdo, $rule['ref'], $rule['ref_label']);
        }
    }
    return $maps;
}

function crud_page_list(PDO $pdo, string $key, array $res): void
{
    $q = mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 100);
    $filters = array_map(
        fn ($v): string => is_string($v) ? $v : '',
        array_intersect_key($_GET, array_flip($res['filters'] ?? []))
    );
    $page = is_string($_GET['p'] ?? null) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
    render('list', [
        'title' => $res['label'], 'current' => $key, 'key' => $key, 'res' => $res, 'q' => $q, 'filters' => $filters,
        'result' => crud_list($pdo, $res, $q, $filters, $page), 'fk' => fk_maps($pdo, $res),
    ]);
}

function crud_page_form(PDO $pdo, string $key, array $res, ?int $id, array $admin): void
{
    $row = $id === null ? null : crud_find($pdo, $res, $id);
    if ($id !== null && $row === null) {
        not_found();
    }
    $errors = [];
    $values = $row ?? array_map(fn (array $rule) => $rule['default'] ?? null, $res['fields']);
    if (is_post()) {
        csrf_require();
        [$data, $errors] = validate($res['fields'], $_POST, $row !== null);
        [$data, $errors, $newFiles] = crud_images($res, $data, $errors, $row);
        if ($errors === []) {
            try {
                crud_save($pdo, $res, $row, $data, $admin);
                flash('ok', $res['label'] . ' berhasil disimpan.');
                redirect('index.php?r=' . rawurlencode($key));
            } catch (CrudDuplicateError | CrudConstraintError $e) {
                $errors['_form'] = $e->getMessage();
            }
        }
        array_map('remove_upload', $newFiles);
        $values = form_values($res, $_POST, $row);
    }
    render('form', [
        'title' => ($row ? 'Edit ' : 'Tambah ') . $res['label'], 'current' => $key, 'key' => $key, 'res' => $res,
        'row' => $row, 'values' => $values, 'errors' => $errors, 'fk' => fk_maps($pdo, $res),
    ]);
}

/** Nilai untuk mengisi ulang form setelah validasi gagal. */
function form_values(array $res, array $post, ?array $row): array
{
    $values = [];
    foreach ($res['fields'] as $name => $rule) {
        $values[$name] = match ($rule['type']) {
            'bool' => !empty($post[$name]),
            'image' => $row[$name] ?? null,
            'password' => '',
            default => is_string($post[$name] ?? null) ? $post[$name] : '',
        };
    }
    return $values;
}

/** @return array{0: array, 1: array, 2: list<string>} [data, errors, file baru yang tersimpan] */
function crud_images(array $res, array $data, array $errors, ?array $existing): array
{
    $cfg = config()['app'];
    $newFiles = [];
    foreach ($res['fields'] as $name => $rule) {
        if ($rule['type'] !== 'image') {
            continue;
        }
        $label = $rule['label'] ?? $name;
        [$path, $err] = store_upload($_FILES[$name] ?? [], $res['table'], $cfg['upload_dir'], $cfg['upload_url']);
        $manual = trim(is_string($_POST[$name . '__path'] ?? null) ? $_POST[$name . '__path'] : '');
        if ($err !== null) {
            $errors[$name] = $err;
        } elseif ($path !== null) {
            $data[$name] = $path;
            $newFiles[] = $path;
        } elseif ($manual !== '') {
            [$link, $linkErr] = check_link($manual, $label);
            if ($linkErr === null) {
                $data[$name] = $link;
            } else {
                $errors[$name] = $linkErr;
            }
        } elseif (!empty($rule['required']) && empty($existing[$name])) {
            $errors[$name] = "$label wajib diisi.";
        }
    }
    return [$data, $errors, $newFiles];
}

function crud_save(PDO $pdo, array $res, ?array $row, array $data, array $admin): void
{
    if ($row === null) {
        $owner = $res['owner'] ?? null;
        crud_insert($pdo, $res, $owner === null ? $data : $data + [$owner => $admin['id']]);
        return;
    }
    crud_update($pdo, $res, (int)$row['id'], $data);
    foreach ($res['fields'] as $name => $rule) {
        if ($rule['type'] === 'image' && isset($data[$name]) && ($row[$name] ?? null) !== $data[$name]) {
            remove_upload($row[$name] ?? null);
        }
    }
}

function crud_page_delete(PDO $pdo, string $key, array $res, int $id, array $admin): void
{
    csrf_require();
    $row = crud_find($pdo, $res, $id) ?? not_found();
    if ($key === 'admins' && (int)$row['id'] === $admin['id']) {
        flash('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        redirect('index.php?r=admins');
    }
    try {
        crud_delete($pdo, $res, $id);
        foreach ($res['fields'] as $name => $rule) {
            if ($rule['type'] === 'image') {
                remove_upload($row[$name] ?? null);
            }
        }
        flash('ok', $res['label'] . ' berhasil dihapus.');
    } catch (CrudConstraintError $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?r=' . rawurlencode($key));
}
```

**Step 3: `admin/core/fields.php` (render input & tampilan nilai)**

```php
<?php
declare(strict_types=1);

function render_field(string $name, array $rule, mixed $value, array $fkOptions): string
{
    $id = 'f_' . e($name);
    $n = e($name);
    $req = !empty($rule['required']) && $rule['type'] !== 'image' ? ' required' : '';
    $v = e(is_scalar($value) && !is_bool($value) ? (string)$value : '');
    $input = match ($rule['type']) {
        'textarea' => "<textarea id=\"$id\" name=\"$n\" rows=\"5\"$req>$v</textarea>",
        'json' => "<textarea id=\"$id\" name=\"$n\" rows=\"3\" placeholder='{\"spotify\": \"https://...\"}'>$v</textarea>",
        'bool' => "<input type=\"checkbox\" id=\"$id\" name=\"$n\" value=\"1\"" . ($value ? ' checked' : '') . '>',
        'enum' => select_html($id, $n, array_combine($rule['options'], $rule['options']), (string)$value, $req, empty($rule['required'])),
        'fk' => select_html($id, $n, $fkOptions, (string)$value, $req, empty($rule['required'])),
        'image' => image_html($id, $n, is_string($value) ? $value : ''),
        'password' => "<input type=\"password\" id=\"$id\" name=\"$n\" minlength=\"8\" autocomplete=\"new-password\">",
        'int' => "<input type=\"number\" id=\"$id\" name=\"$n\" value=\"$v\" step=\"1\"$req>",
        'money' => "<input type=\"number\" id=\"$id\" name=\"$n\" value=\"$v\" min=\"0\" step=\"any\"$req>",
        'date' => "<input type=\"date\" id=\"$id\" name=\"$n\" value=\"$v\"$req>",
        'datetime' => "<input type=\"datetime-local\" id=\"$id\" name=\"$n\" value=\"" . e(to_datetime_local($value)) . "\"$req>",
        'email' => "<input type=\"email\" id=\"$id\" name=\"$n\" value=\"$v\"$req>",
        'slug' => "<input type=\"text\" id=\"$id\" name=\"$n\" value=\"$v\" placeholder=\"otomatis dari judul\">",
        default => "<input type=\"text\" id=\"$id\" name=\"$n\" value=\"$v\"$req>",
    };
    return '<label for="' . $id . '">' . e($rule['label'] ?? $name) . '</label>' . $input;
}

function select_html(string $id, string $name, array $options, string $current, string $req, bool $allowEmpty): string
{
    $html = "<select id=\"$id\" name=\"$name\"$req>" . ($allowEmpty ? '<option value="">— Kosong —</option>' : '');
    foreach ($options as $value => $label) {
        $selected = (string)$value === $current ? ' selected' : '';
        $html .= '<option value="' . e((string)$value) . "\"$selected>" . e((string)$label) . '</option>';
    }
    return $html . '</select>';
}

function image_html(string $id, string $name, string $current): string
{
    $preview = $current !== '' && !str_starts_with($current, '#')
        ? '<img class="preview" src="' . e(admin_asset($current)) . '" alt="">' : '';
    return $preview
        . "<input type=\"file\" id=\"$id\" name=\"$name\" accept=\"image/jpeg,image/png,image/webp\">"
        . "<input type=\"text\" name=\"{$name}__path\" value=\"" . e($current) . '" placeholder="atau isi path/URL, mis. images/product-1.jpg">';
}

function admin_asset(string $path): string
{
    return preg_match('#^https?://#', $path) === 1 ? $path : '../' . ltrim($path, '/');
}

function to_datetime_local(mixed $value): string
{
    if (!is_string($value) || $value === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) === 1) {
        return $value;
    }
    try {
        return (new DateTimeImmutable($value))->format('Y-m-d\TH:i');
    } catch (Exception) {
        return '';
    }
}

function display_value(array $rule, mixed $value, array $fkOptions): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    return match ($rule['type']) {
        'bool' => $value ? 'Ya' : 'Tidak',
        'money' => rupiah((string)$value),
        'fk' => $fkOptions[(int)$value] ?? "#$value",
        'datetime' => (new DateTimeImmutable((string)$value))->format('d/m/Y H:i'),
        'textarea' => mb_strimwidth((string)$value, 0, 60, '…'),
        default => (string)$value,
    };
}

function list_rule(array $res, string $col): array
{
    return $res['fields'][$col] ?? ['type' => str_ends_with($col, '_at') ? 'datetime' : 'text', 'label' => $col];
}
```

**Step 4: View**

`admin/views/layout.php`:

```php
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($title ?? 'Panel') ?> - Admin NOAH</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<?php if (!empty($admin)): ?>
  <aside class="sidebar">
    <a class="brand" href="index.php">NOAH Admin</a>
    <nav>
      <a href="index.php"<?= ($current ?? 'dashboard') === 'dashboard' ? ' class="aktif"' : '' ?>>Dashboard</a>
      <?php foreach ($resources as $menuKey => $menu): ?>
        <?php if (admin_can($admin, $menu['roles'] ?? [])): ?>
          <a href="index.php?r=<?= e($menuKey) ?>"<?= ($current ?? '') === $menuKey ? ' class="aktif"' : '' ?>><?= e($menu['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <a class="lihat-situs" href="../index.php" target="_blank" rel="noopener">Lihat situs</a>
    <form method="post" action="index.php?r=logout">
      <?= csrf_field() ?>
      <button type="submit">Keluar (<?= e($admin['name']) ?>)</button>
    </form>
  </aside>
<?php endif; ?>
  <main class="<?= empty($admin) ? 'konten polos' : 'konten' ?>">
    <?php if ($pesan = flash('ok')): ?><p class="flash ok"><?= e($pesan) ?></p><?php endif; ?>
    <?php if ($pesan = flash('error')): ?><p class="flash error"><?= e($pesan) ?></p><?php endif; ?>
    <?= $content ?>
  </main>
</body>
</html>
```

`admin/views/login.php`:

```php
<div class="kartu login">
  <h1>Masuk Panel Admin</h1>
  <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="index.php?r=login" class="form-admin">
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required autocomplete="current-password">
    <button type="submit" class="btn">Masuk</button>
  </form>
</div>
```

`admin/views/dashboard.php`:

```php
<h1>Dashboard</h1>
<div class="grid-kartu">
  <a class="kartu angka" href="index.php?r=ticket_orders&status=pending">
    <strong><?= $stats['orders_pending'] ?></strong><span>Pesanan tiket pending</span>
  </a>
  <a class="kartu angka" href="index.php?r=fan_submissions">
    <strong><?= $stats['submissions_pending'] ?></strong><span>Kiriman fans belum direview</span>
  </a>
</div>
<div class="grid-kartu">
  <section class="kartu">
    <h2>Event terdekat</h2>
    <ul>
      <?php foreach ($stats['upcoming'] as $event): ?>
        <li><a href="index.php?r=events&a=edit&id=<?= (int)$event['id'] ?>"><?= e($event['title']) ?></a>
          — <?= e($event['city']) ?>, <?= e(tanggal_id($event['start_at'])) ?></li>
      <?php endforeach; ?>
      <?php if ($stats['upcoming'] === []): ?><li>Belum ada event terjadwal.</li><?php endif; ?>
    </ul>
  </section>
  <section class="kartu">
    <h2>Stok menipis (≤ 5)</h2>
    <ul>
      <?php foreach ($stats['low_stock'] as $product): ?>
        <li><a href="index.php?r=products&a=edit&id=<?= (int)$product['id'] ?>"><?= e($product['name']) ?></a> — sisa <?= (int)$product['stock'] ?></li>
      <?php endforeach; ?>
      <?php if ($stats['low_stock'] === []): ?><li>Semua stok aman.</li><?php endif; ?>
    </ul>
  </section>
</div>
```

`admin/views/list.php`:

```php
<div class="judul-baris">
  <h1><?= e($res['label']) ?></h1>
  <a class="btn" href="index.php?r=<?= e($key) ?>&amp;a=create">+ Tambah</a>
</div>
<form method="get" class="cari">
  <input type="hidden" name="r" value="<?= e($key) ?>">
  <?php foreach ($filters as $fName => $fValue): ?>
    <input type="hidden" name="<?= e($fName) ?>" value="<?= e($fValue) ?>">
  <?php endforeach; ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari <?= e(strtolower($res['label'])) ?>...">
  <button type="submit">Cari</button>
  <?php if ($q !== '' || array_filter($filters)): ?><a href="index.php?r=<?= e($key) ?>">Reset</a><?php endif; ?>
</form>
<p class="info"><?= $result['total'] ?> data</p>
<div class="tabel-wrap">
<table>
  <thead>
    <tr>
      <?php foreach ($res['list'] as $col): ?><th><?= e(list_rule($res, $col)['label'] ?? $col) ?></th><?php endforeach; ?>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($result['rows'] as $row): ?>
    <tr>
      <?php foreach ($res['list'] as $col): ?>
        <td><?= e(display_value(list_rule($res, $col), $row[$col] ?? null, $fk[$col] ?? [])) ?></td>
      <?php endforeach; ?>
      <td class="aksi">
        <?php foreach ($res['links'] ?? [] as $text => $tpl): ?>
          <a href="index.php?r=<?= e(str_replace('{id}', (string)(int)$row['id'], $tpl)) ?>"><?= e($text) ?></a>
        <?php endforeach; ?>
        <a href="index.php?r=<?= e($key) ?>&amp;a=edit&amp;id=<?= (int)$row['id'] ?>">Edit</a>
        <form method="post" action="index.php?r=<?= e($key) ?>&amp;a=delete&amp;id=<?= (int)$row['id'] ?>" onsubmit="return confirm('Hapus data ini?')">
          <?= csrf_field() ?>
          <button type="submit" class="hapus">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if ($result['rows'] === []): ?>
    <tr><td colspan="<?= count($res['list']) + 1 ?>">Belum ada data.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<?php if ($result['pages'] > 1): ?>
  <nav class="paginasi">
    <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
      <a class="<?= $i === $result['page'] ? 'aktif' : '' ?>"
         href="index.php?r=<?= e($key) ?>&amp;p=<?= $i ?>&amp;q=<?= e(rawurlencode($q)) ?><?= e(filter_query($filters)) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
```

`admin/views/form.php`:

```php
<h1><?= e($title) ?></h1>
<?php if (isset($errors['_form'])): ?><p class="flash error"><?= e($errors['_form']) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-admin kartu">
  <?= csrf_field() ?>
  <?php foreach ($res['fields'] as $name => $rule): ?>
    <div class="field<?= isset($errors[$name]) ? ' salah' : '' ?>">
      <?= render_field($name, $rule, $values[$name] ?? null, $fk[$name] ?? []) ?>
      <?php if (isset($errors[$name])): ?><p class="pesan-salah"><?= e($errors[$name]) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="tombol-baris">
    <button type="submit" class="btn">Simpan</button>
    <a href="index.php?r=<?= e($key) ?>">Batal</a>
  </div>
</form>
```

`admin/views/error.php`:

```php
<div class="kartu">
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <a class="btn" href="index.php">Kembali ke dashboard</a>
</div>
```

**Step 5: `admin/admin.css`**

```css
:root { --merah: #c62828; --gelap: #1a1a1a; --abu: #f4f4f4; --garis: #ddd; }
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, sans-serif; display: flex; min-height: 100vh; background: var(--abu); color: #222; }
a { color: var(--merah); }
.sidebar { width: 230px; background: var(--gelap); color: #fff; padding: 20px 16px; display: flex; flex-direction: column; gap: 8px; }
.sidebar .brand { color: #fff; font-weight: 700; font-size: 1.2rem; text-decoration: none; margin-bottom: 12px; }
.sidebar nav { display: flex; flex-direction: column; gap: 2px; flex: 1; }
.sidebar nav a { color: #ccc; text-decoration: none; padding: 8px 10px; border-radius: 6px; }
.sidebar nav a:hover, .sidebar nav a.aktif { background: var(--merah); color: #fff; }
.sidebar .lihat-situs { color: #ccc; font-size: .9rem; }
.sidebar button { width: 100%; background: transparent; color: #ccc; border: 1px solid #555; padding: 8px; border-radius: 6px; cursor: pointer; }
.konten { flex: 1; padding: 28px; overflow-x: auto; }
.konten.polos { display: grid; place-items: center; }
.kartu { background: #fff; border: 1px solid var(--garis); border-radius: 10px; padding: 20px; margin-bottom: 16px; }
.login { width: min(380px, 100%); }
.grid-kartu { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
.kartu.angka { display: flex; flex-direction: column; text-decoration: none; color: inherit; }
.kartu.angka strong { font-size: 2.2rem; color: var(--merah); }
.judul-baris { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.btn { display: inline-block; background: var(--merah); color: #fff; border: 0; padding: 9px 16px; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: .95rem; }
.cari { display: flex; gap: 8px; flex-wrap: wrap; margin: 12px 0; align-items: center; }
.cari input, .cari select { padding: 8px; border: 1px solid var(--garis); border-radius: 6px; }
.tabel-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--garis); vertical-align: top; font-size: .92rem; }
th { background: #fafafa; }
.aksi { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.aksi form, td form { display: inline; margin: 0; }
.hapus { background: none; border: 0; color: var(--merah); cursor: pointer; padding: 0; font: inherit; text-decoration: underline; }
.form-admin { display: flex; flex-direction: column; gap: 14px; max-width: 720px; }
.form-admin label { font-weight: 600; display: block; margin-bottom: 4px; }
.form-admin input[type=text], .form-admin input[type=email], .form-admin input[type=password], .form-admin input[type=number],
.form-admin input[type=date], .form-admin input[type=datetime-local], .form-admin select, .form-admin textarea {
  width: 100%; padding: 9px; border: 1px solid var(--garis); border-radius: 6px; font: inherit; }
.form-admin input[type=file] { margin-bottom: 6px; }
.field.salah input, .field.salah select, .field.salah textarea { border-color: var(--merah); }
.pesan-salah { color: var(--merah); margin: 4px 0 0; font-size: .88rem; }
.preview { display: block; max-height: 90px; margin-bottom: 6px; border-radius: 6px; }
.tombol-baris { display: flex; gap: 14px; align-items: center; }
.flash { padding: 10px 14px; border-radius: 6px; }
.flash.ok { background: #e8f5e9; color: #1b5e20; }
.flash.error { background: #fdecea; color: #b71c1c; }
.paginasi { display: flex; gap: 6px; margin-top: 12px; }
.paginasi a { padding: 6px 10px; border: 1px solid var(--garis); border-radius: 6px; text-decoration: none; background: #fff; }
.paginasi a.aktif { background: var(--merah); color: #fff; }
.info { color: #666; font-size: .9rem; }
@media (max-width: 760px) { body { flex-direction: column; } .sidebar { width: 100%; } }
```

**Step 6: Uji manual**

Buat `admin/core/workflow.php` sementara berisi `<?php declare(strict_types=1);` (isinya diisi di Task 15), lalu:

```bash
php -S localhost:8000
```

Buka `http://localhost:8000/admin/` dan cek:
1. Diarahkan ke halaman login. Password salah 5× → muncul pesan "Terlalu banyak percobaan…".
2. Hapus file throttle (`%TEMP%\noah_throttle`), lalu login dengan akun dari Task 9 → masuk dashboard.
3. Produk → Tambah → simpan tanpa nama → error per field tampil dan isian lain tetap terisi.
4. Tambah produk dengan upload `.png` → file muncul di `uploads/products/` dan preview tampil di form edit.
5. Upload file `.php` yang di-rename `.jpg` → ditolak dengan "Format gambar harus JPG, PNG, atau WEBP."
6. Album → klik "Lagu" → daftar lagu terfilter per album.
7. Hapus lagu "Topeng" (dipakai setlist) → muncul pesan "Data masih dipakai…".
8. Login sebagai `editor` (buat lewat menu Admin) → menu Admin tidak tampil, dan `?r=admins` → 403.

**Step 7: Commit**

```bash
git add admin/index.php admin/core/controller.php admin/core/fields.php admin/core/workflow.php admin/admin.css admin/views
git commit -m "feat(admin): router, CRUD generik, view, dan tampilan panel admin"
```

---

### Task 15: Alur khusus: pesanan tiket, moderasi fans, setlist

**Files:**
- Modify: `admin/core/workflow.php` (isi penuh)
- Create: `admin/custom/ticket_orders.php`, `admin/custom/fan_submissions.php`, `admin/custom/setlist.php`
- Create: `admin/views/custom/ticket_orders.php`, `admin/views/custom/fan_submissions.php`, `admin/views/custom/setlist.php`
- Test: `tests/Integration/WorkflowTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class WorkflowTest extends DbTestCase
{
    private function event(): int
    {
        return $this->insert("INSERT INTO events (title, slug, city, start_at) VALUES ('E', 'e', 'K', now())");
    }

    private function track(string $title): int
    {
        return $this->insert('INSERT INTO tracks (title) VALUES (:t)', ['t' => $title]);
    }

    public function testOrderStatus(): void
    {
        $eventId = $this->event();
        $orderId = $this->insert("INSERT INTO ticket_orders (event_id, full_name, email, phone, quantity, total_price)
            VALUES (:e, 'A', 'a@example.com', '08123456789', 1, 100)", ['e' => $eventId]);
        $this->assertTrue(order_set_status(self::$pdo, $orderId, 'paid'));
        $this->assertFalse(order_set_status(self::$pdo, $orderId, 'hilang'));
        $this->assertSame('paid', order_list(self::$pdo, $eventId, null)[0]['status']);
        $this->assertSame([], order_list(self::$pdo, $eventId, 'cancelled'));
    }

    public function testSubmissionReview(): void
    {
        $adminId = $this->insert("INSERT INTO admins (name, email, password_hash) VALUES ('A', 'a@example.com', 'x')");
        $id = $this->insert("INSERT INTO fan_submissions (name, email, type, content) VALUES ('F', 'f@example.com', 'story', 'Halo')");
        $this->assertCount(1, submission_list(self::$pdo, 'pending'));
        $this->assertTrue(submission_review(self::$pdo, $id, 'approved', $adminId));
        $this->assertFalse(submission_review(self::$pdo, $id, 'pending', $adminId));
        $row = fetch_one(self::$pdo, 'SELECT status, reviewed_by FROM fan_submissions WHERE id = :id', ['id' => $id]);
        $this->assertSame(['status' => 'approved', 'reviewed_by' => $adminId], $row);
    }

    public function testSetlistAddAndRemoveRenumbers(): void
    {
        $eventId = $this->event();
        foreach (['Satu', 'Dua', 'Tiga'] as $title) {
            setlist_add(self::$pdo, $eventId, $this->track($title), $title === 'Tiga');
        }
        setlist_remove(self::$pdo, $eventId, 2);
        $items = setlist_items(self::$pdo, $eventId);
        $this->assertSame([[1, 'Satu', false], [2, 'Tiga', true]],
            array_map(fn (array $i): array => [(int)$i['position'], $i['title'], $i['is_encore']], $items));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/WorkflowTest.php`
Expected: FAIL `Call to undefined function order_set_status()`

**Step 3: Implementasi `admin/core/workflow.php`**

```php
<?php
declare(strict_types=1);

const ORDER_STATUSES = ['pending', 'paid', 'cancelled'];
const REVIEW_STATUSES = ['pending', 'approved', 'rejected'];

function order_list(PDO $pdo, ?int $eventId, ?string $status): array
{
    $where = [];
    $params = [];
    if ($eventId !== null) {
        $where[] = 'o.event_id = :event_id';
        $params['event_id'] = $eventId;
    }
    if ($status !== null) {
        $where[] = 'o.status = :status';
        $params['status'] = $status;
    }
    $sql = 'SELECT o.*, e.title AS event_title FROM ticket_orders o JOIN events e ON e.id = o.event_id'
        . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
        . ' ORDER BY o.created_at DESC LIMIT 200';
    return fetch_all($pdo, $sql, $params);
}

function order_set_status(PDO $pdo, int $id, string $status): bool
{
    if (!in_array($status, ORDER_STATUSES, true)) {
        return false;
    }
    $stmt = $pdo->prepare('UPDATE ticket_orders SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
    return $stmt->rowCount() === 1;
}

function order_delete(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM ticket_orders WHERE id = :id')->execute(['id' => $id]);
}

function submission_list(PDO $pdo, string $status): array
{
    return fetch_all($pdo, 'SELECT s.*, c.city FROM fan_submissions s LEFT JOIN communities c ON c.id = s.community_id
        WHERE s.status = :status ORDER BY s.created_at DESC LIMIT 200', ['status' => $status]);
}

function submission_review(PDO $pdo, int $id, string $status, int $adminId): bool
{
    if (!in_array($status, ['approved', 'rejected'], true)) {
        return false;
    }
    $stmt = $pdo->prepare('UPDATE fan_submissions SET status = :status, reviewed_by = :admin WHERE id = :id');
    $stmt->execute(['status' => $status, 'admin' => $adminId, 'id' => $id]);
    return $stmt->rowCount() === 1;
}

function submission_delete(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM fan_submissions WHERE id = :id')->execute(['id' => $id]);
}

function setlist_items(PDO $pdo, int $eventId): array
{
    return fetch_all($pdo, 'SELECT s.position, s.is_encore, t.id AS track_id, t.title, a.title AS album
        FROM setlist_items s JOIN tracks t ON t.id = s.track_id LEFT JOIN albums a ON a.id = t.album_id
        WHERE s.event_id = :event_id ORDER BY s.position', ['event_id' => $eventId]);
}

function setlist_add(PDO $pdo, int $eventId, int $trackId, bool $isEncore): void
{
    $stmt = $pdo->prepare('INSERT INTO setlist_items (event_id, position, track_id, is_encore)
        SELECT CAST(:event_id AS bigint), COALESCE(MAX(position), 0) + 1, CAST(:track_id AS bigint), CAST(:encore AS boolean)
        FROM setlist_items WHERE event_id = :event_id_where');
    $stmt->execute([
        'event_id' => $eventId, 'track_id' => $trackId, 'encore' => db_param($isEncore), 'event_id_where' => $eventId,
    ]);
}

/** Hapus satu posisi lalu rapatkan nomor urut (dua langkah agar tidak bentrok primary key). */
function setlist_remove(PDO $pdo, int $eventId, int $position): void
{
    with_transaction($pdo, function (PDO $pdo) use ($eventId, $position): void {
        $params = ['event_id' => $eventId, 'position' => $position];
        $pdo->prepare('DELETE FROM setlist_items WHERE event_id = :event_id AND position = :position')->execute($params);
        $pdo->prepare('UPDATE setlist_items SET position = position + 10000 WHERE event_id = :event_id AND position > :position')
            ->execute($params);
        $pdo->prepare('UPDATE setlist_items SET position = position - 10001 WHERE event_id = :event_id AND position > 10000')
            ->execute(['event_id' => $eventId]);
    });
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/WorkflowTest.php`
Expected: PASS (3 tests)

**Step 5: Halaman custom (logika)**

`admin/custom/ticket_orders.php`:

```php
<?php
declare(strict_types=1);
/** @var array $admin Didefinisikan di admin/index.php */

$pdo = db();
$eventId = is_string($_GET['event_id'] ?? null) && ctype_digit($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$status = in_array($_GET['status'] ?? null, ORDER_STATUSES, true) ? $_GET['status'] : null;
$back = 'index.php?r=ticket_orders' . filter_query(['event_id' => (string)($eventId ?? ''), 'status' => (string)($status ?? '')]);

if (is_post()) {
    csrf_require();
    $orderId = (int)($_POST['id'] ?? 0);
    if (($_POST['do'] ?? '') === 'delete') {
        if (admin_can($admin, ['super_admin'])) {
            order_delete($pdo, $orderId);
            flash('ok', 'Pesanan dihapus.');
        } else {
            flash('error', 'Hanya super admin yang bisa menghapus pesanan.');
        }
    } elseif (order_set_status($pdo, $orderId, (string)($_POST['status'] ?? ''))) {
        flash('ok', 'Status pesanan diperbarui.');
    } else {
        flash('error', 'Status tidak valid.');
    }
    redirect($back);
}

render('custom/ticket_orders', [
    'title' => 'Pesanan Tiket', 'current' => 'ticket_orders', 'eventId' => $eventId, 'status' => $status,
    'orders' => order_list($pdo, $eventId, $status), 'events' => fk_options($pdo, 'events', 'title'),
]);
```

`admin/custom/fan_submissions.php`:

```php
<?php
declare(strict_types=1);
/** @var array $admin */

$pdo = db();
$status = in_array($_GET['status'] ?? null, REVIEW_STATUSES, true) ? $_GET['status'] : 'pending';

if (is_post()) {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $do = (string)($_POST['do'] ?? '');
    if ($do === 'delete') {
        submission_delete($pdo, $id);
        flash('ok', 'Kiriman dihapus.');
    } elseif (submission_review($pdo, $id, $do, $admin['id'])) {
        flash('ok', $do === 'approved' ? 'Kiriman disetujui dan tampil di halaman Sahabat NOAH.' : 'Kiriman ditolak.');
    } else {
        flash('error', 'Aksi tidak valid.');
    }
    redirect('index.php?r=fan_submissions&status=' . rawurlencode($status));
}

render('custom/fan_submissions', [
    'title' => 'Kiriman Fans', 'current' => 'fan_submissions', 'status' => $status,
    'items' => submission_list($pdo, $status),
]);
```

`admin/custom/setlist.php`:

```php
<?php
declare(strict_types=1);

$pdo = db();
$eventId = is_string($_GET['event_id'] ?? null) && ctype_digit($_GET['event_id']) ? (int)$_GET['event_id'] : null;

if (is_post() && $eventId !== null) {
    csrf_require();
    try {
        if (($_POST['do'] ?? '') === 'remove') {
            setlist_remove($pdo, $eventId, (int)($_POST['position'] ?? 0));
            flash('ok', 'Lagu dihapus dari setlist.');
        } else {
            setlist_add($pdo, $eventId, (int)($_POST['track_id'] ?? 0), !empty($_POST['is_encore']));
            flash('ok', 'Lagu ditambahkan ke setlist.');
        }
    } catch (PDOException) {
        flash('error', 'Lagu atau event tidak valid.');
    }
    redirect('index.php?r=setlist&event_id=' . $eventId);
}

render('custom/setlist', [
    'title' => 'Setlist', 'current' => 'setlist', 'eventId' => $eventId,
    'events' => fk_options($pdo, 'events', 'title'),
    'items' => $eventId === null ? [] : setlist_items($pdo, $eventId),
    'tracks' => fetch_all($pdo, 'SELECT t.id, t.title, a.title AS album FROM tracks t LEFT JOIN albums a ON a.id = t.album_id ORDER BY t.title'),
]);
```

**Step 6: Halaman custom (view)**

`admin/views/custom/ticket_orders.php`:

```php
<h1>Pesanan Tiket</h1>
<form method="get" class="cari">
  <input type="hidden" name="r" value="ticket_orders">
  <select name="event_id">
    <option value="">Semua event</option>
    <?php foreach ($events as $id => $label): ?>
      <option value="<?= $id ?>"<?= $id === $eventId ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">Semua status</option>
    <?php foreach (ORDER_STATUSES as $s): ?>
      <option value="<?= e($s) ?>"<?= $s === $status ? ' selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Filter</button>
</form>
<div class="tabel-wrap">
<table>
  <thead><tr><th>Waktu</th><th>Event</th><th>Pemesan</th><th>Jumlah</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= e(display_value(['type' => 'datetime'], $o['created_at'], [])) ?></td>
      <td><?= e($o['event_title']) ?></td>
      <td><?= e($o['full_name']) ?><br><small><?= e($o['email']) ?> · <?= e($o['phone']) ?></small>
        <?php if ($o['notes']): ?><br><small>Catatan: <?= e($o['notes']) ?></small><?php endif; ?></td>
      <td><?= (int)$o['quantity'] ?></td>
      <td><?= e(rupiah($o['total_price'])) ?></td>
      <td>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
          <select name="status">
            <?php foreach (ORDER_STATUSES as $s): ?>
              <option value="<?= e($s) ?>"<?= $s === $o['status'] ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Simpan</button>
        </form>
      </td>
      <td>
        <?php if (admin_can($admin, ['super_admin'])): ?>
          <form method="post" onsubmit="return confirm('Hapus pesanan ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="do" value="delete">
            <button type="submit" class="hapus">Hapus</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if ($orders === []): ?><tr><td colspan="7">Belum ada pesanan.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
```

`admin/views/custom/fan_submissions.php`:

```php
<h1>Kiriman Fans</h1>
<nav class="cari">
  <?php foreach (REVIEW_STATUSES as $s): ?>
    <a class="<?= $s === $status ? 'btn' : '' ?>" href="index.php?r=fan_submissions&amp;status=<?= e($s) ?>"><?= e(ucfirst($s)) ?></a>
  <?php endforeach; ?>
</nav>
<?php foreach ($items as $item): ?>
  <article class="kartu">
    <p><strong><?= e($item['name']) ?></strong> · <?= e($item['email']) ?> · <?= e($item['type']) ?>
      <?= $item['city'] ? '· ' . e($item['city']) : '' ?> · <?= e(display_value(['type' => 'datetime'], $item['created_at'], [])) ?></p>
    <p><?= nl2br(e($item['content'])) ?></p>
    <?php if ($item['file_url']): ?><img class="preview" src="<?= e(admin_asset($item['file_url'])) ?>" alt=""><?php endif; ?>
    <div class="aksi">
      <?php foreach (['approved' => 'Setujui', 'rejected' => 'Tolak', 'delete' => 'Hapus'] as $do => $text): ?>
        <?php if ($do !== $status): ?>
          <form method="post"<?= $do === 'delete' ? ' onsubmit="return confirm(\'Hapus kiriman ini?\')"' : '' ?>>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <input type="hidden" name="do" value="<?= e($do) ?>">
            <button type="submit" class="<?= $do === 'approved' ? 'btn' : 'hapus' ?>"><?= e($text) ?></button>
          </form>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php if ($items === []): ?><p class="info">Tidak ada kiriman dengan status ini.</p><?php endif; ?>
```

`admin/views/custom/setlist.php`:

```php
<h1>Setlist</h1>
<form method="get" class="cari">
  <input type="hidden" name="r" value="setlist">
  <select name="event_id" required>
    <option value="">Pilih event</option>
    <?php foreach ($events as $id => $label): ?>
      <option value="<?= $id ?>"<?= $id === $eventId ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Buka</button>
</form>
<?php if ($eventId !== null): ?>
  <div class="kartu">
    <ol>
      <?php foreach ($items as $item): ?>
        <li>
          <?= e($item['title']) ?><?= $item['album'] ? ' <small>(' . e($item['album']) . ')</small>' : '' ?>
          <?= $item['is_encore'] ? ' <strong>[Encore]</strong>' : '' ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="remove">
            <input type="hidden" name="position" value="<?= (int)$item['position'] ?>">
            <button type="submit" class="hapus">hapus</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php if ($items === []): ?><p class="info">Setlist masih kosong.</p><?php endif; ?>
  </div>
  <form method="post" class="cari kartu">
    <?= csrf_field() ?>
    <select name="track_id" required>
      <?php foreach ($tracks as $track): ?>
        <option value="<?= (int)$track['id'] ?>"><?= e($track['title']) ?><?= $track['album'] ? ' — ' . e($track['album']) : '' ?></option>
      <?php endforeach; ?>
    </select>
    <label><input type="checkbox" name="is_encore" value="1"> Encore</label>
    <button type="submit" class="btn">Tambah lagu</button>
  </form>
<?php endif; ?>
```

**Step 7: Uji manual**

Dengan `php -S localhost:8000`:
- Event → "Setlist" pada The Great Journey → 12 lagu tampil. Hapus lagu ke-3 → nomor urut rapat kembali. Tambah lagi.
- Kiriman Fans → tab Approved menampilkan 11 kiriman seed.

**Step 8: Commit**

```bash
git add admin/core/workflow.php admin/custom admin/views/custom tests/Integration/WorkflowTest.php
git commit -m "feat(admin): halaman pesanan tiket, moderasi kiriman fans, dan setlist"
```

---

### Task 16: Form publik (`app/forms.php` + `actions/`)

**Files:**
- Create: `app/forms.php`, `actions/order-ticket.php`, `actions/submit-fan.php`, `actions/subscribe.php`
- Test: `tests/Integration/FormsTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class FormsTest extends DbTestCase
{
    private function concert(string $startAt = '+30 days', ?int $quota = 5, string $status = 'published'): int
    {
        return $this->insert("INSERT INTO events (title, slug, city, start_at, ticket_price, quota, status, type)
            VALUES ('K', :slug, 'Jakarta', :start, 350000, :quota, :status, 'concert')", [
            'slug' => bin2hex(random_bytes(4)), 'start' => (new DateTimeImmutable($startAt))->format('c'),
            'quota' => $quota, 'status' => $status,
        ]);
    }

    private function order(int $eventId, int $qty): array
    {
        return order_ticket(self::$pdo, [
            'event_id' => (string)$eventId, 'full_name' => 'Budi', 'email' => 'budi@example.com',
            'phone' => '081234567890', 'quantity' => (string)$qty, 'notes' => '',
        ]);
    }

    public function testOrderComputesTotalOnServer(): void
    {
        $result = $this->order($this->concert(), 2);
        $this->assertTrue($result['ok']);
        $row = fetch_one(self::$pdo, 'SELECT total_price, status FROM ticket_orders WHERE id = :id', ['id' => $result['order_id']]);
        $this->assertSame(['total_price' => '700000.00', 'status' => 'pending'], $row);
    }

    public function testOrderRejectsQuotaExceeded(): void
    {
        $eventId = $this->concert(quota: 3);
        $this->assertTrue($this->order($eventId, 2)['ok']);
        $this->assertSame(['quantity' => 'Sisa tiket tidak mencukupi.'], $this->order($eventId, 2)['errors']);
    }

    public function testOrderRejectsPastDraftAndInvalidQuantity(): void
    {
        $this->assertArrayHasKey('event_id', $this->order($this->concert('-1 day'), 1)['errors']);
        $this->assertArrayHasKey('event_id', $this->order($this->concert(status: 'draft'), 1)['errors']);
        $this->assertArrayHasKey('quantity', $this->order($this->concert(), 11)['errors']);
    }

    public function testSubmitFanIsPending(): void
    {
        $this->assertTrue(submit_fan(self::$pdo, ['name' => 'Sari', 'email' => 's@example.com', 'type' => 'story', 'content' => 'Cerita'])['ok']);
        $this->assertSame('pending', self::$pdo->query('SELECT status FROM fan_submissions')->fetchColumn());
        $this->assertArrayHasKey('type', submit_fan(self::$pdo, ['name' => 'x', 'email' => 'x@example.com', 'type' => 'hack', 'content' => 'y'])['errors']);
    }

    public function testSubscribeIsIdempotent(): void
    {
        $this->assertTrue(subscribe(self::$pdo, ['email' => 'Fan@Example.com'])['ok']);
        $this->assertTrue(subscribe(self::$pdo, ['email' => 'fan@example.com'])['ok']);
        $this->assertSame(1, (int)self::$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn());
    }

    public function testHoneypot(): void
    {
        $this->assertTrue(is_bot(['website' => 'http://spam']));
        $this->assertFalse(is_bot(['website' => '']));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/FormsTest.php`
Expected: FAIL `Call to undefined function order_ticket()`

**Step 3: Implementasi `app/forms.php`**

```php
<?php
declare(strict_types=1);

const FORM_MAX_PER_WINDOW = 5;
const FORM_WINDOW_SEC = 600;

const TICKET_FIELDS = [
    'event_id' => ['type' => 'int', 'label' => 'Konser', 'required' => true, 'min' => 1],
    'full_name' => ['type' => 'text', 'label' => 'Nama lengkap', 'required' => true, 'max' => 120],
    'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
    'phone' => ['type' => 'phone', 'label' => 'Nomor telepon', 'required' => true],
    'quantity' => ['type' => 'int', 'label' => 'Jumlah tiket', 'required' => true, 'min' => 1, 'max' => 10],
    'notes' => ['type' => 'textarea', 'label' => 'Catatan', 'max' => 500],
];

const FAN_FIELDS = [
    'name' => ['type' => 'text', 'label' => 'Nama lengkap', 'required' => true, 'max' => 120],
    'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
    'type' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['story', 'artwork', 'cover']],
    'content' => ['type' => 'textarea', 'label' => 'Pesan', 'required' => true, 'max' => 2000],
];

const SUBSCRIBE_FIELDS = ['email' => ['type' => 'email', 'label' => 'Email', 'required' => true]];

function is_bot(array $input): bool
{
    return trim(is_string($input['website'] ?? null) ? $input['website'] : '') !== '';
}

function honeypot_field(): string
{
    return '<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
}

function tickets_sold(PDO $pdo, int $eventId): int
{
    $row = fetch_one($pdo, "SELECT COALESCE(SUM(quantity), 0) AS sold FROM ticket_orders
        WHERE event_id = :id AND status <> 'cancelled'", ['id' => $eventId]);
    return (int)$row['sold'];
}

/** @return array{ok: bool, errors: array<string, string>, order_id?: int} */
function order_ticket(PDO $pdo, array $input): array
{
    [$data, $errors] = validate(TICKET_FIELDS, $input);
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }
    return with_transaction($pdo, function (PDO $pdo) use ($data): array {
        // FOR UPDATE: pemesanan bersamaan untuk event yang sama diproses bergantian.
        $event = fetch_one($pdo, "SELECT id, ticket_price, quota FROM events
            WHERE id = :id AND type = 'concert' AND status = 'published' AND start_at > now() AND ticket_price IS NOT NULL
            FOR UPDATE", ['id' => $data['event_id']]);
        if ($event === null) {
            return ['ok' => false, 'errors' => ['event_id' => 'Konser ini tidak tersedia untuk dipesan.']];
        }
        if ($event['quota'] !== null && tickets_sold($pdo, (int)$event['id']) + $data['quantity'] > (int)$event['quota']) {
            return ['ok' => false, 'errors' => ['quantity' => 'Sisa tiket tidak mencukupi.']];
        }
        $row = fetch_one($pdo, 'INSERT INTO ticket_orders (event_id, full_name, email, phone, quantity, total_price, notes)
            VALUES (:event_id, :full_name, :email, :phone, :quantity, CAST(:price AS numeric) * CAST(:qty AS integer), :notes)
            RETURNING id', $data + ['price' => $event['ticket_price'], 'qty' => $data['quantity']]);
        return ['ok' => true, 'errors' => [], 'order_id' => (int)$row['id']];
    });
}

function submit_fan(PDO $pdo, array $input): array
{
    [$data, $errors] = validate(FAN_FIELDS, $input);
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }
    $pdo->prepare('INSERT INTO fan_submissions (name, email, type, content) VALUES (:name, :email, :type, :content)')
        ->execute($data);
    return ['ok' => true, 'errors' => []];
}

function subscribe(PDO $pdo, array $input): array
{
    [$data, $errors] = validate(SUBSCRIBE_FIELDS, $input);
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }
    $pdo->prepare('INSERT INTO subscribers (email) VALUES (:email) ON CONFLICT (email) DO UPDATE SET is_active = TRUE')
        ->execute($data);
    return ['ok' => true, 'errors' => []];
}

/** Alur umum form publik: CSRF → honeypot → throttle → proses → flash → redirect (PRG). */
function handle_public_form(string $name, callable $handler, string $redirectTo, string $successMessage): never
{
    csrf_require();
    if (is_bot($_POST)) {
        flash($name, $successMessage);
        redirect($redirectTo);
    }
    $key = "form:$name:" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (throttle_exceeded($key, FORM_MAX_PER_WINDOW, FORM_WINDOW_SEC)) {
        flash($name . '_error', 'Terlalu banyak kiriman. Coba lagi beberapa menit lagi.');
        redirect($redirectTo);
    }
    throttle_record($key, FORM_WINDOW_SEC);
    $result = $handler(db(), $_POST);
    if ($result['ok']) {
        flash($name, $successMessage);
        unset($_SESSION['old'][$name]);
    } else {
        flash($name . '_error', implode(' ', $result['errors']));
        $_SESSION['old'][$name] = array_map(
            fn ($v): string => is_string($v) ? mb_substr($v, 0, 2000) : '',
            array_diff_key($_POST, ['_csrf' => 1, 'website' => 1])
        );
    }
    redirect($redirectTo);
}

function old(string $form, string $field): string
{
    return (string)($_SESSION['old'][$form][$field] ?? '');
}
```

**Step 4: Endpoint `actions/`**

`actions/order-ticket.php`:

```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
handle_public_form('tiket', 'order_ticket', '../tur-event.php#pemesanan-event',
    'Pesanan tiket diterima. Detail pesanan akan dikirim ke email kamu.');
```

`actions/submit-fan.php`:

```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
handle_public_form('cerita', 'submit_fan', '../sahabat-noah.php#form-cerita',
    'Terima kasih! Kirimanmu akan tampil setelah ditinjau admin.');
```

`actions/subscribe.php`:

```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
$targets = ['index' => '../index.php#komunitas', 'tur-event' => '../tur-event.php#langganan'];
$target = $targets[$_POST['from'] ?? ''] ?? '../index.php#komunitas';
handle_public_form('langganan', 'subscribe', $target, 'Email kamu sudah terdaftar. Terima kasih!');
```

**Step 5: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/FormsTest.php`
Expected: PASS (6 tests)

**Step 6: Commit**

```bash
git add app/forms.php actions tests/Integration/FormsTest.php
git commit -m "feat(app): form publik pesan tiket, kirim karya, dan newsletter"
```

---

### Task 17: Query halaman publik (`app/queries.php`)

**Files:**
- Create: `app/queries.php`
- Test: `tests/Integration/QueriesTest.php`

**Step 1: Tulis test yang gagal**

```php
<?php
declare(strict_types=1);

final class QueriesTest extends DbTestCase
{
    private function event(string $slug, string $start, string $status = 'published', ?int $quota = null): int
    {
        return $this->insert("INSERT INTO events (title, slug, city, start_at, status, quota, ticket_price)
            VALUES (:slug, :slug2, 'K', :start, :status, :quota, 100)", [
            'slug' => $slug, 'slug2' => $slug, 'start' => (new DateTimeImmutable($start))->format('c'),
            'status' => $status, 'quota' => $quota,
        ]);
    }

    public function testUpcomingAndPastEvents(): void
    {
        $id = $this->event('akan', '+10 days', quota: 2);
        $this->event('lalu', '-10 days');
        $this->event('draft', '+5 days', 'draft');
        self::$pdo->exec("INSERT INTO ticket_orders (event_id, full_name, email, phone, quantity, total_price)
            VALUES ($id, 'A', 'a@example.com', '0812345678', 2, 200)");
        $upcoming = upcoming_events(self::$pdo, 'concert');
        $this->assertSame(['akan'], array_column($upcoming, 'slug'));
        $this->assertFalse(event_available($upcoming[0]));
        $this->assertSame(['lalu'], array_column(past_events(self::$pdo), 'slug'));
    }

    public function testGroupSetlists(): void
    {
        $rows = [
            ['id' => 1, 'title' => 'A', 'venue' => 'V', 'start_at' => 'x', 'description' => null, 'position' => 1, 'is_encore' => false, 'track' => 'L1'],
            ['id' => 1, 'title' => 'A', 'venue' => 'V', 'start_at' => 'x', 'description' => null, 'position' => 2, 'is_encore' => true, 'track' => 'L2'],
        ];
        $grouped = group_setlists($rows);
        $this->assertCount(1, $grouped);
        $this->assertSame([['position' => 1, 'title' => 'L1']], $grouped[0]['main']);
        $this->assertSame([['position' => 2, 'title' => 'L2']], $grouped[0]['encore']);
    }

    public function testAlbumsWithTopThreeTracks(): void
    {
        $albumId = $this->insert("INSERT INTO albums (title, slug) VALUES ('Al', 'al')");
        foreach ([4, 1, 3, 2] as $no) {
            self::$pdo->exec("INSERT INTO tracks (album_id, track_no, title) VALUES ($albumId, $no, 'T$no')");
        }
        $albums = albums_for_grid(self::$pdo);
        $this->assertSame(['T1', 'T2', 'T3'], array_column($albums[0]['tracks'], 'title'));
    }

    public function testSearchEscapesWildcards(): void
    {
        self::$pdo->exec("INSERT INTO posts (title, slug, body, status, published_at) VALUES ('Diskon 50%', 'd', 'x', 'published', now() - interval '1 hour')");
        self::$pdo->exec("INSERT INTO posts (title, slug, body, status, published_at) VALUES ('Biasa', 'b', 'x', 'published', now() - interval '1 hour')");
        $this->assertCount(1, search_site(self::$pdo, '%')['posts']);
        $this->assertCount(0, search_site(self::$pdo, "' OR 1=1 --")['posts']);
    }

    public function testMapsEmbedOnlyGoogle(): void
    {
        $this->assertSame('https://www.google.com/maps?q=x&output=embed', maps_embed_url('https://www.google.com/maps?q=x&output=embed'));
        $this->assertNull(maps_embed_url('https://evil.example.com/'));
        $this->assertNull(maps_embed_url(null));
    }
}
```

**Step 2: Jalankan dan pastikan gagal**

Run: `vendor/bin/phpunit tests/Integration/QueriesTest.php`
Expected: FAIL `Call to undefined function upcoming_events()`

**Step 3: Implementasi**

```php
<?php
declare(strict_types=1);

const PRODUCT_CATEGORIES = ['kaos' => 'Kaos', 'hoodie' => 'Hoodie', 'aksesoris' => 'Aksesoris', 'musik' => 'CD & Vinyl'];
const ALBUM_TYPES = ['studio' => 'Studio', 'kompilasi' => 'Kompilasi', 'soundtrack' => 'Soundtrack'];
const NEWS_PER_PAGE = 6;

function like_param(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

// ===== Event =====
function upcoming_events(PDO $pdo, string $type, ?int $limit = null): array
{
    return fetch_all($pdo, "SELECT e.*, COALESCE((SELECT SUM(t.quantity) FROM ticket_orders t
            WHERE t.event_id = e.id AND t.status <> 'cancelled'), 0) AS sold
        FROM events e WHERE e.type = :type AND e.status = 'published' AND e.start_at >= now()
        ORDER BY e.start_at" . ($limit === null ? '' : ' LIMIT ' . max(1, $limit)), ['type' => $type]);
}

function event_available(array $event): bool
{
    return $event['quota'] === null || (int)$event['sold'] < (int)$event['quota'];
}

function past_events(PDO $pdo): array
{
    return fetch_all($pdo, "SELECT * FROM events WHERE type = 'concert' AND status = 'published' AND start_at < now()
        ORDER BY start_at");
}

function setlists(PDO $pdo): array
{
    return group_setlists(fetch_all($pdo, "SELECT e.id, e.title, e.venue, e.start_at, e.description,
            s.position, s.is_encore, t.title AS track
        FROM events e JOIN setlist_items s ON s.event_id = e.id JOIN tracks t ON t.id = s.track_id
        WHERE e.status = 'published' AND e.start_at < now() ORDER BY e.start_at DESC, s.position"));
}

function group_setlists(array $rows): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $grouped[$id] ??= [
            'title' => $row['title'], 'venue' => $row['venue'], 'start_at' => $row['start_at'],
            'description' => $row['description'], 'main' => [], 'encore' => [],
        ];
        $grouped[$id][$row['is_encore'] ? 'encore' : 'main'][] = ['position' => (int)$row['position'], 'title' => $row['track']];
    }
    return array_values($grouped);
}

function maps_embed_url(?string $url): ?string
{
    return $url !== null && str_starts_with($url, 'https://www.google.com/maps') ? $url : null;
}

// ===== Berita & media =====
const POST_PUBLISHED = "status = 'published' AND published_at <= now()";

function featured_post(PDO $pdo): ?array
{
    return fetch_one($pdo, "SELECT * FROM posts WHERE category = 'news' AND is_featured AND " . POST_PUBLISHED
        . ' ORDER BY published_at DESC LIMIT 1');
}

function news_posts(PDO $pdo, int $limit, int $offset = 0, bool $excludeFeatured = false): array
{
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE category = 'news' AND " . POST_PUBLISHED
        . ($excludeFeatured ? ' AND NOT is_featured' : '') . ' ORDER BY published_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_news(PDO $pdo, bool $excludeFeatured = false): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE category = 'news' AND " . POST_PUBLISHED
        . ($excludeFeatured ? ' AND NOT is_featured' : ''))->fetchColumn();
}

function behind_scenes(PDO $pdo, int $limit = 3): array
{
    return fetch_all($pdo, "SELECT * FROM posts WHERE category = 'behind_scenes' AND " . POST_PUBLISHED
        . ' ORDER BY published_at DESC LIMIT ' . max(1, $limit));
}

function media_items(PDO $pdo, string $type): array
{
    return fetch_all($pdo, 'SELECT * FROM media WHERE type = :type ORDER BY created_at, id', ['type' => $type]);
}

/** @return array{posts: array, albums: array, events: array} */
function search_site(PDO $pdo, string $q): array
{
    $like = like_param($q);
    return [
        'posts' => fetch_all($pdo, 'SELECT title, slug FROM posts WHERE ' . POST_PUBLISHED
            . ' AND (title ILIKE :a OR excerpt ILIKE :b) ORDER BY published_at DESC LIMIT 10', ['a' => $like, 'b' => $like]),
        'albums' => fetch_all($pdo, 'SELECT title, slug FROM albums WHERE title ILIKE :a ORDER BY release_date DESC LIMIT 10', ['a' => $like]),
        'events' => fetch_all($pdo, "SELECT title, city, start_at FROM events WHERE status = 'published'
            AND (title ILIKE :a OR city ILIKE :b) ORDER BY start_at DESC LIMIT 10", ['a' => $like, 'b' => $like]),
    ];
}

// ===== Diskografi =====
function attach_tracks(PDO $pdo, array $albums, bool $withDuration = false): array
{
    if ($albums === []) {
        return [];
    }
    $ids = '{' . implode(',', array_map(fn (array $a): int => (int)$a['id'], $albums)) . '}';
    $rows = fetch_all($pdo, 'SELECT album_id, track_no, title, duration_sec FROM (
            SELECT t.*, row_number() OVER (PARTITION BY album_id ORDER BY track_no) AS rn
            FROM tracks t WHERE track_no IS NOT NULL AND album_id = ANY(CAST(:ids AS bigint[]))
        ) x WHERE rn <= 3 ORDER BY album_id, track_no', ['ids' => $ids]);
    return array_map(fn (array $album): array => $album + [
        'tracks' => array_values(array_filter($rows, fn (array $t): bool => (int)$t['album_id'] === (int)$album['id'])),
    ], $albums);
}

function albums_for_grid(PDO $pdo): array
{
    return attach_tracks($pdo, fetch_all($pdo, "SELECT * FROM albums WHERE type IN ('studio', 'kompilasi')
        ORDER BY release_date DESC NULLS LAST, id"));
}

function featured_album(PDO $pdo): ?array
{
    $album = fetch_one($pdo, 'SELECT * FROM albums WHERE is_featured ORDER BY release_date DESC LIMIT 1');
    return $album === null ? null : attach_tracks($pdo, [$album])[0];
}

function album_links(array $album): array
{
    $links = json_decode((string)($album['streaming_links'] ?? '{}'), true);
    return is_array($links) ? $links : [];
}

function singles(PDO $pdo): array
{
    return fetch_all($pdo, 'SELECT t.title, t.single_year, a.title AS album FROM tracks t
        LEFT JOIN albums a ON a.id = t.album_id WHERE t.single_year IS NOT NULL ORDER BY t.single_year DESC, t.title');
}

// ===== Cerita band =====
function active_members(PDO $pdo): array
{
    return fetch_all($pdo, 'SELECT * FROM members WHERE is_active ORDER BY sort_order, id');
}

function milestones_by_type(PDO $pdo, string $type): array
{
    return fetch_all($pdo, 'SELECT * FROM milestones WHERE type = :type ORDER BY sort_order, year, id', ['type' => $type]);
}

// ===== Sahabat NOAH =====
function communities(PDO $pdo): array
{
    return fetch_all($pdo, 'SELECT * FROM communities ORDER BY member_count DESC, city');
}

function approved_submissions(PDO $pdo, string $type, int $limit): array
{
    return fetch_all($pdo, "SELECT s.*, c.city FROM fan_submissions s LEFT JOIN communities c ON c.id = s.community_id
        WHERE s.status = 'approved' AND s.type = :type ORDER BY s.created_at DESC LIMIT " . max(1, $limit), ['type' => $type]);
}

// ===== Toko =====
function products(PDO $pdo, bool $limited): array
{
    return fetch_all($pdo, 'SELECT * FROM products WHERE is_limited = :limited ORDER BY created_at DESC, id', ['limited' => $limited]);
}

function product_counts(array $products): array
{
    return array_count_values(array_column($products, 'category'));
}

function badge_class(string $badge): string
{
    return $badge === 'Terbatas' ? 'badge-limited' : 'badge-baru';
}
```

**Step 4: Jalankan test**

Run: `vendor/bin/phpunit tests/Integration/QueriesTest.php`
Expected: PASS (5 tests)

**Step 5: Jalankan semua test**

Run: `vendor/bin/phpunit`
Expected: semua PASS

**Step 6: Commit**

```bash
git add app/queries.php tests/Integration/QueriesTest.php
git commit -m "feat(app): query baca untuk halaman publik"
```

---

### Task 18: Partial header/footer/flash + CSS tambahan

**Files:**
- Create: `partials/header.php`, `partials/footer.php`, `partials/flash.php`
- Modify: `style.css` (tambah di akhir file)

**Step 1: `partials/header.php`**

Header diambil dari `index.html` baris 1–66, dengan perubahan: komentar catatan gambar dibuang, link `.html` diganti `.php`, dan link ke halaman aktif dilewati (sesuai perilaku HTML lama).

```php
<?php
/** @var array{title: string, body: string, active: string, search_id: string} $page */
$navItems = [
    'cerita-band' => 'Cerita Band', 'diskografi' => 'Diskografi', 'tur-event' => 'Event',
    'berita-media' => 'Media', 'sahabat-noah' => 'Sahabat NOAH', 'toko' => 'Toko',
];
$visibleNav = array_filter($navItems, fn (string $file): bool => $file !== $page['active'], ARRAY_FILTER_USE_KEY);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page['title']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="<?= e($page['body']) ?>">

  <!-- ===== HEADER / NAVBAR ===== -->
  <header class="navbar">
    <div class="container navbar-isi">
      <a href="index.php" class="logo">NOAH</a>

      <nav class="menu">
<?php foreach ($visibleNav as $file => $label): ?>
        <a href="<?= e($file) ?>.php"><?= e($label) ?></a>
<?php endforeach; ?>
      </nav>
      <details class="mobile-menu">
        <summary>Menu</summary>
        <div class="mobile-menu-panel">
<?php foreach ($visibleNav as $file => $label): ?>
          <a href="<?= e($file) ?>.php"><?= e($label) ?></a>
<?php endforeach; ?>
        </div>
      </details>
      <div class="navbar-kanan">
        <details class="search-box">
          <summary class="search-icon" aria-label="Buka pencarian"><svg aria-hidden="true" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><title>Ikon pencarian</title><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></summary>
          <form class="search-panel" action="berita-media.php" method="get">
            <label for="<?= e($page['search_id']) ?>">Cari konten NOAH</label>
            <div class="search-input-baris">
              <input id="<?= e($page['search_id']) ?>" type="search" name="q" maxlength="100" placeholder="Album, event, berita...">
              <button type="submit" class="btn btn-merah">Cari</button>
            </div>
            <div class="search-saran">
              <a href="diskografi.php">Diskografi</a>
              <a href="tur-event.php">Event</a>
              <a href="berita-media.php">Media</a>
            </div>
          </form>
        </details>
        <span class="lang">ID</span>
      </div>
    </div>
  </header>
```

**Step 2: `partials/footer.php`**

Salin blok `<footer class="footer">` … `</html>` dari `index.html` (baris 277–324), lalu ganti semua `href="xxx.html` menjadi `href="xxx.php`. Isi akhirnya:

```php
  <footer class="footer">
  <div class="container grid-4">
    <div>
      <div class="logo-footer">NOAH</div>
      <p class="teks-abu">Representasi musik pop-rock terbaik Indonesia. Menjangkau hati lewat melodi dan lirik yang melampaui waktu.</p>
    </div>
    <div>
      <h4 class="footer-judul">Navigasi</h4>
      <ul>
        <li class="nav-cerita"><a href="cerita-band.php">Cerita Band</a></li>
        <li class="nav-diskografi"><a href="diskografi.php">Diskografi</a></li>
        <li class="nav-event"><a href="tur-event.php">Event</a></li>
        <li class="nav-media"><a href="berita-media.php">Media</a></li>
        <li class="nav-sahabat"><a href="sahabat-noah.php">Sahabat NOAH</a></li>
        <li class="nav-toko"><a href="toko.php">Toko</a></li>
      </ul>
    </div>
    <div>
      <h4 class="footer-judul">Musik</h4>
      <ul>
        <li><a href="https://open.spotify.com/album/4EneY8crCrt61L7siIXtkS?si=UTO1NEtATPqdLRThsBjRjQ" target="_blank" rel="noopener">Spotify</a></li>
        <li><a href="https://music.apple.com/id/artist/noah/766158889?l=id" target="_blank" rel="noopener">Apple Music</a></li>
        <li><a href="https://music.youtube.com/channel/UCbtHlc5rJCvSb_k0VyUowLA" target="_blank" rel="noopener">YouTube Music</a></li>
        <li><a href="diskografi.php">Diskografi</a></li>
      </ul>
    </div>
    <div>
      <h4 class="footer-judul">Legal</h4>
      <ul>
        <li><a href="index.php#kebijakan-privasi">Kebijakan Privasi</a></li>
        <li><a href="index.php#ketentuan-layanan">Ketentuan Layanan</a></li>
        <li><a href="tur-event.php#pemesanan-event">Kontak</a></li>
        <li><a href="berita-media.php#materi-pers">Materi Pers</a></li>
      </ul>
    </div>
  </div>
  <div class="container footer-bawah">
    <p>&copy; 2024 NOAH. Hak Cipta Dilindungi.</p>
    <div class="footer-ikon">
      <!-- salin 2 <span> ikon SVG apa adanya dari index.html -->
    </div>
  </div>
</footer>

  <script src="app.js" defer></script>
</body>
</html>
```

(Komentar `salin 2 <span>` di atas diganti dengan 2 baris `<span aria-hidden="true"><svg …></svg></span>` dari `index.html` baris 314–315.)

**Step 3: `partials/flash.php`**

```php
<?php
/** Tampilkan pesan sukses/error form publik. Pakai: render_flash('tiket'). */
function render_flash(string $name): string
{
    $ok = flash($name);
    $error = flash($name . '_error');
    return ($ok !== null ? '<p class="pesan-sukses" role="status">' . e($ok) . '</p>' : '')
        . ($error !== null ? '<p class="pesan-error" role="alert">' . e($error) . '</p>' : '');
}
```

File ini tidak dimuat oleh `app/bootstrap.php`. Halaman publik yang punya form me-`require __DIR__ . '/partials/flash.php'` setelah bootstrap.

**Step 4: Tambahkan di akhir `style.css`**

```css
/* ===== Form dinamis (PHP) ===== */
.hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
.pesan-sukses, .pesan-error { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 0.95rem; }
.pesan-sukses { background: #e8f5e9; color: #1b5e20; }
.pesan-error { background: #fdecea; color: #b71c1c; }
.hasil-cari { list-style: none; display: grid; gap: 12px; padding: 0; }
a.page-btn { display: inline-block; text-decoration: none; }
```

**Step 5: Cek sintaks**

```bash
php -l partials/header.php && php -l partials/footer.php && php -l partials/flash.php
```

Expected: `No syntax errors detected` ×3

**Step 6: Commit**

```bash
git add partials style.css
git commit -m "feat(publik): partial header, footer, dan pesan form"
```

---

### Task 19–25: Konversi 7 halaman publik

Pola yang sama untuk setiap halaman:

1. `git mv <halaman>.html <halaman>.php`
2. Ganti semua baris dari `<!DOCTYPE html>` sampai `</header>` milik navbar (baris pertama sampai sekitar 66) dengan blok PHP pembuka dari task masing-masing.
3. Ganti semua baris dari `<footer class="footer">` sampai `</html>` dengan:
   ```php
   <?php require __DIR__ . '/partials/footer.php'; ?>
   ```
4. Ganti sisa link internal `.html` ke `.php`:
   ```bash
   sed -i -E 's/href="(index|cerita-band|diskografi|tur-event|berita-media|sahabat-noah|toko)\.html/href="\1.php/g' <halaman>.php
   ```
5. Ganti section dinamis dengan potongan kode di task.
6. Cek: `php -l <halaman>.php`, buka `http://localhost:8000/<halaman>.php`, lalu bandingkan dengan screenshot di `stitch_noah_band_official_website_wireframe/.../screen.png` atau versi `.html` di `git show HEAD~1:<halaman>.html`.
7. Commit: `git add -A <halaman>.* && git commit -m "feat(publik): <halaman> membaca data dari database"`

---

### Task 19: `index.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/partials/flash.php';
$pdo = db();
$album = featured_album($pdo);
$concerts = upcoming_events($pdo, 'concert', 3);
$news = news_posts($pdo, 3);
$page = ['title' => 'NOAH - Situs Resmi', 'body' => 'page-index', 'active' => 'index', 'search_id' => 'search-index'];
require __DIR__ . '/partials/header.php';
?>
```

**Section `#player`** (isi `<section id="player" class="player-wrap">`):

```php
  <section id="player" class="player-wrap">
  <?php if ($album !== null): $links = album_links($album); ?>
    <div class="musik-native-card card-border-merah">
      <div class="musik-native-cover">
        <img src="<?= e($album['cover_url'] ?? 'images/album-cover.jpg') ?>" alt="Album <?= e($album['title']) ?>">
      </div>
      <div class="musik-native-info">
        <p class="label-merah">Album Pilihan</p>
        <h2 class="section-title"><?= e($album['title']) ?></h2>
        <p class="lagu-artis">NOAH</p>
        <ol class="musik-native-list">
          <?php foreach ($album['tracks'] as $track): ?>
            <li><span><?= e($track['title']) ?></span><span><?= e(durasi($track['duration_sec'] === null ? null : (int)$track['duration_sec'])) ?></span></li>
          <?php endforeach; ?>
        </ol>
        <div class="musik-native-aksi">
          <?php if (isset($links['spotify'])): ?><a href="<?= e($links['spotify']) ?>" target="_blank" rel="noopener" class="btn btn-merah">Spotify</a><?php endif; ?>
          <?php if (isset($links['ytmusic'])): ?><a href="<?= e($links['ytmusic']) ?>" target="_blank" rel="noopener" class="btn btn-outline-merah">YouTube Music</a><?php endif; ?>
          <?php if (isset($links['apple'])): ?><a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener" class="link-merah">Apple Music</a><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
  </section>
```

**Konser Mendatang** (ganti 3 `<div class="card card-border-merah">` di dalam `<div class="grid-3">` pertama):

```php
<?php foreach ($concerts as $event): ?>
      <div class="card card-border-merah">
        <div class="card-atas">
          <div>
            <p class="tanggal-besar"><?= e(date_part($event['start_at'], 'd')) ?></p>
            <p class="bulan"><?= e(bulan_singkat($event['start_at'])) ?></p>
          </div>
          <span class="label-tersedia"><?= event_available($event) ? 'Tersedia' : 'Habis' ?></span>
        </div>
        <div class="card-tengah">
          <h3 class="kota"><?= e($event['city']) ?></h3>
          <p class="venue"><?= e($event['venue']) ?></p>
        </div>
        <a class="btn btn-merah lebar" href="tur-event.php#konser-mendatang">Info Acara</a>
      </div>
<?php endforeach; ?>
<?php if ($concerts === []): ?><p class="pesan-kosong">Belum ada konser terjadwal.</p><?php endif; ?>
```

**Berita Terbaru** (ganti 3 `<div class="berita">`):

```php
<?php foreach ($news as $post): ?>
      <div class="berita">
        <div class="berita-gambar"><img src="<?= e($post['cover_url'] ?? 'images/news-1.jpg') ?>" alt="<?= e($post['title']) ?>"></div>
        <?php if ($post['tag']): ?><span class="pill"><?= e($post['tag']) ?></span><?php endif; ?>
        <p class="berita-tanggal"><?= e(tanggal_id($post['published_at'])) ?></p>
        <h3 class="berita-judul"><?= e($post['title']) ?></h3>
        <p class="paragraf-kecil"><?= e($post['excerpt']) ?></p>
      </div>
<?php endforeach; ?>
```

**Komunitas/newsletter:** tambahkan `id="komunitas"` pada `<section class="section bg-gelap">`, lalu ganti `<form class="newsletter">…</form>` dengan:

```php
        <?= render_flash('langganan') ?>
        <form class="newsletter" method="post" action="actions/subscribe.php">
          <?= csrf_field() ?><?= honeypot_field() ?>
          <input type="hidden" name="from" value="index">
          <input type="email" name="email" placeholder="Alamat Email Anda" aria-label="Alamat email" required maxlength="150">
          <button type="submit" class="btn btn-merah">Berlangganan</button>
        </form>
```

**Kebijakan Privasi:** isinya sekarang tidak benar lagi. Ganti paragrafnya dengan:

```html
<p class="paragraf-kecil">Data yang kamu kirim lewat form (pesanan tiket, cerita/karya, dan email newsletter) disimpan di database situs ini dan hanya dipakai untuk keperluan website fan NOAH.</p>
```

**Uji:**
- Beranda tampil sama seperti versi HTML, kecuali urutan lagu Album Pilihan (sekarang mengikuti nomor lagu).
- Isi email newsletter → muncul pesan sukses. Kirim email yang sama lagi → tetap sukses, tanpa duplikat di admin Subscriber.

**Commit:** `git add -A index.html index.php && git commit -m "feat(publik): beranda membaca data dari database"`

---

### Task 20: `cerita-band.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pdo = db();
$history = milestones_by_type($pdo, 'history');
$awards = milestones_by_type($pdo, 'award');
$members = active_members($pdo);
$page = ['title' => 'Cerita Band - NOAH', 'body' => 'page-cerita', 'active' => 'cerita-band', 'search_id' => 'search-cerita'];
require __DIR__ . '/partials/header.php';
?>
```

**Timeline** (isi `<div class="timeline">`):

```php
<?php foreach ($history as $item): ?>
        <div class="timeline-item">
          <span class="tahun"><?= e((string)$item['year']) ?></span>
          <h3 class="berita-judul"><?= e($item['title']) ?></h3>
          <p class="paragraf-kecil"><?= e($item['description']) ?></p>
        </div>
<?php endforeach; ?>
```

**Anggota** (isi `<div class="grid-3">` di section Kenali Anggota):

```php
<?php foreach ($members as $member): ?>
        <div class="card card-border-merah tengah">
          <div class="avatar"><img src="<?= e($member['photo_url'] ?? 'images/noah_band.jpg') ?>" alt="<?= e($member['name']) ?>"></div>
          <h4 class="kota"><?= e($member['name']) ?></h4>
          <p class="lagu-artis"><?= e($member['position']) ?></p>
          <p class="paragraf-kecil"><?= e($member['bio']) ?></p>
        </div>
<?php endforeach; ?>
```

**Penghargaan** (isi `<div class="stats">`):

```php
<?php foreach ($awards as $award): ?>
        <div class="stat"><span class="stat-angka"><?= e($award['badge']) ?></span><p class="teks-abu"><?= e($award['title']) ?></p></div>
<?php endforeach; ?>
```

**Uji:** timeline tetap beranimasi (`app.js` membaca `.timeline-item`), 3 anggota dan 6 statistik tampil.

**Commit:** `git add -A cerita-band.html cerita-band.php && git commit -m "feat(publik): cerita band membaca data dari database"`

---

### Task 21: `diskografi.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pdo = db();
$albums = albums_for_grid($pdo);
$singles = singles($pdo);
$page = ['title' => 'Diskografi - NOAH', 'body' => 'page-diskografi', 'active' => 'diskografi', 'search_id' => 'search-diskografi'];
require __DIR__ . '/partials/header.php';
?>
```

**Grid album:** ganti keenam `<div id="album-…" class="card …" data-album …>` di dalam `<div class="grid-3">`. Biarkan tag penutup `</div>` dan `<p id="album-kosong">` apa adanya.

```php
<?php foreach ($albums as $album): ?>
      <div id="album-<?= e($album['slug']) ?>" class="card card-border-merah" data-album data-era="<?= e($album['era']) ?>" data-tipe="<?= e($album['type']) ?>">
        <div class="album-cover"><img src="<?= e($album['cover_url'] ?? 'images/album-cover.jpg') ?>" alt="<?= e($album['title']) ?>"></div>
        <div class="album-head">
          <div>
            <h3 class="berita-judul"><?= e($album['title']) ?></h3>
            <p class="lagu-artis"><?= e(date_part($album['release_date'], 'Y')) ?> &bull; <?= (int)($album['total_tracks'] ?? count($album['tracks'])) ?> Lagu</p>
          </div>
          <span class="pill"><?= e(ALBUM_TYPES[$album['type']]) ?></span>
        </div>
        <ul class="tracklist">
          <?php foreach ($album['tracks'] as $track): ?>
            <li><?= (int)$track['track_no'] ?>. <?= e($track['title']) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn-merah lebar" href="#album-<?= e($album['slug']) ?>">Lihat Album</a>
      </div>
<?php endforeach; ?>
```

**Tabel Single** (isi `<tbody>`):

```php
<?php foreach ($singles as $single): ?>
        <tr><td><?= e($single['title']) ?></td><td><?= (int)$single['single_year'] ?></td><td><?= e($single['album'] ?? '—') ?></td></tr>
<?php endforeach; ?>
```

**Uji:**
- Filter Era/Tipe di `app.js` masih berfungsi.
- Urutan album sekarang berdasarkan tahun rilis (terbaru dulu).
- Album "Tak Bisakah" tampil sebagai "Sebuah Nama, Sebuah Cerita", bukan "OST Alexandria", karena tiap lagu hanya punya satu album di DB.

**Commit:** `git add -A diskografi.html diskografi.php && git commit -m "feat(publik): diskografi membaca data dari database"`

---

### Task 22: `tur-event.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/partials/flash.php';
$pdo = db();
$concerts = upcoming_events($pdo, 'concert');
$archive = past_events($pdo);
$setlists = setlists($pdo);
$mapEvent = null;
foreach ($concerts as $candidate) {
    if (maps_embed_url($candidate['maps_url']) !== null) {
        $mapEvent = $candidate;
        break;
    }
}
$page = ['title' => 'Event - NOAH', 'body' => 'page-event', 'active' => 'tur-event', 'search_id' => 'search-event'];
require __DIR__ . '/partials/header.php';
?>
```

**Banner newsletter:** tambahkan `id="langganan"` pada `<section class="banner-merah">`, lalu ganti `<form class="banner-form">…</form>`:

```php
      <?= render_flash('langganan') ?>
      <form class="banner-form" method="post" action="actions/subscribe.php">
        <?= csrf_field() ?><?= honeypot_field() ?>
        <input type="hidden" name="from" value="tur-event">
        <input type="email" name="email" placeholder="Alamat Email Anda" aria-label="Alamat email" required maxlength="150">
        <button type="submit" class="btn btn-outline-putih">Beritahu Saya</button>
      </form>
```

**Konser Mendatang** (isi `<div class="grid-auto">` pertama):

```php
<?php foreach ($concerts as $event): ?>
      <div class="card card-border-merah">
        <div class="card-atas">
          <div>
            <p class="tanggal-besar"><?= e(date_part($event['start_at'], 'd')) ?></p>
            <p class="bulan"><?= e(bulan_singkat($event['start_at']) . ' ' . date_part($event['start_at'], 'Y')) ?></p>
          </div>
          <span class="label-tersedia"><?= event_available($event) ? 'Tersedia' : 'Habis' ?></span>
        </div>
        <div class="card-tengah">
          <h3 class="kota"><?= e($event['city']) ?></h3>
          <p class="venue"><?= e($event['venue']) ?></p>
          <p class="paragraf-kecil"><?= e($event['description']) ?></p>
        </div>
        <?php if (event_available($event) && $event['ticket_price'] !== null): ?>
          <a class="btn btn-merah lebar" href="#pemesanan-event" data-pilih-konser="<?= (int)$event['id'] ?>">Pesan Tiket</a>
        <?php endif; ?>
      </div>
<?php endforeach; ?>
<?php if ($concerts === []): ?><p class="pesan-kosong">Belum ada konser terjadwal.</p><?php endif; ?>
```

**Arsip Event** (isi `<div class="grid-auto">` di section Arsip Event):

```php
<?php foreach ($archive as $event): ?>
        <div class="card <?= $event['badge'] ? 'card-border-kuning' : 'card-border-merah' ?>">
          <?php if ($event['badge']): ?>
            <span class="label-kuning"><?= e($event['badge']) ?></span>
          <?php else: ?>
            <p class="lagu-artis"><?= e(tanggal_singkat($event['start_at'])) ?></p>
          <?php endif; ?>
          <h3 class="berita-judul"><?= e($event['title']) ?></h3>
          <p class="paragraf-kecil"><?= e($event['description']) ?></p>
          <div class="tag-list">
            <a href="#arsip-setlist" class="pill">Setlist</a>
            <a href="berita-media.php#galeri-foto" class="pill">Galeri</a>
          </div>
        </div>
<?php endforeach; ?>
```

**Arsip Setlist:** ganti ketiga `<details class="setlist">`. Event tanpa lagu tidak tampil (dua accordion kosong di HTML lama hilang sampai admin mengisi setlist-nya).

```php
<?php foreach ($setlists as $i => $set): ?>
    <details class="setlist"<?= $i === 0 ? ' open' : '' ?>>
      <summary class="setlist-head"><span><?= e($set['title']) ?></span></summary>
      <div class="setlist-body grid-2">
        <div>
          <p class="label-merah">Set Utama</p>
          <ol class="tracklist">
            <?php foreach ($set['main'] as $track): ?><li><?= e($track['title']) ?></li><?php endforeach; ?>
          </ol>
        </div>
        <div>
          <?php if ($set['encore'] !== []): ?>
            <p class="label-merah">Encore</p>
            <ul class="tracklist">
              <?php foreach ($set['encore'] as $track): ?><li><?= $track['position'] ?>. <?= e($track['title']) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="detail-show">
            <p class="lagu-artis">Detail Pertunjukan</p>
            <p><strong><?= e($set['venue']) ?></strong></p>
            <p class="paragraf-kecil"><?= e(tanggal_id($set['start_at'])) ?></p>
            <?php if ($set['description']): ?><p class="paragraf-kecil"><?= e($set['description']) ?></p><?php endif; ?>
          </div>
        </div>
      </div>
    </details>
<?php endforeach; ?>
```

**Form tiket** (ganti `<form class="form">…</form>` di `#pemesanan-event`):

```php
        <?= render_flash('tiket') ?>
        <form class="form" method="post" action="actions/order-ticket.php">
          <?= csrf_field() ?><?= honeypot_field() ?>
          <div class="form-baris">
            <input type="text" name="full_name" value="<?= e(old('tiket', 'full_name')) ?>" placeholder="Nama Lengkap" aria-label="Nama lengkap" required maxlength="120">
            <input type="email" name="email" value="<?= e(old('tiket', 'email')) ?>" placeholder="Email" aria-label="Email" required maxlength="150">
          </div>
          <div class="form-baris">
            <input type="tel" name="phone" value="<?= e(old('tiket', 'phone')) ?>" placeholder="Nomor Telepon" aria-label="Nomor telepon" required maxlength="20">
            <input type="number" name="quantity" min="1" max="10" value="<?= e(old('tiket', 'quantity') ?: '1') ?>" aria-label="Jumlah tiket" required>
          </div>
          <select id="pilih-konser" name="event_id" aria-label="Pilih konser" required>
            <option value="" disabled<?= old('tiket', 'event_id') === '' ? ' selected' : '' ?>>Pilih Konser</option>
            <?php foreach ($concerts as $event): ?>
              <?php if (event_available($event) && $event['ticket_price'] !== null): ?>
                <option value="<?= (int)$event['id'] ?>"<?= old('tiket', 'event_id') === (string)$event['id'] ? ' selected' : '' ?>>
                  <?= e($event['city'] . ' - ' . $event['venue'] . ', ' . tanggal_id($event['start_at']) . ' (' . rupiah($event['ticket_price']) . ')') ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <textarea name="notes" rows="4" maxlength="500" placeholder="Catatan Tambahan (Opsional)" aria-label="Catatan tambahan"><?= e(old('tiket', 'notes')) ?></textarea>
          <button type="submit" class="btn btn-merah lebar">Pesan Tiket</button>
        </form>
```

**Peta:** ganti kartu `Lokasi Konser Jakarta`:

```php
        <?php if ($mapEvent !== null): ?>
        <div class="card mb">
          <h3 class="berita-judul">Lokasi Konser <?= e($mapEvent['city']) ?></h3>
          <div class="map-wrap">
            <iframe title="Peta lokasi <?= e($mapEvent['venue']) ?>" src="<?= e(maps_embed_url($mapEvent['maps_url'])) ?>"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        </div>
        <?php endif; ?>
```

**Uji:**
1. Klik "Pesan Tiket" di kartu → `<select>` otomatis terpilih (`app.js` mengisi value = id event).
2. Pesan 2 tiket → pesan sukses. Di admin, Pesanan Tiket menampilkan total `Rp 700.000`.
3. Pesan 11 tiket lewat DevTools (hapus atribut `max`) → error "Jumlah tiket maksimal 10." dan isian lain tetap terisi.
4. Kirim form 6× dalam 10 menit → kiriman ke-6 kena "Terlalu banyak kiriman".

**Commit:** `git add -A tur-event.html tur-event.php && git commit -m "feat(publik): halaman event, setlist, dan pemesanan tiket dari database"`

---

### Task 23: `berita-media.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pdo = db();
$q = mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 100);
$results = $q === '' ? null : search_site($pdo, $q);
$featured = featured_post($pdo);
$pages = max(1, (int)ceil(count_news($pdo, true) / NEWS_PER_PAGE));
$hal = min($pages, max(1, is_string($_GET['hal'] ?? null) && ctype_digit($_GET['hal']) ? (int)$_GET['hal'] : 1));
$news = news_posts($pdo, NEWS_PER_PAGE, ($hal - 1) * NEWS_PER_PAGE, true);
$behind = behind_scenes($pdo);
$photos = media_items($pdo, 'photo');
$pressKits = media_items($pdo, 'press_kit');
$page = ['title' => 'Media - NOAH', 'body' => 'page-media', 'active' => 'berita-media', 'search_id' => 'search-media'];
require __DIR__ . '/partials/header.php';
?>
```

**Hasil pencarian** (sisipkan tepat sebelum `<section id="artikel-hiatus" …>`):

```php
<?php if ($results !== null): ?>
  <section id="hasil-cari" class="container section">
    <h2 class="section-title judul-garis mb">Hasil pencarian "<?= e($q) ?>"</h2>
    <?php if ($results['posts'] === [] && $results['albums'] === [] && $results['events'] === []): ?>
      <p class="pesan-kosong">Tidak ada hasil untuk pencarian ini.</p>
    <?php endif; ?>
    <ul class="hasil-cari">
      <?php foreach ($results['posts'] as $r): ?><li><span class="pill">Berita</span> <a href="#berita-<?= e($r['slug']) ?>" class="link-merah"><?= e($r['title']) ?></a></li><?php endforeach; ?>
      <?php foreach ($results['albums'] as $r): ?><li><span class="pill">Album</span> <a href="diskografi.php#album-<?= e($r['slug']) ?>" class="link-merah"><?= e($r['title']) ?></a></li><?php endforeach; ?>
      <?php foreach ($results['events'] as $r): ?><li><span class="pill">Event</span> <a href="tur-event.php#arsip-setlist" class="link-merah"><?= e($r['title']) ?></a> — <?= e($r['city']) ?></li><?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
```

**Headline:** isi `#artikel-hiatus` dibungkus `<?php if ($featured !== null): ?> … <?php endif; ?>`:

```php
<?php if ($featured !== null): ?>
  <section id="berita-<?= e($featured['slug']) ?>" class="berita-hero">
    <div class="container">
      <?php if ($featured['tag']): ?><span class="pill-merah"><?= e($featured['tag']) ?></span><?php endif; ?>
      <h1 class="hero-judul"><?= e($featured['title']) ?></h1>
      <p class="hero-subjudul-kiri"><?= e($featured['excerpt']) ?></p>
      <div class="hero-meta">
        <span class="teks-abu"><?= e(tanggal_id($featured['published_at'])) ?></span>
        <a class="btn btn-merah" href="#berita-terbaru">Baca Selengkapnya</a>
      </div>
    </div>
  </section>
<?php endif; ?>
```

**Berita Terbaru** (isi `<div class="grid-auto">` + `<div class="pagination">`):

```php
<?php foreach ($news as $post): ?>
      <article id="berita-<?= e($post['slug']) ?>" class="card card-border-merah">
        <div class="berita-gambar"><img src="<?= e($post['cover_url'] ?? 'images/news-1.jpg') ?>" alt="<?= e($post['title']) ?>"></div>
        <?php if ($post['tag']): ?><span class="pill"><?= e($post['tag']) ?></span><?php endif; ?>
        <h3 class="berita-judul"><?= e($post['title']) ?></h3>
        <p class="berita-tanggal"><?= e(tanggal_id($post['published_at'])) ?></p>
        <p class="paragraf-kecil mb"><?= e($post['excerpt']) ?></p>
        <a href="#berita-<?= e($post['slug']) ?>" class="link-merah">Baca Selengkapnya</a>
      </article>
<?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="page-btn<?= $i === $hal ? ' aktif' : '' ?>" href="?hal=<?= $i ?>#berita-terbaru"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($hal < $pages): ?><a class="page-btn" href="?hal=<?= $hal + 1 ?>#berita-terbaru">Berikutnya</a><?php endif; ?>
    </div>
    <?php endif; ?>
```

**Di Balik Layar** (isi `<div class="grid-auto">`):

```php
<?php foreach ($behind as $post): ?>
        <div class="card">
          <div class="berita-gambar"><img src="<?= e($post['cover_url'] ?? 'images/gallery-1.jpg') ?>" alt="<?= e($post['title']) ?>"></div>
          <span class="<?= $post['tag'] === 'Video' ? 'pill-merah' : 'pill' ?>"><?= e($post['tag']) ?></span>
          <h4 class="berita-judul"><?= e($post['title']) ?></h4>
          <p class="paragraf-kecil"><?= e($post['excerpt']) ?></p>
        </div>
<?php endforeach; ?>
```

**Galeri** (isi `<div class="galeri-foto">`; tombol filter tetap statis):

```php
<?php foreach ($photos as $photo): ?>
      <div class="galeri-item" data-foto data-kategori="<?= e($photo['tag']) ?>"><img src="<?= e($photo['file_url']) ?>" alt="<?= e($photo['caption'] ?? $photo['title']) ?>"></div>
<?php endforeach; ?>
```

**Materi Pers** (isi `<div class="card">` di `#materi-pers`):

```php
<?php foreach ($pressKits as $kit): ?>
      <div class="presskit-baris">
        <div>
          <h4 class="berita-judul"><?= e($kit['title']) ?></h4>
          <p class="lagu-artis"><?= e($kit['caption']) ?></p>
        </div>
        <a class="btn btn-merah" href="<?= e($kit['file_url']) ?>">Lihat Info</a>
      </div>
<?php endforeach; ?>
```

**Uji:**
- Cari `wanitaku` dari header → muncul hasil "Pembuatan 'Wanitaku'". Cari `' OR 1=1 --` → "Tidak ada hasil".
- Filter galeri (`app.js`) masih berfungsi.
- Halaman 2 muncul karena seed punya 6 berita non-headline + headline.

**Commit:** `git add -A berita-media.html berita-media.php && git commit -m "feat(publik): halaman media dan pencarian dari database"`

---

### Task 24: `sahabat-noah.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/partials/flash.php';
$pdo = db();
$stories = approved_submissions($pdo, 'story', 3);
$artworks = approved_submissions($pdo, 'artwork', 8);
$communities = communities($pdo);
$communityEvents = upcoming_events($pdo, 'community', 4);
$page = ['title' => 'Sahabat NOAH - Komunitas Resmi', 'body' => 'page-sahabat', 'active' => 'sahabat-noah', 'search_id' => 'search-sahabat'];
require __DIR__ . '/partials/header.php';
?>
```

**Cerita Sahabat** (isi `<div class="grid-auto">` pertama):

```php
<?php foreach ($stories as $story): ?>
      <div class="card card-border-merah">
        <div class="cerita-head">
          <div class="avatar-kecil"><img src="<?= e($story['file_url'] ?? 'images/fan-1.jpg') ?>" alt="<?= e($story['name']) ?>"></div>
          <div><h4 class="kota"><?= e($story['name']) ?></h4><p class="lagu-artis"><?= e($story['city']) ?></p></div>
        </div>
        <p class="kutipan">"<?= e($story['content']) ?>"</p>
        <a href="#form-cerita" class="link-merah">Baca Selengkapnya</a>
      </div>
<?php endforeach; ?>
```

**Karya Sahabat** (isi `<div class="galeri-foto">`):

```php
<?php foreach ($artworks as $art): ?>
        <div class="galeri-item"><img src="<?= e($art['file_url'] ?? 'images/album-1.jpg') ?>" alt="Karya <?= e($art['name']) ?>"></div>
<?php endforeach; ?>
```

**Komunitas Regional** (isi `<div class="grid-auto">` ketiga):

```php
<?php foreach ($communities as $community): ?>
      <div class="card card-border-merah">
        <h3 class="berita-judul"><?= e($community['city']) ?></h3>
        <p class="paragraf-kecil">Anggota: <strong><?= e(number_format((int)$community['member_count'], 0, ',', '.')) ?></strong></p>
        <?php if ($community['leader_name']): ?><p class="paragraf-kecil mb">Ketua: <strong><?= e($community['leader_name']) ?></strong></p><?php endif; ?>
        <a href="<?= e($community['contact_url'] ?? '#form-cerita') ?>" class="link-merah">Gabung Komunitas</a>
      </div>
<?php endforeach; ?>
```

**Event Komunitas** (isi `<div class="grid-auto">` di section Event Komunitas):

```php
<?php foreach ($communityEvents as $i => $event): ?>
        <div class="event-card">
          <div class="event-tanggal <?= $i === 0 ? 'bg-merah' : 'bg-hitam' ?>">
            <span class="event-angka"><?= e(date_part($event['start_at'], 'd')) ?></span>
            <span><?= e(bulan_panjang($event['start_at'])) ?></span>
          </div>
          <div class="event-isi">
            <?php if ($event['badge']): ?><span class="label-merah"><?= e($event['badge']) ?></span><?php endif; ?>
            <h3 class="berita-judul"><?= e($event['title']) ?></h3>
            <p class="paragraf-kecil mb"><?= e($event['description']) ?></p>
            <a class="btn btn-merah" href="#form-cerita">Daftar</a>
          </div>
        </div>
<?php endforeach; ?>
<?php if ($communityEvents === []): ?><p class="pesan-kosong">Belum ada event komunitas terjadwal.</p><?php endif; ?>
```

**Form kirim cerita** (ganti `<form class="form">…</form>` di `#form-cerita`):

```php
      <?= render_flash('cerita') ?>
      <form class="form" method="post" action="actions/submit-fan.php">
        <?= csrf_field() ?><?= honeypot_field() ?>
        <div class="form-baris">
          <div>
            <label for="fan-nama">Nama Lengkap</label>
            <input id="fan-nama" type="text" name="name" value="<?= e(old('cerita', 'name')) ?>" placeholder="Masukkan nama" required maxlength="120">
          </div>
          <div>
            <label for="fan-email">Alamat Email</label>
            <input id="fan-email" type="email" name="email" value="<?= e(old('cerita', 'email')) ?>" placeholder="email@domain.com" required maxlength="150">
          </div>
        </div>
        <label for="fan-kategori">Kategori</label>
        <select id="fan-kategori" name="type" required>
          <option value="">Pilih Kategori</option>
          <?php foreach (['story' => 'Cerita Penggemar', 'artwork' => 'Karya Penggemar', 'cover' => 'Cover Lagu'] as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= old('cerita', 'type') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="fan-pesan">Pesan / Deskripsi</label>
        <textarea id="fan-pesan" name="content" rows="4" maxlength="2000" placeholder="Ceritakan sedikit tentang karyamu..." required><?= e(old('cerita', 'content')) ?></textarea>
        <button type="submit" class="btn btn-merah lebar">Kirim</button>
      </form>
```

**Uji:** kirim cerita → pesan "akan tampil setelah ditinjau". Kiriman belum tampil di halaman. Approve di admin → kiriman muncul di Cerita Sahabat.

**Commit:** `git add -A sahabat-noah.html sahabat-noah.php && git commit -m "feat(publik): halaman Sahabat NOAH dan form kiriman dari database"`

---

### Task 25: `toko.php`

**Blok pembuka:**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pdo = db();
$products = products($pdo, false);
$limited = products($pdo, true);
$counts = product_counts($products);
$maxPrice = (int)(ceil(max([1500000, ...array_map('floatval', array_column($products, 'price'))]) / 50000) * 50000);
$page = ['title' => 'Toko - NOAH', 'body' => 'page-toko', 'active' => 'toko', 'search_id' => 'search-toko'];
require __DIR__ . '/partials/header.php';
?>
```

**Kategori** (isi `<ul class="kategori-list">`):

```php
          <li><button type="button" class="aktif" data-kategori="semua" aria-pressed="true">Semua <span><?= count($products) ?></span></button></li>
<?php foreach (PRODUCT_CATEGORIES as $value => $label): ?>
          <li><button type="button" data-kategori="<?= e($value) ?>" aria-pressed="false"><?= e($label) ?> <span><?= (int)($counts[$value] ?? 0) ?></span></button></li>
<?php endforeach; ?>
```

**Rentang harga:** ubah `max="1500000"` dan `value="1500000"` pada `#filter-harga` menjadi `<?= $maxPrice ?>`, dan teks `#harga-maksimal` menjadi `<?= e(rupiah($maxPrice)) ?>`.

**Grid produk** (ganti 6 kartu; biarkan `<p id="produk-kosong">`):

```php
<?php foreach ($products as $i => $product): ?>
        <div class="card card-border-merah produk" data-produk data-kategori="<?= e($product['category']) ?>" data-harga="<?= (int)$product['price'] ?>" data-urutan="<?= $i + 1 ?>">
          <div class="produk-gambar">
            <?php if ($product['badge']): ?><span class="<?= badge_class($product['badge']) ?>"><?= e($product['badge']) ?></span><?php endif; ?>
            <img src="<?= e($product['image_url'] ?? 'images/shop-banner.jpg') ?>" alt="<?= e($product['name']) ?>">
          </div>
          <h4 class="berita-judul"><?= e($product['name']) ?></h4>
          <p class="paragraf-kecil"><?= e($product['description']) ?></p>
          <p class="harga"><?= e(rupiah($product['price'])) ?></p>
          <a class="btn btn-merah lebar" href="#produk">Tambah ke Keranjang</a>
        </div>
<?php endforeach; ?>
```

**Edisi Terbatas** (isi `<div class="grid-2">` di section Edisi Terbatas):

```php
<?php foreach ($limited as $product): ?>
        <div class="card card-border-merah le-card">
          <div class="produk-gambar"><img src="<?= e($product['image_url'] ?? 'images/shop-banner.jpg') ?>" alt="<?= e($product['name']) ?>"></div>
          <div class="le-isi">
            <div class="le-head">
              <span class="label-emas">TERBATAS</span>
              <span class="link-merah"><?= (int)$product['stock'] > 0 ? 'Sisa ' . (int)$product['stock'] . ' item' : 'Habis' ?></span>
            </div>
            <h3 class="berita-judul"><?= e($product['name']) ?></h3>
            <p class="paragraf-kecil mb"><?= e($product['description']) ?></p>
            <p class="harga-besar"><?= e(rupiah($product['price'])) ?></p>
            <a class="btn btn-merah lebar" href="#produk">Beli Sekarang</a>
          </div>
        </div>
<?php endforeach; ?>
```

**Uji:** filter kategori, slider harga, dan urutan (`app.js`) masih berfungsi. Kategori "CD & Vinyl" sekarang ber-`data-kategori="musik"` (sama seperti HTML lama). Ubah stok Box Set ke 0 di admin → "Habis".

**Commit:** `git add -A toko.html toko.php && git commit -m "feat(publik): toko membaca produk dari database"`

---

### Task 26: Verifikasi akhir

**Step 1: Semua test**

Run: `vendor/bin/phpunit`
Expected: semua PASS (±52 test)

**Step 2: Tidak ada lagi link `.html` internal**

```bash
grep -nE 'href="[a-z-]+\.html' *.php partials/*.php
```

Expected: tidak ada output.

**Step 3: Lint semua file PHP**

```bash
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; | grep -v "No syntax errors"
```

Expected: tidak ada output.

**Step 4: Uji keamanan manual**

| Uji | Cara | Hasil yang diharapkan |
|---|---|---|
| CSRF | `curl -X POST -d "email=a@b.com" http://localhost:8000/actions/subscribe.php` | HTTP 419 |
| Akses admin tanpa login | buka `/admin/?r=products` di jendela incognito | redirect ke login |
| XSS | kirim cerita berisi `<script>alert(1)</script>`, lalu approve | tampil sebagai teks, tidak dieksekusi |
| SQLi | cari `' OR 1=1 --` | "Tidak ada hasil" |
| Upload PHP | upload `shell.php` yang di-rename `.jpg` | ditolak |
| Editor ke menu admin | login editor, buka `?r=admins` | 403 |
| Hapus diri sendiri | super admin hapus akunnya | ditolak dengan pesan |

**Step 5: Bandingkan tampilan**

Buka ketujuh halaman di lebar 375px dan 1280px, lalu bandingkan dengan screenshot wireframe. Catat perbedaan yang memang disengaja di bagian "Keputusan" di atas.

**Step 6: Update dokumen**

Di `docs/plans/2026-10-27-admin-panel-design.md`, tambahkan bagian `## Catatan implementasi`:
- filter tetap memakai `app.js`
- jadwal seed digeser ke 2027
- situs perlu hosting PHP, tidak bisa di GitHub Pages

Ubah juga `todo.md` baris "Catatan: project tetap HTML/CSS native tanpa JavaScript." menjadi deskripsi stack baru.

**Step 7: Commit**

```bash
git add docs/plans/2026-10-27-admin-panel-design.md todo.md
git commit -m "docs: catatan implementasi panel admin dan halaman dinamis"
```

Selesai. **Jangan push.** Beri tahu user bahwa semua commit siap di-push.
