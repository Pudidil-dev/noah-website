# NOAH Website — PHP MVC & PostgreSQL

Website fan NOAH dengan tujuh halaman publik, panel admin CRUD, formulir tiket, moderasi kiriman fans, setlist, dan newsletter. Tampilan lama disimpan sebagai template HTML di `app/views/band/`. Controller di `app/public-controller.php` merendernya dengan data dari model/query `app/queries.php`; panel admin memakai `admin/core/controller.php`, `admin/core/crud.php`, dan view `admin/views/`.

## Persyaratan

- PHP 8.2 dengan ekstensi `pdo_pgsql`, `fileinfo`, `mbstring`, dan `dom`
- Composer, Docker Desktop (PostgreSQL 16), port lokal `5433`

## Menjalankan

```powershell
docker compose up -d
composer install
if (-not (Test-Path config.php)) { Copy-Item config.example.php config.php }
```

Sunting host/port/akun database di `config.php` bila diperlukan. Buat database tes sekali:

```powershell
docker compose exec db createdb -U noah noah_test
```

Pada PowerShell, gunakan `cmd /d /c` untuk meneruskan isi berkas SQL sebagai byte asli (termasuk karakter UTF-8):

```powershell
cmd /d /c "docker compose exec -T db psql -U noah -d noah -v ON_ERROR_STOP=1 -f - < database\schema.sql"
cmd /d /c "docker compose exec -T db psql -U noah -d noah -v ON_ERROR_STOP=1 -f - < database\seed.sql"
```

`schema.sql` dan `seed.sql` ditujukan untuk database **kosong**. Jika data demo sebelumnya sudah dimuat, jangan jalankan seed kedua kali. Untuk menyegarkan database demo yang boleh dihapus, pastikan tidak ada data penting lalu buat ulang databasenya secara terpisah.

Admin tidak disertakan dalam seed. Buat melalui terminal dengan password pilihan sendiri:

```powershell
php database/create-admin.php "Nama Admin" admin@example.com super_admin
php -S localhost:8000 router.php
```

Buka `http://localhost:8000/` untuk halaman band dan `http://localhost:8000/admin/` untuk panel admin. `router.php` mencegah permintaan HTTP ke source, database, dan konfigurasi saat memakai server pengembangan PHP. Halaman publik membutuhkan server PHP dan tidak dapat di-host sebagai situs dinamis di GitHub Pages.

## Struktur ringkas

- `database/schema.sql`, `database/seed.sql`: struktur serta data contoh; `database/create-admin.php`: akun admin melalui CLI.
- `app/queries.php`, `app/forms.php`: model baca dan operasi formulir publik.
- `app/public-controller.php`: controller halaman publik; `app/views/`: fragment dan template band.
- `admin/resources.php`: definisi sebelas resource CRUD; `admin/custom/`: pesanan, moderasi, setlist.
- `actions/`: endpoint POST dengan CSRF, honeypot, pembatasan percobaan, dan redirect.

Filter album, galeri, dan toko tetap dijalankan oleh `app.js`. Pesanan tiket bersifat contoh dan tidak melakukan pembayaran; merchandise belum memiliki keranjang/checkout.

Untuk pengujian otomatis, jalankan `php vendor/bin/phpunit`. Test integrasi mereset skema **`noah_test`**, jadi jangan arahkan database tes ke database utama.
