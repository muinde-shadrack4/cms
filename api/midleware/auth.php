<?php
/* ============================================================
   Auth.php — Session Middleware
   Runs before every API endpoint.
   Validates session, checks role, returns JSON errors.
   ============================================================ */

class Auth {

    // ── START SESSION ─────────────────────────────────────
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ── CHECK IF LOGGED IN ────────────────────────────────
    public static function isLoggedIn(): bool {
        self::startSession();
        return isset($_SESSION['user_id'])
            && !empty($_SESSION['user_id']);
    }

    // ── REQUIRE LOGIN ─────────────────────────────────────
    // Returns user data or sends 401 and exits
    public static function requireLogin(): array {
        if (!self::isLoggedIn()) {
            self::respond([
                'status'  => 'error',
                'message' => 'Not authenticated. Please login.'
            ], 401);
        }
        return $_SESSION;
    }

    // ── REQUIRE SPECIFIC ROLE ─────────────────────────────
    public static function requireRole($allowedRoles): array {
        $session = self::requireLogin();

        $roles = is_array($allowedRoles)
            ? $allowedRoles
            : [$allowedRoles];

        if (!in_array($session['role'], $roles)) {
            self::respond([
                'status'  => 'error',
                'message' => 'Access denied. Insufficient permissions.'
            ], 403);
        }

        return $session;
    }

    // ── GET CURRENT USER ──────────────────────────────────
    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return [
            'user_id'   => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role'],
            'username'  => $_SESSION['username'] ?? ''
        ];
    }

    // ── SET SESSION ───────────────────────────────────────
    public static function setSession(array $user): void {
        self::startSession();
        $_SESSION['user_id']   = $user['user_id']
            ?? $user['customer_id'] ?? null;
        $_SESSION['full_name'] = $user['full_name']
            ?? $user['name'] ?? '';
        $_SESSION['role']      = $user['role'] ?? 'customer';
        $_SESSION['username']  = $user['username']
            ?? $user['email'] ?? '';
    }

    // ── DESTROY SESSION ───────────────────────────────────
    public static function destroySession(): void {
        self::startSession();
        session_destroy();
    }

    // ── SEND JSON RESPONSE ────────────────────────────────
    public static function respond(array $data,
                                   int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE');
        header('Access-Control-Allow-Headers: Content-Type');
        echo json_encode($data);
        exit();
    }

    // ── GET REQUEST BODY ──────────────────────────────────
    public static function getBody(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    // ── GET METHOD ────────────────────────────────────────
    public static function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
}
?>