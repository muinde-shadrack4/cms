<?php
/* ============================================================
   vehicles.php — Fleet Management Endpoint
   GET → list vehicles
   POST → add vehicle (admin only)
   PUT  → update status (admin only)
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/Vehicle.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Vehicle();

if ($method === 'GET') {
    Auth::requireLogin();
    $vehicles = $model->getAll();
    Auth::respond(['status' => 'success', 'data' => $vehicles]);
}

if ($method === 'POST') {
    Auth::requireRole('admin');
    $body = Auth::getBody();

    foreach (['registration_number','type','capacity'] as $f) {
        if (empty($body[$f])) {
            Auth::respond([
                'status'  => 'error',
                'message' => "Field '{$f}' is required."
            ], 400);
        }
    }

    $body['status'] = $body['status'] ?? 'Available';
    $result         = $model->create($body);

    if ($result['success']) {
        Auth::respond([
            'status'  => 'success',
            'message' => 'Vehicle added successfully.',
            'data'    => ['vehicle_id' => $result['vehicle_id']]
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

if ($method === 'PUT') {
    Auth::requireRole('admin');
    $body      = Auth::getBody();
    $vehicleId = (int)($body['vehicle_id'] ?? 0);
    $status    = $body['status'] ?? '';

    if (!$vehicleId || !$status) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'vehicle_id and status required.'
        ], 400);
    }

    $ok = $model->updateStatus($vehicleId, $status);
    Auth::respond([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Status updated.' : 'Update failed.'
    ]);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>