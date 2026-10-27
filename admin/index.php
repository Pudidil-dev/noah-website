<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
foreach (['auth', 'crud', 'upload', 'fields', 'workflow', 'controller'] as $module) require_once __DIR__ . "/core/$module.php";
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
$resources = require __DIR__ . '/resources.php';
$route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'dashboard';
$action = is_string($_GET['a'] ?? null) ? $_GET['a'] : 'list';
if ($route === 'login') admin_login_page();
if ($route === 'logout') { csrf_require(); logout_admin(); redirect('index.php?r=login'); }
$admin = require_admin();
if ($route === 'dashboard') { admin_render('dashboard', ['title' => 'Dashboard', 'stats' => dashboard_stats(db())]); exit; }
$resource = $resources[$route] ?? null;
if ($resource === null) admin_not_found();
if (!admin_can($admin, $resource['roles'] ?? [])) { http_response_code(403); admin_render('error', ['title' => 'Akses ditolak', 'message' => 'Hanya super admin dapat membuka menu ini.']); exit; }
if (isset($resource['custom'])) { require __DIR__ . '/custom/' . $resource['custom'] . '.php'; exit; }
admin_crud($route, $resource, $action, $admin);
