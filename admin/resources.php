<?php
declare(strict_types=1);

if (!defined('STATUS_OPTIONS')) {
    define('STATUS_OPTIONS', ['draft', 'published', 'archived']);
}

return [
    'members' => [
        'label' => 'Anggota', 'table' => 'members', 'list' => ['name', 'position', 'sort_order', 'is_active'],
        'search' => ['name', 'position'], 'order' => 'sort_order, id',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
            'position' => ['type' => 'text', 'label' => 'Posisi', 'required' => true, 'max' => 50],
            'bio' => ['type' => 'textarea', 'label' => 'Biografi'],
            'photo_url' => ['type' => 'image', 'label' => 'Foto'],
            'sort_order' => ['type' => 'int', 'label' => 'Urutan', 'min' => 0, 'default' => 0],
            'is_active' => ['type' => 'bool', 'label' => 'Aktif'],
        ],
    ],
    'milestones' => [
        'label' => 'Sejarah & Penghargaan', 'table' => 'milestones', 'list' => ['type', 'year', 'badge', 'title'],
        'search' => ['title'], 'filters' => ['type'], 'order' => 'type, sort_order, id',
        'fields' => [
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['history', 'award']],
            'year' => ['type' => 'int', 'label' => 'Tahun', 'min' => 1990, 'max' => 2100],
            'badge' => ['type' => 'text', 'label' => 'Badge', 'max' => 20],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'image_url' => ['type' => 'image', 'label' => 'Gambar'],
            'sort_order' => ['type' => 'int', 'label' => 'Urutan', 'min' => 0, 'default' => 0],
        ],
    ],
    'albums' => [
        'label' => 'Album', 'table' => 'albums', 'list' => ['title', 'type', 'era', 'release_date', 'is_featured'],
        'search' => ['title'], 'filters' => ['era', 'type'], 'order' => 'release_date DESC NULLS LAST, id',
        'links' => ['Lagu' => 'tracks&album_id={id}'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'type' => ['type' => 'enum', 'label' => 'Tipe', 'required' => true, 'options' => ['studio', 'kompilasi', 'soundtrack']],
            'era' => ['type' => 'enum', 'label' => 'Era', 'required' => true, 'options' => ['peterpan', 'noah']],
            'release_date' => ['type' => 'date', 'label' => 'Tanggal rilis'],
            'total_tracks' => ['type' => 'int', 'label' => 'Jumlah lagu', 'min' => 1],
            'cover_url' => ['type' => 'image', 'label' => 'Cover'],
            'is_featured' => ['type' => 'bool', 'label' => 'Album pilihan'],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'streaming_links' => ['type' => 'json', 'label' => 'Link streaming (JSON)', 'default' => '{}'],
        ],
    ],
    'tracks' => [
        'label' => 'Lagu', 'table' => 'tracks', 'list' => ['album_id', 'track_no', 'title', 'duration_sec', 'single_year'],
        'search' => ['title'], 'filters' => ['album_id'], 'order' => 'album_id NULLS LAST, track_no NULLS LAST, title',
        'fields' => [
            'album_id' => ['type' => 'fk', 'label' => 'Album', 'ref' => 'albums', 'ref_label' => 'title'],
            'track_no' => ['type' => 'int', 'label' => 'Nomor lagu', 'min' => 1],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'duration_sec' => ['type' => 'int', 'label' => 'Durasi (detik)', 'min' => 1],
            'single_year' => ['type' => 'int', 'label' => 'Tahun single', 'min' => 1990, 'max' => 2100],
        ],
    ],
    'communities' => [
        'label' => 'Komunitas', 'table' => 'communities', 'list' => ['city', 'name', 'leader_name', 'member_count'],
        'search' => ['city', 'name'], 'order' => 'member_count DESC, city',
        'fields' => [
            'city' => ['type' => 'text', 'label' => 'Kota', 'required' => true, 'max' => 80],
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 120],
            'leader_name' => ['type' => 'text', 'label' => 'Koordinator', 'max' => 100],
            'contact_url' => ['type' => 'link', 'label' => 'Link kontak'],
            'member_count' => ['type' => 'int', 'label' => 'Jumlah anggota', 'min' => 0, 'default' => 0],
        ],
    ],
    'events' => [
        'label' => 'Event', 'table' => 'events', 'list' => ['title', 'type', 'city', 'start_at', 'status'],
        'search' => ['title', 'city', 'venue'], 'filters' => ['type', 'status'], 'order' => 'start_at DESC',
        'links' => ['Setlist' => 'setlist&event_id={id}', 'Pesanan' => 'ticket_orders&event_id={id}'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['concert', 'community']],
            'community_id' => ['type' => 'fk', 'label' => 'Komunitas', 'ref' => 'communities', 'ref_label' => 'city'],
            'badge' => ['type' => 'text', 'label' => 'Badge', 'max' => 40],
            'city' => ['type' => 'text', 'label' => 'Kota', 'required' => true, 'max' => 80],
            'venue' => ['type' => 'text', 'label' => 'Lokasi', 'max' => 150],
            'start_at' => ['type' => 'datetime', 'label' => 'Waktu mulai', 'required' => true],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi'],
            'maps_url' => ['type' => 'link', 'label' => 'Link peta'],
            'poster_url' => ['type' => 'image', 'label' => 'Poster'],
            'ticket_price' => ['type' => 'money', 'label' => 'Harga tiket'],
            'quota' => ['type' => 'int', 'label' => 'Kuota', 'min' => 0],
            'status' => ['type' => 'enum', 'label' => 'Status', 'required' => true, 'options' => STATUS_OPTIONS],
        ],
    ],
    'posts' => [
        'label' => 'Berita', 'table' => 'posts', 'owner' => 'admin_id',
        'list' => ['title', 'category', 'tag', 'status', 'published_at'],
        'search' => ['title', 'excerpt'], 'filters' => ['category', 'status'], 'order' => 'published_at DESC NULLS FIRST',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 200],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'title'],
            'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['news', 'behind_scenes']],
            'tag' => ['type' => 'text', 'label' => 'Tag', 'max' => 30],
            'is_featured' => ['type' => 'bool', 'label' => 'Headline'],
            'excerpt' => ['type' => 'textarea', 'label' => 'Ringkasan', 'max' => 300],
            'body' => ['type' => 'textarea', 'label' => 'Isi berita', 'required' => true],
            'cover_url' => ['type' => 'image', 'label' => 'Gambar sampul'],
            'status' => ['type' => 'enum', 'label' => 'Status', 'required' => true, 'options' => STATUS_OPTIONS],
            'published_at' => ['type' => 'datetime', 'label' => 'Tanggal terbit'],
        ],
    ],
    'media' => [
        'label' => 'Galeri & Materi Pers', 'table' => 'media', 'list' => ['type', 'tag', 'title', 'caption'],
        'search' => ['title', 'caption'], 'filters' => ['type'], 'order' => 'type, created_at',
        'fields' => [
            'type' => ['type' => 'enum', 'label' => 'Jenis', 'required' => true, 'options' => ['photo', 'video', 'press_kit']],
            'tag' => ['type' => 'enum', 'label' => 'Filter', 'options' => ['peterpan', 'noah', 'konser', 'studio']],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true, 'max' => 150],
            'caption' => ['type' => 'text', 'label' => 'Keterangan', 'max' => 255],
            'file_url' => ['type' => 'image', 'label' => 'File / URL', 'required' => true],
            'post_id' => ['type' => 'fk', 'label' => 'Berita terkait', 'ref' => 'posts', 'ref_label' => 'title'],
        ],
    ],
    'products' => [
        'label' => 'Produk', 'table' => 'products', 'list' => ['name', 'category', 'price', 'stock', 'is_limited'],
        'search' => ['name'], 'filters' => ['category'], 'order' => 'created_at DESC',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 150],
            'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'name'],
            'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['kaos', 'hoodie', 'aksesoris', 'musik']],
            'badge' => ['type' => 'enum', 'label' => 'Badge', 'options' => ['Baru', 'Terbatas']],
            'price' => ['type' => 'money', 'label' => 'Harga', 'required' => true],
            'stock' => ['type' => 'int', 'label' => 'Stok', 'min' => 0, 'default' => 0],
            'is_limited' => ['type' => 'bool', 'label' => 'Edisi terbatas'],
            'image_url' => ['type' => 'image', 'label' => 'Gambar'],
            'description' => ['type' => 'textarea', 'label' => 'Deskripsi', 'max' => 500],
        ],
    ],
    'subscribers' => [
        'label' => 'Subscriber', 'table' => 'subscribers', 'list' => ['email', 'is_active', 'created_at'],
        'search' => ['email'], 'order' => 'created_at DESC',
        'fields' => [
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'is_active' => ['type' => 'bool', 'label' => 'Aktif'],
        ],
    ],
    'admins' => [
        'label' => 'Admin', 'table' => 'admins', 'roles' => ['super_admin'],
        'list' => ['name', 'email', 'role', 'created_at'], 'search' => ['name', 'email'], 'order' => 'id',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'role' => ['type' => 'enum', 'label' => 'Role', 'required' => true, 'options' => ['editor', 'super_admin']],
            'password_hash' => ['type' => 'password', 'label' => 'Password'],
        ],
    ],
    'ticket_orders' => ['label' => 'Pesanan Tiket', 'custom' => 'ticket_orders'],
    'fan_submissions' => ['label' => 'Kiriman Fans', 'custom' => 'fan_submissions'],
    'setlist' => ['label' => 'Setlist', 'custom' => 'setlist'],
];
