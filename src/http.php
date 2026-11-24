<?php
declare(strict_types=1);

const DEFAULT_ALLOWED_ORIGIN = 'https://municipalitycity.hatchable.site';

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
