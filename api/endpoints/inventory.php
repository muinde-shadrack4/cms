<?php
/* ============================================================
   inventory.php — Warehouse Inventory Endpoint
   GET    → list inventory / available parcels
   POST   → add to inventory
   DELETE → remove from inventory
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/inventory.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Inventory();

// ── GET INVENTORY ─────────────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();

    if (isset($_GET['available'])) {
        $parcels = $model->getAvailable();
        Auth::respond(['status' => 'success', 'data' => $parcels]);
    }

    $search = $_GET['search'] ?? '';
    $items  = $model->getAll($search);
    Auth::respond(['status' => 'success', 'data' => $items]);
}

// ── ADD TO INVENTORY ──────────────────────────────────────
if ($method === 'POST') {
    Auth::requireRole(['admin','warehouse']);
    $body         = Auth::getBody();
    $parcelId     = (int)($body['parcel_id']      ?? 0);
    $shelfLocation = strtoupper(trim(
        $body['shelf_location'] ?? ''
    ));

    if (!$parcelId || !$shelfLocation) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'parcel_id and shelf_location are required.'
        ], 400);
    }

    $result = $model->add($parcelId, $shelfLocation);

    if ($result['success']) {
        Auth::respond([
            'status'  => 'success',
            'message' => "Added to shelf {$shelfLocation}."
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

// ── REMOVE FROM INVENTORY ─────────────────────────────────
if ($method === 'DELETE') {
    Auth::requireRole(['admin','warehouse']);
    $body        = Auth::getBody();
    $inventoryId = (int)($body['inventory_id'] ?? 0);

    if (!$inventoryId) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'inventory_id is required.'
        ], 400);
    }

    $ok = $model->remove($inventoryId);
    Auth::respond([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Removed from inventory.' : 'Remove failed.'
    ]);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>