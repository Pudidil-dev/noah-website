# 🎸 Panduan Presentasi Project — Website Resmi NOAH

> **Mata Kuliah:** Pemrograman Web · **Topik:** Wireframe → Realisasi Halaman Web dengan **CSS Flexbox**
> **Tema desain:** *Sonic Minimalism* (hasil Stitch) · **Warna utama:** Merah `#b7102a`
> **File ini siap di-upload / paste ke Notion.** Setiap potongan kode menyebutkan **file + nomor baris** supaya mudah ditunjuk saat presentasi.

---

## 📑 Daftar Isi

1. [Ringkasan Project (1 menit pembuka)](#1-ringkasan-project)
2. [Alur Kerja: Wireframe Stitch → Halaman Jadi](#2-alur-kerja-wireframe--realisasi)
3. [Desain Stitch yang Diambil](#3-desain-stitch-yang-diambil)
4. [Struktur Codebase (peta file)](#4-struktur-codebase)
5. [Fondasi Layout: Container, Section, Reset](#5-fondasi-layout)
6. [⭐ Inti Presentasi — Flexbox per Pola Fungsi](#6-inti-flexbox-per-pola-fungsi)
7. [Flexbox vs Grid: kapan pakai yang mana](#7-flexbox-vs-grid)
8. [Responsif: Flexbox saat layar HP](#8-responsif)
9. [Cheat-sheet properti Flexbox](#9-cheat-sheet-flexbox)
10. [Skrip & Antisipasi Pertanyaan Dosen](#10-skrip-presentasi--qa)

---

## 1. Ringkasan Project

Website **resmi band NOAH** terdiri dari **7 halaman** statis (HTML + CSS murni, tanpa framework) yang berbagi **satu file CSS** (`style.css`).

| Halaman | File | Isi utama |
|---|---|---|
| Beranda | `index.html` | Hero, music player, konser, profil, berita, newsletter |
| Cerita Band | `cerita-band.html` | Timeline perjalanan, profil anggota, penghargaan |
| Diskografi | `diskografi.html` | Filter album, grid album, tabel singles |
| Tur & Event | `tur-event.html` | Kartu konser, setlist, form booking |
| Berita & Media | `berita-media.html` | Hero berita, grid artikel, galeri, press kit |
| Sahabat NOAH | `sahabat-noah.html` | Statistik, cerita fan, chapter, event komunitas |
| Toko | `toko.html` | Banner, sidebar filter + grid produk, edisi terbatas |

**Kalimat pembuka yang bisa diucapkan:**
> "Kami membuat website resmi NOAH. Desainnya kami rancang dulu sebagai wireframe lewat **Google Stitch**, lalu kami realisasikan menjadi halaman web nyata menggunakan **HTML & CSS murni**, dengan **Flexbox** sebagai tulang punggung penataan komponennya."

---

## 2. Alur Kerja: Wireframe → Realisasi

```
┌────────────────┐     ┌─────────────────────┐     ┌──────────────────────┐
│  1. STITCH      │ ──▶ │  2. DESIGN SYSTEM    │ ──▶ │  3. REALISASI         │
│  Wireframe +    │     │  "Sonic Minimalism"  │     │  HTML + style.css     │
│  screen.png     │     │  (DESIGN.md: warna,  │     │  (Flexbox & Grid      │
│  per halaman    │     │  font, spacing)      │     │  buatan sendiri)      │
└────────────────┘     └─────────────────────┘     └──────────────────────┘
```

- **Stitch** menghasilkan layout berbasis **Tailwind CSS** (utility class seperti `flex justify-between items-center`).
- Saat realisasi, utility itu **diterjemahkan menjadi CSS Flexbox murni** di `style.css` (mis. `display:flex; justify-content:space-between; align-items:center;`).
- Jadi konsep tata letaknya sama, hanya **cara penulisannya** yang berubah dari utility-class → CSS biasa.

**Bukti pemetaan (1:1) — sangat bagus untuk ditunjukkan saat presentasi:**

| Stitch (Tailwind) — `noah_official_homepage/code.html` | Realisasi (CSS murni) — `style.css` |
|---|---|
| `flex justify-between items-center` (navbar, baris 124) | `.navbar-isi` → `display:flex; justify-content:space-between; align-items:center;` (baris 84–89) |
| `flex flex-col md:flex-row items-center gap-6` (player, baris 159) | `.player` → `display:flex; gap:20px; align-items:center;` (baris 199–208) |
| `flex-grow` (info lagu, baris 163) | `.player-info` → `flex-grow:1;` (baris 227–229) |
| `flex justify-center` (wrap player, baris 158) | `.player-wrap` → `display:flex; justify-content:center;` (baris 190–197) |

---

## 3. Desain Stitch yang Diambil

Desain yang dipilih adalah set wireframe **`stitch_noah_band_official_website_wireframe`** dengan design system bernama **"Sonic Minimalism"** (lihat `sonic_minimalism/DESIGN.md`).

**Karakter desain (sebutkan singkat saat presentasi):**
- **Gaya:** Minimalis + high-contrast bold — banyak ruang putih, satu warna aksen.
- **Warna kunci** (diambil persis ke `style.css`):
  - Primary / merah: `#b7102a`
  - Background krem: `#fff8f7`
  - Teks gelap: `#271717`
  - Abu pembeda section: `#f1f1f1`
- **Tipografi:** keluarga **Inter** (di realisasi kami pakai fallback `Arial, Helvetica, sans-serif`).
- **Bentuk:** sudut lembut `~4px`, pill membulat penuh `999px` untuk label/tag.
- **Ciri khas:** garis **border merah 4px di sisi kiri** kartu (`.card-border-merah`) + bayangan halus.

> Tiap halaman punya wireframe-nya sendiri: `wireframe_noah_homepage`, `wireframe_diskografi`, `wireframe_tur_event`, `wireframe_cerita_band`, `wireframe_berita_media`, `wireframe_sahabat_noah`, `wireframe_toko` — masing-masing ada `screen.png` (gambar) dan `code.html` (versi Tailwind).

---

## 4. Struktur Codebase

```
week 11/
├── index.html              ← Beranda
├── cerita-band.html
├── diskografi.html
├── tur-event.html
├── berita-media.html
├── sahabat-noah.html
├── toko.html
├── style.css               ← SATU file CSS untuk semua halaman (di sinilah semua Flexbox)
├── images/                 ← Semua gambar (hero, album, produk, dst.)
└── stitch_..._wireframe/    ← Hasil Stitch: wireframe + DESIGN.md (referensi desain)
```

**Poin penting:** Seluruh aturan **Flexbox berada di `style.css`**. File HTML hanya memasang **nama class** (mis. `class="navbar-isi"`), lalu CSS yang mengatur tata letaknya. Inilah konsep **pemisahan struktur (HTML) dan tampilan (CSS)**.

---

## 5. Fondasi Layout

Sebelum Flexbox, ada 3 aturan dasar yang menopang semua halaman (`style.css`):

```css
/* style.css : 7–11 — Reset: hapus margin/padding bawaan, box-sizing mudah dihitung */
* { margin: 0; padding: 0; box-sizing: border-box; }

/* style.css : 21–25 — Container: batasi lebar maks 1200px & ketengahkan */
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

/* style.css : 28–30 — Section: jarak vertikal antar bagian */
.section { padding: 80px 0; }
```

> `margin: 0 auto` adalah cara klasik menengahkan blok. Flexbox nanti dipakai untuk menata **isi di dalam** container ini.

---

## 6. ⭐ Inti: Flexbox per Pola Fungsi

Flexbox di project ini muncul **puluhan kali**, tapi sebenarnya hanya **6 pola fungsi** yang diulang-ulang. Kuasai 6 pola ini = kuasai seluruh layout. Tiap pola di bawah punya: **fungsi → kode → di mana dipakai**.

> 💡 **Tips presentasi:** jelaskan "segitiga Flexbox" dulu — begitu sebuah elemen diberi `display:flex`, ia menjadi **container**, dan anak-anak langsungnya menjadi **item**. `justify-content` mengatur sumbu **horizontal** (main axis), `align-items` mengatur sumbu **vertikal** (cross axis).

---

### Pola A — Dorong ke Kiri & Kanan (`justify-content: space-between`)

**Fungsi:** menempatkan dua kelompok elemen di ujung berlawanan dalam satu baris (kiri ↔ kanan), sejajar di tengah secara vertikal. Ini pola **paling sering** dipakai.

```css
/* style.css : 84–89 — NAVBAR: logo di kiri, menu di tengah/kanan */
.navbar-isi {
  display: flex;
  align-items: center;            /* sejajarkan vertikal di tengah */
  justify-content: space-between; /* logo mentok kiri, sisanya mentok kanan */
  height: 70px;
}
```

**Dipakai juga oleh (fungsi serupa):**

| Class | Baris | Untuk apa |
|---|---|---|
| `.section-header` | 285–290 | Judul section (kiri) ↔ link "Lihat Semua" (kanan) |
| `.card-atas` | 337–341 | Tanggal konser (kiri) ↔ label "Tersedia/Habis" (kanan) |
| `.player-kontrol` | 255–259 | Tombol putar (kiri) ↔ tombol volume/dll (kanan) |
| `.footer-bawah` | 562–570 | Teks copyright ↔ ikon sosial |
| `.album-head` | 846–850 | Judul album ↔ tahun rilis |
| `.le-head` | 1334–1338 | Label "LIMITED" ↔ sisa stok |
| `.setlist-head` | 961–967 | Nama kota ↔ tanda buka/tutup |
| `.kategori-list li` | 1246–1252 | Nama kategori ↔ jumlah produk (toko) |
| `.presskit-baris` | 1124–1130 | Nama berkas ↔ tombol unduh |

---

### Pola B — Pusatkan di Tengah (centering, `justify-content + align-items: center`)

**Fungsi:** menaruh konten tepat di **tengah** sebuah kotak (horizontal **dan** vertikal). Dipakai untuk placeholder gambar, ikon dalam lingkaran, dan avatar.

```css
/* style.css : 271–281 — Tombol PLAY: ikon ▶ di tengah lingkaran merah */
.play {
  width: 38px; height: 38px;
  border-radius: 50%;        /* jadikan lingkaran */
  display: flex;
  align-items: center;       /* tengah vertikal */
  justify-content: center;   /* tengah horizontal */
}
```

**Pola centering yang sama dipakai di:**

| Class | Baris | Untuk apa |
|---|---|---|
| `.player-cover` | 215–225 | Kotak cover album |
| `.band-foto` | 394–402 | Placeholder foto band |
| `.berita-gambar` | 411–419 | Placeholder gambar berita |
| `.galeri-item` | 513–520 | Sel galeri Instagram |
| `.album-cover` | 836–844 | Cover album diskografi |
| `.avatar` / `.avatar-kecil` | 727–751 | Foto anggota / fan (bulat) |
| `.banner-gambar` | 1199–1207 | Gambar banner toko |
| `.event-tanggal` | 1163–1172 | Tanggal event (lihat Pola F — pakai arah kolom) |

---

### Pola C — Deret Item Berjarak Rapi (`gap`)

**Fungsi:** menyusun beberapa item dalam satu baris dengan **jarak antar item** yang konsisten via `gap` (tanpa perlu `margin` manual).

```css
/* style.css : 110–116 — Bagian kanan navbar: ikon cari + teks bahasa */
.navbar-kanan {
  display: flex;
  align-items: center;
  gap: 15px;                 /* jarak antar elemen otomatis */
}
```

**Dipakai juga:**

| Class | Baris | Untuk apa |
|---|---|---|
| `.sosial` | 476–480 | Deretan kotak ikon media sosial (`gap:12px`) |
| `.tombol-kiri` / `.tombol-kanan` | 261–268 | Grup tombol di music player (`gap:15px`) |
| `.cerita-head` | 1141–1146 | Avatar fan + nama berdampingan |
| `.hero-meta` | 1066–1071 | Pill kategori + tanggal di hero berita |

---

### Pola D — Isi Sisa Ruang vs Lebar Tetap (`flex-grow` / `flex-shrink`)

**Fungsi:** dalam satu baris, satu item **lebarnya tetap** (`flex-shrink:0` atau `width` pasti) dan item lain **memanjang mengisi sisa ruang** (`flex-grow:1`). Ini kunci layout "sidebar + konten" dan "gambar kecil + teks panjang".

```css
/* style.css : 199–229 — MUSIC PLAYER */
.player        { display: flex; gap: 20px; align-items: center; }
.player-cover  { width: 90px; height: 90px; flex-shrink: 0; }  /* cover TETAP 90px */
.player-info   { flex-grow: 1; }                               /* info MELAR isi sisa */
```

```css
/* style.css : 1228–1284 — LAYOUT TOKO: sidebar tetap + grid produk melar */
.toko-layout { display: flex; gap: 40px; }
.sidebar     { width: 240px; flex-shrink: 0; }  /* filter selebar 240px */
.produk-grid { flex-grow: 1; }                  /* sisa ruang untuk produk */
```

```css
/* style.css : 494–504 — FORM NEWSLETTER: input melar, tombol seukuran isinya */
.newsletter        { display: flex; }
.newsletter input  { flex-grow: 1; }  /* kolom email memanjang, tombol "Berlangganan" tetap */
```

> 🔑 **`flex-shrink: 0`** mencegah elemen (cover, sidebar, ikon) **menyusut/gepeng** saat ruang sempit. Lihat juga `.icon` (baris 41–47) dan `.avatar-kecil` (740–751).

---

### Pola E — Otomatis Turun Baris (`flex-wrap: wrap`)

**Fungsi:** kalau item tak muat dalam satu baris, otomatis **pindah ke baris berikutnya** — tanpa media query. Bagus untuk tombol, statistik, dan tag.

```css
/* style.css : 143–148 — Tombol hero: turun baris di layar sempit */
.hero-tombol {
  display: flex;
  gap: 15px;
  justify-content: center;
  flex-wrap: wrap;           /* tombol membungkus saat sempit */
}
```

**Dipakai juga:**

| Class | Baris | Untuk apa |
|---|---|---|
| `.stats` | 770–775 | Angka statistik komunitas (Anggota/Chapter/Event) |
| `.filter-grup` | 804–810 | Tombol filter diskografi/galeri |
| `.tag-list` | 755–759 | Daftar tag genre |
| `.banner-form` | 900–905 | Input + tombol di banner hiatus |

---

### Pola F — Susun ke Bawah (`flex-direction: column`)

**Fungsi:** mengubah arah Flexbox dari **mendatar** (default `row`) menjadi **menurun** (`column`). Dipakai untuk menumpuk konten vertikal yang tetap rata tengah.

```css
/* style.css : 1163–1172 — Kotak tanggal event: angka di atas, bulan di bawah, rata tengah */
.event-tanggal {
  display: flex;
  flex-direction: column;    /* susun ke bawah */
  align-items: center;
  justify-content: center;
  text-align: center;
}
```

> Pola ini juga muncul di **bagian responsif** (Bab 8) untuk mengubah baris menjadi kolom di layar HP.

---

## 7. Flexbox vs Grid

Project ini memakai **keduanya** secara sadar — ini poin nilai tambah saat presentasi.

| | **Flexbox** (`display:flex`) | **Grid** (`display:grid`) |
|---|---|---|
| Arah | Satu dimensi (baris **atau** kolom) | Dua dimensi (baris **dan** kolom) |
| Cocok untuk | Komponen: navbar, tombol, kartu kecil, sidebar+konten | Layout kartu beraturan: galeri, daftar produk |
| Contoh di project | `.navbar-isi`, `.player`, `.toko-layout` | `.grid-3`, `.grid-2`, `.grid-4`, `.galeri-foto` |

```css
/* style.css : 310–327 — GRID dipakai untuk kisi kartu yang seragam */
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
.grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; }
```

> **Cara menjelaskan:** "Untuk **deret kartu yang rapi sebaris** (3 konser, 4 kolom footer) kami pakai **Grid**. Untuk **penataan komponen di dalamnya** (navbar, isi kartu, player) kami pakai **Flexbox**. Bahkan keduanya digabung: `.toko-layout` (flex) berisi `.produk-grid` (grid)."

---

## 8. Responsif

Saat layar ≤ 768px (HP), Flexbox membantu menata ulang dengan **mengubah arah** dari baris → kolom (`style.css` baris 582–606 & 1353–1381):

```css
/* style.css : 582–606 */
@media (max-width: 768px) {
  .menu { display: none; }                 /* sembunyikan menu panjang */

  .grid-3, .grid-2, .grid-4 {
    grid-template-columns: 1fr;             /* grid → 1 kolom */
  }

  .player { flex-direction: column; }       /* cover di atas, info di bawah */
  .footer-bawah { flex-direction: column; } /* copyright & ikon menumpuk */
}
```

```css
/* style.css : 1366–1376 — toko & event ikut menumpuk di HP */
.toko-layout { flex-direction: column; }   /* sidebar pindah ke atas produk */
.event-card  { flex-direction: column; }   /* tanggal di atas, isi di bawah */
```

> **Inti yang ditekankan:** cukup **satu properti** `flex-direction: column` untuk mengubah layout berdampingan menjadi bertumpuk di HP — tanpa menulis ulang HTML.

---

## 9. Cheat-sheet Flexbox

Hafalkan tabel ini untuk menjawab pertanyaan cepat:

| Properti | Dipasang di | Fungsi singkat |
|---|---|---|
| `display: flex` | container | Mengaktifkan Flexbox |
| `flex-direction: row \| column` | container | Arah: mendatar (default) / menurun |
| `justify-content` | container | Posisi di **sumbu utama** (`center`, `space-between`, dll) |
| `align-items` | container | Posisi di **sumbu silang** (`center`, `flex-end`, dll) |
| `gap` | container | Jarak antar item |
| `flex-wrap: wrap` | container | Izinkan item turun baris |
| `flex-grow: 1` | item | Item ikut **memanjang** mengisi sisa ruang |
| `flex-shrink: 0` | item | Item **tidak menyusut** (jaga ukuran tetap) |

**Nilai `justify-content` yang dipakai di project:** `center` (hero, player-wrap, pagination), `space-between` (navbar, card-atas, footer), `flex-start` (default).

---

## 10. Skrip Presentasi & Q&A

### 🎤 Alur bicara (saran 5–7 menit)
1. **Pembuka** — perkenalan project & tema *Sonic Minimalism* (Bab 1 & 3).
2. **Tunjukkan wireframe** — buka `screen.png` di folder Stitch, lalu buka halaman jadinya. "Ini desain awal, ini hasil akhirnya." (Bab 2)
3. **Buka `style.css`** — tunjukkan `.navbar-isi` (Pola A), `.player` (Pola D), `.play` (Pola B). Jelaskan container vs item.
4. **Demo responsif** — kecilkan jendela browser sampai layout menumpuk (Bab 8).
5. **Penutup** — Flexbox vs Grid (Bab 7).

### ❓ Antisipasi pertanyaan dosen

> **"Kenapa pakai Flexbox, bukan `float`?"**
> Flexbox lebih modern, otomatis menyejajarkan vertikal (`align-items`), memberi jarak rapi (`gap`), dan mudah dibuat responsif (`flex-direction`). `float` perlu trik `clearfix` dan rawan berantakan.

> **"Apa beda `justify-content` dan `align-items`?"**
> `justify-content` = sumbu utama (horizontal saat `row`). `align-items` = sumbu silang (vertikal saat `row`). Saat `flex-direction: column`, keduanya bertukar arah.

> **"Apa fungsi `flex-grow` dan `flex-shrink`?"**
> `flex-grow:1` membuat item memanjang mengisi sisa (mis. info lagu / grid produk). `flex-shrink:0` mencegah item menyusut (mis. cover album / sidebar) supaya tidak gepeng.

> **"Kenapa sebagian pakai Grid?"**
> Untuk kisi 2 dimensi yang seragam (galeri, kartu). Flexbox untuk penataan komponen 1 dimensi. Lihat `.toko-layout` (flex) yang membungkus `.produk-grid` (grid).

> **"Di mana semua kode CSS-nya?"**
> Satu file: `style.css`. Semua halaman HTML berbagi file ini lewat `<link rel="stylesheet" href="style.css">`.

---

### ✅ Checklist sebelum maju presentasi
- [ ] Buka `style.css` di editor, siapkan tab ke baris 84 (navbar) & 199 (player)
- [ ] Buka 1 `screen.png` wireframe + halaman jadinya untuk perbandingan
- [ ] Siapkan browser untuk demo resize (responsif)
- [ ] Hafal 6 pola Flexbox (Bab 6) + cheat-sheet (Bab 9)
- [ ] Tahu jawaban 5 pertanyaan di atas

> **Satu kalimat penutup:** *"Dari wireframe Stitch bertema Sonic Minimalism, kami realisasikan menjadi 7 halaman web dengan CSS murni, di mana Flexbox menata setiap komponen lewat 6 pola fungsi yang konsisten dan responsif."*
