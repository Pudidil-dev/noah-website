A. Grid Utilitas

  Selector: .grid-auto
  Baris: style.css:323
  Properti Grid: display:grid; repeat(auto-fit, minmax(260px, 1fr)); gap:24px
  Fungsi: Kartu responsif untuk Event, Media, Sahabat NOAH, dan Toko.
  ------------------------------------------------------------------------
  Selector: .grid-3
  Baris: style.css:309
  Properti Grid: display:grid; repeat(3, 1fr); gap:24px
  Fungsi: Grid tiga kolom tetap untuk layout lama yang masih cocok.
  ------------------------------------------------------------------------
  Selector: .grid-2
  Baris: style.css:315
  Properti Grid: display:grid; 1fr 1fr; gap:50px; align-items:center
  Fungsi: Dua kolom seimbang untuk section profil, form, dan layout umum.
  ------------------------------------------------------------------------
  Selector: .grid-4
  Baris: style.css:329
  Properti Grid: display:grid; repeat(4, 1fr); gap:40px
  Fungsi: Empat kolom footer.
  ------------------------------------------------------------------------
  Selector: .galeri-foto
  Baris: style.css:1172
  Properti Grid: repeat(auto-fill, minmax(140px, 1fr)); gap:15px
  Fungsi: Galeri foto responsif, jumlah kolom mengikuti lebar layar.

B. Halaman Toko

  Selector: .toko-layout
  Baris: style.css:1293
  Properti Grid: grid-template-columns: 240px 1fr; gap:40px
  Fungsi: Sidebar filter di kiri dan grid produk di kanan.
  ------------------------------------------------------------------------
  Selector: .produk-grid
  Baris: style.css:1341
  Properti Grid: repeat(auto-fill, minmax(220px, 1fr)); gap:24px
  Fungsi: Produk tersusun responsif dari banyak kolom menjadi sedikit kolom.
  ------------------------------------------------------------------------
  Selector: .le-head
  Baris: style.css:1395
  Properti Grid: grid-template-columns: auto auto; justify-content:space-between
  Fungsi: Label edisi terbatas di kiri dan sisa item di kanan.

C. Halaman Sahabat

  Selector: .stats
  Baris: style.css:777
  Properti Grid: repeat(auto-fit, minmax(120px, 1fr)); justify-items:center
  Fungsi: Statistik komunitas rata dan responsif.
  ------------------------------------------------------------------------
  Selector: .cerita-head
  Baris: style.css:1204
  Properti Grid: grid-template-columns: auto 1fr; align-items:center
  Fungsi: Avatar di kiri, nama dan kota di kanan.
  ------------------------------------------------------------------------
  Selector: .event-card
  Baris: style.css:1220
  Properti Grid: grid-template-columns: 120px 1fr
  Fungsi: Tanggal event di kolom kiri, isi event di kolom kanan.

D. Halaman Media

  Selector: .presskit-baris
  Baris: style.css:1186
  Properti Grid: grid-template-columns: 1fr auto; align-items:center
  Fungsi: Info materi pers di kiri dan tombol unduh di kanan.

E. Embed

  Selector: .map-wrap
  Baris: style.css:1032
  Properti Layout: position:relative; padding-top:56.25%
  Fungsi: Membuat iframe Google Maps responsif dengan rasio 16:9 tanpa JS.

Catatan Flexbox vs Grid:
  Grid dipakai untuk layout dua arah: baris dan kolom kartu, sidebar-produk,
  galeri, dan footer. Flexbox tetap dipakai untuk komponen kecil satu arah,
  seperti navbar, tombol, icon group, dan isi tanggal event. Jadi Grid mengatur
  struktur halaman, sedangkan Flexbox merapikan isi komponen.
