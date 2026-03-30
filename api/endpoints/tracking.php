<?php
/* ============================================================
   tracking.php — Tracking Updates Endpoint
   GET  ?parcel_id= → get tracking history
   POST → add status update (driver only)
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/Parcel.php';

Auth::startSession();

$method = Auth::getMethod();

// ── GET TRACKING HISTORY ──────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();
    $parcelId = (int)($_GET['parcel_id'] ?? 0);

    if (!$parcelId) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'parcel_id is required.'
        ], 400);
    }

    $conn = Database::getInstance()->getConnection();
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM tracking_updates
         WHERE parcel_id = ?
         ORDER BY updated_at ASC");
    mysqli_stmt_bind_param($stmt, 'i', $parcelId);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $updates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $updates[] = $row;
    }
    mysqli_stmt_close($stmt);

    Auth::respond(['status' => 'success', 'data' => $updates]);
}

// ── ADD TRACKING UPDATE ───────────────────────────────────
if ($method === 'POST') {
    $session = Auth::requireRole([
        'driver', 'dispatch', 'admin', 'customer_service'
    ]);
    $body = Auth::getBody();

    $parcelId  = (int)($body['parcel_id']  ?? 0);
    $newStatus = $body['new_status'] ?? '';
    $location  = $body['location']   ?? '';
    $notes     = $body['notes']      ?? '';
    $updatedBy = $body['updated_by'] ?? $session['full_name'];

    if (!$parcelId || !$newStatus) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'parcel_id and new_status are required.'
        ], 400);
    }

    $conn = Database::getInstance()->getConnection();

    // Verify driver owns this parcel
    if ($session['role'] === 'driver') {
        $check = mysqli_prepare($conn,
            "SELECT parcel_id FROM parcels
             WHERE parcel_id = ?
             AND assigned_driver_id = ?");
        mysqli_stmt_bind_param($check, 'ii',
            $parcelId, $session['user_id']);
        mysqli_stmt_execute($check);
        $r = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if (mysqli_num_rows($r) === 0) {
            Auth::respond([
                'status'  => 'error',
                'message' => 'You can only update your own parcels.'
            ], 403);
        }
    }

    // Update parcel status
    $parcelModel = new Parcel();
    $parcelModel->updateStatus($parcelId, $newStatus);

    // Add tracking update
    $stmt = mysqli_prepare($conn,
        "INSERT INTO tracking_updates
         (parcel_id, status, location, notes, updated_by)
         VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'issss',
        $parcelId, $newStatus, $location, $notes, $updatedBy);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update dispatch assignment if driver
    if ($session['role'] === 'driver') {
        $dispatchStmt = mysqli_prepare($conn,
            "UPDATE dispatch_assignments
             SET status = ?
             WHERE parcel_id = ? AND driver_id = ?");
        mysqli_stmt_bind_param($dispatchStmt, 'sii',
            $newStatus, $parcelId, $session['user_id']);
        mysqli_stmt_execute($dispatchStmt);
        mysqli_stmt_close($dispatchStmt);
    }

    Auth::respond([
        'status'  => 'success',
        'message' => "Status updated to '{$newStatus}'."
    ]);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>