<?php
require_once '../db.php';
require_once '../auth.php';
require_role('customer_service');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Customer Service — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">CUSTOMER SERVICE</div>
    <a href="dashboard.php" style="color:#fff;background:#334155;">📊 Dashboard</a>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Book Parcel</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Customer Service<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>Customer Service Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <?php
    $total_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels"))['t'];
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM customers"))['t'];
    $today_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels
         WHERE DATE(date_registered)=CURDATE()"))['t'];
    ?>
    <div class="stats" style="grid-template-columns:repeat(3,1fr);">
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
            <div class="stat-icon">📅</div>
            <div class="stat-num"><?= $today_parcels ?></div>
            <div class="stat-label">Booked Today</div>
        </div>
    </div>
    <div class="quick-links">
        <a href="register_customer.php" class="quick-card">
            <span>👤</span>
            <strong>Register Customer</strong>
            <small>Add new customer</small>
        </a>
        <a href="register_parcel.php" class="quick-card">
            <span>📦</span>
            <strong>Book Parcel</strong>
            <small>New shipment</small>
        </a>
        <a href="track_parcel.php" class="quick-card">
            <span>🔍</span>
            <strong>Track Parcel</strong>
            <small>Check status</small>
        </a>
    </div>
</div>
</body>
</html>