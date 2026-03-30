<?php
/* ============================================================
   auth.php — Authentication Endpoint
   POST   → login
   POST   ?action=logout → logout
   GET    ?action=check  → check session
   ============================================================ */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';

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
        Auth::respond(['status' => 'success', 'data' => $user]);
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
    $password = $body['password'] ?? '';
    $role     = $body['role'] ?? '';

    if (!$username || !$password || !$role) {
        Auth::respond([
            'status'  => 'error',
            'message' => 'Username, password and role are required.'
        ], 400);
    }

    // Customer login (uses email)
    if ($role === 'customer') {
        $customerModel = new Customer();
        $customer      = $customerModel->verifyLogin($username, $password);

        if ($customer) {
            $customer['role']    = 'customer';
            $customer['user_id'] = 'C' . $customer['customer_id'];
            Auth::setSession($customer);
            unset($customer['password']);
            Auth::respond([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'user_id'     => $_SESSION['user_id'],
                    'full_name'   => $customer['name'],
                    'role'        => 'customer',
                    'customer_id' => $customer['customer_id']
                ]
            ]);
        }

        Auth::respond([
            'status'  => 'error',
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // Staff login
    $userModel = new User();
    $user      = $userModel->verifyLogin($username, $password, $role);

    if ($user) {
        Auth::setSession($user);
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