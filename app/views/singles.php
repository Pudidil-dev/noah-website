<?php foreach($rows as $t): ?><tr><td><?= e($t['title']) ?></td><td><?= (int)$t['single_year'] ?></td><td><?= e($t['album']??'Single') ?></td></tr><?php endforeach; ?>
