<?php
/* ============================================================
   reports.php — Management Reports Endpoint
   Objective 4 — Reports for decision-making
   GET ?from=&to=       → summary stats
   GET ?type=zone       → parcels by zone
   GET ?type=drivers    → top drivers
   ============================================================ */

require_once __DIR__ . '/../midleware/auth.php';
require_once __DIR__ . '/../classes/parcel.php';
require_once __DIR__ . '/../classes/customer.php';
require_once __DIR__ . '/../classes/inventory.php';
require_once __DIR__ . '/../classes/dispatch.php';
require_once __DIR__ . '/../classes/user.php';

Auth::startSession();

$method = Auth::getMethod();

if ($method !== 'GET') {
    Auth::respond([
        'status'  => 'error',
        'message' => 'Method not allowed'
    ], 405);
}

Auth::requireRole('admin');

$conn     = Database::getInstance()->getConnection();
$type     = $_GET['type'] ?? 'summary';
$from     = $_GET['from'] ?? date('Y-m-01');
$to       = $_GET['to']   ?? date('Y-m-d');

// ── SUMMARY STATS ─────────────────────────────────────────
if ($type === 'summary' || !isset($_GET['type'])) {

    $parcelModel   = new Parcel();
    $customerModel = new Customer();
    $inventoryModel= new Inventory();
    $userModel     = new User();

    $statusCounts = $parcelModel->countByStatus();

    // Revenue from delivered parcels in period
    $revenueStmt = mysqli_prepare($conn,
        "SELECT COALESCE(SUM(price),0) AS revenue
         FROM parcels
         WHERE status='Delivered'
         AND DATE(date_registered) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($revenueStmt, 'ss', $from, $to);
    mysqli_stmt_execute($revenueStmt);
    $revenueResult = mysqli_stmt_get_result($revenueStmt);
    $revenue = (float)mysqli_fetch_assoc($revenueResult)['revenue'];
    mysqli_stmt_close($revenueStmt);

    // Total parcels in period
    $totalStmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total FROM parcels
         WHERE DATE(date_registered) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($totalStmt, 'ss', $from, $to);
    mysqli_stmt_execute($totalStmt);
    $totalResult  = mysqli_stmt_get_result($totalStmt);
    $totalParcels = (int)mysqli_fetch_assoc($totalResult)['total'];
    mysqli_stmt_close($totalStmt);

    // Staff count
    $staffCounts = $userModel->countByRole();
    $totalStaff  = array_sum($staffCounts);

    Auth::respond([
        'status' => 'success',
        'data'   => [
            'total_parcels'  => $totalParcels,
            'total_customers'=> $customerModel->count(),
            'total_staff'    => $totalStaff,
            'in_warehouse'   => $inventoryModel->count(),
            'in_transit'     => $statusCounts['In Transit']    ?? 0,
            'delivered'      => $statusCounts['Delivered']     ?? 0,
            'booked'         => $statusCounts['Booked']        ?? 0,
            'failed'         => $statusCounts['Failed']        ?? 0,
            'revenue'        => $revenue,
        ]
    ]);
}

// ── BY ZONE ───────────────────────────────────────────────
if ($type === 'zone') {
    $stmt = mysqli_prepare($conn,
        "SELECT zone,
                COUNT(*) AS total,
                COALESCE(SUM(price),0) AS revenue
         FROM parcels
         WHERE DATE(date_registered) BETWEEN ? AND ?
         GROUP BY zone
         ORDER BY total DESC");
    mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $zones  = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $zones[] = $row;
    }
    mysqli_stmt_close($stmt);
    Auth::respond(['status' => 'success', 'data' => $zones]);
}

// ── TOP DRIVERS ───────────────────────────────────────────
if ($type === 'drivers') {
    $dispatch = new Dispatch();
    $drivers  = $dispatch->getTopDrivers();
    Auth::respond(['status' => 'success', 'data' => $drivers]);
}

Auth::respond(['status' => 'error', 'message' => 'Unknown report type'], 400);
?>