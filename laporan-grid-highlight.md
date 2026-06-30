# Highlight Implementasi CSS Grid - Website NOAH

Dokumen ini menyorot bagian kode dan halaman yang memakai CSS Grid untuk laporan. Grid dipakai untuk layout utama, daftar kartu, galeri, footer, dan susunan konten yang butuh baris-kolom responsif.

## 1. Grid Utilitas Global

File: `style.css`

```css
.grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 50px;
  align-items: center;
}

.grid-auto {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}

.grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
}
```

Highlight:
- `.grid-2` dipakai untuk layout dua kolom, misalnya profil band dan banner toko.
- `.grid-3` dipakai untuk daftar kartu tiga kolom di beranda.
- `.grid-auto` dipakai untuk kartu responsif yang otomatis menyesuaikan lebar layar.
- `.grid-4` dipakai untuk mega footer empat kolom.

Contoh penggunaan:

```html
<div class="grid-auto">
  <div class="card card-border-merah">...</div>
  <div class="card card-border-merah">...</div>
  <div class="card card-border-merah">...</div>
</div>
```

## 2. Halaman Event (`tur-event.html`)

Bagian yang memakai Grid:
- `Konser Mendatang`: `.grid-auto`
- `Arsip Event`: `.grid-auto`
- Isi setlist: `.setlist-body grid-2`
- Form dan info pemesanan: `.container grid-2`
- Peta responsif: `.map-wrap`

Cuplikan HTML:

```html
<section id="konser-mendatang" class="container section">
  <h1 class="page-judul tengah mb">Konser Mendatang</h1>
  <div class="grid-auto">
    <div class="card card-border-merah">...</div>
    <div class="card card-border-merah">...</div>
    <div class="card card-border-merah">...</div>
  </div>
</section>
```

Cuplikan CSS peta responsif:

```css
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

Penjelasan:
`.grid-auto` membuat kartu event otomatis menjadi beberapa kolom di layar lebar dan turun menjadi satu kolom di layar kecil. `.map-wrap` memakai teknik rasio 16:9 agar Google Maps tetap proporsional tanpa JavaScript.

## 3. Halaman Media (`berita-media.html`)

Bagian yang memakai Grid:
- Berita terbaru: `.grid-auto`
- Di Balik Layar: `.grid-auto`
- Galeri foto: `.galeri-foto`
- Materi pers: `.presskit-baris`

Cuplikan HTML:

```html
<section class="container section">
  <h2 class="section-title judul-garis mb">Berita Terbaru</h2>
  <div class="grid-auto">
    <article class="card card-border-merah">...</article>
    <article class="card card-border-merah">...</article>
    <article class="card card-border-merah">...</article>
  </div>
</section>
```

Cuplikan CSS:

```css
.galeri-foto {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 15px;
}

.presskit-baris {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  border-bottom: 1px solid #eee;
}
```

Penjelasan:
Galeri foto memakai `auto-fill` agar jumlah kolom mengikuti ruang yang tersedia. Baris materi pers memakai `grid-template-columns: 1fr auto`, sehingga teks menempati ruang utama dan tombol unduh tetap rapat di kanan.

## 4. Halaman Sahabat NOAH (`sahabat-noah.html`)

Bagian yang memakai Grid:
- Statistik komunitas: `.stats`
- Cerita Sahabat: `.grid-auto`
- Kepala kartu cerita: `.cerita-head`
- Komunitas regional: `.grid-auto`
- Event komunitas: `.event-card`

Cuplikan CSS:

```css
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 40px;
  justify-items: center;
}

.cerita-head {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.event-card {
  display: grid;
  grid-template-columns: 120px 1fr;
  background-color: #ffffff;
  box-shadow: 0 4px 20px rgba(29,29,29,0.08);
  overflow: hidden;
}
```

Penjelasan:
`.stats` membuat angka komunitas tersebar rata. `.cerita-head` memisahkan avatar dan identitas pengguna. `.event-card` membuat tanggal berada di kolom kiri dan isi acara di kolom kanan.

## 5. Halaman Toko (`toko.html`)

Bagian yang memakai Grid:
- Banner toko: `.container grid-2`
- Layout toko: `.toko-layout`
- Grid produk: `.produk-grid`
- Header edisi terbatas: `.le-head`

Cuplikan CSS:

```css
.toko-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 40px;
  align-items: start;
}

.produk-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 24px;
}

.le-head {
  display: grid;
  grid-template-columns: auto auto;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
```

Penjelasan:
`.toko-layout` membagi sidebar filter dan daftar produk. `.produk-grid` membuat produk otomatis responsif. `.le-head` merapikan label edisi terbatas dan informasi sisa item.

## 6. Mega Footer Semua Halaman

Footer memakai `.grid-4` di semua halaman.

Cuplikan HTML:

```html
<footer class="footer">
  <div class="container grid-4">
    <div>...</div>
    <div>...</div>
    <div>...</div>
    <div>...</div>
  </div>
</footer>
```

Penjelasan:
`.grid-4` membuat footer menjadi empat kolom: identitas NOAH, navigasi, musik, dan legal. Pada layar kecil, media query mengubah grid menjadi satu kolom agar tetap mudah dibaca.

## 7. Responsif Mobile

File: `style.css`

```css
@media (max-width: 768px) {
  .grid-3,
  .grid-2,
  .grid-4 {
    grid-template-columns: 1fr;
  }

  .toko-layout {
    grid-template-columns: 1fr;
  }

  .event-card {
    grid-template-columns: 1fr;
  }
}
```

Penjelasan:
Media query membuat layout multi-kolom berubah menjadi satu kolom pada layar kecil. Ini penting agar halaman tetap rapi di HP tanpa JavaScript.

## 8. Ringkasan Halaman yang Mengimplementasikan Grid

| Halaman | Implementasi Grid Utama |
|---|---|
| `index.html` | `.grid-3`, `.grid-2`, `.grid-4` |
| `tur-event.html` | `.grid-auto`, `.grid-2`, `.map-wrap`, `.grid-4` |
| `berita-media.html` | `.grid-auto`, `.galeri-foto`, `.presskit-baris`, `.grid-4` |
| `sahabat-noah.html` | `.stats`, `.grid-auto`, `.cerita-head`, `.event-card`, `.grid-4` |
| `toko.html` | `.toko-layout`, `.produk-grid`, `.le-head`, `.grid-2`, `.grid-4` |

Kesimpulan:
CSS Grid dipakai untuk struktur halaman yang membutuhkan susunan baris dan kolom. Flexbox tetap digunakan untuk komponen kecil satu arah seperti navbar, tombol, dan icon group. Kombinasi ini membuat kode tetap sederhana, native HTML/CSS, dan responsif.
