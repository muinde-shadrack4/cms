<?php
/* ============================================================
   parcels.php — Parcel Management Endpoint
   GET  → list parcels / find by tracking
   POST → book new parcel
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/Parcel.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Parcel();

// ── GET PARCELS ───────────────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();

    // Find by tracking number
    $tracking = $_GET['tracking'] ?? null;
    if ($tracking) {
        $parcel = $model->getByTracking(strtoupper($tracking));
        if (!$parcel) {
            Auth::respond([
                'status'  => 'error',
                'message' => 'Parcel not found.'
            ], 404);
        }
        Auth::respond(['status' => 'success', 'data' => $parcel]);
    }

    // Get by customer
    $customerId = $_GET['customer_id'] ?? null;
    if ($customerId) {
        $parcels = $model->getByCustomer((int)$customerId);
        Auth::respond(['status' => 'success', 'data' => $parcels]);
    }

    // Get all with filters
    $filters = [
        'status'      => $_GET['status']      ?? null,
        'from'        => $_GET['from']         ?? null,
        'to'          => $_GET['to']           ?? null,
        'limit'       => $_GET['limit']        ?? null,
        'customer_id' => $_GET['customer_id']  ?? null,
    ];
    $filters = array_filter($filters);
    $parcels = $model->getAll($filters);
    Auth::respond(['status' => 'success', 'data' => $parcels]);
}

// ── CREATE PARCEL ─────────────────────────────────────────
if ($method === 'POST') {
    Auth::requireRole(['admin','customer_service']);
    $body = Auth::getBody();

    $required = ['customer_id','recipient_name','recipient_phone',
                 'recipient_address','weight','zone','service_type'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            Auth::respond([
                'status'  => 'error',
                'message' => "Field '{$field}' is required."
            ], 400);
        }
    }

    $result = $model->create($body);

    if ($result['success']) {
        Auth::respond([
            'status'  => 'success',
            'message' => 'Parcel booked successfully.',
            'data'    => [
                'parcel_id'       => $result['parcel_id'],
                'tracking_number' => $result['tracking_number'],
                'price'           => $result['price']
            ]
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>