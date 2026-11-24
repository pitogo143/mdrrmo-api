<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

try {
    run_database_migrations();
    fwrite(STDOUT, "Database migrations completed.\n");
} catch (Throwable $error) {
    error_log('Database migration failed. Check DATABASE_URL, PostGIS availability, and migration permissions.');
    fwrite(STDERR, "Database migration failed. Check the Render service configuration.\n");
    exit(1);
}
