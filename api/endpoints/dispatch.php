<?php
/* ============================================================
   dispatch.php — Dispatch Assignment Endpoint
   GET  ?status=pending  → parcels awaiting assignment (FIFO)
   GET  ?driver_id=      → driver's assigned parcels
   GET  ?recent=true     → recent assignments
   POST → assign parcel to driver
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/Dispatch.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Dispatch();

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();

    // Pending parcels (FIFO queue)
    if (isset($_GET['status']) && $_GET['status'] === 'pending') {
        $parcels = $model->getPending();
        Auth::respond(['status' => 'success', 'data' => $parcels]);
    }

    // Driver's parcels
    if (isset($_GET['driver_id'])) {
        $parcels = $model->getByDriver((int)$_GET['driver_id']);
        Auth::respond(['status' => 'success', 'data' => $parcels]);
    }

    // Recent assignments
    if (isset($_GET['recent'])) {
        $assignments = $model->getRecent();
        Auth::respond(['status' => 'success', 'data' => $assignments]);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => 'Specify status=pending, driver_id, or recent=true'
    ], 400);
}

// ── ASSIGN ────────────────────────────────────────────────
if ($method === 'POST') {
    $session = Auth::requireRole(['admin','dispatch']);
    $body    = Auth::getBody();

    $parcelId = (int)($body['parcel_id'] ?? 0);
    $driverId = (int)($body['driver_id'] ?? 0);
    $notes    = $body['notes'] ?? '';

    if (!$parcelId || !$driverId) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'parcel_id and driver_id are required.'
        ], 400);
    }

    $result = $model->assign(
        $parcelId,
        $driverId,
        (int)$session['user_id'],
        $notes
    );

    if ($result['success']) {
        Auth::respond([
            'status'  => 'success',
            'message' => "Parcel assigned to {$result['driver_name']}."
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>