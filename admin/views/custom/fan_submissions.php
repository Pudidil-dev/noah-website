<h1>Kiriman Fans</h1>
<nav class="cari">
  <?php foreach (REVIEW_STATUSES as $category): ?>
    <a href="index.php?r=fan_submissions&amp;status=<?= e($category) ?>"><?= e(ucfirst($category)) ?></a>
  <?php endforeach; ?>
</nav>
<?php foreach ($items as $item): ?>
  <article class="kartu">
    <strong><?= e($item['name']) ?></strong> · <?= e($item['email']) ?> · <?= e($item['type']) ?>
    <?php if ($item['city']): ?> · <?= e($item['city']) ?><?php endif; ?>
    <p><?= nl2br(e($item['content'])) ?></p>
    <?php if ($item['file_url']): ?>
      <?php if (preg_match('/\.mp3$/i', $item['file_url'])): ?>
        <audio controls preload="none" src="<?= e(admin_asset($item['file_url'])) ?>">Browser tidak mendukung audio.</audio>
      <?php else: ?>
        <img class="preview" src="<?= e(admin_asset($item['file_url'])) ?>" alt="Berkas kiriman <?= e($item['name']) ?>">
      <?php endif; ?>
    <?php endif; ?>
    <div class="aksi">
      <?php foreach (['approved'=>'Setujui','rejected'=>'Tolak','delete'=>'Hapus'] as $do=>$label): ?>
        <?php if ($do!==$status): ?>
          <form method="post"<?= $do==='delete'?' onsubmit="return confirm(\'Hapus kiriman ini?\')"':'' ?>>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <input type="hidden" name="do" value="<?= e($do) ?>">
            <button class="<?= $do==='approved'?'btn':'hapus' ?>"><?= e($label) ?></button>
          </form>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php if (!$items): ?><p>Belum ada kiriman dalam kategori ini.</p><?php endif; ?>
