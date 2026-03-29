<?php
require_once '../db.php';
require_once '../auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Admin Dashboard — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">ADMIN PANEL</div>
    <a href="dashboard.php" style="color:#fff;background:#334155;">📊 Dashboard</a>
    <a href="manage_staff.php">👥 Manage Staff</a>
    <a href="manage_vehicles.php">🚗 Manage Vehicles</a>
    <a href="reports.php">📈 Reports</a>
    <div class="nav-section">QUICK LINKS</div>
    <a href="../customer_service/register_parcel.php">📦 Book Parcel</a>
    <a href="../dispatch/assign_parcel.php">🚛 Dispatch</a>
    <a href="../warehouse/inventory.php">🏭 Inventory</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Branch Manager<br/>
        <a href="../logout.php"
           style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?> —
           Branch Manager</p>
    </div>

    <?php
    $total_parcels   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels"))['t'];
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM customers"))['t'];
    $total_staff     = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM users WHERE role != 'admin'"))['t'];
    $delivered       = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels WHERE status='Delivered'"))['t'];
    $in_transit      = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM parcels WHERE status='In Transit'"))['t'];
    $in_warehouse    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM inventory"))['t'];
    $total_revenue   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(price),0) AS t FROM parcels
         WHERE status='Delivered'"))['t'];
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
            <div class="stat-icon">🧑‍💼</div>
            <div class="stat-num"><?= $total_staff ?></div>
            <div class="stat-label">Staff Members</div>
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

    <!-- STAFF LIST -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:16px;">
            <h2>Staff Members</h2>
            <a href="manage_staff.php" class="track-btn">+ Add Staff</a>
        </div>
        <?php
        $staff = mysqli_query($conn,
            "SELECT * FROM users WHERE role != 'admin'
             ORDER BY role, full_name");
        ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
            <?php while($s = mysqli_fetch_assoc($staff)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($s['username']) ?></td>
                <td>
                    <span class="badge" style="background:#e0e7ff;color:#3730a3;">
                        <?= ucfirst(str_replace('_',' ',$s['role'])) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td>
                    <span class="badge <?= $s['status']==='active' ?
                        'badge-delivered' : 'badge-failed' ?>">
                        <?= $s['status'] ?>
                    </span>
                </td>
                <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($staff) === 0): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">
                    No staff added yet.
                    <a href="manage_staff.php" style="color:#2563eb;">Add staff →</a>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- RECENT PARCELS -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:16px;">
            <h2>Recent Parcels</h2>
            <a href="reports.php" class="track-btn">View Reports</a>
        </div>
        <?php
        $parcels = mysqli_query($conn, "
            SELECT p.tracking_number, p.recipient_name, p.status,
                   p.price, p.date_registered, c.name AS customer_name
            FROM parcels p
            JOIN customers c ON p.customer_id = c.customer_id
            ORDER BY p.date_registered DESC LIMIT 5");
        ?>
        <table>
            <thead>
                <tr>
                    <th>Tracking No.</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Price (KES)</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php while($p = mysqli_fetch_assoc($parcels)): ?>
            <tr>
                <td><span class="tracking">
                    <?= htmlspecialchars($p['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><?= htmlspecialchars($p['recipient_name']) ?></td>
                <td><?= number_format($p['price'],2) ?></td>
                <td><span class="badge badge-<?= strtolower(
                    str_replace(' ','-',$p['status'])) ?>">
                    <?= $p['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($p['date_registered'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>