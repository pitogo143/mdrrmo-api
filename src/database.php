<?php
declare(strict_types=1);

require_once __DIR__ . '/db_connection.php';

const DEFAULT_ALLOWED_ORIGIN = 'https://municipalitycity.hatchable.site';
const ALLOWED_INCIDENT_TYPES = ['flood', 'fire', 'landslide', 'earthquake', 'medical', 'storm', 'other'];

function database(): PDO
{
    return db_connection();
}

function initialize_database(): void
{
    $sql = <<<'SQL'
CREATE EXTENSION IF NOT EXISTS postgis;

CREATE TABLE IF NOT EXISTS incident_reports (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    idempotency_key UUID NOT NULL UNIQUE,
    reporter_name VARCHAR(120) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    incident_type VARCHAR(40) NOT NULL CHECK (
        incident_type IN ('flood', 'fire', 'landslide', 'earthquake', 'medical', 'storm', 'other')
    ),
    description TEXT NOT NULL,
    barangay VARCHAR(120),
    address VARCHAR(250),
    persons_affected INTEGER NOT NULL DEFAULT 0 CHECK (persons_affected >= 0),
    latitude DOUBLE PRECISION NOT NULL CHECK (latitude BETWEEN -90 AND 90),
    longitude DOUBLE PRECISION NOT NULL CHECK (longitude BETWEEN -180 AND 180),
    accuracy_meters NUMERIC(10, 2) NOT NULL CHECK (accuracy_meters > 0),
    location GEOMETRY(Point, 4326) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'received' CHECK (
        status IN ('received', 'in_review', 'resolved')
    ),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS incident_reports_created_at_idx
    ON incident_reports (created_at DESC);

CREATE INDEX IF NOT EXISTS incident_reports_location_gix
    ON incident_reports USING GIST (location);
SQL;

    $connection = database();
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $connection->exec($statement);
        }
    }
}

function configure_http(array $allowedMethods): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Origin');

    $configuredOrigins = getenv('CORS_ALLOWED_ORIGINS');
    $allowedOrigins = $configuredOrigins === false || trim($configuredOrigins) === ''
        ? [DEFAULT_ALLOWED_ORIGIN]
        : array_values(array_filter(array_map('trim', explode(',', $configuredOrigins))));

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        if (!in_array($origin, $allowedOrigins, true)) {
            send_json(403, ['error' => 'origin_not_allowed']);
        }
        header('Access-Control-Allow-Origin: ' . $origin);
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods) . ', OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Idempotency-Key');
    header('Access-Control-Max-Age: 600');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function send_json(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function read_json_body(): array
{
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') {
        send_json(415, ['error' => 'content_type_must_be_application_json']);
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || strlen($rawBody) > 16384) {
        send_json(413, ['error' => 'request_body_too_large']);
    }

    try {
        $payload = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        send_json(400, ['error' => 'invalid_json']);
    }

    if (!is_array($payload)) {
        send_json(400, ['error' => 'json_object_required']);
    }

    return $payload;
}

function request_idempotency_key(): string
{
    $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key)) {
        send_json(400, ['error' => 'valid_idempotency_key_required']);
    }
    return strtolower($key);
}

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
