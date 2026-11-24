<?php
declare(strict_types=1);

function db_connection(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl === false || $databaseUrl === '') {
        throw new RuntimeException('DATABASE_URL is not configured.');
    }

    $parts = parse_url($databaseUrl);
    if (!is_array($parts) || empty($parts['host']) || empty($parts['path']) || empty($parts['user'])) {
        throw new RuntimeException('DATABASE_URL is invalid.');
    }

    $databaseName = ltrim($parts['path'], '/');
    if ($databaseName === '') {
        throw new RuntimeException('DATABASE_URL is invalid.');
    }

    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $sslMode = $query['sslmode'] ?? 'require';
    if (!in_array($sslMode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
        $sslMode = 'require';
    }

    $host = $parts['host'];
    $port = (int) ($parts['port'] ?? 5432);
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
        $host,
        $port,
        $databaseName,
        $sslMode
    );

    $connection = new PDO(
        $dsn,
        rawurldecode($parts['user']),
        rawurldecode($parts['pass'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}
