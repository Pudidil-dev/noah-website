<?php
declare(strict_types=1);

final class CrudConstraintError extends RuntimeException {}
final class CrudDuplicateError extends RuntimeException {}

const CRUD_PER_PAGE = 20;

function ident(string $name): string
{
    if (preg_match('/^[a-z_][a-z0-9_]*$/', $name) !== 1) {
        throw new InvalidArgumentException('Nama kolom/tabel tidak valid.');
    }
    return '"' . $name . '"';
}

function crud_columns(array $res, array $data): array
{
    $allowed = $res['fields'] + (isset($res['owner']) ? [$res['owner'] => true] : []);
    return array_intersect_key($data, $allowed);
}

function crud_run(PDO $pdo, string $sql, array $params = []): PDOStatement
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_map('db_param', $params));
        return $stmt;
    } catch (PDOException $e) {
        throw match ((string)$e->getCode()) {
            '23503' => new CrudConstraintError('Data masih dipakai oleh data lain.', 0, $e),
            '23505' => new CrudDuplicateError('Nilai unik sudah digunakan (periksa slug atau email).', 0, $e),
            default => $e,
        };
    }
}

function crud_where(array $res, string $q, array $filters): array
{
    $parts = [];
    $params = [];
    if ($q !== '' && !empty($res['search'])) {
        $likes = [];
        foreach (array_values($res['search']) as $i => $col) {
            $likes[] = ident($col) . "::text ILIKE :q$i ESCAPE '!'";
            $params["q$i"] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        }
        $parts[] = '(' . implode(' OR ', $likes) . ')';
    }
    foreach ($res['filters'] ?? [] as $col) {
        $value = $filters[$col] ?? '';
        if (is_string($value) && $value !== '') {
            $parts[] = ident($col) . "::text = :f_$col";
            $params["f_$col"] = $value;
        }
    }
    return [$parts === [] ? '' : 'WHERE ' . implode(' AND ', $parts), $params];
}

function crud_list(PDO $pdo, array $res, string $q = '', array $filters = [], int $page = 1): array
{
    [$where, $params] = crud_where($res, $q, $filters);
    $table = ident($res['table']);
    $total = (int)crud_run($pdo, "SELECT COUNT(*) FROM $table $where", $params)->fetchColumn();
    $pages = max(1, (int)ceil($total / CRUD_PER_PAGE));
    $current = min(max(1, $page), $pages);
    $order = $res['order'] ?? 'id DESC';
    if (preg_match('/^[a-z_][a-z0-9_]*(?:\s+(?:ASC|DESC))?(?:\s+NULLS\s+(?:FIRST|LAST))?(?:,\s*[a-z_][a-z0-9_]*(?:\s+(?:ASC|DESC))?(?:\s+NULLS\s+(?:FIRST|LAST))?)*$/i', $order) !== 1) {
        throw new InvalidArgumentException('Urutan resource tidak valid.');
    }
    $stmt = $pdo->prepare("SELECT * FROM $table $where ORDER BY $order LIMIT :_limit OFFSET :_offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('_limit', CRUD_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue('_offset', ($current - 1) * CRUD_PER_PAGE, PDO::PARAM_INT);
    $stmt->execute();
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $current, 'pages' => $pages];
}

function crud_find(PDO $pdo, array $res, int $id): ?array
{
    $row = crud_run($pdo, 'SELECT * FROM ' . ident($res['table']) . ' WHERE id = :id', ['id' => $id])->fetch();
    return $row === false ? null : $row;
}

function crud_insert(PDO $pdo, array $res, array $data): int
{
    $cols = crud_columns($res, $data);
    if ($cols === []) {
        throw new InvalidArgumentException('Tidak ada kolom yang bisa disimpan.');
    }
    $names = array_keys($cols);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING id', ident($res['table']),
        implode(', ', array_map('ident', $names)), implode(', ', array_map(fn ($n) => ":$n", $names)));
    return (int)crud_run($pdo, $sql, $cols)->fetchColumn();
}

function crud_update(PDO $pdo, array $res, int $id, array $data): void
{
    $cols = crud_columns($res, $data);
    if ($cols === []) {
        return;
    }
    $set = implode(', ', array_map(fn ($n) => ident($n) . " = :$n", array_keys($cols)));
    crud_run($pdo, 'UPDATE ' . ident($res['table']) . " SET $set WHERE id = :_id", $cols + ['_id' => $id]);
}

function crud_delete(PDO $pdo, array $res, int $id): void
{
    crud_run($pdo, 'DELETE FROM ' . ident($res['table']) . ' WHERE id = :id', ['id' => $id]);
}

function fk_options(PDO $pdo, string $table, string $labelCol): array
{
    $label = ident($labelCol);
    $rows = $pdo->query('SELECT id, ' . $label . ' AS label FROM ' . ident($table) . ' ORDER BY ' . $label)->fetchAll();
    return array_combine(array_map('intval', array_column($rows, 'id')), array_column($rows, 'label'));
}
