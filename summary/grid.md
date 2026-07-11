# Dokumentasi Implementasi CSS Grid

Dokumen ini merangkum selector Grid utama yang dipakai di website fan NOAH.

## A. Grid Utilitas

Selector: `.grid-auto`  
Baris: `style.css:488`  
Properti Grid: `display:grid; repeat(auto-fit, minmax(260px, 1fr)); gap:24px`  
Fungsi: Kartu responsif untuk Event, Media, Sahabat NOAH, dan Toko.

Selector: `.grid-3`  
Baris: `style.css:474`  
Properti Grid: `display:grid; repeat(3, 1fr); gap:24px`  
Fungsi: Grid tiga kolom tetap untuk layout yang memang stabil di desktop.

Selector: `.grid-2`  
Baris: `style.css:480`  
Properti Grid: `display:grid; 1fr 1fr; gap:50px; align-items:center`  
Fungsi: Dua kolom seimbang untuk section profil, form, banner, dan layout umum.

Selector: `.grid-4`  
Baris: `style.css:494`  
Properti Grid: `display:grid; repeat(4, 1fr); gap:40px`  
Fungsi: Empat kolom footer.

Selector: `.galeri-foto`  
Baris: `style.css:1445`  
Properti Grid: `repeat(auto-fill, minmax(140px, 1fr)); gap:15px`  
Fungsi: Galeri foto responsif, jumlah kolom mengikuti lebar layar.

## B. Halaman Toko

Selector: `.toko-layout`  
Baris: `style.css:1566`  
Properti Grid: `grid-template-columns: 240px 1fr; gap:40px`  
Fungsi: Sidebar filter di kiri dan grid produk di kanan.

Selector: `.produk-grid`  
Baris: `style.css:1614`  
Properti Grid: `repeat(auto-fill, minmax(220px, 1fr)); gap:24px`  
Fungsi: Produk tersusun responsif dari banyak kolom menjadi sedikit kolom.

Selector: `.le-head`  
Baris: `style.css:1668`  
Properti Grid: `grid-template-columns: auto auto; justify-content:space-between`  
Fungsi: Label edisi terbatas di kiri dan sisa item di kanan.

## C. Halaman Sahabat

Selector: `.stats`  
Baris: `style.css:1003`  
Properti Grid: `repeat(auto-fit, minmax(120px, 1fr)); justify-items:center`  
Fungsi: Statistik komunitas rata dan responsif.

Selector: `.cerita-head`  
Baris: `style.css:1477`  
Properti Grid: `grid-template-columns: auto 1fr; align-items:center`  
Fungsi: Avatar di kiri, nama dan kota di kanan.

Selector: `.event-card`  
Baris: `style.css:1493`  
Properti Grid: `grid-template-columns: 120px 1fr`  
Fungsi: Tanggal event di kolom kiri, isi event di kolom kanan.

## D. Halaman Media

Selector: `.presskit-baris`  
Baris: `style.css:1459`  
Properti Grid: `grid-template-columns: 1fr auto; align-items:center`  
Fungsi: Info materi pers di kiri dan tombol aksi di kanan.

## E. Embed

Selector: `.map-wrap`  
Baris: `style.css:1305`  
Properti Layout: `position:relative; padding-top:56.25%`  
Fungsi: Membuat iframe Google Maps responsif dengan rasio 16:9 tanpa JavaScript.

## F. Halaman Cerita Band

Selector: `.timeline` dan `.timeline-item`  
Baris: `style.css:896` dan `style.css:914`  
Properti Grid/Layout: timeline tengah memakai `::before` untuk garis, `::after` untuk titik, dan `:nth-child(odd/even)` untuk posisi kiri-kanan.  
Fungsi: Section Tonggak Sejarah menjadi alternating timeline tanpa JavaScript.

## Catatan Flexbox vs Grid

Grid dipakai untuk layout dua arah: baris dan kolom kartu, sidebar-produk, galeri, footer, press kit, dan timeline. Flexbox tetap dipakai untuk komponen kecil satu arah seperti navbar, kontrol player, icon group, dan form kecil. Jadi Grid mengatur struktur halaman, sedangkan Flexbox merapikan isi komponen.
