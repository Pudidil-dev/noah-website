# Desain Panel Admin & Halaman Dinamis — NOAH Official Website

- **Stack:** PHP 8.2 native + PDO `pgsql` (sudah aktif di XAMPP) + PostgreSQL 16 (Docker)
- **Skema DB:** [`database/schema.sql`](../../database/schema.sql) · [desain basis data](2026-10-27-database-draft-design.md)
- **Pendekatan:** CRUD generik berbasis config + halaman custom untuk alur khusus
- **Cakupan:** panel admin, form publik, dan konversi 7 halaman publik ke `.php`

## 1. Struktur folder

```
week 11/
├── app/                 bootstrap.php, db.php, helpers.php, csrf.php, queries.php
├── config.example.php   contoh kredensial (di-commit)
├── config.php           kredensial asli (gitignore)
├── partials/            header.php, footer.php, search.php
├── *.php                7 halaman publik (rename dari .html)
├── actions/             order-ticket.php, submit-fan.php, subscribe.php
├── admin/
│   ├── index.php        router ?r=<resource>&a=<list|create|edit|delete>&id=
│   ├── resources.php    definisi resource
│   ├── core/            auth.php, crud.php, upload.php
│   ├── views/           layout.php, list.php, form.php, login.php, dashboard.php
│   └── custom/          ticket_orders.php, fan_submissions.php, setlist.php
├── uploads/             file upload admin (gitignore, .htaccess non-exec)
└── database/            schema.sql, seed.sql
```

**Alur:**
- **Publik GET:** halaman → `app/bootstrap.php` → `queries.php` → render.
- **Publik POST:** `actions/*` → CSRF → validasi → INSERT → redirect + flash (PRG).
- **Admin:** `admin/index.php` → cek login & role → `custom/<r>.php` kalau ada, kalau tidak pakai CRUD generik.

## 2. CRUD generik

Setiap resource di `resources.php` punya `label`, `table`, `list` (kolom yang tampil), `search`, `order`, `roles` (opsional), dan `fields`.

Tipe field yang didukung: `text`, `textarea`, `slug` (otomatis dari field lain), `int`, `money`, `bool`, `date`, `datetime`, `enum` (`options`), `fk` (`ref`, `label`), `image`, `json`.

| Aksi | Perilaku |
|---|---|
| list | Pencarian `ILIKE`, paginasi 20 baris, filter FK lewat query string (mis. `?album_id=`) |
| create/edit | Form otomatis dari `fields`, validasi `required`/`min`/`max`/`options` |
| simpan | Prepared statement; nama kolom hanya dari key config (whitelist) |
| delete | Hanya POST + CSRF; pelanggaran FK (SQLSTATE 23503) → pesan ramah |

| Generik | Custom |
|---|---|
| members, milestones, albums, tracks, communities, events, posts, media, products, subscribers, admins (super_admin) | `ticket_orders` (list, filter event, ubah status, tanpa create) · `fan_submissions` (antrian moderasi approve/reject, isi `reviewed_by`) · `setlist` (susun lagu per event) |

**Dashboard** menampilkan pesanan pending, kiriman fans yang belum direview, 3 event terdekat, dan produk dengan stok ≤ 5.

## 3. Login & keamanan

- **Login:** `password_verify()` → `session_regenerate_id(true)`. Cookie `httponly` + `samesite=Lax`. Session habis setelah 2 jam idle.
- **Batas percobaan:** 5 kali gagal per 15 menit.
- **Role:** `editor` bisa CRUD konten. `super_admin` tambahan: menu Admin dan hapus pesanan tiket.
- Admin awal dibuat lewat `database/create-admin.php` (CLI, password di-hash PHP). Tidak ada password di kode atau seed.
- Semua query pakai PDO dengan `ERRMODE_EXCEPTION` dan `EMULATE_PREPARES=false`.
- Semua output lewat `e()`. Isi berita berupa teks biasa yang ditampilkan dengan `nl2br(e())`.
- Token CSRF di semua form POST. Error ke user berupa pesan umum, detailnya ke `error_log()`.
- **Upload:** jpg/png/webp dicek via `finfo`, maksimal 2 MB, nama file acak, disimpan di `uploads/<tabel>/`. File lama dihapus saat diganti atau datanya dihapus.

**Form publik**
- **Tiket:** event harus `published` dan belum lewat; `quantity` 1–10. `total_price` dihitung di server. Kuota dicek dalam transaksi dengan `SELECT … FOR UPDATE`.
- **Kiriman fans:** status awal `pending`, baru tampil di halaman setelah di-approve.
- **Newsletter:** `INSERT … ON CONFLICT (email) DO NOTHING`.
- Ketiga form memakai honeypot anti-bot.

## 4. Halaman publik dinamis

| Halaman | Dinamis | Sumber |
|---|---|---|
| index.php | Konser Mendatang, Berita Terbaru, Album Pilihan | events, posts, albums |
| cerita-band.php | Tonggak Sejarah, Anggota, Penghargaan | milestones, members |
| diskografi.php | Album + lagu | albums, tracks |
| tur-event.php | Konser mendatang, arsip, setlist, select form tiket | events, setlist_items, tracks |
| berita-media.php | Berita, Di Balik Layar, Galeri, Materi Pers, hasil `?q=` | posts, media (+ search albums, events) |
| sahabat-noah.php | Cerita/Karya Sahabat, Komunitas, Event Komunitas | fan_submissions (approved), communities, events |
| toko.php | Produk, Edisi Terbatas, hitungan kategori, filter `?kategori=&max=&urut=` | products |

- Parameter `urut` divalidasi lewat whitelist (`terbaru`, `termurah`, `termahal`).
- Header, footer, dan search dipindah ke `partials/`. Link `.html` diganti `.php`.
- `seed.sql` berisi konten contoh dari HTML. Kartu dan data situs dibaca dari database, sehingga pembaruan admin langsung tercermin di halaman publik.

## 5. Pengujian

- **Manual:** 7 halaman dibandingkan dengan versi statis. CRUD setiap resource, termasuk upload dan hapus data yang masih dipakai tabel lain.
- **PHPUnit** (DB `noah_test`): validasi field, `slugify()`, perhitungan total tiket, cek kuota.
- **Keamanan:** POST tanpa CSRF ditolak, payload SQLi di search tidak berefek, upload `.php` ditolak.

## 6. Urutan implementasi

1. `app/`, config, jalankan `schema.sql` + `seed.sql`
2. Login admin + CRUD generik (mulai dari `products`)
3. Resource lain + halaman custom
4. Form publik (`actions/`)
5. Konversi 7 halaman publik

## Di luar cakupan

Keranjang/checkout toko, akun fans, rich text editor, dan pembayaran tiket online.

## Catatan implementasi

- Struktur MVC sederhana: `app/queries.php` dan `app/forms.php` sebagai model/query, `app/public-controller.php` serta `admin/core/controller.php` sebagai controller, dan `app/views/` serta `admin/views/` sebagai view. Template HTML band dipindah ke `app/views/band/` dan konten dinamis diganti saat render.
- Filter toko, diskografi, dan galeri tetap menggunakan `app.js` dan atribut `data-*` yang sudah ada. Form publik memakai POST, token CSRF, validasi, throttle, dan pola redirect setelah submit.
- Jadwal konser contoh pada seed memakai tahun 2027 karena tanggal 2026 pada HTML lama sudah lewat. Jadwal ini konten demonstrasi website fan, bukan pengumuman konser resmi.
- Jalankan server pengembangan melalui `php -S localhost:8000 router.php` untuk menutup akses HTTP ke berkas internal; halaman PHP dan panel admin memerlukan hosting PHP/PostgreSQL dan tidak dapat berjalan di GitHub Pages.
- `database/create-admin.php` membuat super admin melalui CLI. Kredensial pribadi tidak disimpan dalam repositori.
- Form Sahabat: `community_id` opsional dan harus merujuk komunitas yang ada; `artwork` wajib berkas JPG/PNG/WEBP (maks. 2 MB), `cover` wajib MP3 (maks. 8 MB), sedangkan `story` boleh berupa teks saja. Unggahan disimpan di `uploads/fans/`, menunggu persetujuan admin; saat admin menghapus kiriman, berkas unggahan lokal turut dibersihkan.
