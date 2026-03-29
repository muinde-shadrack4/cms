<?php
require_once '../db.php';
require_once '../auth.php';
require_role('admin');

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

$total_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels
     WHERE DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM customers"))['t'];
$total_revenue   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(price),0) AS t FROM parcels WHERE status='Delivered'
     AND DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];
$delivered = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels WHERE status='Delivered'
     AND DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];
$in_transit = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels WHERE status='In Transit'
     AND DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];
$failed = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels WHERE status='Failed'
     AND DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];
$delivery_rate = $total_parcels > 0
    ? round(($delivered/$total_parcels)*100,1) : 0;

$by_zone = mysqli_query($conn,
    "SELECT zone, COUNT(*) AS total, SUM(price) AS revenue
     FROM parcels WHERE DATE(date_registered) BETWEEN '$from' AND '$to'
     GROUP BY zone ORDER BY total DESC");

$recent = mysqli_query($conn,
    "SELECT p.tracking_number, p.recipient_name, p.zone, p.service_type,
            p.price, p.status, p.date_registered, c.name AS customer_name
     FROM parcels p JOIN customers c ON p.customer_id=c.customer_id
     WHERE DATE(p.date_registered) BETWEEN '$from' AND '$to'
     ORDER BY p.date_registered DESC LIMIT 15");

$top_drivers = mysqli_query($conn,
    "SELECT u.full_name, COUNT(da.assignment_id) AS deliveries
     FROM dispatch_assignments da
     JOIN users u ON da.driver_id = u.user_id
     WHERE da.status = 'Delivered'
     GROUP BY da.driver_id ORDER BY deliveries DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Reports — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">ADMIN PANEL</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="manage_staff.php">👥 Manage Staff</a>
    <a href="manage_vehicles.php">🚗 Manage Vehicles</a>
    <a href="reports.php" style="color:#fff;background:#334155;">📈 Reports</a>
    <div class="nav-section">QUICK LINKS</div>
    <a href="../customer_service/register_parcel.php">📦 Book Parcel</a>
    <a href="../dispatch/assign_parcel.php">🚛 Dispatch</a>
    <a href="../warehouse/inventory.php">🏭 Inventory</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Branch Manager<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Operational Reports</h1>
        <p>Objective 4 — Management reports for decision-making</p>
    </div>

    <div class="card" style="padding:16px;margin-bottom:20px;">
        <form method="GET" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <label style="font-size:13px;font-weight:600;">Filter by Date:</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:13px;color:#64748b;">From</span>
                <input type="date" name="from" value="<?= $from ?>"
                       style="padding:7px 10px;border:1px solid #d1d5db;
                              border-radius:6px;font-size:13px;outline:none;"/>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:13px;color:#64748b;">To</span>
                <input type="date" name="to" value="<?= $to ?>"
                       style="padding:7px 10px;border:1px solid #d1d5db;
                              border-radius:6px;font-size:13px;outline:none;"/>
            </div>
            <button type="submit" class="track-btn">Generate Report</button>
            <a href="reports.php" style="font-size:13px;color:#64748b;
                                         text-decoration:none;">Reset</a>
        </form>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-num"><?= $total_parcels ?></div>
            <div class="stat-label">Total Parcels</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-num">KES <?= number_format($total_revenue,0) ?></div>
            <div class="stat-label">Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $delivered ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-num"><?= $delivery_rate ?>%</div>
            <div class="stat-label">Delivery Rate</div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <h2>Status Breakdown</h2>
            <table>
                <thead>
                    <tr><th>Status</th><th>Count</th><th>%</th><th>Bar</th></tr>
                </thead>
                <tbody>
                <?php
                $statuses = [
                    'In Transit' => [$in_transit,'in-transit'],
                    'Delivered'  => [$delivered, 'delivered'],
                    'Failed'     => [$failed,    'failed'],
                ];
                foreach($statuses as $label => $data):
                    $pct = $total_parcels > 0
                        ? round(($data[0]/$total_parcels)*100,1) : 0;
                ?>
                <tr>
                    <td><span class="badge badge-<?= $data[1] ?>"><?= $label ?></span></td>
                    <td><strong><?= $data[0] ?></strong></td>
                    <td><?= $pct ?>%</td>
                    <td>
                        <div style="background:#f1f5f9;border-radius:4px;
                                    height:8px;width:100%;">
                            <div style="background:#2563eb;height:8px;
                                        width:<?= $pct ?>%;border-radius:4px;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="margin-top:20px;">Parcels by Zone</h2>
            <table>
                <thead>
                    <tr><th>Zone</th><th>Parcels</th><th>Revenue (KES)</th></tr>
                </thead>
                <tbody>
                <?php while($z = mysqli_fetch_assoc($by_zone)): ?>
                <tr>
                    <td><?= htmlspecialchars($z['zone']) ?></td>
                    <td><?= $z['total'] ?></td>
                    <td><?= number_format($z['revenue'],2) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Top Drivers (by Deliveries)</h2>
            <?php if (mysqli_num_rows($top_drivers) > 0): ?>
            <table>
                <thead>
                    <tr><th>Driver</th><th>Deliveries Completed</th></tr>
                </thead>
                <tbody>
                <?php while($d = mysqli_fetch_assoc($top_drivers)): ?>
                <tr>
                    <td><?= htmlspecialchars($d['full_name']) ?></td>
                    <td><strong><?= $d['deliveries'] ?></strong></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="empty-msg">No deliveries completed yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>Detailed Parcel Report
            <span style="font-size:12px;color:#64748b;font-weight:400;margin-left:10px;">
                Period: <?= date('d M Y',strtotime($from)) ?> —
                        <?= date('d M Y',strtotime($to)) ?>
            </span>
        </h2>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Zone</th>
                    <th>Price (KES)</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php while($r = mysqli_fetch_assoc($recent)): ?>
            <tr>
                <td><span class="tracking">
                    <?= htmlspecialchars($r['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['recipient_name']) ?></td>
                <td><?= $r['zone'] ?></td>
                <td><?= number_format($r['price'],2) ?></td>
                <td><span class="badge badge-<?= strtolower(
                    str_replace(' ','-',$r['status'])) ?>">
                    <?= $r['status'] ?></span></td>
                <td><?= date('d M Y',strtotime($r['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>