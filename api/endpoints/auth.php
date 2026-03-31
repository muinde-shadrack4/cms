<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ============================================================
   auth.php — Authentication Endpoint
   POST              → login
   POST ?action=logout → logout
   GET  ?action=check  → check session
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/user.php';
require_once __DIR__ . '/../classes/customer.php';

Auth::startSession();

$method = Auth::getMethod();
$action = $_GET['action'] ?? '';

// ── HANDLE OPTIONS (CORS preflight) ──────────────────────
if ($method === 'OPTIONS') {
    Auth::respond(['status' => 'ok']);
}

// ── CHECK SESSION ─────────────────────────────────────────
if ($method === 'GET' && $action === 'check') {
    $user = Auth::getUser();
    if ($user) {
        Auth::respond([
            'status' => 'success',
            'data'   => [
                'user_id'     => $user['user_id'],
                'full_name'   => $user['full_name'],
                'role'        => $user['role'],
                'username'    => $user['username']    ?? '',
                'customer_id' => $user['customer_id'] ?? null
            ]
        ]);
    }
    Auth::respond([
        'status'  => 'error',
        'message' => 'Not authenticated'
    ], 401);
}

// ── LOGOUT ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    Auth::destroySession();
    Auth::respond([
        'status'  => 'success',
        'message' => 'Logged out successfully'
    ]);
}

// ── LOGIN ─────────────────────────────────────────────────
if ($method === 'POST') {
    $body     = Auth::getBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password']      ?? '';
    $role     = $body['role']          ?? '';

    if (!$username || !$password || !$role) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'Username, password and role are required.'
        ], 400);
    }

    // ── CUSTOMER LOGIN ────────────────────────────────────
    if ($role === 'customer') {
        $customerModel = new Customer();
        $customer      = $customerModel->verifyLogin($username, $password);

        if ($customer) {
            // ✅ Build session data
            $sessionData = [
                'user_id'     => 'C' . $customer['customer_id'],
                'full_name'   => $customer['name'],
                'role'        => 'customer',
                'username'    => $customer['email'],
                'customer_id' => $customer['customer_id']
            ];

            Auth::setSession($sessionData);

            Auth::respond([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'user_id'     => $sessionData['user_id'],
                    'full_name'   => $sessionData['full_name'],
                    'role'        => 'customer',
                    'customer_id' => $sessionData['customer_id']
                ]
            ]);
        }

        Auth::respond([
            'status'  => 'error',
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // ── STAFF LOGIN ───────────────────────────────────────
    $userModel = new User();
    $user      = $userModel->verifyLogin($username, $password, $role);

    if ($user) {
        // ✅ Build session data
        $sessionData = [
            'user_id'   => $user['user_id'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'username'  => $user['username']
        ];

        Auth::setSession($sessionData);

        Auth::respond([
            'status'  => 'success',
            'message' => 'Login successful',
            'data'    => [
                'user_id'   => $user['user_id'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
                'username'  => $user['username']
            ]
        ]);
    }

    Auth::respond([
        'status'  => 'error',
        'message' => 'Invalid username, password or role.'
    ], 401);
}

Auth::respond(['status' => 'error', 'message' => 'Method not allowed'], 405);
?>