<?php foreach ($rows as $item): ?>
  <?php if (is_string($item['file_url'] ?? null) && preg_match('/\.mp3$/i', $item['file_url'])): ?>
    <article class="card card-border-merah">
      <h3 class="berita-judul"><?= e($item['name']) ?></h3>
      <p class="paragraf-kecil"><?= e($item['city']??'Sahabat NOAH') ?> · <?= e($item['content']) ?></p>
      <audio controls preload="none" src="<?= e($item['file_url']) ?>">Browser tidak mendukung pemutar audio.</audio>
    </article>
  <?php endif; ?>
<?php endforeach; ?>
