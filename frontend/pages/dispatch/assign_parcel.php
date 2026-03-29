<?php
require_once '../db.php';
require_once '../auth.php';
require_role('dispatch');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parcel_id  = intval($_POST['parcel_id']);
    $driver_id  = intval($_POST['driver_id']);
    $assigned_by = $_SESSION['user_id'];
    $notes      = trim($_POST['notes']);

    // Create assignment
    $stmt = mysqli_prepare($conn,
        "INSERT INTO dispatch_assignments
         (parcel_id, driver_id, assigned_by, notes)
         VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "iiis",
        $parcel_id, $driver_id, $assigned_by, $notes);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Update parcel status and assigned driver
        $stmt2 = mysqli_prepare($conn,
            "UPDATE parcels SET status='Picked Up',
             assigned_driver_id=?, assigned_at=NOW()
             WHERE parcel_id=?");
        mysqli_stmt_bind_param($stmt2, "ii", $driver_id, $parcel_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // Add tracking update
        $driver = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT full_name FROM users WHERE user_id=$driver_id"));
        $driver_name = $driver['full_name'];

        $stmt3 = mysqli_prepare($conn,
            "INSERT INTO tracking_updates
             (parcel_id, status, location, notes, updated_by)
             VALUES (?, 'Picked Up', 'Assigned to Driver', ?, ?)");
        $note = "Assigned to driver: $driver_name";
        mysqli_stmt_bind_param($stmt3, "iss", $parcel_id, $note, $driver_name);
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_close($stmt3);

        $success = "Parcel assigned to $driver_name successfully!";
    } else {
        $error = "Error: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Parcels waiting assignment
$parcels = mysqli_query($conn,
    "SELECT p.*, c.name AS customer_name FROM parcels p
     JOIN customers c ON p.customer_id=c.customer_id
     WHERE p.status='Booked' AND p.assigned_driver_id IS NULL
     ORDER BY p.date_registered ASC");

// Available drivers
$drivers = mysqli_query($conn,
    "SELECT user_id, full_name, phone FROM users
     WHERE role='driver' AND status='active'
     ORDER BY full_name");

// Recent assignments
$recent = mysqli_query($conn,
    "SELECT da.*, p.tracking_number, p.recipient_name, p.zone,
            u.full_name AS driver_name
     FROM dispatch_assignments da
     JOIN parcels p ON da.parcel_id=p.parcel_id
     JOIN users u ON da.driver_id=u.user_id
     ORDER BY da.assigned_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Assign Parcel — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">DISPATCH</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="assign_parcel.php" style="color:#fff;background:#334155;">🚛 Assign Parcels</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Dispatch Officer<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>Assign Parcels to Drivers</h1>
        <p>Objective 3 — Dispatch coordination: assign parcels to available drivers</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="card">
            <h2>Assign Parcel to Driver</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Select Parcel <span class="required">*</span></label>
                    <select name="parcel_id" required>
                        <option value="">-- Select Parcel --</option>
                        <?php
                        mysqli_data_seek($parcels, 0);
                        while($p = mysqli_fetch_assoc($parcels)): ?>
                        <option value="<?= $p['parcel_id'] ?>">
                            <?= htmlspecialchars($p['tracking_number']) ?> —
                            <?= htmlspecialchars($p['recipient_name']) ?>
                            (<?= $p['zone'] ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assign to Driver <span class="required">*</span></label>
                    <select name="driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php while($d = mysqli_fetch_assoc($drivers)): ?>
                        <option value="<?= $d['user_id'] ?>">
                            <?= htmlspecialchars($d['full_name']) ?>
                            (<?= htmlspecialchars($d['phone']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="notes"
                           placeholder="e.g. Deliver before 3PM"/>
                </div>
                <button type="submit" class="btn">Assign to Driver</button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Assignments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tracking No.</th>
                        <th>Recipient</th>
                        <th>Zone</th>
                        <th>Driver</th>
                        <th>Assigned</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($r = mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td><span class="tracking">
                        <?= htmlspecialchars($r['tracking_number']) ?></span></td>
                    <td><?= htmlspecialchars($r['recipient_name']) ?></td>
                    <td><?= $r['zone'] ?></td>
                    <td><strong><?= htmlspecialchars($r['driver_name']) ?></strong></td>
                    <td><?= date('d M H:i',strtotime($r['assigned_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>