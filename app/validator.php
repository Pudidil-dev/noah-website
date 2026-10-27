<?php
declare(strict_types=1);

/**
 * Validasi input berdasarkan definisi field. Image ditangani upload, password kosong saat edit tidak diubah.
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function validate(array $fields, array $input, bool $isEdit = false): array
{
    $clean = [];
    $errors = [];
    foreach ($fields as $name => $rule) {
        [$has, $value, $error] = validate_field($name, $rule, $input, $isEdit);
        if ($error !== null) {
            $errors[$name] = $error;
        } elseif ($has) {
            $clean[$name] = $value;
        }
    }
    return [$clean, $errors];
}

/** @return array{0: bool, 1: mixed, 2: ?string} */
function validate_field(string $name, array $rule, array $input, bool $isEdit): array
{
    $type = $rule['type'];
    $label = $rule['label'] ?? $name;
    if ($type === 'image') {
        return [false, null, null];
    }
    if ($type === 'bool') {
        return [true, !empty($input[$name]), null];
    }
    if ($type === 'slug') {
        $own = $input[$name] ?? '';
        $source = $input[$rule['from'] ?? ''] ?? '';
        if (!is_string($own) || !is_string($source)) {
            return [false, null, "$label tidak valid."];
        }
        $slug = slugify(trim($own) !== '' ? $own : $source);
        return $slug === '' ? [false, null, "$label tidak boleh kosong."] : [true, $slug, null];
    }
    if (isset($input[$name]) && !is_string($input[$name])) {
        return [false, null, "$label tidak valid."];
    }
    $raw = trim($input[$name] ?? '');
    if ($raw === '') {
        if ($type === 'password' && $isEdit) {
            return [false, null, null];
        }
        if (!empty($rule['required']) || $type === 'password') {
            return [false, null, "$label wajib diisi."];
        }
        return [true, $rule['default'] ?? null, null];
    }
    [$value, $error] = validate_value($type, $raw, $rule, $label);
    return [$error === null, $value, $error];
}

/** @return array{0: mixed, 1: ?string} */
function validate_value(string $type, string $raw, array $rule, string $label): array
{
    return match ($type) {
        'text', 'textarea' => check_length($raw, $rule, $label),
        'email' => filter_var($raw, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($raw) <= 150
            ? [mb_strtolower($raw), null] : [null, "$label tidak valid."],
        'phone' => preg_match('/^\+?[0-9][0-9\s-]{7,18}$/', $raw) === 1 ? [$raw, null] : [null, "$label tidak valid."],
        'link' => check_link($raw, $label),
        'int', 'fk' => check_int($raw, $rule, $label),
        'money' => preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw) === 1
            ? [$raw, null] : [null, "$label harus angka tanpa titik ribuan."],
        'date' => check_datetime($raw, 'Y-m-d', 'Y-m-d', $label),
        'datetime' => check_datetime($raw, 'Y-m-d\TH:i', 'Y-m-d H:i:00', $label),
        'enum' => in_array($raw, $rule['options'], true) ? [$raw, null] : [null, "$label tidak valid."],
        'json' => check_json_links($raw, $label),
        'password' => mb_strlen($raw) >= 8
            ? [password_hash($raw, PASSWORD_DEFAULT), null] : [null, "$label minimal 8 karakter."],
        default => throw new LogicException("Tipe field tidak dikenal: $type"),
    };
}

function check_length(string $raw, array $rule, string $label): array
{
    $max = $rule['max'] ?? null;
    return ($max !== null && mb_strlen($raw) > $max) ? [null, "$label maksimal $max karakter."] : [$raw, null];
}

/** Hanya http(s) atau path lokal tanpa skema (menolak javascript:, data:, dll). */
function check_link(string $raw, string $label): array
{
    $ok = preg_match('#^(https?://\S+|(?!//)[A-Za-z0-9_\-./]+(\#[A-Za-z0-9_\-]+)?|\#[A-Za-z0-9_\-]+)$#', $raw) === 1;
    return $ok && mb_strlen($raw) <= 500 ? [$raw, null] : [null, "$label harus URL http(s) atau path lokal."];
}

function check_int(string $raw, array $rule, string $label): array
{
    if (preg_match('/^-?\d{1,9}$/', $raw) !== 1) {
        return [null, "$label harus angka bulat."];
    }
    $number = (int)$raw;
    if (isset($rule['min']) && $number < $rule['min']) {
        return [null, "$label minimal {$rule['min']}."];
    }
    if (isset($rule['max']) && $number > $rule['max']) {
        return [null, "$label maksimal {$rule['max']}."];
    }
    return [$number, null];
}

function check_datetime(string $raw, string $inFormat, string $outFormat, string $label): array
{
    $date = DateTimeImmutable::createFromFormat('!' . $inFormat, $raw);
    $warnings = DateTimeImmutable::getLastErrors();
    $invalid = $date === false || ($warnings !== false && ($warnings['warning_count'] + $warnings['error_count']) > 0);
    return $invalid ? [null, "$label tidak valid."] : [$date->format($outFormat), null];
}

/** JSON objek {nama: url}. Setiap url wajib http(s). */
function check_json_links(string $raw, string $label): array
{
    $decoded = json_decode($raw);
    if (!$decoded instanceof stdClass) {
        return [null, "$label harus JSON objek, contoh {\"spotify\": \"https://...\"}."];
    }
    foreach ($decoded as $url) {
        if (!is_string($url) || preg_match('#^https?://\S+$#', $url) !== 1) {
            return [null, "$label: setiap link harus diawali http:// atau https://."];
        }
    }
    return [json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), null];
}
