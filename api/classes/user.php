<?php
/* ============================================================
   User.php — Staff User Model (OOP)
   Handles all staff operations:
   admin, driver, dispatch, warehouse, customer_service
   ============================================================ */

require_once __DIR__ . '/../config/Database.php';

class User {

    private $conn;
    private $table = 'users';

    // ── CONSTRUCTOR ───────────────────────────────────────
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── GET ALL STAFF ─────────────────────────────────────
    // Optional filter by role
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

    // ── GET ACTIVE DRIVERS ────────────────────────────────
    public function getDrivers(): array {
        return $this->getAll('driver', 'active');
    }

    // ── GET SINGLE USER ───────────────────────────────────
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

    // ── CREATE STAFF ──────────────────────────────────────
    public function create(array $data): array {
        // Hash password
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

        // Duplicate username/email
        if (str_contains($error, 'Duplicate')) {
            return ['success' => false,
                    'message' => 'Username or email already exists.'];
        }

        return ['success' => false, 'message' => $error];
    }

    // ── UPDATE STATUS ─────────────────────────────────────
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

    // ── VERIFY LOGIN ──────────────────────────────────────
    // Used by auth endpoint
    public function verifyLogin(string $username,
                                string $password,
                                string $role): ?array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT user_id, full_name, username,
                    password, role, status
             FROM {$this->table}
             WHERE username = ? AND role = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) return null;
        if ($user['status'] !== 'active') return null;
        if (!password_verify($password, $user['password'])) return null;

        // Remove password before returning
        unset($user['password']);
        return $user;
    }

    // ── COUNT BY ROLE ─────────────────────────────────────
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