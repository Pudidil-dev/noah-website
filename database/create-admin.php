<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan dari terminal.\n");
}
require dirname(__DIR__) . '/app/db.php';
require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/validator.php';

[, $name, $email, $role] = $argv + [null, '', '', 'super_admin'];
if ($name === '' || $email === '') {
    fwrite(STDERR, "Pemakaian: php database/create-admin.php \"Nama Admin\" email@contoh.com [super_admin|editor]\n");
    exit(1);
}
fwrite(STDOUT, 'Password (min 8 karakter, akan terlihat di layar): ');
$password = trim((string)fgets(STDIN));
[$data, $errors] = validate([
    'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 100],
    'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
    'role' => ['type' => 'enum', 'label' => 'Role', 'required' => true, 'options' => ['super_admin', 'editor']],
    'password_hash' => ['type' => 'password', 'label' => 'Password'],
], ['name' => $name, 'email' => $email, 'role' => $role, 'password_hash' => $password]);
if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
try {
    db()->prepare('INSERT INTO admins (name, email, role, password_hash) VALUES (:name, :email, :role, :password_hash)')->execute($data);
} catch (PDOException $e) {
    if ($e->getCode() !== '23505') {
        throw $e;
    }
    fwrite(STDERR, "Email admin sudah terdaftar.\n");
    exit(1);
}
echo "Admin {$data['email']} berhasil dibuat.\n";
