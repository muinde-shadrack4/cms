<?php
require_once '../db.php';
require_once '../auth.php';
require_role('dispatch');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Dispatch Dashboard — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">DISPATCH</div>
    <a href="dashboard.php" style="color:#fff;background:#334155;">📊 Dashboard</a>
    <a href="assign_parcel.php">🚛 Assign Parcels</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Dispatch Officer<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>Dispatch Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <?php
    $pending = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels
         WHERE status='Booked' AND assigned_driver_id IS NULL"))['t'];
    $assigned = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels
         WHERE status IN ('Picked Up','In Transit','Out for Delivery')"))['t'];
    $drivers = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM users
         WHERE role='driver' AND status='active'"))['t'];
    ?>
    <div class="stats" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-num"><?= $pending ?></div>
            <div class="stat-label">Pending Assignment</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-num"><?= $assigned ?></div>
            <div class="stat-label">Out for Delivery</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🧑‍💼</div>
            <div class="stat-num"><?= $drivers ?></div>
            <div class="stat-label">Available Drivers</div>
        </div>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:16px;">
            <h2>Parcels Awaiting Assignment</h2>
            <a href="assign_parcel.php" class="track-btn">Assign Now →</a>
        </div>
        <?php
        $parcels = mysqli_query($conn,
            "SELECT p.*, c.name AS customer_name FROM parcels p
             JOIN customers c ON p.customer_id=c.customer_id
             WHERE p.status='Booked' AND p.assigned_driver_id IS NULL
             ORDER BY p.date_registered ASC");
        ?>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th><th>Sender</th>
                    <th>Recipient</th><th>Zone</th>
                    <th>Weight</th><th>Booked</th>
                </tr>
            </thead>
            <tbody>
            <?php while($p = mysqli_fetch_assoc($parcels)): ?>
            <tr>
                <td><span class="tracking">
                    <?= htmlspecialchars($p['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><?= htmlspecialchars($p['recipient_name']) ?></td>
                <td><?= $p['zone'] ?></td>
                <td><?= $p['weight'] ?> kg</td>
                <td><?= date('d M Y',strtotime($p['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>