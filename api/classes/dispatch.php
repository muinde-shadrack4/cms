<?php
/* ============================================================
   Dispatch.php — Dispatch Assignment Model (OOP)
   DSA: FIFO Queue — oldest parcels dispatched first
   ============================================================ */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/parcel.php';

class Dispatch {

    private $conn;
    private $table  = 'dispatch_assignments';
    private $parcel;

    public function __construct() {
        $this->conn   = Database::getInstance()->getConnection();
        $this->parcel = new Parcel();
    }

    // ── GET PENDING PARCELS (FIFO Queue) ──────────────────
    public function getPending(): array {
        $result = mysqli_query($this->conn,
            "SELECT p.parcel_id, p.tracking_number,
                    p.recipient_name, p.recipient_address,
                    p.zone, p.weight, p.service_type,
                    p.date_registered, p.status,
                    c.name AS customer_name
             FROM parcels p
             JOIN customers c ON p.customer_id = c.customer_id
             WHERE p.status = 'Booked'
             AND p.assigned_driver_id IS NULL
             ORDER BY p.date_registered ASC");

        // ✅ Check query succeeded
        if (!$result) {
            error_log('getPending failed: ' . mysqli_error($this->conn));
            return [];
        }

        $parcels = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parcels[] = $row;
        }
        return $parcels;
    }

    // ── ASSIGN PARCEL TO DRIVER ───────────────────────────
    public function assign(int $parcelId,
                           int $driverId,
                           int $assignedBy,
                           string $notes = ''): array {

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO {$this->table}
             (parcel_id, driver_id, assigned_by, notes)
             VALUES (?, ?, ?, ?)");

        // ✅ Check prepare succeeded
        if (!$stmt) {
            return ['success' => false,
                    'message' => mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, 'iiis',
            $parcelId, $driverId, $assignedBy, $notes);

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return ['success' => false, 'message' => $error];
        }
        mysqli_stmt_close($stmt);

        // Update parcel status
        $this->parcel->assignDriver($parcelId, $driverId);

        // Get driver name
        $driverStmt = mysqli_prepare($this->conn,
            "SELECT full_name FROM system_users WHERE user_id = ?");

        $driverName = 'Driver';
        if ($driverStmt) {
            mysqli_stmt_bind_param($driverStmt, 'i', $driverId);
            mysqli_stmt_execute($driverStmt);
            $driverResult = mysqli_stmt_get_result($driverStmt);
            $driver       = mysqli_fetch_assoc($driverResult);
            mysqli_stmt_close($driverStmt);
            $driverName = $driver['full_name'] ?? 'Driver';
        }

        // Add tracking update
        $note      = "Assigned to driver: {$driverName}";
        $trackStmt = mysqli_prepare($this->conn,
            "INSERT INTO tracking_updates
             (parcel_id, status, location, notes, updated_by)
             VALUES (?, 'Picked Up', 'Assigned to Driver', ?, ?)");

        if ($trackStmt) {
            mysqli_stmt_bind_param($trackStmt, 'iss',
                $parcelId, $note, $driverName);
            mysqli_stmt_execute($trackStmt);
            mysqli_stmt_close($trackStmt);
        }

        return ['success' => true, 'driver_name' => $driverName];
    }

    // ── GET RECENT ASSIGNMENTS ────────────────────────────
    public function getRecent(int $limit = 10): array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT da.*,
                    p.tracking_number, p.recipient_name,
                    p.zone, p.status AS parcel_status,
                    u.full_name AS driver_name
             FROM {$this->table} da
             JOIN parcels p ON da.parcel_id = p.parcel_id
             JOIN system_users u ON da.driver_id = u.user_id
             ORDER BY da.assigned_at DESC
             LIMIT ?");

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $result      = mysqli_stmt_get_result($stmt);
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $assignments;
    }

    // ── GET BY DRIVER ─────────────────────────────────────
    public function getByDriver(int $driverId): array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT p.parcel_id, p.tracking_number,
                    p.recipient_name, p.recipient_address,
                    p.zone, p.weight, p.status,
                    p.date_registered, c.name AS customer_name
             FROM parcels p
             JOIN customers c ON p.customer_id = c.customer_id
             WHERE p.assigned_driver_id = ?
             ORDER BY p.date_registered DESC");

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'i', $driverId);
        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $parcels = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parcels[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $parcels;
    }

    // ── UPDATE ASSIGNMENT STATUS ──────────────────────────
    public function updateStatus(int $parcelId,
                                  int $driverId,
                                  string $status): bool {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE {$this->table}
             SET status = ?
             WHERE parcel_id = ? AND driver_id = ?");

        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, 'sii',
            $status, $parcelId, $driverId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // ── TOP DRIVERS REPORT ────────────────────────────────
    public function getTopDrivers(int $limit = 5): array {
        $stmt = mysqli_prepare($this->conn,
            "SELECT u.full_name,
                    COUNT(da.assignment_id) AS deliveries
             FROM {$this->table} da
             JOIN system_users u ON da.driver_id = u.user_id
             WHERE da.status = 'Delivered'
             GROUP BY da.driver_id
             ORDER BY deliveries DESC
             LIMIT ?");

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $drivers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $drivers[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $drivers;
    }
}
?>