<?php
declare(strict_types=1);

require_once __DIR__ . '/db_connection.php';

function database(): PDO
{
    return db_connection();
}

function run_database_migrations(): void
{
    $migrationFiles = glob(__DIR__ . '/../migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);

    $connection = database();
    foreach ($migrationFiles as $migrationFile) {
        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException('Could not read a database migration.');
        }

        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $connection->exec($statement);
            }
        }
    }
}
