<?php
require 'db.php';

// ── DATE FILTER ──────────────────────────────────────────
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

// ── SUMMARY STATS ────────────────────────────────────────
$total_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels
     WHERE DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];

$total_customers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM customers"))['t'];

$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(price),0) AS t FROM parcels
     WHERE status='Delivered'
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

$booked = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM parcels WHERE status='Booked'
     AND DATE(date_registered) BETWEEN '$from' AND '$to'"))['t'];

// ── PARCELS BY ZONE ───────────────────────────────────────
$by_zone = mysqli_query($conn,
    "SELECT zone, COUNT(*) AS total, SUM(price) AS revenue
     FROM parcels
     WHERE DATE(date_registered) BETWEEN '$from' AND '$to'
     GROUP BY zone ORDER BY total DESC");

// ── PARCELS BY SERVICE TYPE ───────────────────────────────
$by_service = mysqli_query($conn,
    "SELECT service_type, COUNT(*) AS total, SUM(price) AS revenue
     FROM parcels
     WHERE DATE(date_registered) BETWEEN '$from' AND '$to'
     GROUP BY service_type ORDER BY total DESC");

// ── RECENT PARCELS ────────────────────────────────────────
$recent = mysqli_query($conn,
    "SELECT p.tracking_number, p.recipient_name, p.zone,
            p.service_type, p.price, p.status, p.date_registered,
            c.name AS customer_name
     FROM parcels p
     JOIN customers c ON p.customer_id = c.customer_id
     WHERE DATE(p.date_registered) BETWEEN '$from' AND '$to'
     ORDER BY p.date_registered DESC LIMIT 10");

// ── DELIVERY RATE ─────────────────────────────────────────
$delivery_rate = $total_parcels > 0
    ? round(($delivered / $total_parcels) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Reports — Wells Fargo CMS</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <a href="index.php">📊 Dashboard</a>
    <div class="nav-section">OBJECTIVE 1</div>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Register Parcel</a>
    <a href="inventory.php">🏭 Inventory</a>
    <div class="nav-section">OBJECTIVE 2</div>
    <a href="update_status.php">🔄 Update Status</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="nav-section">OBJECTIVE 4</div>
    <a href="reports.php" style="color:#fff;background:#334155;">📈 Reports</a>
</div>

<div class="main">
    <div class="page-header">
        <h1>Operational Reports</h1>
        <p>Objective 4 — Generate reports to support management decision-making</p>
    </div>

    <!-- DATE FILTER -->
    <div class="card" style="padding:16px;">
        <form method="GET" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <label style="font-size:13px;font-weight:600;color:#374151;">
                Filter by Date:
            </label>
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
            <a href="reports.php"
               style="font-size:13px;color:#64748b;text-decoration:none;">
                Reset
            </a>
        </form>
    </div>

    <!-- SUMMARY STATS -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-num"><?= $total_parcels ?></div>
            <div class="stat-label">Total Parcels</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-num"><?= $total_customers ?></div>
            <div class="stat-label">Total Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-num">KES <?= number_format($total_revenue, 0) ?></div>
            <div class="stat-label">Revenue (Delivered)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-num"><?= $delivery_rate ?>%</div>
            <div class="stat-label">Delivery Rate</div>
        </div>
    </div>

    <!-- STATUS BREAKDOWN -->
    <div class="two-col">
        <div class="card">
            <h2>Parcel Status Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Visual</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $statuses = [
                    'Booked'           => [$booked,    'booked'],
                    'In Transit'       => [$in_transit,'in-transit'],
                    'Delivered'        => [$delivered, 'delivered'],
                    'Failed'           => [$failed,    'failed'],
                ];
                foreach($statuses as $label => $data):
                    $count = $data[0];
                    $cls   = $data[1];
                    $pct   = $total_parcels > 0
                        ? round(($count/$total_parcels)*100,1) : 0;
                ?>
                <tr>
                    <td><span class="badge badge-<?= $cls ?>"><?= $label ?></span></td>
                    <td><strong><?= $count ?></strong></td>
                    <td><?= $pct ?>%</td>
                    <td>
                        <div style="background:#f1f5f9;border-radius:4px;
                                    height:8px;width:100%;overflow:hidden;">
                            <div style="background:#2563eb;height:8px;
                                        width:<?= $pct ?>%;border-radius:4px;">
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- BY ZONE -->
        <div class="card">
            <h2>Parcels by Delivery Zone</h2>
            <table>
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Parcels</th>
                        <th>Revenue (KES)</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                mysqli_data_seek($by_zone, 0);
                while($z = mysqli_fetch_assoc($by_zone)):
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($z['zone']) ?></strong></td>
                    <td><?= $z['total'] ?></td>
                    <td><?= number_format($z['revenue'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <h2 style="margin-top:20px;">Parcels by Service Type</h2>
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Parcels</th>
                        <th>Revenue (KES)</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($s = mysqli_fetch_assoc($by_service)): ?>
                <tr>
                    <td><strong><?= ucfirst($s['service_type']) ?></strong></td>
                    <td><?= $s['total'] ?></td>
                    <td><?= number_format($s['revenue'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECENT PARCELS REPORT -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:16px;">
            <h2>Detailed Parcel Report</h2>
            <span style="font-size:12px;color:#64748b;">
                Period: <?= date('d M Y', strtotime($from)) ?> —
                        <?= date('d M Y', strtotime($to)) ?>
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Zone</th>
                    <th>Service</th>
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
                <td><?= htmlspecialchars($r['zone']) ?></td>
                <td><?= ucfirst($r['service_type']) ?></td>
                <td><strong><?= number_format($r['price'], 2) ?></strong></td>
                <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$r['status'])) ?>">
                    <?= $r['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($r['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>