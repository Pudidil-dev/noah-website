# Laporan Fitur Native NOAH

Dokumen ini mencatat implementasi poin 2-8 tanpa JavaScript.

## CTA dan Anchor

- Placeholder `href="#"` diganti menjadi anchor internal atau halaman yang relevan.
- Footer legal diarahkan ke:
  - `index.html#kebijakan-privasi`
  - `index.html#ketentuan-layanan`
  - `tur-event.html#pemesanan-event`
  - `berita-media.html#materi-pers`
- Halaman Media memakai ID artikel (`#berita-orkestra`, `#berita-final-show`, `#berita-lirik`) sebagai target baca lanjut.
- Halaman Diskografi memakai ID album sebagai target tombol `Lihat Album`.

## Event Demo

`tur-event.html#konser-mendatang` diberi catatan bahwa jadwal adalah contoh event komunitas untuk website fan NOAH, bukan pengumuman konser resmi.

## Detail Native

Detail halaman tidak dibuat sebagai file baru. Untuk menjaga struktur sederhana, tombol detail diarahkan ke section/card terkait dengan anchor native.

## Aksesibilitas

- Link ikon sosial di homepage berubah dari `<div>` menjadi `<a>` dengan `aria-label`.
- Ikon dekoratif footer diberi `aria-hidden="true"`.
- `style.css` menambahkan `:focus-visible` untuk link, tombol, summary, input, select, dan textarea.

## Mobile Menu Native

Navbar memiliki `<details class="mobile-menu">` di setiap halaman. Di layar kecil, `.menu` disembunyikan dan menu ringkas `<details>` ditampilkan. Efek buka memakai CSS animation yang sama dengan search panel.

## Search Native

Search tetap memakai `<details>` + `<form>`:

- Tidak ada `<script>`.
- Panel search muncul dengan CSS transition/animation.
- Form mengarah ke `berita-media.html` dengan parameter `q`.

## Validasi

Perintah yang dipakai:

```powershell
$files = Get-ChildItem -Name -Filter *.html
rg -n 'href="#"' $files
rg -n '<script|onclick=|javascript:' $files
```

Target hasil: tidak ada output untuk kedua perintah.
