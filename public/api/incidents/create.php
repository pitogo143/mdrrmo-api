<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/http.php';
require_once __DIR__ . '/../../../src/database.php';
require_once __DIR__ . '/../../../src/incident_validation.php';

configure_http(['POST']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_json(405, ['error' => 'method_not_allowed']);
}

$payload = read_json_body();
$idempotencyKey = request_idempotency_key();

$reporterName = required_string($payload, 'reporter_name', 120);
$contactNumber = required_string($payload, 'contact_number', 40);
$contactDigits = preg_replace('/\D/', '', $contactNumber) ?? '';
if (strlen($contactDigits) < 7 || strlen($contactDigits) > 20) {
    send_json(422, ['error' => 'contact_number_is_invalid']);
}

$incidentType = required_string($payload, 'incident_type', 40);
if (!in_array($incidentType, ALLOWED_INCIDENT_TYPES, true)) {
    send_json(422, ['error' => 'incident_type_is_invalid']);
}

$description = required_string($payload, 'description', 3000);
$latitude = required_coordinate($payload, 'latitude', -90, 90);
$longitude = required_coordinate($payload, 'longitude', -180, 180);
$barangay = optional_string($payload, 'barangay', 120);
$address = optional_string($payload, 'address', 250);

$personsAffected = filter_var($payload['persons_affected'] ?? null, FILTER_VALIDATE_INT);
if ($personsAffected === false || $personsAffected < 0 || $personsAffected > 1000000) {
    send_json(422, ['error' => 'persons_affected_is_invalid']);
}

$accuracyMeters = $payload['accuracy_meters'] ?? null;
if (!is_numeric($accuracyMeters) || !is_finite((float) $accuracyMeters) || (float) $accuracyMeters <= 0 || (float) $accuracyMeters > 100000) {
    send_json(422, ['error' => 'accuracy_meters_is_invalid']);
}

try {
    $connection = database();
    $insert = $connection->prepare(
        "INSERT INTO incident_reports (
            idempotency_key, reporter_name, contact_number, incident_type, description,
            barangay, address, persons_affected, latitude, longitude, accuracy_meters, location
        ) VALUES (
            :idempotency_key, :reporter_name, :contact_number, :incident_type, :description,
            :barangay, :address, :persons_affected, :latitude, :longitude, :accuracy_meters,
            ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)
        )
        ON CONFLICT (idempotency_key) DO NOTHING
        RETURNING id, status, created_at"
    );
    $insert->execute([
        ':idempotency_key' => $idempotencyKey,
        ':reporter_name' => $reporterName,
        ':contact_number' => $contactNumber,
        ':incident_type' => $incidentType,
        ':description' => $description,
        ':barangay' => $barangay,
        ':address' => $address,
        ':persons_affected' => $personsAffected,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':accuracy_meters' => (float) $accuracyMeters,
    ]);

    $report = $insert->fetch();
    $created = $report !== false;
    if (!$created) {
        $find = $connection->prepare(
            'SELECT id, status, created_at FROM incident_reports WHERE idempotency_key = :idempotency_key'
        );
        $find->execute([':idempotency_key' => $idempotencyKey]);
        $report = $find->fetch();
    }

    if ($report === false) {
        send_json(503, ['error' => 'report_could_not_be_saved']);
    }

    send_json($created ? 201 : 200, [
        'report' => [
            'id' => $report['id'],
            'status' => $report['status'],
            'created_at' => $report['created_at'],
        ],
        'duplicate' => !$created,
    ]);
} catch (Throwable $error) {
    error_log('Incident report could not be stored.');
    send_json(503, ['error' => 'report_service_unavailable']);
}
