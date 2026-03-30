<?php
/* ============================================================
   Customer.php — Customer Model (OOP)
   Separate from staff — customers are NOT employees
   ============================================================ */

require_once __DIR__ . '/../config/Database.php';

class Customer {

    private $conn;
    private $table = 'customers';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── GET ALL CUSTOMERS ─────────────────────────────────
    public function getAll(): array {
        $result    = mysqli_query($this->conn,
            "SELECT customer_id, name, email, phone, created_at
             FROM {$this->table}
             ORDER BY created_at DESC");
        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
        return $customers;
    }

    // ── GET BY ID ─────────────────────────────────────────
    public function getById(int $id): ?array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT customer_id, name, email, phone, created_at
             FROM {$this->table} WHERE customer_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result   = mysqli_stmt_get_result($stmt);
        $customer = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $customer ?: null;
    }

    // ── CREATE CUSTOMER ───────────────────────────────────
    public function create(array $data): array {
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (name, email, phone, password)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss',
            $data['name'],
            $data['email'],
            $data['phone'],
            $hashed
        );

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'customer_id' => $id];
        }

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if (str_contains($error, 'Duplicate')) {
            return ['success' => false,
                    'message' => 'Email already registered.'];
        }

        return ['success' => false, 'message' => $error];
    }

    // ── VERIFY LOGIN ──────────────────────────────────────
    public function verifyLogin(string $email,
                                string $password): ?array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT customer_id, name, email, password
             FROM {$this->table} WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result   = mysqli_stmt_get_result($stmt);
        $customer = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$customer) return null;
        if (!password_verify($password, $customer['password'])) return null;

        unset($customer['password']);
        return $customer;
    }

    // ── COUNT ─────────────────────────────────────────────
    public function count(): int {
        $result = mysqli_query($this->conn,
            "SELECT COUNT(*) AS total FROM {$this->table}");
        return (int)mysqli_fetch_assoc($result)['total'];
    }
}
?>