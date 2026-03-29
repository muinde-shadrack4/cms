<?php
require 'db.php';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking   = strtoupper(trim($_POST['tracking_number']));
    $new_status = $_POST['new_status'];
    $location   = trim($_POST['location']);
    $notes      = trim($_POST['notes']);
    $updated_by = trim($_POST['updated_by']);

    // SECURITY: Prepared statement to find parcel
    $stmt = mysqli_prepare($conn,
        "SELECT parcel_id FROM parcels WHERE tracking_number = ?");
    mysqli_stmt_bind_param($stmt, "s", $tracking);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($result) > 0) {
        $parcel = mysqli_fetch_assoc($result);
        $pid    = $parcel['parcel_id'];

        // SECURITY: Prepared statement to update status
        $stmt2 = mysqli_prepare($conn,
            "UPDATE parcels SET status = ? WHERE parcel_id = ?");
        mysqli_stmt_bind_param($stmt2, "si", $new_status, $pid);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // SECURITY: Prepared statement for tracking log
        $stmt3 = mysqli_prepare($conn,
            "INSERT INTO tracking_updates (parcel_id, status, location, notes, updated_by)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt3, "issss",
            $pid, $new_status, $location, $notes, $updated_by);
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_close($stmt3);

        $success = "Status updated to '$new_status' for $tracking";
    } else {
        $error = "Tracking number '$tracking' not found.";
    }
}

$parcels = mysqli_query($conn, "SELECT p.*, c.name AS customer_name FROM parcels p
           JOIN customers c ON p.customer_id = c.customer_id
           ORDER BY p.date_registered DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Update Status — Wells Fargo CMS</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <a href="index.php">📊 Dashboard</a>
    <div class="nav-section">OBJECTIVE 1</div>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Register Parcel</a>
    <div class="nav-section">OBJECTIVE 2</div>
    <a href="update_status.php" style="color:#fff;background:#334155;">🔄 Update Status</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
</div>

<div class="main">
    <div class="page-header">
        <h1>Update Parcel Status</h1>
        <p>Objective 3 — Staff update tracking status at each checkpoint</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="card">
            <h2>Status Update Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Tracking Number <span class="required">*</span></label>
                    <input type="text" name="tracking_number"
                           placeholder="e.g. WF-2026-1234567"
                           style="text-transform:uppercase;" required/>
                </div>
                <div class="form-group">
                    <label>New Status <span class="required">*</span></label>
                    <select name="new_status" required>
                        <option value="Booked">Booked</option>
                        <option value="Picked Up">Picked Up</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Failed">Failed Delivery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Current Location</label>
                    <input type="text" name="location"
                           placeholder="e.g. Westlands Sorting Hub"/>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="notes"
                           placeholder="e.g. Collected from branch"/>
                </div>
                <div class="form-group">
                    <label>Updated By <span class="required">*</span></label>
                    <input type="text" name="updated_by"
                           placeholder="Your name" required/>
                </div>
                <button type="submit" class="btn">Update Status</button>
            </form>
            <div style="margin-top:16px;padding:12px;background:#f8fafc;
                        border-radius:6px;font-size:12px;color:#374151;">
                <strong>Checkpoints:</strong><br/>
                Booked → Picked Up → In Transit → Out for Delivery → Delivered
            </div>
        </div>

        <div class="card">
            <h2>All Parcels</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tracking No.</th>
                        <th>Sender</th>
                        <th>Recipient</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($p = mysqli_fetch_assoc($parcels)): ?>
                <tr>
                    <td>
                        <a href="#" onclick="document.querySelector('[name=tracking_number]').value='<?= $p['tracking_number'] ?>';window.scrollTo(0,0);return false;"
                           class="tracking"><?= htmlspecialchars($p['tracking_number']) ?></a>
                    </td>
                    <td><?= htmlspecialchars($p['customer_name']) ?></td>
                    <td><?= htmlspecialchars($p['recipient_name']) ?></td>
                    <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$p['status'])) ?>">
                        <?= $p['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>