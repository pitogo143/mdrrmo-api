<?php
declare(strict_types=1);

const ALLOWED_INCIDENT_TYPES = ['flood', 'fire', 'landslide', 'earthquake', 'medical', 'storm', 'other'];

function required_string(array $payload, string $field, int $maxLength): string
{
    $value = $payload[$field] ?? null;
    if (!is_string($value)) {
        send_json(422, ['error' => $field . '_is_required']);
    }

    $value = trim($value);
    if ($value === '' || strlen($value) > $maxLength) {
        send_json(422, ['error' => $field . '_is_invalid']);
    }

    return $value;
}

function optional_string(array $payload, string $field, int $maxLength): ?string
{
    $value = $payload[$field] ?? null;
    if ($value === null) {
        return null;
    }
    if (!is_string($value)) {
        send_json(422, ['error' => $field . '_is_invalid']);
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (strlen($value) > $maxLength) {
        send_json(422, ['error' => $field . '_is_too_long']);
    }

    return $value;
}

function required_coordinate(array $payload, string $field, float $min, float $max): float
{
    $value = $payload[$field] ?? null;
    if (!is_numeric($value)) {
        send_json(422, ['error' => $field . '_is_invalid']);
    }

    $number = (float) $value;
    if (!is_finite($number) || $number < $min || $number > $max) {
        send_json(422, ['error' => $field . '_is_out_of_range']);
    }

    return $number;
}
