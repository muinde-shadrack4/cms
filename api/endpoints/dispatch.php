<?php
/* ============================================================
   dispatch.php — Dispatch Assignment Endpoint
   GET  ?status=pending  → parcels awaiting assignment (FIFO)
   GET  ?driver_id=      → driver's assigned parcels
   GET  ?recent=true     → recent assignments
   POST → assign parcel to driver
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/dispatch.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/db.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Dispatch();

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {
    // Auth::requireLogin(); // bypassed temporarily

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
    // $session = Auth::requireRole(['admin','dispatch']); // bypassed temporarily
    $body     = Auth::getBody();

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
        1, // temporary admin user_id
        $notes
    );

    if ($result['success']) {

        // ✅ Send email to BOTH sender and recipient
        try {
            $conn = Database::getInstance()->getConnection();

            // Get parcel + customer + recipient details
            $stmt = mysqli_prepare($conn,
                "SELECT p.tracking_number,
                        p.recipient_name,
                        p.recipient_email,
                        p.zone,
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
                        'Picked Up',
                        $data['zone'],
                        "Dear {$data['customer_name']}, your parcel has been assigned to driver {$result['driver_name']} and is on its way!"
                    );
                }

                // ✅ Email to RECIPIENT
                if (!empty($data['recipient_email'])) {
                    Mailer::sendStatusUpdate(
                        $data['recipient_email'],
                        $data['recipient_name'],
                        $data['tracking_number'],
                        'Picked Up',
                        $data['zone'],
                        "Dear {$data['recipient_name']}, a parcel is on its way to you! Driver {$result['driver_name']} will deliver it shortly."
                    );
                }
            }

        } catch (Exception $e) {
            error_log('Email error: ' . $e->getMessage());
        }

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