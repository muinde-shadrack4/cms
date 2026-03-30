<?php
/* ============================================================
   users.php — Staff Management Endpoint
   GET  → list staff
   POST → create staff (admin only)
   PUT  → update status (admin only)
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/User.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new User();

// ── GET STAFF ─────────────────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();
    $role   = $_GET['role']   ?? null;
    $status = $_GET['status'] ?? null;
    $staff  = $model->getAll($role, $status);
    Auth::respond(['status' => 'success', 'data' => $staff]);
}

// ── CREATE STAFF ──────────────────────────────────────────
if ($method === 'POST') {
    Auth::requireRole('admin');
    $body = Auth::getBody();

    $required = ['full_name','username','email','phone',
                 'password','role'];
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
            'message' => 'Staff member added successfully.',
            'data'    => ['user_id' => $result['user_id']]
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

// ── UPDATE STATUS ─────────────────────────────────────────
if ($method === 'PUT') {
    Auth::requireRole('admin');
    $body   = Auth::getBody();
    $userId = (int)($body['user_id'] ?? 0);
    $status = $body['status'] ?? '';

    if (!$userId || !$status) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'user_id and status are required.'
        ], 400);
    }

    $ok = $model->updateStatus($userId, $status);
    Auth::respond([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Status updated.' : 'Update failed.'
    ]);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>