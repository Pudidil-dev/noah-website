<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SEC = 900;
const SESSION_IDLE_SEC = 7200;

function attempt_login(PDO $pdo, string $email, string $password): ?array
{
    static $dummyHash = null;
    $row = fetch_one($pdo, 'SELECT id, name, email, role, password_hash FROM admins WHERE lower(email) = :email', [
        'email' => mb_strtolower(trim($email)),
    ]);
    $dummyHash ??= password_hash('dummy-password', PASSWORD_DEFAULT);
    if (!password_verify($password, $row['password_hash'] ?? $dummyHash) || $row === null) {
        return null;
    }
    return ['id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];
}

function login_admin(array $admin): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['admin'] = $admin;
    $_SESSION['admin_seen'] = time();
}

function logout_admin(): void
{
    unset($_SESSION['admin'], $_SESSION['admin_seen']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function current_admin(?int $now = null): ?array
{
    $time = $now ?? time();
    $admin = $_SESSION['admin'] ?? null;
    if (!is_array($admin)) {
        return null;
    }
    if ($time - (int)($_SESSION['admin_seen'] ?? 0) > SESSION_IDLE_SEC) {
        logout_admin();
        return null;
    }
    $_SESSION['admin_seen'] = $time;
    return $admin;
}

function require_admin(): array
{
    return current_admin() ?? redirect('index.php?r=login');
}

function admin_can(array $admin, array $roles): bool
{
    return $roles === [] || in_array($admin['role'] ?? '', $roles, true);
}
