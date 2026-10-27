<?php foreach ($rows as $row): ?>
    <div class="timeline-item"><span class="tahun"><?= e((string) $row['year']) ?></span>
        <h3 class="berita-judul"><?= e($row['title']) ?></h3>
        <p class="paragraf-kecil"><?= e($row['description'] ?? '') ?></p>
    </div><?php endforeach; ?>