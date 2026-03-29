<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Wells Fargo CMS</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <a href="index.php" style="color:#fff;background:#334155;">📊 Dashboard</a>
    <div class="nav-section">OBJECTIVE 1 — Registration & Inventory</div>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Register Parcel</a>
    <a href="inventory.php">🏭 Warehouse Inventory</a>
    <div class="nav-section">OBJECTIVE 2 & 3 — Tracking</div>
    <a href="update_status.php">🔄 Update Status</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="nav-section">OBJECTIVE 4 — Reports</div>
    <a href="reports.php">📈 Operational Reports</a>
    <div class="sidebar-footer">
        BUS-242-002/2022<br/>
        Githinji Jecinta Wacheke<br/>
        Multimedia University of Kenya
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Wells Fargo Courier Management System — Nairobi CBD Branch</p>
    </div>

    <?php
    $total_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels"))['t'];
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM customers"))['t'];
    $delivered       = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels WHERE status='Delivered'"))['t'];
    $in_transit      = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels WHERE status='In Transit'"))['t'];
    $in_warehouse    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM inventory"))['t'];
    ?>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-num"><?= $total_parcels ?></div>
            <div class="stat-label">Total Parcels</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-num"><?= $total_customers ?></div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏭</div>
            <div class="stat-num"><?= $in_warehouse ?></div>
            <div class="stat-label">In Warehouse</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-num"><?= $in_transit ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $delivered ?></div>
            <div class="stat-label">Delivered</div>
        </div>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2>Recent Parcels</h2>
            <a href="register_parcel.php" class="track-btn">+ New Parcel</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = mysqli_query($conn, "
                SELECT p.tracking_number, p.recipient_name, p.status,
                       p.date_registered, c.name AS customer_name
                FROM parcels p
                JOIN customers c ON p.customer_id = c.customer_id
                ORDER BY p.date_registered DESC LIMIT 5
            ");
            while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><span class="tracking"><?= htmlspecialchars($row['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['recipient_name']) ?></td>
                <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$row['status'])) ?>">
                    <?= $row['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($row['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="quick-links">
        <a href="register_customer.php" class="quick-card">
            <span>👤</span>
            <strong>Register Customer</strong>
            <small>Objective 1</small>
        </a>
        <a href="register_parcel.php" class="quick-card">
            <span>📦</span>
            <strong>Book Parcel</strong>
            <small>Objective 1</small>
        </a>
        <a href="inventory.php" class="quick-card">
            <span>🏭</span>
            <strong>Inventory</strong>
            <small>Objective 1</small>
        </a>
        <a href="update_status.php" class="quick-card">
            <span>🔄</span>
            <strong>Update Status</strong>
            <small>Objective 2 & 3</small>
        </a>
        <a href="track_parcel.php" class="quick-card">
            <span>🔍</span>
            <strong>Track Parcel</strong>
            <small>Objective 2 & 3</small>
        </a>
        <a href="reports.php" class="quick-card">
            <span>📈</span>
            <strong>Reports</strong>
            <small>Objective 4</small>
        </a>
    </div>
</div>
</body>
</html>