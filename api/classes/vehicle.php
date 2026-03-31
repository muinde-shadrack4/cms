<?php
/* ============================================================
   Vehicle.php — Fleet Management Model (OOP)
   Admin manages vehicles here
   ============================================================ */

require_once __DIR__ . '/../config/db.php';

class Vehicle {

    private $conn;
    private $table = 'vehicles';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── GET ALL VEHICLES ──────────────────────────────────
    public function getAll(): array {
        $result   = mysqli_query($this->conn,
            "SELECT * FROM {$this->table}
             ORDER BY status, type");
        $vehicles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vehicles[] = $row;
        }
        return $vehicles;
    }

    // ── CREATE VEHICLE ────────────────────────────────────
    public function create(array $data): array {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (registration_number, type, capacity, status)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssds',
            $data['registration_number'],
            $data['type'],
            $data['capacity'],
            $data['status']
        );

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'vehicle_id' => $id];
        }

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if (str_contains($error, 'Duplicate')) {
            return ['success' => false,
                    'message' => 'Registration number already exists.'];
        }
        return ['success' => false, 'message' => $error];
    }

    // ── UPDATE STATUS ─────────────────────────────────────
    public function updateStatus(int $id, string $status): bool {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE {$this->table}
             SET status = ? WHERE vehicle_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // ── COUNT BY STATUS ───────────────────────────────────
    public function countByStatus(): array {
        $result = mysqli_query($this->conn,
            "SELECT status, COUNT(*) AS total
             FROM {$this->table} GROUP BY status");
        $counts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $counts[$row['status']] = (int)$row['total'];
        }
        return $counts;
    }
}
?>