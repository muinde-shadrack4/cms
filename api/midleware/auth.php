<?php
class Auth {

    // Start session safely
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: http://localhost');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    // Get request method
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    // Get JSON body
    public static function getBody() {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // Require login — 401 if not logged in
    public static function requireLogin() {
        if (empty($_SESSION['user_id'])) {
            self::respond([
                'status'  => 'error',
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }
        return $_SESSION;
    }

    // Require specific role
    public static function requireRole($roles) {
        self::requireLogin();
        $allowed = is_array($roles) ? $roles : [$roles];

        if (!in_array($_SESSION['role'], $allowed)) {
            self::respond([
                'status'  => 'error',
                'message' => 'Access denied.'
            ], 403);
        }
        return $_SESSION;
    }

    // Set session after successful login
    public static function setSession(array $user): void {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'] ?? $user['name'] ?? '';
        $_SESSION['role']      = $user['role'];
        $_SESSION['username']  = $user['username'] ?? '';
        if (isset($user['customer_id'])) {
            $_SESSION['customer_id'] = $user['customer_id'];
        }
    }

    // Destroy session on logout
    public static function destroySession(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // Get current user
    public static function getUser(): ?array {
        if (empty($_SESSION['user_id'])) return null;
        return $_SESSION;
    }

    // Send JSON response and stop
    public static function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}