// Menjalankan animasi ketika elemen mulai terlihat di layar.
function siapkanAnimasiScroll() {
  const elemenAnimasi = document.querySelectorAll(".section");

  if (!elemenAnimasi.length) return;

  // Tampilkan langsung jika browser tidak mendukung IntersectionObserver.
  if (!("IntersectionObserver" in window)) {
    elemenAnimasi.forEach(function (elemen) {
      elemen.classList.add("sudah-terlihat");
    });
    return;
  }

  elemenAnimasi.forEach(function (elemen) {
    elemen.classList.add("animasi-scroll");
  });

  const pengamat = new IntersectionObserver(function (daftarElemen) {
    daftarElemen.forEach(function (data) {
      if (data.isIntersecting) {
        data.target.classList.add("sudah-terlihat");
        pengamat.unobserve(data.target);
      }
    });
  }, { threshold: 0.12 });

  elemenAnimasi.forEach(function (elemen) {
    pengamat.observe(elemen);
  });
}

function siapkanAnimasiTimeline() {
  const timeline = document.querySelector(".timeline");
  if (!timeline) return;

  const tonggakSejarah = timeline.querySelectorAll(".timeline-item");
  timeline.classList.add("timeline-mewah");

  function perbaruiTimeline() {
    const batasTampil = window.innerHeight * 0.75;
    const posisiTimeline = timeline.getBoundingClientRect();
    const panjangTerisi = batasTampil - posisiTimeline.top;
    const progres = Math.min(Math.max(panjangTerisi / timeline.offsetHeight, 0), 1);

    timeline.style.setProperty("--progres-timeline", progres * 100 + "%");

    tonggakSejarah.forEach(function (item) {
      if (item.getBoundingClientRect().top < batasTampil) {
        item.classList.add("timeline-terlihat");
      }
    });
  }

  window.addEventListener("scroll", perbaruiTimeline);
  perbaruiTimeline();
}

function siapkanAnimasiJudul() {
  const judul = document.querySelector(".page-judul, .hero-judul");
  if (!judul) return;

  judul.classList.add("judul-animasi");

  requestAnimationFrame(function () {
    judul.classList.add("judul-muncul");
  });
}

function siapkanEfekNavbar() {
  const navbar = document.querySelector(".navbar");
  if (!navbar) return;

  function perbaruiNavbar() {
    navbar.classList.toggle("navbar-scroll", window.scrollY > 20);
  }

  window.addEventListener("scroll", perbaruiNavbar);
  perbaruiNavbar();
}

function siapkanEfekKlikTombol() {
  const tombol = document.querySelectorAll(
    ".btn, .filter-btn, .page-btn, .kategori-list button"
  );

  tombol.forEach(function (item) {
    item.addEventListener("click", function () {
      item.classList.remove("tombol-diklik");

      requestAnimationFrame(function () {
        item.classList.add("tombol-diklik");
      });
    });

    item.addEventListener("animationend", function () {
      item.classList.remove("tombol-diklik");
    });
  });
}

function siapkanPilihanKonser() {
  const pilihanKonser = document.querySelector("#pilih-konser");
  const tombolKonser = document.querySelectorAll("[data-pilih-konser]");
  if (!pilihanKonser) return;

  tombolKonser.forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      pilihanKonser.value = tombol.dataset.pilihKonser;
    });
  });
}

// Mengubah tombol yang dipilih menjadi berwarna merah.
function ubahTombolAktif(daftarTombol, tombolDipilih) {
  daftarTombol.forEach(function (tombol) {
    const sedangAktif = tombol === tombolDipilih;
    tombol.classList.toggle("aktif", sedangAktif);
    tombol.setAttribute("aria-pressed", sedangAktif);
  });
}

// Menyaring album berdasarkan era dan tipe album.
function siapkanFilterDiskografi() {
  const filter = document.querySelector("#filter-diskografi");
  if (!filter) return;

  const album = document.querySelectorAll("[data-album]");
  const pesanKosong = document.querySelector("#album-kosong");
  const pilihan = { era: "semua", tipe: "semua" };

  filter.querySelectorAll(".filter-btn").forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      const grup = tombol.closest("[data-filter-grup]");
      const namaGrup = grup.dataset.filterGrup;
      pilihan[namaGrup] = tombol.dataset.nilai;

      ubahTombolAktif(grup.querySelectorAll(".filter-btn"), tombol);

      let jumlahTampil = 0;
      album.forEach(function (item) {
        const eraSesuai = pilihan.era === "semua" || item.dataset.era === pilihan.era;
        const tipeSesuai = pilihan.tipe === "semua" || item.dataset.tipe === pilihan.tipe;
        item.hidden = !(eraSesuai && tipeSesuai);
        if (!item.hidden) jumlahTampil++;
      });

      pesanKosong.hidden = jumlahTampil !== 0;
    });
  });
}

// Menyaring foto galeri berdasarkan kategori yang dipilih.
function siapkanFilterGaleri() {
  const filter = document.querySelector("#filter-galeri");
  if (!filter) return;

  const tombolFilter = filter.querySelectorAll(".filter-btn");
  const foto = document.querySelectorAll("[data-foto]");

  tombolFilter.forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      const kategori = tombol.dataset.nilai;
      ubahTombolAktif(tombolFilter, tombol);

      foto.forEach(function (item) {
        item.hidden = kategori !== "semua" && item.dataset.kategori !== kategori;
      });
    });
  });
}

// Menyaring produk memakai kategori dan batas harga.
function tampilkanProduk() {
  const daftarProduk = document.querySelector("#produk");
  if (!daftarProduk) return;

  const kategoriAktif = document.querySelector(".kategori-list button.aktif").dataset.kategori;
  const batasHarga = Number(document.querySelector("#filter-harga").value);
  const produk = daftarProduk.querySelectorAll("[data-produk]");
  let jumlahTampil = 0;

  produk.forEach(function (item) {
    const kategoriSesuai = kategoriAktif === "semua" || item.dataset.kategori === kategoriAktif;
    const hargaSesuai = Number(item.dataset.harga) <= batasHarga;
    item.hidden = !(kategoriSesuai && hargaSesuai);
    if (!item.hidden) jumlahTampil++;
  });

  document.querySelector("#produk-kosong").hidden = jumlahTampil !== 0;
}

// Mengurutkan kartu produk dengan memindahkan urutan elemennya.
function urutkanProduk(caraUrut) {
  const daftarProduk = document.querySelector("#produk");
  const produk = Array.from(daftarProduk.querySelectorAll("[data-produk]"));

  if (caraUrut === "termurah") {
    produk.sort(function (a, b) {
      return Number(a.dataset.harga) - Number(b.dataset.harga);
    });
  } else if (caraUrut === "termahal") {
    produk.sort(function (a, b) {
      return Number(b.dataset.harga) - Number(a.dataset.harga);
    });
  } else {
    produk.sort(function (a, b) {
      return Number(a.dataset.urutan) - Number(b.dataset.urutan);
    });
  }

  produk.forEach(function (item) {
    daftarProduk.appendChild(item);
  });
}

// Mengaktifkan semua kontrol filter yang ada di halaman toko.
function siapkanFilterToko() {
  const daftarKategori = document.querySelectorAll(".kategori-list button");
  const filterHarga = document.querySelector("#filter-harga");
  const pilihanUrut = document.querySelector("#urut-produk");
  if (!daftarKategori.length) return;

  daftarKategori.forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      ubahTombolAktif(daftarKategori, tombol);
      tampilkanProduk();
    });
  });

  filterHarga.addEventListener("input", function () {
    document.querySelector("#harga-maksimal").textContent =
      "Rp " + Number(filterHarga.value).toLocaleString("id-ID");
    tampilkanProduk();
  });

  pilihanUrut.addEventListener("change", function () {
    urutkanProduk(pilihanUrut.value);
  });
}

siapkanAnimasiScroll();
siapkanAnimasiTimeline();
siapkanAnimasiJudul();
siapkanEfekNavbar();
siapkanEfekKlikTombol();
siapkanPilihanKonser();
siapkanFilterDiskografi();
siapkanFilterGaleri();
siapkanFilterToko();
