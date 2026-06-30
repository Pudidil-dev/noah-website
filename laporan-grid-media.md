# Implementasi CSS Grid - Halaman Media

File halaman: `berita-media.html`  
File style: `style.css`

## 1. Berita Terbaru

HTML:

```html
<section id="berita-terbaru" class="container section">
  <h2 class="section-title judul-garis mb">Berita Terbaru</h2>
  <div class="grid-auto">
    <article id="berita-orkestra" class="card card-border-merah">...</article>
    <article id="berita-final-show" class="card card-border-merah">...</article>
    <article id="berita-lirik" class="card card-border-merah">...</article>
  </div>
</section>
```

CSS:

```css
.grid-auto {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}
```

Fungsi: daftar artikel otomatis menjadi beberapa kolom di desktop dan turun menjadi kolom lebih sedikit saat layar mengecil.

## 2. Di Balik Layar

HTML:

```html
<section class="section bg-abu">
  <div class="container">
    <h2 class="section-title judul-garis mb">Di Balik Layar</h2>
    <div class="grid-auto">
      <div class="card">...</div>
      <div class="card">...</div>
      <div class="card">...</div>
    </div>
  </div>
</section>
```

CSS yang dipakai sama dengan `.grid-auto`.

Fungsi: kartu konten video/foto memakai pola Grid yang konsisten dengan Berita Terbaru.

## 3. Galeri Foto

HTML:

```html
<section id="galeri-foto" class="container section">
  <h2 class="section-title judul-garis mb">Galeri Foto</h2>
  <div class="galeri-foto">
    <div class="galeri-item"><img src="images/gallery-1.jpg" alt="Foto 1"></div>
    ...
  </div>
</section>
```

CSS:

```css
.galeri-foto {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 15px;
}
```

Fungsi: galeri foto mengisi ruang yang tersedia dengan kolom fleksibel. `auto-fill` menjaga item tetap tersusun rapat dan responsif.

## 4. Materi Pers

HTML:

```html
<section id="materi-pers" class="container section">
  <h2 class="section-title judul-garis mb">Materi Pers &amp; Sumber Media</h2>
  <div class="card">
    <div class="presskit-baris">
      <div>...</div>
      <a class="btn btn-merah" href="#materi-pers">Lihat Info</a>
    </div>
  </div>
</section>
```

CSS:

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

Fungsi: informasi file berada di kolom kiri, tombol aksi berada di kolom kanan. Kolom `1fr auto` membuat teks memakai ruang sisa dan tombol tetap selebar kontennya.

## Ringkasan

Halaman Media memakai CSS Grid pada tiga pola utama:

- `.grid-auto` untuk kartu artikel dan konten di balik layar.
- `.galeri-foto` untuk galeri gambar responsif.
- `.presskit-baris` untuk baris materi pers dengan kolom info dan tombol.

Flexbox masih dipakai hanya untuk komponen kecil seperti filter tombol dan pagination, bukan layout utama halaman.
