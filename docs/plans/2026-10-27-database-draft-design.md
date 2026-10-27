# Rancangan Awal (Draft) Basis Data — NOAH Official Website

- **DBMS:** PostgreSQL 14+
- **Backend rencana:** PHP native (PDO `pgsql`) + panel admin CRUD
- **ERD (FigJam):** https://www.figma.com/board/rjNSexSYa67PhBXSV8zeJ4
- **DDL:** [`database/schema.sql`](../../database/schema.sql)

## Prinsip "compact"

14 tabel, tanpa tabel lookup terpisah. Pilihan tetap (kategori, status, tipe) memakai `ENUM` PostgreSQL. Beberapa section halaman yang mirip digabung ke satu tabel dengan kolom `type`:

| Digabung | Tabel | Pembeda |
|---|---|---|
| Tonggak Sejarah + Penghargaan | `milestones` | `type`: history / award |
| Konser resmi + Event Komunitas | `events` | `type` + `community_id` (NULL = resmi) |
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

- `albums 1—N tracks`
- `events N—N tracks` lewat `setlist_items` (PK gabungan `event_id, position`)
- `events 1—N ticket_orders`
- `communities 1—N events`, `communities 1—N fan_submissions` (opsional)
- `admins 1—N posts`, `admins 1—N fan_submissions` (reviewer)
- `posts 1—N media` (opsional)

## Catatan keamanan untuk tahap implementasi

- Password admin disimpan dengan `password_hash()`; login pakai `password_verify()`.
- Semua query memakai prepared statement PDO.
- Form CRUD admin pakai token CSRF; output di-escape dengan `htmlspecialchars()`.

## Sengaja ditunda (YAGNI)

- Checkout/keranjang toko (`orders`, `order_items`) — halaman toko belum punya alur beli.
- Akun member penggemar — form publik cukup nama + email.
- Tag/kategori dinamis untuk berita.
