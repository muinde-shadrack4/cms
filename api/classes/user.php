<?php
/* ============================================================
   User.php — Staff User Model (OOP)
   Handles all staff operations:
   admin, driver, dispatch, warehouse, customer_service
   ============================================================ */

require_once __DIR__ . '/../config/db.php';

class User {

    private $conn;
    private $table = 'system_users';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll(string $role = null,
                           string $status = null): array {
        $sql  = "SELECT user_id, full_name, username, email,
                        phone, role, status, created_at
                 FROM {$this->table}
                 WHERE role != 'admin'";
        $params = [];
        $types  = '';

        if ($role) {
            $sql    .= " AND role = ?";
            $types  .= 's';
            $params[] = $role;
        }

        if ($status) {
            $sql    .= " AND status = ?";
            $types  .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY role, full_name";

        $stmt = mysqli_prepare($this->conn, $sql);

        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users  = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $users;
    }

    public function getDrivers(): array {
        return $this->getAll('driver', 'active');
    }

    public function getById(int $id): ?array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT user_id, full_name, username, email,
                    phone, role, status, created_at
             FROM {$this->table} WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user ?: null;
    }

    public function create(array $data): array {
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (full_name, username, email, phone, password, role)
             VALUES (?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, 'ssssss',
            $data['full_name'],
            $data['username'],
            $data['email'],
            $data['phone'],
            $hashed,
            $data['role']
        );

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'user_id' => $id];
        }

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if (str_contains($error, 'Duplicate')) {
            return ['success' => false,
                    'message' => 'Username or email already exists.'];
        }

        return ['success' => false, 'message' => $error];
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE {$this->table}
             SET status = ?
             WHERE user_id = ? AND role != 'admin'");
        mysqli_stmt_bind_param($stmt, 'si', $status, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // ── VERIFY LOGIN ✅ FIXED ─────────────────────────────
    public function verifyLogin(string $username,
                                string $password,
                                string $role): ?array {

        // ✅ Check table exists
        $check = mysqli_query($this->conn,
            "SHOW TABLES LIKE '{$this->table}'");
        if (mysqli_num_rows($check) === 0) {
            error_log("Table {$this->table} does not exist!");
            return null;
        }

        // ✅ Match by username OR email + role
        $stmt = mysqli_prepare($this->conn,
            "SELECT user_id, full_name, username,
                    password, role, status
             FROM {$this->table}
             WHERE (username = ? OR email = ?) AND role = ?");

        // ✅ Check prepare succeeded
        if (!$stmt) {
            error_log('Prepare failed: ' . mysqli_error($this->conn));
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'sss', $username, $username, $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) return null;
        if ($user['status'] !== 'active') return null;

        // ✅ Support hashed and plain text passwords
        $hash  = $user['password'];
        $valid = (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$'))
            ? password_verify($password, $hash)
            : ($password === $hash);

        if (!$valid) return null;

        unset($user['password']);
        return $user;
    }

    public function countByRole(): array {
        $result = mysqli_query($this->conn,
            "SELECT role, COUNT(*) AS total
             FROM {$this->table}
             WHERE status = 'active' AND role != 'admin'
             GROUP BY role");

        $counts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $counts[$row['role']] = (int)$row['total'];
        }
        return $counts;
    }
}
?>