  A. Navbar & Hero

  Selector: .navbar-isi
  Baris: 84–89
  Properti Flexbox: display:flex; align-items:center;
    justify-content:space-between
  Fungsi: Logo mentok kiri, menu+ikon mentok kanan, sejajar tengah vertikal
  ────────────────────────────────────────
  Selector: .navbar-kanan
  Baris: 110–116
  Properti Flexbox: display:flex; align-items:center; gap:15px
  Fungsi: Ikon cari + teks "ID/EN" berderet rapi
  ────────────────────────────────────────
  Selector: .hero-tombol
  Baris: 143–148
  Properti Flexbox: display:flex; gap:15px; justify-content:center;
    flex-wrap:wrap
  Fungsi: 2 tombol hero di tengah, turun baris kalau sempit

  B. Music Player

  Selector: .player-wrap
  Baris: 190–197
  Properti Flexbox: display:flex; justify-content:center
  Fungsi: Menengahkan kartu player
  ────────────────────────────────────────
  Selector: .player
  Baris: 199–208
  Properti Flexbox: display:flex; gap:20px; align-items:center
  Fungsi: Cover + info lagu berdampingan
  ────────────────────────────────────────
  Selector: .player-cover
  Baris: 215–225
  Properti Flexbox: display:flex; align-items:center;
    justify-content:center; flex-shrink:0
  Fungsi: Pusatkan teks cover; flex-shrink:0 = cover tetap 90px (tak
    menyusut)
  ────────────────────────────────────────
  Selector: .player-info
  Baris: 227–229
  Properti Flexbox: flex-grow:1
  Fungsi: Info lagu memanjang mengisi sisa ruang
  ────────────────────────────────────────
  Selector: .player-kontrol
  Baris: 255–259
  Properti Flexbox: display:flex; justify-content:space-between;
    align-items:center
  Fungsi: Tombol kiri ↔ tombol kanan
  ────────────────────────────────────────
  Selector: .tombol-kiri, .tombol-kanan
  Baris: 261–268
  Properti Flexbox: display:flex; align-items:center; gap:15px
  Fungsi: Grup ikon kontrol berderet
  ────────────────────────────────────────
  Selector: .play
  Baris: 271–281
  Properti Flexbox: display:flex; align-items:center;
  justify-content:center
  Fungsi: Ikon ▶ tepat di tengah lingkaran merah

  C. Judul Section & Kartu

  Selector: .section-header
  Baris: 285–290
  Properti Flexbox: display:flex; justify-content:space-between;
    align-items:flex-end
  Fungsi: Judul (kiri) ↔ link "Lihat Semua" (kanan), rata bawah
  ────────────────────────────────────────
  Selector: .card-atas
  Baris: 337–341
  Properti Flexbox: display:flex; justify-content:space-between
  Fungsi: Tanggal konser ↔ label "Tersedia/Habis"

  D. Placeholder Gambar (pola "pusatkan")

  Semua ini pakai display:flex; align-items:center; justify-content:center
  untuk menaruh teks/ikon tepat di tengah kotak:

  ┌────────────────┬───────────┬──────────────────────────────────┐
  │    Selector    │   Baris   │              Fungsi              │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .band-foto     │ 394–402   │ Placeholder foto band            │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .berita-gambar │ 411–419   │ Placeholder gambar berita        │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .galeri-item   │ 513–520   │ Sel galeri Instagram             │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .album-cover   │ 836–844   │ Cover album diskografi           │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .avatar        │ 727–738   │ Foto anggota (bulat)             │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .avatar-kecil  │ 740–751   │ Foto fan kecil (+ flex-shrink:0) │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .banner-gambar │ 1199–1207 │ Gambar banner toko               │
  ├────────────────┼───────────┼──────────────────────────────────┤
  │ .produk-gambar │ 1286–1296 │ Placeholder gambar produk        │
  └────────────────┴───────────┴──────────────────────────────────┘

  E. Komunitas / Newsletter / Footer

  Selector: .sosial
  Baris: 476–480
  Properti Flexbox: display:flex; gap:12px
  Fungsi: Deretan kotak ikon medsos
  ────────────────────────────────────────
  Selector: .newsletter
  Baris: 494–496
  Properti Flexbox: display:flex
  Fungsi: Input email + tombol berdampingan
  ────────────────────────────────────────
  Selector: .newsletter input
  Baris: 498–504
  Properti Flexbox: flex-grow:1
  Fungsi: Kolom email memanjang, tombol seukuran isinya
  ────────────────────────────────────────
  Selector: .footer-bawah
  Baris: 562–570
  Properti Flexbox: display:flex; justify-content:space-between;
    align-items:center
  Fungsi: Copyright ↔ ikon sosial

  F. Halaman lain (cerita, diskografi, tur, berita, sahabat, toko)

  Selector: .tag-list
  Baris: 755–759
  Properti Flexbox: display:flex; flex-wrap:wrap; gap:10px
  Fungsi: Daftar tag genre, membungkus
  ────────────────────────────────────────
  Selector: .stats
  Baris: 770–775
  Properti Flexbox: display:flex; flex-wrap:wrap; justify-content:center;
    gap:40px
  Fungsi: Angka statistik komunitas
  ────────────────────────────────────────
  Selector: .filter-grup
  Baris: 804–810
  Properti Flexbox: display:flex; flex-wrap:wrap; align-items:center;
    gap:10px
  Fungsi: Tombol filter
  ────────────────────────────────────────
  Selector: .album-head
  Baris: 846–850
  Properti Flexbox: display:flex; justify-content:space-between;
    align-items:flex-start
  Fungsi: Judul album ↔ tahun
  ────────────────────────────────────────
  Selector: .banner-form
  Baris: 900–905
  Properti Flexbox: display:flex; justify-content:center; gap:10px;
    flex-wrap:wrap
  Fungsi: Form di banner hiatus
  ────────────────────────────────────────
  Selector: .setlist-head
  Baris: 961–967
  Properti Flexbox: display:flex; justify-content:space-between
  Fungsi: Nama kota ↔ tanda buka/tutup
  ────────────────────────────────────────
  Selector: .hero-meta
  Baris: 1066–1071
  Properti Flexbox: display:flex; align-items:center; gap:20px;
    flex-wrap:wrap
  Fungsi: Pill + tanggal di hero berita
  ────────────────────────────────────────
  Selector: .pagination
  Baris: 1087–1092
  Properti Flexbox: display:flex; justify-content:center; gap:10px
  Fungsi: Tombol halaman di tengah
  ────────────────────────────────────────
  Selector: .presskit-baris
  Baris: 1124–1130
  Properti Flexbox: display:flex; justify-content:space-between;
    align-items:center
  Fungsi: Nama berkas ↔ tombol unduh
  ────────────────────────────────────────
  Selector: .cerita-head
  Baris: 1141–1146
  Properti Flexbox: display:flex; align-items:center; gap:15px
  Fungsi: Avatar fan + nama
  ────────────────────────────────────────
  Selector: .event-card
  Baris: 1156–1161
  Properti Flexbox: display:flex
  Fungsi: Kotak tanggal + isi event berdampingan
  ────────────────────────────────────────
  Selector: .event-tanggal
  Baris: 1163–1172
  Properti Flexbox: display:flex; flex-direction:column;
  align-items:center;
     justify-content:center
  Fungsi: Angka di atas, bulan di bawah, rata tengah
  ────────────────────────────────────────
  Selector: .toko-layout
  Baris: 1228–1231
  Properti Flexbox: display:flex; gap:40px
  Fungsi: Sidebar + grid produk berdampingan
  ────────────────────────────────────────
  Selector: .sidebar
  Baris: 1233–1236
  Properti Flexbox: flex-shrink:0 (+width:240px)
  Fungsi: Sidebar lebar tetap 240px
  ────────────────────────────────────────
  Selector: .kategori-list li
  Baris: 1246–1252
  Properti Flexbox: display:flex; justify-content:space-between
  Fungsi: Nama kategori ↔ jumlah
  ────────────────────────────────────────
  Selector: .range-label
  Baris: 1264–1269
  Properti Flexbox: display:flex; justify-content:space-between
  Fungsi: Harga min ↔ maks
  ────────────────────────────────────────
  Selector: .produk-grid
  Baris: 1279–1284
  Properti Flexbox: flex-grow:1 (lalu display:grid)
  Fungsi: Item flex yang melar, sekaligus container grid untuk produk
  ────────────────────────────────────────
  Selector: .le-head
  Baris: 1334–1338
  Properti Flexbox: display:flex; justify-content:space-between
  Fungsi: Label "LIMITED" ↔ sisa stok

  G. Properti flex item global

  ┌──────────┬───────┬───────────────┬──────────────────────────────────┐
  │ Selector │ Baris │   Properti    │              Fungsi              │
  ├──────────┼───────┼───────────────┼──────────────────────────────────┤
  │ .icon    │ 41–47 │ flex-shrink:0 │ Ikon SVG tak ikut menyusut saat  │
  │          │       │               │ berada di dalam flex container   │
  └──────────┴───────┴───────────────┴──────────────────────────────────┘

  H. Responsif — flex-direction: column (layar ≤768px)

  ┌───────────────┬───────────┬─────────────────────────────────────────┐
  │   Selector    │   Baris   │                 Fungsi                  │
  ├───────────────┼───────────┼─────────────────────────────────────────┤
  │ .player       │ 597–600   │ Cover di atas, info di bawah (menumpuk) │
  ├───────────────┼───────────┼─────────────────────────────────────────┤
  │ .footer-bawah │ 602–605   │ Copyright & ikon menumpuk vertikal      │
  ├───────────────┼───────────┼─────────────────────────────────────────┤
  │ .toko-layout  │ 1366–1368 │ Sidebar pindah ke atas produk           │
  ├───────────────┼───────────┼─────────────────────────────────────────┤
  │ .event-card   │ 1374–1376 │ Tanggal di atas, isi event di bawah     │
  └───────────────┴───────────┴─────────────────────────────────────────┘

  ---
  Total: ~40 container Flexbox + properti item (flex-grow, flex-shrink,
  flex-wrap, flex-direction).

  ▎ Catatan: .grid-2/3/4, .galeri, .galeri-foto, .info-isi, .form-baris 
  ▎ bukan Flexbox — itu CSS Grid. Khusus .produk-grid (baris 1279) menarik
  ▎ karena ia dua-duanya: jadi item flex (melar via flex-grow) di dalam
  ▎ .toko-layout, sekaligus container grid untuk produk di dalamnya —
  ▎ contoh bagus kombinasi Flexbox + Grid untuk disebut saat presentasi.

  Mau saya tambahkan tabel lengkap ini sebagai lampiran di file
  PANDUAN-PRESENTASI-FLEXBOX.md, supaya jadi satu dokumen utuh untuk
  Notion?