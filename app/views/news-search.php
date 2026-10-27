<h2>Hasil pencarian “<?= e($q) ?>”</h2>
<?php foreach (['posts' => 'Berita', 'albums' => 'Album', 'events' => 'Event'] as $kind => $label): ?>
    <h3><?= e($label) ?></h3>
    <ul><?php foreach ($found[$kind] as $item): ?>
            <li><a
                    href="<?= e($kind === 'posts' ? 'berita-media.php?artikel=' . rawurlencode($item['slug']) : ($kind === 'albums' ? 'diskografi.php#album-' . rawurlencode($item['slug']) : 'tur-event.php#konser-mendatang')) ?>"><?= e($item['title']) ?></a>
            </li><?php endforeach; ?><?php if (!$found[$kind]): ?>
            <li>Belum ada hasil.</li><?php endif; ?>
    </ul><?php endforeach; ?>