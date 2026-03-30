<?php
/* ============================================================
   customers.php — Customer Management Endpoint
   GET  → list all customers
   POST → register new customer
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/Customer.php';

Auth::startSession();

$method = Auth::getMethod();
$model  = new Customer();

// ── GET CUSTOMERS ─────────────────────────────────────────
if ($method === 'GET') {
    Auth::requireLogin();

    $id = $_GET['id'] ?? null;

    if ($id) {
        $customer = $model->getById((int)$id);
        if (!$customer) {
            Auth::respond([
                'status'  => 'error',
                'message' => 'Customer not found.'
            ], 404);
        }
        Auth::respond(['status' => 'success', 'data' => $customer]);
    }

    $customers = $model->getAll();
    Auth::respond(['status' => 'success', 'data' => $customers]);
}

// ── CREATE CUSTOMER ───────────────────────────────────────
if ($method === 'POST') {
    $body = Auth::getBody();

    foreach (['name','email','phone','password'] as $field) {
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
            'message' => 'Customer registered successfully.',
            'data'    => ['customer_id' => $result['customer_id']]
        ], 201);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => $result['message']
    ], 400);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>