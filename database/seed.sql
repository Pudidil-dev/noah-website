-- Data awal konten NOAH. Jalankan sekali setelah schema.sql pada database kosong.
BEGIN;
INSERT INTO members (name, position, bio, photo_url, sort_order) VALUES
('Ariel', 'Vokal / Frontman', 'Sang visioner dan penulis lirik utama yang suaranya telah menjadi lagu tema bagi jutaan orang.', 'images/member-ariel.jpg', 1),
('Lukman', 'Gitar', 'Arsitek melodi gitar yang memberikan nuansa atmosferik khas pada setiap lagu NOAH.', 'images/member-lukman.jpg', 2),
('David', 'Keyboard', 'Master harmonisasi yang membawa sentuhan modern lewat permainan keyboard yang elegan.', 'images/member-david.jpg', 3);
INSERT INTO milestones (year, badge, title, description, type, sort_order) VALUES
(1997, NULL, 'Terbentuknya Topi', 'Cikal bakal NOAH dibentuk di Bandung.', 'history', 1),
(2000, NULL, 'Lahirnya Peterpan', 'Nama Peterpan mulai digunakan.', 'history', 2),
(2004, NULL, 'Bintang di Surga', 'Album fenomenal Peterpan.', 'history', 3),
(2012, NULL, 'Reinkarnasi: NOAH', 'Memulai perjalanan dengan nama NOAH.', 'history', 4),
(2024, NULL, 'Hiatus & Refleksi', 'Mengambil jeda untuk karya baru.', 'history', 5),
(NULL, '3x', 'AMI Awards', NULL, 'award', 1),
(NULL, '2x', 'MTV Asia', NULL, 'award', 2),
(2007, '2007', 'Asia Song Fest', NULL, 'award', 3),
(NULL, '2', 'MURI Records', NULL, 'award', 4),
(NULL, '9M+', 'Album Terjual', NULL, 'award', 5),
(NULL, 'KCA', 'Nickelodeon', NULL, 'award', 6);
INSERT INTO albums (title, slug, type, era, release_date, total_tracks, cover_url, is_featured) VALUES
('Keterkaitan Keterikatan', 'keterkaitan-keterikatan', 'studio', 'noah', '2019-01-01', 8, 'images/album-1.jpg', FALSE),
('Second Chance', 'second-chance', 'studio', 'noah', '2014-01-01', 12, 'images/album-2.jpg', FALSE),
('Sings Legends', 'sings-legends', 'kompilasi', 'noah', '2016-01-01', 9, 'images/album-3.jpg', FALSE),
('Seperti Seharusnya', 'seperti-seharusnya', 'studio', 'noah', '2012-01-01', 10, 'images/album-cover.jpg', TRUE),
('Bintang di Surga', 'bintang-di-surga', 'studio', 'peterpan', '2004-01-01', 10, 'images/gallery-2.jpg', FALSE),
('Sebuah Nama, Sebuah Cerita', 'sebuah-nama-sebuah-cerita', 'kompilasi', 'peterpan', '2008-01-01', 11, 'images/gallery-4.jpg', FALSE);
INSERT INTO tracks (album_id, track_no, title, duration_sec, single_year)
SELECT a.id, t.track_no, t.title, t.duration_sec, t.single_year FROM (VALUES
('keterkaitan-keterikatan', 1, 'Wanitaku', NULL, 2019),
('keterkaitan-keterikatan', 2, 'Kupeluk Hatimu', NULL, NULL),
('keterkaitan-keterikatan', 3, 'Mendekati Lugu', NULL, NULL),
('second-chance', 1, 'Hero', NULL, NULL),
('second-chance', 2, 'Seperti Kemarin', NULL, NULL),
('second-chance', 3, 'Suara Pikiranku', NULL, NULL),
('sings-legends', 1, 'Andaikan Kau Datang', NULL, NULL),
('sings-legends', 2, 'Sajadah Panjang', NULL, NULL),
('sings-legends', 3, 'Biarlah Sendiri', NULL, NULL),
('seperti-seharusnya', 1, 'Separuh Aku', 266, 2012),
('seperti-seharusnya', 2, 'Hidup Untukmu, Mati Tanpamu', NULL, NULL),
('seperti-seharusnya', 3, 'Jika Engkau', 266, NULL),
('seperti-seharusnya', 4, 'Raja Negeriku', 270, NULL),
('bintang-di-surga', 1, 'Mungkin Nanti', NULL, NULL),
('bintang-di-surga', 2, 'Ada Apa Denganmu', NULL, NULL),
('bintang-di-surga', 3, 'Kukatakan Dengan Indah', NULL, NULL),
('bintang-di-surga', NULL, 'Khayalan Tingkat Tinggi', NULL, 2004),
('bintang-di-surga', NULL, 'Bintang di Surga', NULL, NULL),
('sebuah-nama-sebuah-cerita', 1, 'Menunggumu', NULL, 2008),
('sebuah-nama-sebuah-cerita', 2, 'Tak Bisakah', NULL, 2005),
('sebuah-nama-sebuah-cerita', 3, 'Walau Habis Terang', NULL, NULL)
) AS t(slug, track_no, title, duration_sec, single_year) JOIN albums a ON a.slug = t.slug;
INSERT INTO tracks (title) VALUES ('Topeng'), ('Ada Gajah Dibalik Batu (Intro)'), ('Menghapus Jejakmu'), ('Yang Terdalam'), ('Mimpi Yang Sempurna');
INSERT INTO communities (city, name, leader_name, member_count) VALUES
('Jakarta', 'Sahabat NOAH Jakarta', 'Hendry Kusuma', 8420),
('Bandung', 'Sahabat NOAH Bandung', 'Asep Kurnia', 6150),
('Surabaya', 'Sahabat NOAH Surabaya', 'Budi Santoso', 4890),
('Yogyakarta', 'Sahabat NOAH Yogyakarta', 'Rarasati', 3720),
('Medan', 'Sahabat NOAH Medan', 'Iwan Ginting', 2900),
('Makassar', 'Sahabat NOAH Makassar', 'Zulkifli', 2140);
UPDATE albums SET streaming_links='{"spotify":"https://open.spotify.com/album/4EneY8crCrt61L7siIXtkS?si=UTO1NEtATPqdLRThsBjRjQ","ytmusic":"https://music.youtube.com/channel/UCbtHlc5rJCvSb_k0VyUowLA","apple":"https://music.apple.com/id/artist/noah/766158889?l=id"}' WHERE slug='seperti-seharusnya';
INSERT INTO events (community_id, title, slug, type, badge, city, venue, start_at, description, ticket_price, quota, status) VALUES
(NULL, 'Malam Karya NOAH Jakarta', 'malam-karya-noah-jakarta-2027', 'concert', NULL, 'Jakarta', 'JIExpo Kemayoran', '2027-08-24 19:00+07', 'Malam karya NOAH bersama Sahabat.', 350000, 1000, 'published'),
(NULL, 'Temu Sahabat NOAH Bandung', 'temu-sahabat-noah-bandung-2027', 'concert', NULL, 'Bandung', 'SABUGA', '2027-09-12 19:00+07', 'Malam musik akustik.', 250000, 600, 'published'),
(NULL, 'Malam Tribut NOAH Surabaya', 'malam-tribut-noah-surabaya-2027', 'concert', NULL, 'Surabaya', 'Grand City Convention Hall', '2027-10-03 19:00+07', 'Lagu lintas era.', 250000, 800, 'published'),
(NULL, '6 Kota dalam 24 Jam (2004)', '6-kota-dalam-24-jam-2004', 'concert', 'EVENT BERSEJARAH', 'Medan', NULL, '2004-01-01 19:00+07', 'Medan, Padang, Pekanbaru, Lampung, Semarang, Surabaya', NULL, NULL, 'published'),
(NULL, '5 Negara dalam 24 Jam', '5-negara-dalam-24-jam-2012', 'concert', NULL, 'Melbourne', NULL, '2012-11-15 19:00+07', 'Melbourne, Hong Kong, Kuala Lumpur, Singapore, Jakarta', NULL, NULL, 'published'),
(NULL, 'Tur AS (2015)', 'tur-as-2015', 'concert', 'TUR DUNIA', 'Los Angeles', NULL, '2015-01-01 19:00+07', 'Los Angeles, San Francisco, Washington DC, New York', NULL, NULL, 'published'),
(NULL, 'The Great Journey of NOAH - Jakarta (2023)', 'the-great-journey-jakarta-2023', 'concert', NULL, 'Jakarta', 'Ancol Beach City International Stadium', '2023-12-03 19:00+07', 'Tamu: Reza (Drummer Asli)', NULL, NULL, 'published'),
((SELECT id FROM communities WHERE city = 'Jakarta'), 'Sahabat NOAH Nasional', 'sahabat-noah-nasional-2027', 'community', 'Gathnas 2027', 'Jakarta', NULL, '2027-10-24 10:00+07', 'Pertemuan akbar tahunan seluruh komunitas wilayah di Jakarta.', NULL, NULL, 'published'),
((SELECT id FROM communities WHERE city = 'Bandung'), 'Lokakarya Musik & Kopi', 'lokakarya-musik-kopi-2026', 'community', 'Bandung', 'Bandung', NULL, '2026-11-12 15:00+07', 'Bedah instrumen lagu-lagu NOAH bersama musisi lokal.', NULL, NULL, 'published');
UPDATE events SET maps_url = 'https://www.google.com/maps?q=JIExpo%20Convention%20Centre%20and%20Theatre%20Jakarta&output=embed'
WHERE slug = 'malam-karya-noah-jakarta-2027';
INSERT INTO setlist_items (event_id, position, track_id, is_encore)
SELECT e.id, s.position, t.id, s.is_encore FROM (VALUES
(1, 'Topeng', FALSE), (2, 'Ada Gajah Dibalik Batu (Intro)', FALSE), (3, 'Separuh Aku', FALSE),
(4, 'Mungkin Nanti', FALSE), (5, 'Menghapus Jejakmu', FALSE), (6, 'Tak Bisakah', FALSE),
(7, 'Yang Terdalam', FALSE), (8, 'Bintang di Surga', FALSE), (9, 'Khayalan Tingkat Tinggi', FALSE),
(10, 'Wanitaku', FALSE), (11, 'Walau Habis Terang', TRUE), (12, 'Mimpi Yang Sempurna', TRUE)
) AS s(position, title, is_encore) JOIN tracks t ON t.title=s.title
CROSS JOIN (SELECT id FROM events WHERE slug='the-great-journey-jakarta-2023') e;
INSERT INTO posts (title, slug, category, tag, is_featured, excerpt, body, cover_url, status, published_at) VALUES
('NOAH Umumkan Hiatus: Apa yang Terjadi Selanjutnya?', 'hiatus', 'news', 'Pengumuman', TRUE, 'Setelah dekade penuh kesuksesan, NOAH mengambil jeda.', 'Perjalanan NOAH terus berlanjut bersama Sahabat.', 'images/hero-news.jpg', 'published', '2024-01-15 10:00+07'),
('Kolaborasi Spesial NOAH dengan Orkestra Nasional', 'orkestra', 'news', 'Musik', FALSE, 'Kolaborasi rock dan simfoni.', 'Karya lintas genre dari NOAH.', 'images/news-1.jpg', 'published', '2024-01-12 10:00+07'),
('Tiket Konser The Final Show Terjual Habis', 'final-show', 'news', 'Konser', FALSE, 'Antusiasme Sahabat memuncak.', 'Pertunjukan penuh kenangan.', 'images/news-2.jpg', 'published', '2024-01-08 10:00+07'),
('Ariel Berbagi Cerita Dibalik Lirik Lagu Teranyar', 'lirik', 'news', 'Pribadi', FALSE, 'Proses kreatif dan refleksi musik Ariel.', 'Wawancara mengenai perjalanan musiknya.', 'images/news-3.jpg', 'published', '2024-01-05 10:00+07'),
('Dibalik Layar Pembuatan Aransemen Baru Keterkaitan Keterikatan', 'aransemen-keterkaitan-keterikatan', 'news', 'Album', FALSE, 'Eksplorasi sound terbaru NOAH.', 'Orkestra dan synthesizer dalam aransemen terbaru.', 'images/news-1.jpg', 'published', '2024-09-12 10:00+07'),
('Pengalaman Tur The Great Journey di Singapura', 'great-journey-singapura', 'news', 'Tur', FALSE, 'Ribuan Sahabat memadati konser.', 'Perjalanan tur bersama Sahabat NOAH.', 'images/news-2.jpg', 'published', '2024-09-05 10:00+07'),
('Sesi Musik NOAH Bersama Musisi Mancanegara', 'sesi-musisi-mancanegara', 'news', 'Media', FALSE, 'Kolaborasi lintas genre.', 'Sesi kolaborasi NOAH dengan musisi mancanegara.', 'images/news-3.jpg', 'published', '2024-08-28 10:00+07'),
('Proses Rekaman Studio', 'proses-rekaman-studio', 'behind_scenes', 'Video', FALSE, 'Melihat pembuatan album.', 'Di balik lagu NOAH.', 'images/gallery-1.jpg', 'published', '2024-01-03 10:00+07'),
('Momen Belakang Panggung', 'momen-belakang-panggung', 'behind_scenes', 'Foto', FALSE, 'Persiapan di belakang panggung.', 'Dokumentasi jelang konser.', 'images/gallery-2.jpg', 'published', '2024-01-02 10:00+07'),
('Pembuatan Wanitaku', 'pembuatan-wanitaku', 'behind_scenes', 'Video', FALSE, 'Eksplorasi desain suara.', 'Proses di balik Wanitaku.', 'images/gallery-3.jpg', 'published', '2024-01-01 10:00+07');
INSERT INTO media (type, tag, title, caption, file_url) VALUES
('photo', 'studio', 'Rekaman di studio', 'NOAH saat rekaman', 'images/gallery-1.jpg'),
('photo', 'peterpan', 'Era Peterpan', 'Penampilan Peterpan', 'images/gallery-2.jpg'),
('photo', 'konser', 'Panggung konser', 'NOAH di panggung', 'images/gallery-3.jpg'),
('photo', 'noah', 'NOAH terbaru', 'Dokumentasi NOAH', 'images/feed-1.jpg'),
('photo', 'peterpan', 'Perjalanan Peterpan', 'Dokumentasi Peterpan', 'images/gallery-4.jpg'),
('photo', 'konser', 'Suasana konser', 'Sahabat dalam konser', 'images/feed-2.jpg'),
('photo', 'studio', 'Kegiatan studio', 'NOAH di studio', 'images/feed-3.jpg'),
('photo', 'noah', 'Kegiatan anggota', 'Dokumentasi anggota', 'images/feed-4.jpg'),
('press_kit', NULL, 'Biografi Resmi', 'PDF · Informasi pers', '#materi-pers'),
('press_kit', NULL, 'Paket Foto Resolusi Tinggi', 'ZIP · Informasi pers', '#materi-pers'),
('press_kit', NULL, 'Paket Logo', 'SVG/PNG · Informasi pers', '#materi-pers');
INSERT INTO products (name, slug, category, badge, price, stock, is_limited, image_url, description, created_at) VALUES
('NOAH Logo Tee Black', 'noah-logo-tee-black', 'kaos', 'Baru', 250000, 50, FALSE, 'images/product-1.jpg', 'Kaos katun premium.', now()),
('NOAH Logo Tee White', 'noah-logo-tee-white', 'kaos', NULL, 250000, 50, FALSE, 'images/product-2.jpg', 'Varian putih.', now()-interval '1 day'),
('Bintang di Surga Anniversary Hoodie', 'bintang-di-surga-anniversary-hoodie', 'hoodie', 'Terbatas', 550000, 30, FALSE, 'images/product-3.jpg', 'Edisi album legendaris.', now()-interval '2 day'),
('NOAH Snapback Cap', 'noah-snapback-cap', 'aksesoris', NULL, 185000, 40, FALSE, 'images/product-4.jpg', 'Topi snapback.', now()-interval '3 day'),
('Keterkaitan Keterikatan Vinyl', 'keterkaitan-keterikatan-vinyl', 'musik', NULL, 450000, 20, FALSE, 'images/product-5.jpg', 'Piringan hitam.', now()-interval '4 day'),
('NOAH Tote Bag', 'noah-tote-bag', 'aksesoris', NULL, 120000, 60, FALSE, 'images/product-6.jpg', 'Tas kanvas.', now()-interval '5 day'),
('Box Set Edisi Terbatas', 'box-set-edisi-terbatas', 'musik', NULL, 1500000, 23, TRUE, 'images/limited-1.jpg', 'Box set kolektor.', now()-interval '6 day'),
('Peterpan Legacy Tee', 'peterpan-legacy-tee', 'kaos', NULL, 350000, 12, TRUE, 'images/limited-2.jpg', 'Kaos arsip.', now()-interval '7 day');
INSERT INTO fan_submissions (community_id, name, email, type, content, file_url, status) VALUES
((SELECT id FROM communities WHERE city='Jakarta'), 'Andhika Prasetya', 'andhika@example.com', 'story', 'NOAH mengubah hidup saya.', 'images/fan-1.jpg', 'approved'),
((SELECT id FROM communities WHERE city='Bandung'), 'Siti Rahma', 'siti@example.com', 'story', 'Menemukan keluarga baru di komunitas ini.', 'images/fan-2.jpg', 'approved'),
((SELECT id FROM communities WHERE city='Surabaya'), 'Reza Kurniawan', 'reza@example.com', 'story', 'Musik NOAH menemani setiap perjalanan.', 'images/fan-3.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/artwork-1.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/artwork-2.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/artwork-3.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/artwork-4.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/gallery-2.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/gallery-4.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-1.jpg', 'approved'),
(NULL, 'Sahabat NOAH', 'karya@example.com', 'artwork', 'Karya Sahabat', 'images/album-2.jpg', 'approved');
COMMIT;
