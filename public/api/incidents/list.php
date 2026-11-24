<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/http.php';
require_once __DIR__ . '/../../../src/database.php';

configure_http(['GET']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    send_json(405, ['error' => 'method_not_allowed']);
}

$limit = filter_var($_GET['limit'] ?? 50, FILTER_VALIDATE_INT);
if ($limit === false || $limit < 1) {
    $limit = 50;
}
$limit = min($limit, 100);

try {
    $query = database()->prepare(
        'SELECT id, incident_type, status, created_at
         FROM incident_reports
         ORDER BY created_at DESC
         LIMIT :limit'
    );
    $query->bindValue(':limit', $limit, PDO::PARAM_INT);
    $query->execute();
    send_json(200, ['reports' => $query->fetchAll()]);
} catch (Throwable $error) {
    error_log('Incident report list could not be loaded.');
    send_json(503, ['error' => 'report_service_unavailable']);
}
