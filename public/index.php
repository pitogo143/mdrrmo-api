<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/http.php';

send_json(200, [
    'name' => 'MDRRMO Incident Reporting API',
    'status' => 'prototype',
    'notice' => 'Do not use this prototype for real emergencies or real personal information.',
    'endpoints' => [
        'POST /api/incidents/create.php',
        'GET /api/incidents/list.php',
        'GET /api/incidents/view.php?id={report-id}',
    ],
]);
