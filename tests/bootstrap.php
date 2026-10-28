<?php
declare(strict_types=1);

putenv('NOAH_DB_NAME=noah_test');
putenv('NOAH_THROTTLE_DIR=' . sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'noah_throttle_test');
$_SESSION = [];

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$files = [
    'app/db.php', 'app/helpers.php', 'app/csrf.php', 'app/validator.php', 'app/throttle.php',
    'app/queries.php', 'app/fan-upload.php', 'app/forms.php',
    'admin/core/crud.php', 'admin/core/upload.php', 'admin/core/auth.php', 'admin/core/workflow.php',
    'tests/DbTestCase.php',
];
foreach ($files as $file) {
    if (is_file($root . '/' . $file)) {
        require_once $root . '/' . $file;
    }
}
