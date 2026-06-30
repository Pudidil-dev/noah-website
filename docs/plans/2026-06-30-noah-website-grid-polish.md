# NOAH Website — Grid, Link, Embed & Polish Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Merapikan website fan NOAH (7 halaman HTML statis) — label header diringkas, semua CTA berfungsi sebagai link, 4 halaman dikonversi ke layout CSS Grid penuh, tambah embed musik + maps, mega footer diseragamkan, dropdown setlist diberi efek native, seluruh teks Inggris diterjemahkan ke Indonesia, dan efek hover/transisi dipoles.

**Architecture:** Situs statis murni **HTML + CSS native, TANPA JavaScript**. Satu file `style.css` dipakai 7 halaman (`index`, `cerita-band`, `diskografi`, `tur-event`, `berita-media`, `sahabat-noah`, `toko`). Navbar & footer di-copy identik di tiap halaman (tidak ada include karena tanpa JS/build). Interaktivitas dropdown memakai elemen native `<details>/<summary>`. Embed musik & maps memakai `<iframe>` (bukan JS). Semua efek memakai CSS `transition`/`@keyframes`.

**Tech Stack:** HTML5, CSS3 (Grid + Flexbox + transitions), elemen native `<details>`, `<iframe>` embed (Spotify + Google Maps). Tidak ada framework, tidak ada build tool, tidak ada JS.

---

## Keputusan Desain & Asumsi (baca sebelum mulai)

Asumsi default berikut dipakai agar tidak over-engineering. Koreksi sebelum eksekusi bila tidak setuju.

1. **Nama file TIDAK diganti.** Hanya **label** di header/navbar yang diringkas: `Tur & Event` → **Event**, `Berita & Media` → **Media**. File tetap `tur-event.html` & `berita-media.html` supaya semua link lama tidak putus. (Permintaan berbunyi "perbaikan header", bukan "rename file".)
2. **"Full GRID"** = container layout utama di 4 halaman target (`tur-event`, `berita-media`, `sahabat-noah`, `toko`) memakai `display:grid`. Layout yang sekarang `display:flex` (`.toko-layout`, `.event-card`, `.stats`, `.cerita-head`, `.presskit-baris`, `.le-head`) dikonversi ke Grid. Grid kartu yang sudah ada (`.grid-3`, `.galeri-foto`, `.produk-grid`) dipertahankan/dirapikan. Navbar tetap flex (bukan halaman target & flex lebih tepat untuk navbar).
3. **Embed musik** = Spotify embed (1 lagu NOAH) di `index.html`. **Embed maps** = Google Maps embed (lokasi venue/manajemen) di `tur-event.html`. Keduanya `<iframe>`, gratis, tanpa API key.
4. **CTA tanpa tujuan backend nyata** (Beli Tiket, Tambah ke Keranjang, Download) diarahkan ke halaman/anchor yang relevan atau `#`-anchor di halaman yang sama — yang penting jadi `<a>` yang bisa diklik, bukan `<button>` mati. Link streaming (Spotify/YouTube) diarahkan ke URL eksternal dengan `target="_blank" rel="noopener"`.
5. **Git dipakai sebagai checkpoint** (folder ini belum repo). Task 1 melakukan `git init` agar tiap task bisa di-commit & mudah di-rollback. Kalau user menolak git, lewati semua step `git commit` — sisanya tetap jalan.

---

## Cara Verifikasi (karena tidak ada test framework)

Tiap task memakai 2 jenis verifikasi:
- **Cek teks** dengan ripgrep, mis. `rg "Read More" *.html` → harus **0 hasil**. Cepat & objektif.
- **Cek visual** dengan membuka file di browser. Windows PowerShell: `Start-Process "berita-media.html"` (atau drag file ke browser). Perhatikan poin spesifik yang disebut tiap task.

Definisi "lulus" tiap task ada di step verifikasi masing-masing.

---

## Blok Kanonik (Reusable — dirujuk banyak task)

### A. Navbar kanonik (label sudah diringkas)

```html
<header class="navbar">
  <div class="container navbar-isi">
    <a href="index.html" class="logo">NOAH</a>
    <nav class="menu">
      <a href="cerita-band.html">Cerita Band</a>
      <a href="diskografi.html">Diskografi</a>
      <a href="tur-event.html">Event</a>
      <a href="berita-media.html">Media</a>
      <a href="sahabat-noah.html">Sahabat NOAH</a>
      <a href="toko.html">Toko</a>
    </nav>
    <div class="navbar-kanan">
      <span class="search-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
      <span class="lang">ID</span>
    </div>
  </div>
</header>
```

> Pada halaman aktif, tambahkan `class="aktif"` di link halaman tsb. Peta link-aktif per file:
> - `cerita-band.html` → `Cerita Band`
> - `diskografi.html` → `Diskografi`
> - `tur-event.html` → `Event`
> - `berita-media.html` → `Media`
> - `sahabat-noah.html` → `Sahabat NOAH`
> - `toko.html` → `Toko`
> - `index.html` → tidak ada link aktif (logo = beranda)

### B. Mega footer kanonik (identik di SEMUA 7 halaman)

```html
<footer class="footer">
  <div class="container grid-4">
    <div>
      <div class="logo-footer">NOAH</div>
      <p class="teks-abu">Representasi musik pop-rock terbaik Indonesia. Menjangkau hati lewat melodi dan lirik yang melampaui waktu.</p>
    </div>
    <div>
      <h4 class="footer-judul">Navigasi</h4>
      <ul>
        <li class="nav-cerita"><a href="cerita-band.html">Cerita Band</a></li>
        <li class="nav-diskografi"><a href="diskografi.html">Diskografi</a></li>
        <li class="nav-event"><a href="tur-event.html">Event</a></li>
        <li class="nav-media"><a href="berita-media.html">Media</a></li>
        <li class="nav-sahabat"><a href="sahabat-noah.html">Sahabat NOAH</a></li>
        <li class="nav-toko"><a href="toko.html">Toko</a></li>
      </ul>
    </div>
    <div>
      <h4 class="footer-judul">Musik</h4>
      <ul>
        <li><a href="https://open.spotify.com/artist/4tujQJicOnuZRLiBFdp3Ou" target="_blank" rel="noopener">Spotify</a></li>
        <li><a href="https://music.apple.com/id/artist/noah/473652585" target="_blank" rel="noopener">Apple Music</a></li>
        <li><a href="https://www.youtube.com/@noah" target="_blank" rel="noopener">YouTube Music</a></li>
        <li><a href="diskografi.html">Diskografi</a></li>
      </ul>
    </div>
    <div>
      <h4 class="footer-judul">Legal</h4>
      <ul>
        <li><a href="#">Kebijakan Privasi</a></li>
        <li><a href="#">Ketentuan Layanan</a></li>
        <li><a href="#">Kontak</a></li>
        <li><a href="#">Materi Pers</a></li>
      </ul>
    </div>
  </div>
  <div class="container footer-bawah">
    <p>&copy; 2024 NOAH. Hak Cipta Dilindungi.</p>
    <div class="footer-ikon">
      <span><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
      <span><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></span>
    </div>
  </div>
</footer>
```

> **Inilah perbaikan "mega footer" inti (requirement #5):** blok footer di atas **identik di tiap halaman** (DRY), tiap `<li>` Navigasi diberi class (`nav-cerita`, `nav-event`, dst). Perilaku **kontekstual** ditangani CSS, bukan JS — lihat Task 3.
>
> **Perilaku yang diinginkan:** saat sebuah halaman dibuka, **nama halaman itu sendiri tidak muncul** di nav footer (karena pengguna sudah ada di situ). Contoh: di halaman Media, kolom Navigasi footer menampilkan Cerita Band · Diskografi · Event · Sahabat NOAH · Toko (link **Media** disembunyikan). Ini dicapai dengan class di `<body>` per halaman + aturan CSS `display:none` — footer HTML tetap sama persis di semua file.

### B2. Peta class `<body>` per halaman (untuk footer kontekstual)

Tiap file diberi class di tag `<body>`:

| File | `<body class="...">` |
|---|---|
| `index.html` | `page-index` |
| `cerita-band.html` | `page-cerita` |
| `diskografi.html` | `page-diskografi` |
| `tur-event.html` | `page-event` |
| `berita-media.html` | `page-media` |
| `sahabat-noah.html` | `page-sahabat` |
| `toko.html` | `page-toko` |

---

## Task 1: Setup git checkpoint + baseline

**Files:**
- Tidak mengubah file konten; hanya inisialisasi repo + snapshot.

**Step 1: Inisialisasi git**

Run:
```bash
git init
git add -A
git commit -m "chore: baseline NOAH website sebelum perbaikan grid & polish"
```
Expected: commit pertama dibuat berisi semua file existing.

**Step 2: Buat `.gitignore` ringan**

Create `.gitignore`:
```
# tidak ada build tool — abaikan file OS
Thumbs.db
.DS_Store
```

**Step 3: Commit**

```bash
git add .gitignore
git commit -m "chore: tambah .gitignore"
```

> Jika user menolak git: lewati Task 1 sepenuhnya dan abaikan semua step `git commit` di task berikutnya.

---

## Task 2: Ringkas label header di navbar (7 halaman)

**Files:**
- Modify: `index.html`, `cerita-band.html`, `diskografi.html`, `tur-event.html`, `berita-media.html`, `sahabat-noah.html`, `toko.html` (blok `<nav class="menu">`)

**Step 1: Ganti label di tiap navbar**

Di tiap file, ubah baris navbar:
- `<a href="tur-event.html" ...>Tur &amp; Event</a>` → `<a href="tur-event.html" ...>Event</a>`
- `<a href="berita-media.html" ...>Berita &amp; Media</a>` → `<a href="berita-media.html" ...>Media</a>`

Pertahankan `class="aktif"` pada link halaman yang sedang dibuka (lihat Blok Kanonik A). Gunakan navbar kanonik A sebagai acuan akhir.

**Step 2: Samakan toggle bahasa**

Karena situs jadi full Indonesia (Task 7), ubah `<span class="lang">ID / EN</span>` → `<span class="lang">ID</span>` di ketujuh file.

**Step 3: Verifikasi teks**

Run: `rg "Tur &amp; Event|Berita &amp; Media" *.html`
Expected: **0 hasil** di dalam `<nav class="menu">`. (Catatan: `<title>` & heading body diurus di Task 7 — abaikan dulu hasil di luar navbar.)

Run: `rg -n ">Event<|>Media<" *.html`
Expected: muncul di ketujuh navbar.

**Step 4: Verifikasi visual**

Buka `index.html` di browser. Pastikan menu berbunyi: Cerita Band · Diskografi · **Event** · **Media** · Sahabat NOAH · Toko.

**Step 5: Commit**

```bash
git add *.html
git commit -m "feat: ringkas label navbar jadi Event & Media di semua halaman"
```

---

## Task 3: Mega footer kontekstual + seragam di 7 halaman (requirement #5)

> **Inti requirement (klarifikasi user):** footer harus **kontekstual/"interaktif"** — saat sebuah halaman dibuka, **nama halaman itu sendiri tidak muncul** di nav footer. Mis. di halaman Media, link "Media" tidak ditampilkan di nav footer. Karena **tanpa JS**, ini dicapai dengan: footer HTML identik di semua file + class di `<body>` per halaman + 1 aturan CSS `display:none`.

**Files:**
- Modify: `index.html`, `cerita-band.html`, `diskografi.html`, `tur-event.html`, `berita-media.html`, `sahabat-noah.html`, `toko.html` (tag `<body>` + blok `<footer class="footer">...</footer>`)
- Modify: `style.css` (aturan footer kontekstual)

**Step 1: Tambah class halaman di tag `<body>`**

Di tiap file, ubah `<body>` menjadi `<body class="page-XXX">` sesuai peta **Blok Kanonik B2**:
- `index.html` → `<body class="page-index">`
- `cerita-band.html` → `<body class="page-cerita">`
- `diskografi.html` → `<body class="page-diskografi">`
- `tur-event.html` → `<body class="page-event">`
- `berita-media.html` → `<body class="page-media">`
- `sahabat-noah.html` → `<body class="page-sahabat">`
- `toko.html` → `<body class="page-toko">`

**Step 2: Ganti seluruh blok footer dengan footer kanonik (identik di semua file)**

Di tiap file, ganti elemen `<footer class="footer"> ... </footer>` dengan **Blok Kanonik B** persis (perhatikan tiap `<li>` Navigasi punya class `nav-cerita`/`nav-diskografi`/`nav-event`/`nav-media`/`nav-sahabat`/`nav-toko`). Ini sekaligus:
- Footer **identik** di semua halaman (DRY) — keenam link selalu ada di sumber HTML.
- Menerjemahkan teks footer (`Privacy Policy`→`Kebijakan Privasi`, `Terms of Service`→`Ketentuan Layanan`, `Contact`→`Kontak`, `Press Kit`→`Materi Pers`, `All Rights Reserved`→`Hak Cipta Dilindungi`).
- Memberi link nyata ke streaming (Spotify/Apple/YouTube) + Diskografi.

> Catatan: `sahabat-noah.html` & `toko.html` sebelumnya punya struktur footer berbeda (kolom "Menu Utama", "Newsletter", dll). Tetap **timpa** dengan footer kanonik B agar konsisten. (Newsletter sudah ada di body halaman lain; tidak perlu duplikat di footer.)

**Step 3: Tambah aturan CSS footer kontekstual di `style.css`**

Tambahkan di bagian footer `style.css`:

```css
/* ===== MEGA FOOTER KONTEKSTUAL =====
   Sembunyikan link halaman yang SEDANG dibuka dari nav footer.
   "Interaktif" lewat CSS (class di <body>), tanpa JS. */
.page-cerita    .nav-cerita,
.page-diskografi .nav-diskografi,
.page-event     .nav-event,
.page-media     .nav-media,
.page-sahabat   .nav-sahabat,
.page-toko      .nav-toko {
  display: none;
}
```

> `page-index` sengaja tidak punya aturan: di beranda keenam link tampil lengkap (beranda diwakili logo, bukan item nav).

**Step 4: Verifikasi perilaku kontekstual**

Run: `rg -c "class=\"nav-media\"" *.html`
Expected: tiap file = 1 (link Media ada di sumber HTML semua halaman).

Buka `berita-media.html` → scroll footer → kolom Navigasi menampilkan **5** link (Cerita Band, Diskografi, Event, Sahabat NOAH, Toko); **"Media" TIDAK muncul**.
Buka `toko.html` → footer **tanpa** link "Toko".
Buka `index.html` → footer menampilkan **6** link lengkap.

Run: `rg "Privacy Policy|Terms of Service|All Rights Reserved" *.html`
Expected: **0 hasil**.

**Step 5: Commit**

```bash
git add *.html style.css
git commit -m "feat: mega footer kontekstual (sembunyikan link halaman aktif via CSS) + teks Indonesia"
```

---

## Task 4: Linkkan semua CTA (requirement #2)

**Files:**
- Modify: `index.html`, `tur-event.html`, `berita-media.html`, `sahabat-noah.html`, `toko.html`, `diskografi.html`, `cerita-band.html`

**Prinsip konversi:**
- Tombol navigasi/aksi yang bukan submit form → ubah `<button class="btn ...">Teks</button>` menjadi `<a class="btn ..." href="TUJUAN">Teks</a>`.
- Tombol di dalam `<form>` (Berlangganan, Daftar, Kirim Inquiry, Kirim) → biarkan `<button type="submit">` (itu sudah benar).
- Link `href="#"` yang seharusnya menuju halaman → arahkan ke halaman nyata.
- CTA streaming → URL eksternal `target="_blank" rel="noopener"`.

**Step 1: CTA di `index.html`**

- Hero: `Dengarkan Sekarang` → `<a class="btn btn-merah" href="#player">Dengarkan Sekarang</a>` (anchor ke section player; beri `id="player"` pada `<section class="player-wrap">`). `Selengkapnya` → `<a class="btn btn-outline-putih" href="cerita-band.html">Selengkapnya</a>`.
- `Lihat Semua Jadwal` (`link-merah href="#"`) → `href="tur-event.html"`.
- Konser cards `Beli Tiket` → `<a class="btn btn-merah lebar" href="tur-event.html">Beli Tiket</a>`. Tombol `Terjual Habis` (nonaktif) tetap `<button class="btn btn-nonaktif lebar">` (memang mati).
- `Baca Cerita Lengkap` → `<a class="btn btn-merah" href="cerita-band.html">Baca Cerita Lengkap</a>`.
- `Baca Semua Berita` (`href="#"`) → `href="berita-media.html"`.

**Step 2: CTA di `tur-event.html`**

- Kartu arsip event: bungkus pill `Setlist`/`Galeri` jadi `<a href="#arsip-setlist" class="pill">Setlist</a>` & `<a href="berita-media.html" class="pill">Galeri</a>`. Beri `id="arsip-setlist"` pada section Arsip Setlist.
- `Download Technical Rider (PDF)` → `<a class="btn btn-gelap" href="#" download>Unduh Technical Rider (PDF)</a>` (placeholder file).

**Step 3: CTA di `berita-media.html`**

- 3× `Read More` (`href="#"`) → `<a href="#" class="link-merah">Baca Selengkapnya</a>` (terjemahan tuntas di Task 7; di sini cukup pastikan elemen `<a>`).
- `Baca Selengkapnya` hero → `<a class="btn btn-merah" href="#">Baca Selengkapnya</a>` (ganti `<button>`).
- 3× `Download` press kit → `<a class="btn btn-merah" href="#" download>Unduh</a>`.

**Step 4: CTA di `sahabat-noah.html`**

- `Daftar Sekarang` hero → `<a class="btn btn-outline-putih mb" href="#form-cerita">Daftar Sekarang</a>` (beri `id="form-cerita"` di section form kirim cerita).
- `Bagikan Ceritamu` & `Kirim Karyamu` → `<a class="btn ..." href="#form-cerita">...</a>`.
- Tombol `Daftar` pada event komunitas → `<a class="btn btn-merah" href="#form-cerita">Daftar</a>`.
- (Tombol `Kirim` di dalam `<form>` tetap submit.)

**Step 5: CTA di `toko.html`**

- `Belanja Sekarang` banner → `<a class="btn btn-putih" href="#produk">Belanja Sekarang</a>` (beri `id="produk"` pada grid produk).
- 6× `Tambah ke Keranjang` → `<a class="btn btn-merah lebar" href="#produk">Tambah ke Keranjang</a>`.
- 2× `Beli Sekarang` edisi terbatas → `<a class="btn btn-merah lebar" href="#produk">Beli Sekarang</a>`.

**Step 6: CTA di `diskografi.html` & `cerita-band.html`**

- `diskografi.html`: 6× `Lihat Album` → `<a class="btn btn-merah lebar" href="#">Lihat Album</a>`.
- `cerita-band.html`: tidak ada tombol aksi menggantung (hanya teks). Lewati bila tak ada `<button>` non-form.

**Step 7: Verifikasi**

Run: `rg -n "href=\"#\"" *.html`
Expected: hanya tersisa pada Legal footer & link placeholder yang memang belum ada tujuan (boleh `#`), bukan pada CTA navigasi utama.

Buka `index.html`, klik `Beli Tiket` → harus pindah ke `tur-event.html`. Klik `Dengarkan Sekarang` → harus scroll ke player.

**Step 8: Commit**

```bash
git add *.html
git commit -m "feat: jadikan semua CTA link yang berfungsi (a href / anchor)"
```

---

## Task 5: Terjemahkan seluruh teks Inggris ke Indonesia (requirement #7)

**Files:**
- Modify: ketujuh `.html` (body) + verifikasi `<title>`.

**Step 1: Kamus terjemahan (terapkan di semua file yang memuatnya)**

| Inggris | Indonesia |
|---|---|
| Read More | Baca Selengkapnya |
| Download | Unduh |
| New (badge) | Baru |
| Limited (badge) | Terbatas |
| Active Members | Anggota Aktif |
| Milestones | Tonggak Sejarah |
| Roots & Inspiration | Akar & Inspirasi |
| The Evolution | Sang Evolusi |
| Albums Sold | Album Terjual |
| HISTORIC EVENT | EVENT BERSEJARAH |
| WORLD TOUR | TUR DUNIA |
| Main Set | Set Utama |
| Encore | Encore *(tetap, istilah baku)* |
| Detail Show | Detail Pertunjukan |
| Guest | Tamu |
| Booking & Inquiry | Pemesanan & Kerja Sama |
| Corporate Event | Acara Korporat |
| Privat Concert | Konser Privat |
| Technical Rider *(judul)* | Technical Rider *(istilah teknis, beri subjudul "Kebutuhan Teknis")* |
| Collector's Choice | Pilihan Kolektor |
| Limited Edition Box Set | Box Set Edisi Terbatas |
| Backstage Moments | Momen Belakang Panggung |
| The Making of 'Wanitaku' | Pembuatan 'Wanitaku' |
| Press Kit & Sumber Media | Materi Pers & Sumber Media |
| Tracks | Lagu |
| NOAH 2012-Now | NOAH 2012-Kini |

> Nama merek/penghargaan **tidak** diterjemahkan: Spotify, Apple Music, YouTube Music, Deezer, AMI Awards, MTV Asia, Asia Song Fest, MURI Records, Nickelodeon, KCA. Nama produk yang mengandung "Tee/Hoodie/Snapback/Vinyl/Tote Bag" boleh dipertahankan (brand merchandise), tapi badge UI ("New"/"Limited") wajib diterjemahkan.

**Step 2: Perbaiki `<title>` & heading**

- `tur-event.html` `<title>Tur &amp; Event - NOAH</title>` → `<title>Event - NOAH</title>`.
- `berita-media.html` `<title>Berita &amp; Media - NOAH</title>` → `<title>Media - NOAH</title>`.
- Heading body yang masih "Tur & Event"/"Berita & Media" → sesuaikan ke "Event"/"Media" bila muncul sebagai judul halaman.

**Step 3: Verifikasi**

Run: `rg -n "Read More|Download|Active Members|Collector's Choice|Albums Sold|HISTORIC EVENT|WORLD TOUR|Privacy|Terms of Service" *.html`
Expected: **0 hasil**.

Run: `rg -ni "\b(the making of|backstage|tracks|booking)\b" *.html`
Expected: 0 hasil (kecuali yang sengaja dipertahankan; tinjau manual bila ada).

**Step 4: Commit**

```bash
git add *.html
git commit -m "feat: terjemahkan seluruh teks Inggris ke Indonesia di semua halaman"
```

---

## Task 6: Konversi halaman EVENT (`tur-event.html`) ke CSS Grid + setlist `<details>` berefek (requirement #3 & #6)

**Files:**
- Modify: `tur-event.html` (section arsip event, setlist, booking)
- Modify: `style.css` (kelas grid + setlist details)

**Step 1: Pastikan layout utama memakai Grid**

Section yang sudah `grid-2`/`grid-3` dipertahankan. Untuk "full grid", tambahkan kelas grid responsif baru di `style.css`:

```css
/* Grid kartu responsif — dipakai halaman Event/Media/Sahabat/Toko */
.grid-auto {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}
```

Ubah container arsip event dari `<div class="grid-2">` menjadi `<div class="grid-auto">` (3 kartu jadi rapi & responsif via Grid).

**Step 2: Ubah setlist jadi `<details>/<summary>` (dropdown native, tanpa JS)**

Ganti tiap blok `<div class="setlist">` menjadi:

```html
<details class="setlist" open>
  <summary class="setlist-head">
    <span>The Great Journey of NOAH - Jakarta (2023)</span>
    <span class="setlist-chevron"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
  </summary>
  <div class="setlist-body grid-2">
    <!-- isi Set Utama + Encore + Detail Pertunjukan tetap sama -->
  </div>
</details>
```

Untuk 2 setlist tertutup, gunakan `<details class="setlist">` (tanpa `open`) dengan hanya `<summary class="setlist-head">…</summary>` (boleh tanpa body, atau beri body ringkas). Hapus kelas `tertutup` (state buka/tutup kini ditangani `<details>`).

**Step 3: Tambah efek setlist di `style.css`**

```css
/* ===== SETLIST sebagai <details> (dropdown native + efek) ===== */
.setlist summary {
  list-style: none;            /* hilangkan segitiga default */
  cursor: pointer;
}
.setlist summary::-webkit-details-marker { display: none; }

.setlist-head {
  transition: background-color .25s ease, padding-left .25s ease;
}
.setlist-head:hover {
  background-color: #ffd9d6;
  padding-left: 28px;          /* geser halus saat hover */
}

.setlist-chevron {
  transition: transform .3s ease;
}
.setlist[open] .setlist-chevron {
  transform: rotate(180deg);   /* panah berputar saat terbuka */
}

/* animasi muncul isi setlist */
.setlist[open] .setlist-body {
  animation: setlist-turun .3s ease;
}
@keyframes setlist-turun {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}
```

**Step 4: Verifikasi**

Buka `tur-event.html`. Klik header setlist tertutup → isi muncul dengan animasi turun, panah berputar. Hover header → background berubah & teks bergeser halus. Tanpa JS.

Run: `rg -n "class=\"setlist tertutup\"|setlist-head tertutup" tur-event.html`
Expected: 0 hasil (sudah diganti `<details>`).

**Step 5: Commit**

```bash
git add tur-event.html style.css
git commit -m "feat(event): grid responsif + setlist <details> dengan efek native"
```

---

## Task 7: Konversi halaman MEDIA (`berita-media.html`) ke CSS Grid penuh (requirement #3)

**Files:**
- Modify: `berita-media.html`
- Modify: `style.css` (bila perlu)

**Step 1: Pastikan semua section pakai Grid**

- "Berita Terbaru" (`grid-3`) & "Di Balik Layar" (`grid-3`) → boleh tetap `grid-3`, atau ubah ke `grid-auto` (Step 1 Task 6) agar responsif. Pakai `grid-auto`.
- "Galeri Foto" (`galeri-foto`, sudah grid 4 kolom) → pertahankan; tambahkan opsi responsif:

```css
.galeri-foto {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 15px;
}
```

- "Press Kit" — ubah `.presskit-baris` dari flex ke grid:

```css
.presskit-baris {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  border-bottom: 1px solid #eee;
}
```

- Filter galeri (`filter-grup`) boleh tetap flex (itu deretan tombol, bukan layout utama) ATAU `display:grid; grid-auto-flow:column; justify-content:start; gap:10px;` bila ingin konsisten "full grid". Default: biarkan flex (tidak over-engineering).

**Step 2: Verifikasi**

Run: `rg -n "presskit-baris" style.css` lalu pastikan blok memakai `display: grid`.
Buka `berita-media.html`, kecilkan lebar window → kartu berita & galeri menyusun ulang otomatis (Grid `auto-fit`/`auto-fill`).

**Step 3: Commit**

```bash
git add berita-media.html style.css
git commit -m "feat(media): konversi layout ke CSS Grid penuh + grid responsif"
```

---

## Task 8: Konversi halaman SAHABAT NOAH (`sahabat-noah.html`) ke CSS Grid penuh (requirement #3)

**Files:**
- Modify: `sahabat-noah.html`
- Modify: `style.css`

**Step 1: Konversi `.stats` flex → grid**

```css
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 40px;
  justify-items: center;
}
```

**Step 2: Konversi `.cerita-head` flex → grid**

```css
.cerita-head {
  display: grid;
  grid-template-columns: auto 1fr;   /* avatar | nama */
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}
```

**Step 3: Konversi `.event-card` flex → grid**

```css
.event-card {
  display: grid;
  grid-template-columns: 120px 1fr;  /* tanggal | isi */
  background-color: #ffffff;
  box-shadow: 0 4px 20px rgba(29,29,29,0.08);
  overflow: hidden;
}
```

Tambahkan override responsif di blok `@media (max-width: 768px)`:

```css
.event-card { grid-template-columns: 1fr; }
```

**Step 4: Grid kartu**

"Cerita Sahabat" (`grid-3`), "Chapter Regional" (`grid-3`), "Event Komunitas" (`grid-2`) → ubah ke `grid-auto` agar konsisten & responsif. "Karya Sahabat" memakai `galeri-foto` (sudah grid).

**Step 5: Verifikasi**

Buka `sahabat-noah.html`. Statistik, kartu cerita, chapter, dan event semua tersusun grid. Perkecil window → kolom menyusut otomatis. `.event-card` jadi 1 kolom di layar HP.

**Step 6: Commit**

```bash
git add sahabat-noah.html style.css
git commit -m "feat(sahabat): konversi stats/cerita-head/event-card & kartu ke CSS Grid"
```

---

## Task 9: Konversi halaman TOKO (`toko.html`) ke CSS Grid penuh (requirement #3)

**Files:**
- Modify: `toko.html`
- Modify: `style.css`

**Step 1: Konversi `.toko-layout` flex → grid (sidebar + produk)**

```css
.toko-layout {
  display: grid;
  grid-template-columns: 240px 1fr;   /* sidebar | grid produk */
  gap: 40px;
  align-items: start;
}
```

Hapus `width:240px; flex-shrink:0;` pada `.sidebar` (lebar kini diatur kolom grid):

```css
.sidebar { /* width & flex-shrink dihapus */ }
```

**Step 2: Konversi `.le-head` flex → grid**

```css
.le-head {
  display: grid;
  grid-template-columns: auto auto;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
```

**Step 3: Produk grid responsif**

```css
.produk-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 24px;
}
```

**Step 4: Responsif**

Di `@media (max-width: 768px)` tambahkan:

```css
.toko-layout { grid-template-columns: 1fr; }   /* sidebar menumpuk di atas */
```

(Hapus aturan lama `.toko-layout { flex-direction: column; }` bila ada — diganti grid.)

**Step 5: Verifikasi**

Buka `toko.html`. Sidebar kategori di kiri, grid produk di kanan (Grid). Perkecil window → sidebar pindah ke atas, produk jadi 1–2 kolom. Edisi Terbatas dua kolom.

Run: `rg -n "flex-direction: column" style.css`
Expected: tidak menyentuh `.toko-layout` lagi.

**Step 6: Commit**

```bash
git add toko.html style.css
git commit -m "feat(toko): konversi toko-layout & le-head ke CSS Grid + produk responsif"
```

---

## Task 10: Tambah embed musik (Spotify) + embed maps (Google Maps) (requirement #4)

**Files:**
- Modify: `index.html` (embed musik)
- Modify: `tur-event.html` (embed maps)
- Modify: `style.css` (wrapper responsif iframe)

**Step 1: CSS wrapper embed responsif**

```css
/* ===== EMBED (musik & maps) ===== */
.embed-section { padding: 60px 0; }

.embed-musik iframe {
  width: 100%;
  border: 0;
  border-radius: 12px;
}

/* peta rasio 16:9 responsif tanpa JS */
.map-wrap {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
  border-radius: 8px;
  overflow: hidden;
}
.map-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
}
```

**Step 2: Embed Spotify di `index.html`**

Tepat setelah section player (`</section>` dari `player-wrap`), sisipkan:

```html
<!-- ===== EMBED MUSIK (Spotify, tanpa JS) ===== -->
<section class="container embed-section embed-musik">
  <div class="section-header">
    <h2 class="section-title">Dengarkan di Spotify</h2>
    <a href="https://open.spotify.com/artist/4tujQJicOnuZRLiBFdp3Ou" target="_blank" rel="noopener" class="link-merah">Buka Spotify</a>
  </div>
  <iframe style="border-radius:12px"
    src="https://open.spotify.com/embed/track/3Qm86XLflmIXVm1wcwkgDK?utm_source=generator"
    height="352" loading="lazy"
    allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe>
</section>
```

> Catatan: ID track di atas placeholder NOAH; saat eksekusi, ambil "Embed track" dari Spotify untuk lagu NOAH yang diinginkan (mis. "Separuh Aku"). Format embed: `https://open.spotify.com/embed/track/<TRACK_ID>`.

**Step 3: Embed Google Maps di `tur-event.html`**

Di dalam section "Booking & Inquiry", pada kolom kanan setelah kartu kontak management, tambahkan kartu peta:

```html
<div class="card mb">
  <h3 class="berita-judul">Lokasi Manajemen</h3>
  <div class="map-wrap">
    <iframe
      src="https://www.google.com/maps?q=JIExpo%20Kemayoran%20Jakarta&output=embed"
      loading="lazy" referrerpolicy="no-referrer-when-downgrade"
      allowfullscreen></iframe>
  </div>
</div>
```

> Format embed maps tanpa API key: `https://www.google.com/maps?q=<QUERY_URL_ENCODED>&output=embed`. Ganti query sesuai venue.

**Step 4: Verifikasi**

Buka `index.html` (butuh koneksi internet) → player Spotify tampil & bisa diputar. Buka `tur-event.html` → peta tampil & responsif (rasio 16:9). Tidak ada JS yang ditambahkan (cek: `rg -n "<script" *.html` → 0 hasil).

**Step 5: Commit**

```bash
git add index.html tur-event.html style.css
git commit -m "feat: tambah embed musik Spotify (index) & embed Google Maps (event)"
```

---

## Task 11: Poles efek & transisi native (requirement #8)

**Files:**
- Modify: `style.css`

**Step 1: Transisi & lift pada kartu**

```css
/* ===== POLISH: transisi & hover halus ===== */
.card,
.event-card,
.produk,
.berita {
  transition: transform .25s ease, box-shadow .25s ease;
}
.card:hover,
.event-card:hover,
.produk:hover,
.berita:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 30px rgba(29,29,29,0.15);
}
```

**Step 2: Zoom halus gambar dalam kotak**

```css
.berita-gambar,
.album-cover,
.produk-gambar,
.galeri-item,
.banner-gambar {
  overflow: hidden;
}
.berita-gambar img,
.album-cover img,
.produk-gambar img,
.galeri-item img,
.banner-gambar img {
  transition: transform .4s ease;
}
.berita-gambar:hover img,
.album-cover:hover img,
.produk-gambar:hover img,
.galeri-item:hover img,
.banner-gambar:hover img {
  transform: scale(1.06);
}
```

**Step 3: Transisi tombol & link**

```css
.btn { transition: background-color .2s ease, color .2s ease, transform .15s ease; }
.btn:hover { transform: translateY(-2px); }
.btn:active { transform: translateY(0); }

.menu a { transition: color .2s ease; }
.link-merah { transition: color .2s ease, border-color .2s ease; }
.footer ul li a { transition: color .2s ease, padding-left .2s ease; }
.footer ul li a:hover { padding-left: 4px; }

.sosial-box,
.filter-btn,
.page-btn,
.kategori-list li { transition: background-color .2s ease, color .2s ease; }
```

**Step 4: Hormati preferensi reduce-motion (aksesibilitas, simpel)**

```css
@media (prefers-reduced-motion: reduce) {
  * { transition: none !important; animation: none !important; }
}
```

**Step 5: Verifikasi**

Buka beberapa halaman. Hover kartu → naik halus + bayangan. Hover gambar → zoom halus. Hover tombol → naik sedikit. Tidak ada perubahan mendadak/janky. Semua murni CSS.

**Step 6: Commit**

```bash
git add style.css
git commit -m "polish: transisi & hover halus pada kartu, gambar, tombol, link"
```

---

## Task 12: Tonggak Sejarah — timeline tengah (alternating) sesuai mockup (requirement #10)

> **Target:** Section "Tonggak Sejarah" (sebelumnya "Milestones") di `cerita-band.html` diubah dari timeline rata-kiri menjadi **timeline tengah** seperti mockup `stitch_noah_band_official_website_wireframe/.../cerita_band_noah_official_website/screen.png`: satu garis vertikal di tengah, tiap entri **berselang-seling kiri↔kanan**, dengan **titik (dot)** di garis. **Murni CSS, tanpa JS** (memakai `:nth-child(odd/even)` + `::before`/`::after`).

**Files:**
- Modify: `style.css` (ganti blok `.timeline` & `.timeline-item`, tambah versi responsif)
- Modify: `cerita-band.html` (HTML hampir tidak berubah; cukup pastikan struktur sesuai Step 2)

**Step 1: Ganti CSS timeline lama dengan timeline tengah**

Di `style.css`, **ganti** blok lama:
```css
.timeline {
  border-left: 3px solid #e4bebc;
  padding-left: 30px;
}
.timeline-item {
  margin-bottom: 40px;
}
```
menjadi:
```css
/* ===== TIMELINE TENGAH (alternating kiri-kanan, tanpa JS) ===== */
.timeline {
  position: relative;
  max-width: 1000px;
  margin: 0 auto;
}

/* garis vertikal di tengah */
.timeline::before {
  content: "";
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 3px;
  transform: translateX(-50%);
  background-color: #e4bebc;
}

.timeline-item {
  position: relative;
  width: 50%;
  padding: 0 45px 50px;
}

/* entri ganjil → sisi KIRI, teks rata kanan */
.timeline-item:nth-child(odd) {
  left: 0;
  text-align: right;
}

/* entri genap → sisi KANAN, teks rata kiri */
.timeline-item:nth-child(even) {
  left: 50%;
  text-align: left;
}

/* titik di garis tengah */
.timeline-item::after {
  content: "";
  position: absolute;
  top: 8px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #b7102a;
  border: 4px solid #fff8f7;
  box-shadow: 0 0 0 1px #e4bebc;
  z-index: 2;
  transition: transform .25s ease;   /* polish: titik membesar saat hover */
}
.timeline-item:nth-child(odd)::after  { right: -8px; }
.timeline-item:nth-child(even)::after { left: -8px; }
.timeline-item:hover::after { transform: scale(1.35); }
```

> Catatan: `.tahun`, `.berita-judul`, `.paragraf-kecil` tetap dipakai apa adanya — hanya layout-nya yang berubah.

**Step 2: Pastikan HTML `cerita-band.html` sesuai**

Struktur yang sudah ada **sudah cocok** (CSS bekerja via urutan `:nth-child`), cukup pastikan judul section memakai teks Indonesia & kelas tetap:
```html
<h2 class="section-title tengah mb">Tonggak Sejarah</h2>
<div class="timeline">
  <div class="timeline-item">
    <span class="tahun">1997</span>
    <h3 class="berita-judul">Terbentuknya Topi</h3>
    <p class="paragraf-kecil">Cikal bakal NOAH yang dibentuk di Bandung, mengawali perjalanan dari kafe ke kafe.</p>
  </div>
  <!-- 2000, 2004, 2012, 2024 — urutan menentukan sisi (ganjil=kiri, genap=kanan) -->
</div>
```
Urutan entri (1997, 2000, 2004, 2012, 2024) menghasilkan pola kiri-kanan-kiri-kanan-kiri, sama seperti mockup.

**Step 3: Tambah aturan responsif (HP) di blok `@media (max-width: 768px)`**

```css
/* timeline tengah → menumpuk 1 sisi di layar kecil */
.timeline::before { left: 8px; }
.timeline-item,
.timeline-item:nth-child(odd),
.timeline-item:nth-child(even) {
  width: 100%;
  left: 0;
  text-align: left;
  padding: 0 0 40px 35px;
}
.timeline-item:nth-child(odd)::after,
.timeline-item:nth-child(even)::after {
  left: 0;
  right: auto;
}
```

**Step 4: Verifikasi**

Buka `cerita-band.html` di browser (lebar desktop). Pastikan:
- Garis vertikal **di tengah**.
- 1997 di kiri (teks rata kanan), 2000 di kanan (teks rata kiri), 2004 kiri, 2012 kanan, 2024 kiri — **zigzag** seperti mockup.
- Tiap entri punya **titik merah** di garis tengah; titik membesar halus saat hover.
- Perkecil window < 768px → semua entri menumpuk rapi di kiri (garis pindah ke kiri).

**Step 5: Commit**

```bash
git add cerita-band.html style.css
git commit -m "feat(cerita-band): timeline Tonggak Sejarah jadi tengah/alternating sesuai mockup"
```

---

## Task 13: Dokumentasi terstruktur + verifikasi akhir (requirement #9)

**Files:**
- Create: `grid.md` (analog dengan `flex.md` yang sudah ada)
- Modify: `todo.md` (tandai selesai) — opsional

**Step 1: Tulis `grid.md`** mengikuti gaya `flex.md`: daftar selector Grid, baris di `style.css`, properti grid, dan fungsinya. Minimal mencakup:

```
A. Grid Utilitas
  .grid-auto  → repeat(auto-fit, minmax(260px,1fr)) — kartu responsif Event/Media/Sahabat/Toko
  .grid-3 / .grid-2 / .grid-4 — grid kolom tetap
  .galeri-foto → repeat(auto-fill, minmax(140px,1fr)) — galeri responsif

B. Halaman Toko
  .toko-layout → grid-template-columns: 240px 1fr — sidebar | produk
  .produk-grid → repeat(auto-fill, minmax(220px,1fr))
  .le-head     → grid auto auto — label & sisa item

C. Halaman Sahabat
  .stats       → repeat(auto-fit, minmax(120px,1fr))
  .cerita-head → auto 1fr — avatar | nama
  .event-card  → 120px 1fr — tanggal | isi

D. Halaman Media
  .presskit-baris → 1fr auto — info | tombol unduh

E. Embed
  .map-wrap → teknik padding-top 56.25% (rasio 16:9 tanpa JS)

F. Halaman Cerita Band
  .timeline / .timeline-item → timeline tengah alternating via :nth-child(odd/even)
    + ::before (garis tengah) + ::after (titik) — tanpa JS
```

Sertakan catatan singkat perbedaan Flexbox vs Grid (1 paragraf) untuk presentasi.

**Step 2: Verifikasi menyeluruh lintas halaman**

Checklist final (buka tiap halaman & cek):
- [ ] Navbar di 7 halaman: label **Event** & **Media**, link aktif benar.
- [ ] Footer **kontekstual**: di tiap halaman, link halaman itu sendiri tidak muncul (mis. di Media tak ada link "Media"); di beranda 6 link lengkap. Sumber HTML footer identik di semua file.
- [ ] Tidak ada `<button>` navigasi yang mati (semua CTA = `<a>` atau submit form).
- [ ] Tidak ada teks Inggris tersisa: `rg -ni "read more|download|active members|all rights|privacy policy|terms of service" *.html` → 0 hasil.
- [ ] Setlist Event: dropdown `<details>` berfungsi + animasi + chevron berputar.
- [ ] Embed Spotify (index) & Maps (event) tampil.
- [ ] Hover kartu/gambar/tombol halus.
- [ ] Tanpa JS: `rg -n "<script|onclick=|javascript:" *.html` → 0 hasil.
- [ ] Grid: 4 halaman target memakai `display:grid` untuk layout utama (cek `rg -n "display: ?flex" style.css` — pastikan sisa flex hanya navbar/kontrol kecil yang memang wajar).
- [ ] Tonggak Sejarah (`cerita-band.html`): timeline **tengah/alternating** dengan titik, zigzag seperti mockup; menumpuk rapi di layar HP.

**Step 3: Commit**

```bash
git add grid.md todo.md
git commit -m "docs: tambah grid.md (dokumentasi terstruktur) + checklist verifikasi akhir"
```

---

## Ringkasan Pemetaan Requirement → Task

| # | Requirement | Task |
|---|---|---|
| 1 | Header Tur&Event→Event, Berita&Media→Media | Task 2 (+ judul di Task 5) |
| 2 | Linkkan semua CTA | Task 4 |
| 3 | Page event/media/sahabat/toko full GRID | Task 6, 7, 8, 9 |
| 4 | Embed musik & maps | Task 10 |
| 5 | Mega footer kontekstual: nama halaman aktif tidak muncul di nav footer (CSS, tanpa JS) | Task 3 |
| 6 | Efek di dropdown arsip setlist | Task 6 |
| 7 | Inggris → full Indonesia | Task 3 (footer) + Task 5 (body) |
| 8 | Poles efek yang masih native | Task 11 |
| 9 | Tetap HTML/CSS native tanpa JS + dokumentasi terstruktur + simpel | Task 13 + dijaga di semua task |
| 10 | Tonggak Sejarah timeline tengah (alternating) sesuai mockup | Task 12 |
