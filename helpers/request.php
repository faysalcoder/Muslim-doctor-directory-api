<?php
declare(strict_types=1);

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function str_value(mixed $value, string $default = ''): string
{
    if ($value === null) return $default;
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_array($value)) return $default;
    return trim((string)$value);
}

function int_value(mixed $value, ?int $default = null): ?int
{
    if ($value === null || $value === '') return $default;
    if (is_bool($value)) return $value ? 1 : 0;
    return (int)$value;
}

function bool_value(mixed $value): int
{
    if (is_bool($value)) return $value ? 1 : 0;
    if (is_numeric($value)) return ((int)$value) ? 1 : 0;
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1','true','yes','on'], true) ? 1 : 0;
}
