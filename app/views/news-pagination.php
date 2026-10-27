<?php for ($i = 1; $i <= $pages; $i++): ?><a class="page-btn <?= $i === $current ? 'aktif' : '' ?>"
        href="berita-media.php?p=<?= $i ?>#berita-terbaru"><?= $i ?></a><?php endfor; ?>