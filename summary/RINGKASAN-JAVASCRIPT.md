# Ringkasan Penambahan JavaScript

Project sekarang memakai satu file JavaScript terpisah bernama `app.js`. File tersebut dipanggil oleh tujuh halaman utama agar HTML tetap rapi dan fungsi JavaScript tidak ditulis berulang kali.

## Fungsi yang Ditambahkan

### 1. `siapkanAnimasiScroll()`

Memberikan animasi muncul dari bawah ketika section atau bagian timeline masuk ke layar. Fungsi ini memakai `IntersectionObserver`, sehingga animasi hanya dijalankan saat elemen benar-benar terlihat.

### 1.1. `siapkanAnimasiTimeline()`

Mengisi garis timeline mengikuti scroll, menampilkan tonggak sejarah dari kiri dan kanan, serta memberi denyut halus pada titik tahun yang sudah terlihat.

### 2. `siapkanAnimasiJudul()`

Memberikan efek judul muncul perlahan dari bawah ketika halaman pertama kali dibuka.

### 3. `siapkanEfekNavbar()`

Menambahkan bayangan pada navbar ketika halaman sudah digulir lebih dari 20 piksel. Bayangan dihapus kembali saat pengguna kembali ke bagian paling atas.

### 4. `siapkanEfekKlikTombol()`

Memberikan efek memantul kecil ketika tombol diklik. Class animasi dilepas lagi setelah animasi selesai agar dapat digunakan pada klik berikutnya.

### 5. `ubahTombolAktif()`

Mengatur warna tombol filter yang sedang dipilih. Fungsi ini dipakai kembali oleh filter diskografi, galeri, dan toko agar kode tidak berulang.

### 5.1. `siapkanPilihanKonser()`

Memilih nama konser secara otomatis ketika tombol Pesan Tiket pada kartu Jakarta, Bandung, atau Surabaya diklik.

### 6. `siapkanFilterDiskografi()`

Menyaring kartu album berdasarkan dua pilihan:

- Era Peterpan atau NOAH.
- Tipe Studio atau Kompilasi.

Kedua filter dapat digunakan bersamaan. Contohnya, pengguna dapat memilih era NOAH dan tipe Studio.

### 7. `siapkanFilterGaleri()`

Menyaring foto pada halaman Berita & Media berdasarkan kategori Peterpan, NOAH, Konser, atau Studio.

### 8. `tampilkanProduk()`

Menampilkan produk yang sesuai dengan kategori dan batas harga pada halaman Toko. Jika tidak ada produk yang cocok, halaman menampilkan pesan kosong.

### 9. `urutkanProduk()`

Mengurutkan produk berdasarkan urutan terbaru, harga terendah, atau harga tertinggi.

### 10. `siapkanFilterToko()`

Memasang event `click`, `input`, dan `change` pada kontrol halaman Toko. Fungsi ini menghubungkan tombol kategori, slider harga, dan pilihan urutan dengan fungsi filter produk.

## Perubahan pada HTML dan CSS

- Atribut `data-*` ditambahkan pada album, foto, dan produk sebagai informasi yang dibaca JavaScript.
- Atribut `aria-pressed` ditambahkan agar status tombol filter lebih mudah dikenali alat bantu pembaca layar.
- Class animasi dan pesan hasil kosong ditambahkan ke `style.css`.
- Semua halaman utama memanggil `app.js` dengan atribut `defer` agar JavaScript berjalan setelah HTML selesai dibaca.

## Cara Mencoba

1. Buka `diskografi.html`, lalu coba gabungkan filter Era dan Tipe.
2. Buka `berita-media.html`, lalu pilih kategori pada bagian Galeri Foto.
3. Buka `toko.html`, lalu pilih kategori, geser batas harga, dan ubah urutan produk.
4. Muat ulang halaman untuk melihat judul muncul dari bawah.
5. Scroll halaman untuk melihat animasi section dan bayangan navbar.
6. Klik tombol filter untuk melihat efek memantul kecil.

Tidak ada library atau dependency tambahan. Semua fungsi dibuat menggunakan JavaScript bawaan browser supaya alurnya mudah dipelajari mahasiswa pemula.
