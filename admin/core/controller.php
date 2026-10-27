<?php
declare(strict_types=1);

function admin_render(string $view, array $vars = []): void
{
    $admin = array_key_exists('admin', $vars) ? $vars['admin'] : current_admin();
    $resources = $GLOBALS['resources'] ?? [];
    extract($vars, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}
function admin_not_found(): never
{
    http_response_code(404);
    admin_render('error', ['title' => 'Tidak ditemukan', 'message' => 'Halaman atau data tidak ditemukan.']);
    exit;
}
function admin_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function admin_login_page(): never
{
    if (current_admin()) redirect('index.php');
    $email = '';
    $error = null;
    if (admin_post()) {
        csrf_require();
        $email = is_string($_POST['email'] ?? null) ? mb_substr(trim($_POST['email']), 0, 150) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $key = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (throttle_exceeded($key, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_SEC)) {
            $error = 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.';
        } else {
            $found = attempt_login(db(), $email, $password);
            if ($found !== null) { throttle_clear($key); login_admin($found); redirect('index.php'); }
            throttle_record($key, LOGIN_WINDOW_SEC);
            $error = 'Email atau password salah.';
        }
    }
    admin_render('login', compact('email', 'error') + ['title' => 'Masuk', 'admin' => null]);
    exit;
}
function dashboard_stats(PDO $pdo): array
{
    return [
        'orders' => (int)$pdo->query("SELECT COUNT(*) FROM ticket_orders WHERE status='pending'")->fetchColumn(),
        'fans' => (int)$pdo->query("SELECT COUNT(*) FROM fan_submissions WHERE status='pending'")->fetchColumn(),
        'upcoming' => $pdo->query("SELECT id,title,city,start_at FROM events WHERE start_at>=now() AND status='published' ORDER BY start_at LIMIT 3")->fetchAll(),
        'stock' => $pdo->query('SELECT id,name,stock FROM products WHERE stock<=5 ORDER BY stock,name')->fetchAll(),
    ];
}
function admin_fk_maps(PDO $pdo, array $res): array
{
    $maps = [];
    foreach ($res['fields'] as $name => $rule) if ($rule['type'] === 'fk') $maps[$name] = fk_options($pdo, $rule['ref'], $rule['ref_label']);
    return $maps;
}
function admin_crud(string $key, array $res, string $action, array $admin): void
{
    $pdo = db();
    $rawId = $_GET['id'] ?? null;
    $id = is_string($rawId) && ctype_digit($rawId) ? (int)$rawId : null;
    switch ($action) {
        case 'list':
            $q = mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 100);
            $filters = [];
            foreach ($res['filters'] ?? [] as $field) if (is_string($_GET[$field] ?? null)) $filters[$field] = $_GET[$field];
            $page = is_string($_GET['p'] ?? null) && ctype_digit($_GET['p']) ? min(1000000,(int)$_GET['p']) : 1;
            admin_render('list', ['title' => $res['label'], 'key' => $key, 'current' => $key, 'res' => $res,
                'q' => $q, 'filters' => $filters, 'result' => crud_list($pdo, $res, $q, $filters, $page), 'fk' => admin_fk_maps($pdo, $res)]);
            return;
        case 'create': case 'edit':
            if ($action === 'edit' && $id === null) admin_not_found();
            $row = $id === null ? null : crud_find($pdo, $res, $id);
            if ($id !== null && $row === null) admin_not_found();
            $values = $row ?? [];
            $errors = [];
            if (admin_post()) {
                csrf_require();
                [$data, $errors] = validate($res['fields'], $_POST, $id !== null);
                $newFiles = [];
                $cfg = config()['app'];
                foreach ($res['fields'] as $field => $rule) {
                    if ($rule['type'] !== 'image') continue;
                    $manual = $_POST[$field . '__path'] ?? '';
                    [$path, $err] = store_upload($_FILES[$field] ?? [], $res['table'], $cfg['upload_dir'], $cfg['upload_url']);
                    if ($err !== null) $errors[$field] = $err;
                    elseif ($path !== null) { $data[$field] = $path; $newFiles[] = $path; }
                    elseif (is_string($manual) && trim($manual) !== '') {
                        [$clean, $linkError] = check_link(trim($manual), $rule['label'] ?? $field);
                        if ($linkError !== null) $errors[$field] = $linkError;
                        else $data[$field] = $clean;
                    } elseif (!empty($rule['required']) && empty($row[$field])) $errors[$field] = ($rule['label'] ?? $field) . ' wajib diisi.';
                }
                if ($errors === []) {
                    try {
                        if ($key === 'posts' && ($data['status'] ?? null) === 'published'
                            && empty($data['published_at']) && empty($row['published_at'])) {
                            $data['published_at'] = date('Y-m-d H:i:s');
                        }
                        if ($id === null) crud_insert($pdo, $res, isset($res['owner']) ? $data + [$res['owner'] => $admin['id']] : $data);
                        else crud_update($pdo, $res, $id, $data);
                        if ($row) foreach ($res['fields'] as $field => $rule) if ($rule['type'] === 'image' && isset($data[$field]) && $data[$field] !== ($row[$field] ?? null)) delete_upload($row[$field] ?? null, $cfg['upload_dir'], $cfg['upload_url']);
                        flash('ok', $res['label'] . ' berhasil disimpan.');
                        redirect('index.php?r=' . rawurlencode($key));
                    } catch (CrudDuplicateError | CrudConstraintError $e) { $errors['_form'] = $e->getMessage(); }
                    catch (Throwable $e) {
                        foreach ($newFiles as $file) delete_upload($file, $cfg['upload_dir'], $cfg['upload_url']);
                        throw $e;
                    }
                }
                foreach ($newFiles as $file) delete_upload($file, $cfg['upload_dir'], $cfg['upload_url']);
                $values = $_POST + ($row ?? []);
                foreach ($res['fields'] as $field => $rule) if ($rule['type'] === 'bool') $values[$field] = !empty($_POST[$field]);
            }
            admin_render('form', ['title' => ($id === null ? 'Tambah ' : 'Edit ') . $res['label'], 'key' => $key, 'current' => $key,
                'res' => $res, 'row' => $row, 'values' => $values, 'errors' => $errors, 'fk' => admin_fk_maps($pdo, $res)]);
            return;
        case 'delete':
            if ($id === null) admin_not_found();
            csrf_require();
            $row = crud_find($pdo, $res, $id) ?? admin_not_found();
            if ($key === 'admins' && $id === $admin['id']) { flash('error', 'Tidak bisa menghapus akun sendiri.'); redirect('index.php?r=admins'); }
            try {
                crud_delete($pdo, $res, $id);
                $cfg = config()['app'];
                foreach ($res['fields'] as $field => $rule) if ($rule['type'] === 'image') delete_upload($row[$field] ?? null, $cfg['upload_dir'], $cfg['upload_url']);
                flash('ok', 'Data berhasil dihapus.');
            } catch (CrudConstraintError $e) { flash('error', $e->getMessage()); }
            redirect('index.php?r=' . rawurlencode($key));
        default: admin_not_found();
    }
}
