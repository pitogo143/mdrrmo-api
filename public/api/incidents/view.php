<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/database.php';

configure_http(['GET']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    send_json(405, ['error' => 'method_not_allowed']);
}

$id = $_GET['id'] ?? '';
if (!is_string($id) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
    send_json(400, ['error' => 'valid_report_id_required']);
}

try {
    $query = database()->prepare(
        'SELECT id, incident_type, status, created_at
         FROM incident_reports
         WHERE id = :id'
    );
    $query->execute([':id' => strtolower($id)]);
    $report = $query->fetch();
    if ($report === false) {
        send_json(404, ['error' => 'report_not_found']);
    }

    send_json(200, ['report' => $report]);
} catch (Throwable $error) {
    error_log('Incident report could not be loaded.');
    send_json(503, ['error' => 'report_service_unavailable']);
}
