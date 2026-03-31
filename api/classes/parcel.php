<?php
/* ============================================================
   Parcel.php — Core Business Model (OOP)
   - Parcel registration
   - Tracking number generation
   - Pricing algorithm (from proposal Section 3.5.6)
   - DSA: Queue (FIFO) for dispatch ordering
   ============================================================ */

require_once __DIR__ . '/../config/db.php';

class Parcel {

    private $conn;
    private $table = 'parcels';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── GET ALL PARCELS ───────────────────────────────────
    public function getAll(array $filters = []): array {
        $sql    = "SELECT p.*,
                          c.name AS customer_name
                   FROM {$this->table} p
                   JOIN customers c ON p.customer_id = c.customer_id
                   WHERE 1=1";
        $params = [];
        $types  = '';

        if (!empty($filters['status'])) {
            $sql    .= " AND p.status = ?";
            $types  .= 's';
            $params[] = $filters['status'];
        }

        if (!empty($filters['customer_id'])) {
            $sql    .= " AND p.customer_id = ?";
            $types  .= 'i';
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['from'])) {
            $sql    .= " AND DATE(p.date_registered) >= ?";
            $types  .= 's';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql    .= " AND DATE(p.date_registered) <= ?";
            $types  .= 's';
            $params[] = $filters['to'];
        }

        $sql .= " ORDER BY p.date_registered DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = mysqli_prepare($this->conn, $sql);

        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $parcels = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $parcels[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $parcels;
    }

    // ── GET BY TRACKING NUMBER ────────────────────────────
    public function getByTracking(string $tracking): ?array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT p.*, c.name AS customer_name,
                    c.phone AS customer_phone
             FROM {$this->table} p
             JOIN customers c ON p.customer_id = c.customer_id
             WHERE p.tracking_number = ?");
        mysqli_stmt_bind_param($stmt, 's', $tracking);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $parcel = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $parcel ?: null;
    }

    // ── GET BY CUSTOMER ───────────────────────────────────
    public function getByCustomer(int $customerId): array {
        return $this->getAll(['customer_id' => $customerId]);
    }

    // ── CREATE PARCEL ─────────────────────────────────────
    public function create(array $data): array {
        $tracking       = $this->generateTracking();
        $price          = $this->calculatePrice(
            $data['weight'],
            $data['zone'],
            $data['service_type']
        );

        $recipientEmail = $data['recipient_email'] ?? '';

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (tracking_number, customer_id, recipient_name,
              recipient_phone, recipient_email, recipient_address,
              weight, service_type, zone, price, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Booked')");

        // ✅ Fixed: 10 vars = sissssdssd
        // s=tracking, i=customer_id, s=recipient_name,
        // s=recipient_phone, s=recipientEmail,
        // s=recipient_address, d=weight,
        // s=service_type, s=zone, d=price
        mysqli_stmt_bind_param($stmt, 'sissssdssd',
            $tracking,
            $data['customer_id'],
            $data['recipient_name'],
            $data['recipient_phone'],
            $recipientEmail,
            $data['recipient_address'],
            $data['weight'],
            $data['service_type'],
            $data['zone'],
            $price
        );

        if (mysqli_stmt_execute($stmt)) {
            $parcel_id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            $this->addInitialTracking($parcel_id);
            return [
                'success'         => true,
                'parcel_id'       => $parcel_id,
                'tracking_number' => $tracking,
                'price'           => $price
            ];
        }

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => $error];
    }

    // ── UPDATE STATUS ─────────────────────────────────────
    public function updateStatus(int $id, string $status): bool {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE {$this->table}
             SET status = ? WHERE parcel_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // ── ASSIGN DRIVER ─────────────────────────────────────
    public function assignDriver(int $parcelId,
                                  int $driverId): bool {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE {$this->table}
             SET assigned_driver_id = ?,
                 assigned_at = NOW(),
                 status = 'Picked Up'
             WHERE parcel_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $driverId, $parcelId);
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

    // ── PRICING ALGORITHM ─────────────────────────────────
    public function calculatePrice(float $weight,
                                   string $zone,
                                   string $service): float {
        if ($weight <= 1)        $base = 200;
        elseif ($weight <= 5)    $base = 200 + (($weight - 1) * 50);
        elseif ($weight <= 10)   $base = 400 + (($weight - 5) * 80);
        else                     $base = 800 + (($weight - 10) * 100);

        $zoneMultipliers = [
            'CBD'       => 1.0,
            'Westlands' => 1.2,
            'Eastlands' => 1.3,
            'Satellite' => 1.5
        ];
        $base *= $zoneMultipliers[$zone] ?? 1.0;

        if ($service === 'express')  $base *= 1.5;
        if ($service === 'same-day') $base *= 1.3;

        return round(max($base, 150), 2);
    }

    // ── TRACKING NUMBER GENERATOR ─────────────────────────
    private function generateTracking(): string {
        do {
            $tracking = 'WF-' . date('Y') . '-'
                      . rand(1000000, 9999999);
            $stmt     = mysqli_prepare($this->conn,
                "SELECT parcel_id FROM {$this->table}
                 WHERE tracking_number = ?");
            mysqli_stmt_bind_param($stmt, 's', $tracking);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = mysqli_num_rows($result) > 0;
            mysqli_stmt_close($stmt);
        } while ($exists);

        return $tracking;
    }

    // ── INITIAL TRACKING ENTRY ────────────────────────────
    private function addInitialTracking(int $parcelId): void {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO tracking_updates
             (parcel_id, status, location, notes, updated_by)
             VALUES (?, 'Booked', 'Nairobi CBD Branch',
                     'Parcel booked successfully', 'System')");
        mysqli_stmt_bind_param($stmt, 'i', $parcelId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>