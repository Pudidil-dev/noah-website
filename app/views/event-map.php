<?php if ($url !== null): ?>
<iframe title="Peta lokasi konser" src="<?= e($url) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
<?php else: ?>
<p class="paragraf-kecil">Peta lokasi belum tersedia.</p>
<?php endif; ?>
