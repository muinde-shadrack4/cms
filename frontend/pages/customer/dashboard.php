<?php
require_once '../db.php';
require_once '../auth.php';
require_role('customer');

// Get customer ID from session
$customer_id_raw = $_SESSION['user_id'];
$customer_id = intval(str_replace('C', '', $customer_id_raw));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>My Account — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">MY ACCOUNT</div>
    <a href="dashboard.php" style="color:#fff;background:#334155;">📊 My Parcels</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Customer Account<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>My Parcels</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    <?php
    $my_parcels = mysqli_query($conn,
        "SELECT * FROM parcels WHERE customer_id=$customer_id
         ORDER BY date_registered DESC");
    $total = mysqli_num_rows($my_parcels);
    $delivered = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels
         WHERE customer_id=$customer_id AND status='Delivered'"))['t'];
    ?>
    <div class="stats" style="grid-template-columns:repeat(2,1fr);max-width:400px;">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total Parcels</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $delivered ?></div>
            <div class="stat-label">Delivered</div>
        </div>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:16px;">
            <h2>My Shipments</h2>
            <a href="track_parcel.php" class="track-btn">Track a Parcel →</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th><th>Recipient</th>
                    <th>Zone</th><th>Price</th>
                    <th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            mysqli_data_seek($my_parcels, 0);
            $count = 0;
            while($p = mysqli_fetch_assoc($my_parcels)):
                $count++;
            ?>
            <tr>
                <td><span class="tracking">
                    <?= htmlspecialchars($p['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($p['recipient_name']) ?></td>
                <td><?= $p['zone'] ?></td>
                <td>KES <?= number_format($p['price'],2) ?></td>
                <td><span class="badge badge-<?= strtolower(
                    str_replace(' ','-',$p['status'])) ?>">
                    <?= $p['status'] ?></span></td>
                <td><?= date('d M Y',strtotime($p['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($count === 0): ?>
            <tr>
                <td colspan="6" style="text-align:center;
                                       color:#94a3b8;padding:20px;">
                    No parcels yet.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>