<?php
/* ============================================================
   Inventory.php — Warehouse Inventory Model (OOP)
   Objective 1 — Systematic inventory management
   ============================================================ */

require_once __DIR__ . '/../config/db.php';

class Inventory {

    private $conn;
    private $table = 'inventory';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── GET ALL INVENTORY ─────────────────────────────────
    public function getAll(string $search = ''): array {
        $sql = "SELECT i.*,
                       p.tracking_number, p.recipient_name,
                       p.recipient_address, p.weight,
                       p.status, p.zone,
                       c.name AS customer_name
                FROM {$this->table} i
                JOIN parcels p ON i.parcel_id = p.parcel_id
                JOIN customers c ON p.customer_id = c.customer_id
                WHERE 1=1";

        $params = [];
        $types  = '';

        if ($search) {
            $like     = "%{$search}%";
            $sql     .= " AND (p.tracking_number LIKE ?
                          OR p.recipient_name LIKE ?
                          OR i.shelf_location LIKE ?)";
            $types   .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY i.shelf_location";

        $stmt = mysqli_prepare($this->conn, $sql);

        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items  = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $items;
    }

    // ── GET AVAILABLE PARCELS (not yet shelved) ───────────
    public function getAvailable(): array {
        $result  = mysqli_query($this->conn,
            "SELECT p.parcel_id, p.tracking_number,
                    p.recipient_name, p.weight, p.status
             FROM parcels p
             WHERE p.parcel_id NOT IN
                   (SELECT parcel_id FROM {$this->table})
             AND p.status NOT IN ('Delivered','Failed')
             ORDER BY p.date_registered DESC");

        $parcels = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parcels[] = $row;
        }
        return $parcels;
    }

    // ── ADD TO INVENTORY ──────────────────────────────────
    public function add(int $parcelId,
                        string $shelfLocation): array {
        // Check if already shelved
        $check = mysqli_prepare($this->conn,
            "SELECT inventory_id FROM {$this->table}
             WHERE parcel_id = ?");
        mysqli_stmt_bind_param($check, 'i', $parcelId);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if (mysqli_num_rows($result) > 0) {
            return ['success' => false,
                    'message' => 'Parcel already in inventory.'];
        }

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (parcel_id, shelf_location) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'is', $parcelId, $shelfLocation);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true];
        }

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => $error];
    }

    // ── REMOVE FROM INVENTORY ─────────────────────────────
    public function remove(int $inventoryId): bool {
        $stmt = mysqli_prepare($this->conn,
            "DELETE FROM {$this->table}
             WHERE inventory_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $inventoryId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // ── COUNT ─────────────────────────────────────────────
    public function count(): int {
        $result = mysqli_query($this->conn,
            "SELECT COUNT(*) AS total FROM {$this->table}");
        return (int)mysqli_fetch_assoc($result)['total'];
    }
}
?>