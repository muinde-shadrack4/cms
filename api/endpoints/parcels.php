<?php
/* ============================================================
   parcels.php — Parcel Management Endpoint
   GET  → list parcels / find by tracking
   POST → book new parcel
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/parcel.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/db.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Parcel();

// ── GET PARCELS ───────────────────────────────────────────
if ($method === 'GET') {
    // Auth::requireLogin(); // bypassed temporarily

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

    $customerId = $_GET['customer_id'] ?? null;
    if ($customerId) {
        $parcels = $model->getByCustomer((int)$customerId);
        Auth::respond(['status' => 'success', 'data' => $parcels]);
    }

    $filters = [
        'status'      => $_GET['status']     ?? null,
        'from'        => $_GET['from']        ?? null,
        'to'          => $_GET['to']          ?? null,
        'limit'       => $_GET['limit']       ?? null,
        'customer_id' => $_GET['customer_id'] ?? null,
    ];
    $filters = array_filter($filters);
    $parcels = $model->getAll($filters);
    Auth::respond(['status' => 'success', 'data' => $parcels]);
}

// ── CREATE PARCEL ─────────────────────────────────────────
if ($method === 'POST') {
    // Auth::requireRole(['admin','customer_service','customer']); // bypassed
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

        // ✅ Send email to BOTH sender and recipient
        try {
            $conn = Database::getInstance()->getConnection();

            // Get customer details
            $stmt = mysqli_prepare($conn,
                "SELECT name, email FROM customers
                 WHERE customer_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $body['customer_id']);
            mysqli_stmt_execute($stmt);
            $res      = mysqli_stmt_get_result($stmt);
            $customer = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            $notes = 'Parcel booked successfully. Price: KES ' .
                     number_format($result['price'], 2);

            // ✅ Email to SENDER
            if ($customer && !empty($customer['email'])) {
                Mailer::sendStatusUpdate(
                    $customer['email'],
                    $customer['name'],
                    $result['tracking_number'],
                    'Booked',
                    'Nairobi CBD Branch',
                    "Dear {$customer['name']}, " . $notes
                );
            }

            // ✅ Email to RECIPIENT
            $recipientEmail = $body['recipient_email'] ?? '';
            $recipientName  = $body['recipient_name']  ?? '';

            if (!empty($recipientEmail)) {
                Mailer::sendStatusUpdate(
                    $recipientEmail,
                    $recipientName,
                    $result['tracking_number'],
                    'Booked',
                    'Nairobi CBD Branch',
                    "Dear {$recipientName}, a parcel has been booked for you and will be delivered to {$body['recipient_address']} shortly."
                );
            }

        } catch (Exception $e) {
            error_log('Email error: ' . $e->getMessage());
        }

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