# Laporan Implementasi CSS Grid — Halaman Media

**LAPORAN LATIHAN**
**DESAIN WEB**

Latihan CSS Grid

Untuk Memenuhi Salah Satu Tugas Praktikum Desain Web

Instruktur: Ferry Faisal

Disusun oleh:
Dimas Miftahul Arifin (NIM: 3202416067)

Program D3 Teknik Informatika
Departemen Teknik Elektro
Politeknik Negeri Pontianak

2026

---

## 1. Gambaran Proyek

Proyek ini merupakan implementasi halaman situs web resmi band NOAH yang dibangun berdasarkan rancangan wireframe situs NOAH. Halaman yang dipilih untuk latihan CSS Grid ini adalah Halaman Media (`berita-media.html`). Halaman ini berfungsi sebagai pusat media dan pers resmi band, tempat pengunjung membaca berita terbaru, melihat konten di balik layar, menjelajahi galeri foto, serta mengunduh materi pers. Arah visual mengikuti konsep editorial merah–hitam–putih yang konsisten dengan halaman NOAH lainnya, namun tata letaknya dibangun ulang dengan CSS Grid sebagai metode layout utama.

Tujuan latihan ini adalah mendemonstrasikan penggunaan HTML dan CSS Grid dalam membuat tata letak halaman web yang terstruktur. Halaman memuat beberapa bagian penting, antara lain navigasi header, hero berita, daftar berita terbaru, bagian di balik layar, galeri foto, materi pers, dan footer. Setiap bagian disusun menggunakan struktur grid yang jelas sehingga hasil akhir tetap dekat dengan rancangan wireframe dan lebih mudah dipelihara.

## 2. Halaman Wireframe Terpilih: Media

Wireframe yang dipilih adalah halaman Media NOAH, yang berfungsi sebagai halaman berita dan pers dalam situs. Halaman ini dipilih karena memiliki tata letak yang lebih kompleks dibandingkan halaman pendarat biasa. Halaman ini memuat area hero berita yang besar, daftar kartu berita, kartu konten di balik layar, galeri foto yang responsif, serta baris-baris materi pers. Bagian-bagian ini membuat halaman cocok untuk implementasi CSS Grid karena konten perlu disusun dalam baris dan kolom sekaligus.

Halaman Media juga melengkapi struktur keseluruhan situs NOAH. Situs yang sudah ada mencakup halaman cerita band, diskografi, tur/event, sahabat NOAH, dan toko. Halaman media menutup proyek dengan menyediakan ruang editorial tempat kabar terbaru dan aset pers band dapat ditampilkan dalam tata letak yang rapi dan terbaca.

## 3. Referensi Wireframe dan Hasil

Gambar-gambar berikut menggunakan rancangan halaman media dari wireframe situs NOAH sebagai referensi visual untuk implementasi CSS Grid. Karena tangkapan layar halaman utuh sangat panjang, referensi dibagi menjadi beberapa bagian agar setiap section dapat dilihat dengan jelas di dalam laporan: hero berita, berita terbaru, di balik layar, galeri foto, dan materi pers.

_(Sisipkan tangkapan layar wireframe dan hasil implementasi untuk masing-masing bagian di sini.)_

## 4. Penjelasan Implementasi CSS Grid

Berkas `style.css` bertanggung jawab atas tata letak grid utama Halaman Media (`berita-media.html`). Beberapa bagian penting dijadikan container grid dengan menerapkan `display: grid` pada kelas seperti `.grid-auto`, `.galeri-foto`, `.presskit-baris`, dan `.grid-4`. Pendekatan ini membuat halaman lebih mudah dikendalikan karena setiap blok konten besar memiliki struktur gridnya sendiri. Alih-alih memakai elemen mengambang atau positioning manual, tata letak diatur melalui baris, kolom, dan gap.

```css
.grid-auto,
.galeri-foto,
.presskit-baris,
.grid-4 {
  display: grid;
}
```

Bagian Berita Terbaru memakai `.grid-auto` sebagai contoh utama CSS Grid di halaman ini. Pola yang sama juga dipakai pada bagian Di Balik Layar agar kartu konten video/foto konsisten dengan kartu berita. `repeat(auto-fit, minmax(260px, 1fr))` membuat kartu otomatis melipat menjadi lebih sedikit kolom saat layar mengecil, sambil memastikan setiap kartu tidak lebih sempit dari 260px dan mengembang merata mengisi ruang yang tersisa.

```css
.grid-auto {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}
```

Galeri Foto memakai pola yang sedikit berbeda. `auto-fill` dipakai sebagai pengganti `auto-fit` agar item foto tetap tersusun rapat dan responsif mengisi ruang yang tersisa dengan kolom fleksibel. `minmax(140px, 1fr)` menetapkan lebar minimum 140px per sel foto, sehingga galeri tetap rapi baik di desktop maupun ponsel.

```css
.galeri-foto {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 15px;
}
```

Bagian Materi Pers memakai grid dua kolom dengan pola `1fr auto`. Kolom kiri (informasi file) memakai `1fr` sehingga mengisi ruang sisa, sedangkan kolom kanan (tombol aksi) memakai `auto` sehingga lebarnya hanya selebar konten tombol. `align-items: center` membuat teks dan tombol sejajar secara vertikal. Setiap baris berisi satu entri materi pers (biografi resmi, paket foto resolusi tinggi, paket logo) dengan garis pemisah antar baris.

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

Footer memakai `.grid-4` untuk menyusun empat grup link (tentang NOAH, navigasi, musik, legal) dalam empat kolom yang sama lebar. `repeat(4, 1fr)` membagi ruang secara merata, dan `gap: 40px` memberi jarak yang nyaman antar grup.

```css
.grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
}
```

Perilaku responsif diatur dengan media query. Pada layar kecil (maksimal 768px), `.grid-4` turun menjadi satu kolom, `.galeri-foto` menjadi dua kolom, sementara `.grid-auto` melipat otomatis berkat `auto-fit` tanpa perlu aturan tambahan. Pendekatan ini menjaga halaman tetap dapat dibaca di ponsel tanpa overflow horizontal.

```css
@media (max-width: 768px) {
  .grid-3,
  .grid-2,
  .grid-4 {
    grid-template-columns: 1fr;
  }

  .galeri-foto,
  .info-isi,
  .produk-grid,
  .form-baris {
    grid-template-columns: 1fr 1fr;
  }
}
```

Flexbox tetap dipakai, namun hanya untuk komponen kecil seperti tombol filter, pagination, dan `hero-meta` — bukan untuk tata letak utama halaman.

## 5. Kesimpulan

Sebagai kesimpulan, Halaman Media NOAH berhasil mendemonstrasikan penggunaan CSS Grid untuk membangun tata letak halaman web yang terstruktur. HTML memakai elemen semantik seperti `header`, `nav`, `section`, `article`, `details`, dan `footer` untuk menciptakan struktur dokumen yang jelas. CSS memakai Grid untuk menyusun bagian-bagian utama halaman, termasuk daftar berita terbaru, kartu di balik layar, galeri foto, baris materi pers, dan footer empat kolom.

Secara keseluruhan, hasil akhir adalah halaman media editorial merah–hitam–putih yang rapi, mengikuti rancangan wireframe, dan memenuhi tujuan pembuatan halaman web dengan HTML serta CSS Grid.
