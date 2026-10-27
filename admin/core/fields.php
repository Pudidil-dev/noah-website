<?php
declare(strict_types=1);

function admin_asset(string $path): string
{
    return preg_match('#^https?://#', $path) ? $path : '../' . ltrim($path, '/');
}

function render_field(string $name, array $rule, mixed $value, array $fk = []): string
{
    $label = e($rule['label'] ?? $name);
    $id = 'field_' . e($name);
    $n = e($name);
    $type = $rule['type'];
    $required = !empty($rule['required']) && !in_array($type, ['image', 'slug'], true) ? ' required' : '';
    $v = e(is_scalar($value) && !is_bool($value) ? (string)$value : '');
    $html = '<label for="' . $id . '">' . $label . '</label>';
    if ($type === 'bool') return $html . '<input type="checkbox" id="' . $id . '" name="' . $n . '" value="1"' . (db_bool($value) ? ' checked' : '') . '>';
    if ($type === 'textarea' || $type === 'json') return $html . '<textarea id="' . $id . '" name="' . $n . '" rows="5"' . $required . '>' . $v . '</textarea>';
    if ($type === 'enum' || $type === 'fk') {
        $options = $type === 'enum' ? array_combine($rule['options'], $rule['options']) : $fk;
        $html .= '<select id="' . $id . '" name="' . $n . '"' . $required . '>';
        if (empty($rule['required'])) $html .= '<option value="">— Kosong —</option>';
        foreach ($options as $k => $text) $html .= '<option value="' . e((string)$k) . '"' . ((string)$k === (string)$value ? ' selected' : '') . '>' . e((string)$text) . '</option>';
        return $html . '</select>';
    }
    if ($type === 'image') {
        if ($v !== '' && !str_starts_with((string)$value, '#') && ($rule['media'] ?? '') !== 'document') {
            $html .= '<img class="preview" src="' . e(admin_asset((string)$value)) . '" alt="">';
        }
        $html .= '<input type="file" id="' . $id . '" name="' . $n . '" accept="image/jpeg,image/png,image/webp">';
        return $html . '<input name="' . $n . '__path" value="' . $v . '" placeholder="atau path/URL gambar">';
    }
    $inputType = match ($type) {'int','money' => 'number', 'email' => 'email', 'date' => 'date', 'datetime' => 'datetime-local', 'password' => 'password', default => 'text'};
    if ($type === 'datetime' && $v !== '') {
        try {
            $v = e((new DateTimeImmutable((string)$value))->format('Y-m-d\TH:i'));
        } catch (Exception) {
            $v = '';
        }
    }
    if ($type === 'password') $v = '';
    $step = $type === 'money' ? ' step="0.01" min="0"' : ($type === 'int' ? ' step="1"' : '');
    return $html . '<input type="' . $inputType . '" id="' . $id . '" name="' . $n . '" value="' . $v . '"' . $required . $step . '>';
}

function display_value(array $rule, mixed $value, array $fk = []): string
{
    if ($value === null || $value === '') return '—';
    return match ($rule['type']) {
        'bool' => db_bool($value) ? 'Ya' : 'Tidak',
        'money' => rupiah((string)$value),
        'fk' => $fk[(int)$value] ?? '#' . $value,
        'datetime' => (new DateTimeImmutable((string)$value))->format('d/m/Y H:i'),
        default => mb_strimwidth((string)$value, 0, 80, '…'),
    };
}
