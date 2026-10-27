# Rancangan Awal (Draft) Basis Data — NOAH Official Website

- **DBMS:** PostgreSQL 16 (Docker)
- **Backend rencana:** PHP native (PDO `pgsql`) + panel admin CRUD
- **ERD revisi (FigJam):** https://www.figma.com/board/rjNSexSYa67PhBXSV8zeJ4?node-id=10-1966
- **DDL:** [`database/schema.sql`](../../database/schema.sql)

## Prinsip "compact"

14 tabel, tanpa tabel lookup terpisah. Pilihan tetap (kategori, status, tipe) memakai `ENUM` PostgreSQL. Beberapa section halaman yang mirip digabung ke satu tabel dengan kolom `type`:

| Digabung | Tabel | Pembeda |
|---|---|---|
| Tonggak Sejarah + Penghargaan | `milestones` | `type`: history / award |
| Konser resmi + Event Komunitas | `events` | `type`: concert / community; `community_id` opsional |
| Galeri Foto + Materi Pers | `media` | `type`: photo / video / press_kit |
| Berita Terbaru + Di Balik Layar | `posts` | `category`: news / behind_scenes |
| Cerita Sahabat + Karya Sahabat | `fan_submissions` | `type` + `status` approved |

## Pemetaan halaman → tabel

| Halaman | Tabel | Diisi oleh |
|---|---|---|
| `index.html` | events, posts, albums, subscribers | admin / publik (newsletter) |
| `cerita-band.html` | members, milestones | admin |
| `diskografi.html` | albums, tracks | admin |
| `tur-event.html` | events, setlist_items, ticket_orders | admin / publik (form tiket) |
| `berita-media.html` | posts, media | admin |
| `sahabat-noah.html` | communities, events, fan_submissions | admin / publik (form kirim karya) |
| `toko.html` | products | admin |

## Menu panel admin (CRUD)

| Menu | Create | Read | Update | Delete |
|---|---|---|---|---|
| Anggota, Sejarah, Album + Lagu, Event + Setlist, Berita, Media, Produk, Komunitas | ✓ | ✓ | ✓ | ✓ |
| Pesanan Tiket | — (dari publik) | ✓ | status saja | batalkan |
| Kiriman Penggemar | — (dari publik) | ✓ | approve / reject | ✓ |
| Subscriber | — (dari publik) | ✓ | nonaktifkan | ✓ |
| Admin | super_admin saja | ✓ | ✓ | ✓ |

## Relasi

Setiap induk dapat memiliki **0 sampai banyak** baris anak. Kardinalitas di sisi induk adalah **0..1** jika FK anak boleh NULL, dan **1** jika FK wajib diisi.

| Induk → anak | Foreign key di anak | Induk per baris anak | Saat induk dihapus |
|---|---|---|---|
| albums → tracks | `album_id` | 0..1 | SET NULL |
| communities → events | `community_id` | 0..1 | SET NULL |
| events → setlist_items | `event_id` | 1 | CASCADE |
| tracks → setlist_items | `track_id` | 1 | RESTRICT |
| events → ticket_orders | `event_id` | 1 | RESTRICT |
| admins → posts | `admin_id` | 0..1 | SET NULL |
| posts → media | `post_id` | 0..1 | SET NULL |
| communities → fan_submissions | `community_id` | 0..1 | SET NULL |
| admins → fan_submissions | `reviewed_by` | 0..1 | SET NULL |

- `events N—N tracks` dipecah lewat `setlist_items`, dengan PK gabungan (`event_id`, `position`). Satu posisi pada event hanya memuat satu lagu; lagu yang sama dapat muncul lagi pada posisi berbeda.
- `members`, `milestones`, `products`, dan `subscribers` berdiri sendiri sesuai kebutuhan halaman saat ini.
- `events.type` menentukan konser resmi atau event komunitas. `community_id` NULL juga dapat terjadi setelah komunitas dihapus, sehingga NULL saja tidak menentukan jenis event.

## Revisi 2026-10-27

Struktur tetap **14 tabel**. Revisi menyelaraskan penyimpanan dengan section pada tujuh halaman HTML dan filter `data-*` di `app.js`; halaman publik akan dikonversi ke `.php` pada tahap implementasi berikutnya.

### Kolom tambahan dan kegunaannya

| Tabel / kolom | Halaman / section | Alasan |
|---|---|---|
| `milestones.badge` | cerita-band — Penghargaan | Menyimpan label statistik seperti `3x`, terpisah dari tahun tonggak sejarah. |
| `albums.era` | diskografi — filter era | Membedakan rilis era Peterpan dan NOAH. |
| `albums.total_tracks` | diskografi — kartu album | Jumlah lagu resmi pada rilis, meskipun belum semua lagu dimasukkan ke tabel `tracks`; bukan hitungan baris `tracks`. |
| `albums.is_featured` | index — Album Pilihan | Menentukan album yang ditampilkan di beranda. |
| `tracks.single_year` | diskografi — Single | Menyimpan tahun rilis single, termasuk lagu yang belum dikaitkan dengan album. |
| `communities.leader_name` | sahabat-noah — Komunitas | Menyimpan nama koordinator komunitas. |
| `events.description` | tur-event / sahabat-noah — detail event | Menyimpan deskripsi konser atau kegiatan komunitas. |
| `events.badge` | tur-event — kartu konser | Menyimpan label acara pada kartu. |
| `setlist_items.is_encore` | tur-event — Arsip Setlist | Memisahkan lagu encore dari set utama. |
| `ticket_orders.notes` | tur-event — Form Pemesanan Tiket | Menyimpan catatan tambahan pemesan. |
| `posts.tag` | index / berita-media — kartu berita | Menyimpan label tampilan, terpisah dari kategori `news` atau `behind_scenes`. |
| `posts.is_featured` | berita-media — berita utama | Menentukan berita yang ditonjolkan. |
| `media.tag` | berita-media — Galeri | Menyimpan filter `peterpan`, `noah`, `konser`, atau `studio`. |
| `media.caption` | berita-media — Galeri / Materi Pers | Menyimpan keterangan foto atau informasi berkas pers. |
| `products.badge` | toko — kartu produk | Menyimpan label produk, misalnya produk baru atau edisi terbatas. |

### ENUM yang diselaraskan

- `album_type`: `studio`, `kompilasi`, `soundtrack` (menggantikan `album`, `single`, `compilation`). Single ditampilkan dari `tracks.single_year`.
- `album_era` baru: `peterpan`, `noah`.
- `product_category`: `kaos`, `hoodie`, `aksesoris`, `musik` (menggantikan `cd_vinyl` dengan `musik`).
- Total ada **12 tipe ENUM**; nilai kategori cocok dengan filter halaman yang ada.

### NULL, kunci, dan batasan data

- `milestones.year` boleh NULL untuk statistik penghargaan yang tidak mewakili satu tahun. Tahun yang terisi harus 1990–2100.
- `tracks.album_id` dan `tracks.track_no` boleh NULL untuk lagu lepas / setlist. FK album memakai `ON DELETE SET NULL`, sehingga penghapusan album tidak menghilangkan lagu atau merusak arsip setlist.
- `posts.admin_id` boleh NULL agar seed berita dapat dibuat sebelum admin tersedia; penghapusan admin mempertahankan berita melalui `ON DELETE SET NULL`.
- Semua tabel memiliki PK; 13 tabel memakai identity `id`, sedangkan `setlist_items` memakai PK gabungan.
- Email admin dan subscriber, slug album/event/post/produk, serta kota komunitas bersifat unik. Kombinasi (`album_id`, `track_no`) juga unik untuk nomor lagu dalam album; PostgreSQL mengizinkan lebih dari satu baris jika salah satu nilainya NULL.
- `CHECK` membatasi jumlah tiket 1–10, harga/stok/kuota/jumlah anggota tidak negatif, dan nomor lagu/posisi/durasi/jumlah lagu album yang terisi harus positif.
- Harga memakai `NUMERIC(12,2)`; waktu event/publikasi/pembuatan data memakai `TIMESTAMPTZ`. `streaming_links` memakai JSONB untuk tautan platform streaming.
- Indeks tambahan: waktu event, event pada pesanan tiket, status + waktu publikasi berita, dan status moderasi kiriman.

### Batas validasi rancangan awal

Skema menjaga integritas kunci, relasi, tipe, dan rentang nilai. Aturan lintas baris seperti kuota tiket, perhitungan total saat pemesanan, konsistensi data sesuai jenis konten, serta hak akses admin diterapkan oleh kode PHP pada tahap berikutnya. `schema.sql` adalah DDL inisialisasi untuk database kosong, bukan migrasi otomatis untuk database yang sudah berisi data.

### Hasil verifikasi revisi

- DDL berhasil dijalankan pada **PostgreSQL 16.13**, database dev `noah`, dalam satu transaksi dengan `ON_ERROR_STOP=1`.
- Katalog database mengonfirmasi **14 tabel, 118 kolom, 12 ENUM, 14 primary key, 9 foreign key, 8 constraint UNIQUE, dan 13 constraint CHECK**. Selain indeks PK/UNIQUE otomatis, ada 4 indeks tambahan.
- Uji SQL memverifikasi 22 penolakan data/operasi yang tidak valid dan 5 skenario penghapusan induk (`SET NULL`, `RESTRICT`, `CASCADE`). Data uji dibungkus transaksi dan diakhiri `ROLLBACK`.
- ERD revisi sudah menggantikan diagram draft lama. Seluruh 118 kolom dan 9 relasi beserta kardinalitasnya dicocokkan dengan DDL; diagram juga diperiksa secara visual.
- Berkas untuk rancangan awal: dokumen ini, `database/schema.sql`, dan ERD revisi pada tautan di atas. Detail `NOT NULL`, nilai default, dan ekspresi `CHECK` lengkap mengacu ke DDL.

## Catatan keamanan untuk tahap implementasi

- Password admin disimpan dengan `password_hash()`; login pakai `password_verify()`.
- Semua query memakai prepared statement PDO.
- Form CRUD admin pakai token CSRF; output di-escape dengan `htmlspecialchars()`.

## Sengaja ditunda (YAGNI)

- Checkout/keranjang toko (`orders`, `order_items`) — halaman toko belum punya alur beli.
- Akun member penggemar — form publik cukup nama + email.
- Tag/kategori dinamis untuk berita.
