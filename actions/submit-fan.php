<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
handle_public_form('cerita',static fn(PDO $pdo,array $input):array => submit_fan($pdo,$input,$_FILES['karya']??null),
    '../sahabat-noah.php#form-cerita','Terima kasih! Kirimanmu akan tampil setelah disetujui admin.');
