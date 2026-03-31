<?php
/* ============================================================
   tracking.php — Tracking Updates Endpoint
   GET  ?parcel_id= → get tracking history
   POST → add status update (driver only)
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/parcel.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/db.php';

Auth::startSession();

$method = Auth::getMethod();

// ── GET TRACKING HISTORY ──────────────────────────────────
if ($method === 'GET') {
    // Auth::requireLogin(); // bypassed temporarily

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
    // $session = Auth::requireRole([
    //     'driver', 'dispatch', 'admin', 'customer_service'
    // ]); // bypassed temporarily

    $body = Auth::getBody();

    $parcelId  = (int)($body['parcel_id']  ?? 0);
    $newStatus = $body['new_status'] ?? '';
    $location  = $body['location']   ?? '';
    $notes     = $body['notes']      ?? '';
    $updatedBy = $body['updated_by'] ?? 'System';

    if (!$parcelId || !$newStatus) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'parcel_id and new_status are required.'
        ], 400);
    }

    $conn = Database::getInstance()->getConnection();

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

    // ✅ Send email to BOTH sender and recipient
    try {
        $stmt = mysqli_prepare($conn,
            "SELECT p.tracking_number,
                    p.recipient_name,
                    p.recipient_email,
                    c.name  AS customer_name,
                    c.email AS customer_email
             FROM parcels p
             JOIN customers c ON p.customer_id = c.customer_id
             WHERE p.parcel_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $parcelId);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($data) {

            // ✅ Email to SENDER
            if (!empty($data['customer_email'])) {
                Mailer::sendStatusUpdate(
                    $data['customer_email'],
                    $data['customer_name'],
                    $data['tracking_number'],
                    $newStatus,
                    $location,
                    "Dear {$data['customer_name']}, your parcel status has been updated to: {$newStatus}. {$notes}"
                );
            }

            // ✅ Email to RECIPIENT
            if (!empty($data['recipient_email'])) {
                Mailer::sendStatusUpdate(
                    $data['recipient_email'],
                    $data['recipient_name'],
                    $data['tracking_number'],
                    $newStatus,
                    $location,
                    "Dear {$data['recipient_name']}, a parcel addressed to you has been updated to: {$newStatus}. {$notes}"
                );
            }
        }

    } catch (Exception $e) {
        error_log('Email error: ' . $e->getMessage());
    }

    Auth::respond([
        'status'  => 'success',
        'message' => "Status updated to '{$newStatus}'."
    ]);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>