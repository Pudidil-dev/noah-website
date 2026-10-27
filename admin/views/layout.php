<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= e($title ?? 'Panel') ?> · NOAH Admin</title><link rel="stylesheet" href="admin.css"></head>
<body>
<?php if ($admin): ?><aside class="sidebar"><a class="brand" href="index.php">NOAH / ADMIN</a><nav><a href="index.php">Dashboard</a><?php foreach ($resources as $route => $resource): if (admin_can($admin, $resource['roles'] ?? [])): ?><a href="index.php?r=<?= e($route) ?>"><?= e($resource['label']) ?></a><?php endif; endforeach; ?></nav><a href="../index.php">Lihat situs</a><form method="post" action="index.php?r=logout"><?= csrf_field() ?><button>Keluar (<?= e($admin['name']) ?>)</button></form></aside><?php endif; ?>
<main class="konten <?= $admin ? '' : 'polos' ?>"><?php if ($msg = flash('ok')): ?><p class="flash ok"><?= e($msg) ?></p><?php endif; ?><?php if ($msg = flash('error')): ?><p class="flash error"><?= e($msg) ?></p><?php endif; ?><?= $content ?></main>
</body></html>
