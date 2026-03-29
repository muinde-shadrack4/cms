<?php
require 'db.php';
$success = '';
$error   = '';

// Add parcel to inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $parcel_id     = intval($_POST['parcel_id']);
        $shelf_location = trim($_POST['shelf_location']);

        // Check if already in inventory
        $check = mysqli_prepare($conn,
            "SELECT inventory_id FROM inventory WHERE parcel_id = ?");
        mysqli_stmt_bind_param($check, "i", $parcel_id);
        mysqli_stmt_execute($check);
        $check_result = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "This parcel is already in the warehouse inventory.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO inventory (parcel_id, shelf_location) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "is", $parcel_id, $shelf_location);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Parcel added to warehouse inventory at shelf: $shelf_location";
            } else {
                $error = "Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($_POST['action'] === 'remove') {
        $inventory_id = intval($_POST['inventory_id']);
        $stmt = mysqli_prepare($conn,
            "DELETE FROM inventory WHERE inventory_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $inventory_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Parcel removed from warehouse inventory.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Search
$search = '';
$where  = '';
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = trim($_GET['search']);
    $where  = "AND (p.tracking_number LIKE '%$search%'
                OR p.recipient_name LIKE '%$search%'
                OR i.shelf_location LIKE '%$search%')";
}

// Get all inventory items
$inventory = mysqli_query($conn, "
    SELECT i.*, p.tracking_number, p.recipient_name,
           p.recipient_address, p.weight, p.status,
           c.name AS customer_name,
           p.date_registered
    FROM inventory i
    JOIN parcels p ON i.parcel_id = p.parcel_id
    JOIN customers c ON p.customer_id = c.customer_id
    WHERE 1=1 $where
    ORDER BY i.received_date DESC
");

// Get parcels NOT yet in inventory (available to add)
$available = mysqli_query($conn, "
    SELECT p.parcel_id, p.tracking_number, p.recipient_name, p.weight, p.status
    FROM parcels p
    WHERE p.parcel_id NOT IN (SELECT parcel_id FROM inventory)
    AND p.status NOT IN ('Delivered','Failed')
    ORDER BY p.date_registered DESC
");

// Stats
$total_inv  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM inventory"))['t'];
$total_shelf = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT shelf_location) AS t FROM inventory"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Inventory — Wells Fargo CMS</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <a href="index.php">📊 Dashboard</a>
    <div class="nav-section">OBJECTIVE 1</div>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Register Parcel</a>
    <a href="inventory.php" style="color:#fff;background:#334155;">🏭 Inventory</a>
    <div class="nav-section">OBJECTIVE 2</div>
    <a href="update_status.php">🔄 Update Status</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="nav-section">OBJECTIVE 4</div>
    <a href="reports.php">📈 Reports</a>
</div>

<div class="main">
    <div class="page-header">
        <h1>Warehouse Inventory</h1>
        <p>Objective 1 — Systematic inventory management for efficient warehouse organization</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats" style="grid-template-columns:repeat(2,1fr);max-width:500px;margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-num"><?= $total_inv ?></div>
            <div class="stat-label">Parcels in Warehouse</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🗄️</div>
            <div class="stat-num"><?= $total_shelf ?></div>
            <div class="stat-label">Shelf Locations Used</div>
        </div>
    </div>

    <div class="two-col">

        <!-- ADD TO INVENTORY FORM -->
        <div class="card">
            <h2>Add Parcel to Warehouse</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add"/>
                <div class="form-group">
                    <label>Select Parcel <span class="required">*</span></label>
                    <select name="parcel_id" required>
                        <option value="">-- Select Parcel --</option>
                        <?php while($p = mysqli_fetch_assoc($available)): ?>
                        <option value="<?= $p['parcel_id'] ?>">
                            <?= htmlspecialchars($p['tracking_number']) ?> —
                            <?= htmlspecialchars($p['recipient_name']) ?>
                            (<?= $p['weight'] ?>kg)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Shelf Location <span class="required">*</span></label>
                    <input type="text" name="shelf_location"
                           placeholder="e.g. A1, B3, C2" required/>
                </div>
                <div class="info-box">
                    Assign a shelf location for quick parcel retrieval.
                </div>
                <button type="submit" class="btn">Add to Warehouse</button>
            </form>
        </div>

        <!-- SEARCH -->
        <div class="card">
            <h2>Search Inventory</h2>
            <form method="GET" style="display:flex;gap:10px;margin-bottom:16px;">
                <input type="text" name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search by tracking no, recipient or shelf..."
                       style="flex:1;padding:9px 12px;border:1px solid #d1d5db;
                              border-radius:6px;font-size:13px;outline:none;"/>
                <button type="submit" class="track-btn">🔍 Search</button>
            </form>
            <?php if ($search): ?>
            <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
                Showing results for: <strong><?= htmlspecialchars($search) ?></strong>
                <a href="inventory.php" style="color:#2563eb;margin-left:8px;">Clear</a>
            </p>
            <?php endif; ?>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;
                        padding:12px;font-size:12px;color:#166534;">
                <strong>How to use:</strong> Search by tracking number, recipient name,
                or shelf location to find parcels quickly.
            </div>
        </div>
    </div>

    <!-- INVENTORY TABLE -->
    <div class="card">
        <h2>Current Warehouse Inventory (<?= mysqli_num_rows($inventory) ?> parcels)</h2>
        <?php if (mysqli_num_rows($inventory) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Shelf</th>
                    <th>Tracking No.</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Weight</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while($item = mysqli_fetch_assoc($inventory)): ?>
            <tr>
                <td>
                    <span style="background:#1e293b;color:#fff;padding:4px 10px;
                                 border-radius:4px;font-weight:700;font-size:13px;">
                        <?= htmlspecialchars($item['shelf_location']) ?>
                    </span>
                </td>
                <td><span class="tracking"><?= htmlspecialchars($item['tracking_number']) ?></span></td>
                <td><?= htmlspecialchars($item['customer_name']) ?></td>
                <td><?= htmlspecialchars($item['recipient_name']) ?></td>
                <td><?= $item['weight'] ?> kg</td>
                <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$item['status'])) ?>">
                    <?= $item['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($item['received_date'])) ?></td>
                <td>
                    <form method="POST" style="margin:0;"
                          onsubmit="return confirm('Remove from inventory?')">
                        <input type="hidden" name="action" value="remove"/>
                        <input type="hidden" name="inventory_id"
                               value="<?= $item['inventory_id'] ?>"/>
                        <button type="submit"
                                style="background:#fee2e2;color:#991b1b;border:none;
                                       padding:4px 10px;border-radius:4px;
                                       cursor:pointer;font-size:12px;">
                            Remove
                        </button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="empty-msg">
            <?= $search ? 'No parcels found matching your search.' : 'No parcels in warehouse yet. Add one above.' ?>
        </p>
        <?php endif; ?>
    </div>
</div>
        </body>
        </html>
        