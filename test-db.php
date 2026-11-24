<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/src/database.php';

try {
    database()->query('SELECT 1')->fetchColumn();
    fwrite(STDOUT, "Database connection successful.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Database connection failed. Check DATABASE_URL and the PHP PDO_PGSQL driver.\n");
    exit(1);
}
