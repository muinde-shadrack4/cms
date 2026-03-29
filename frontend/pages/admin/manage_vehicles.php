<?php
require_once '../db.php';
require_once '../auth.php';
require_role('admin');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $reg    = trim($_POST['registration_number']);
    $type   = trim($_POST['type']);
    $cap    = floatval($_POST['capacity']);
    $status = $_POST['status'];

    $stmt = mysqli_prepare($conn,
        "INSERT INTO vehicles (registration_number, type, capacity, status)
         VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssds", $reg, $type, $cap, $status);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Vehicle '$reg' added successfully!";
    } else {
        $error = "Error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_status') {
    $vehicle_id = intval($_POST['vehicle_id']);
    $status     = $_POST['new_status'];
    $stmt = mysqli_prepare($conn,
        "UPDATE vehicles SET status=? WHERE vehicle_id=?");
    mysqli_stmt_bind_param($stmt, "si", $status, $vehicle_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = "Vehicle status updated.";
}

$vehicles = mysqli_query($conn, "SELECT * FROM vehicles ORDER BY status, type");
$available = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM vehicles WHERE status='Available'"))['t'];
$on_delivery = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM vehicles WHERE status='On Delivery'"))['t'];
$maintenance = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM vehicles WHERE status='Maintenance'"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Vehicles — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">ADMIN PANEL</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="manage_staff.php">👥 Manage Staff</a>
    <a href="manage_vehicles.php" style="color:#fff;background:#334155;">🚗 Manage Vehicles</a>
    <a href="reports.php">📈 Reports</a>
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
        <h1>Manage Vehicles</h1>
        <p>Add and manage the vehicle fleet used for courier deliveries</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="stats" style="grid-template-columns:repeat(3,1fr);
                              max-width:600px;margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $available ?></div>
            <div class="stat-label">Available</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-num"><?= $on_delivery ?></div>
            <div class="stat-label">On Delivery</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔧</div>
            <div class="stat-num"><?= $maintenance ?></div>
            <div class="stat-label">Maintenance</div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <h2>Add New Vehicle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add"/>
                <div class="form-group">
                    <label>Registration Number <span class="required">*</span></label>
                    <input type="text" name="registration_number"
                           placeholder="e.g. KCA 123A" required/>
                </div>
                <div class="form-group">
                    <label>Vehicle Type <span class="required">*</span></label>
                    <select name="type" required>
                        <option value="">-- Select Type --</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                        <option value="Car">Car</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Capacity (kg) <span class="required">*</span></label>
                    <input type="number" name="capacity"
                           step="0.1" min="1" placeholder="e.g. 500" required/>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Available">Available</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <button type="submit" class="btn">Add Vehicle</button>
            </form>
        </div>

        <div class="card">
            <h2>All Vehicles (<?= mysqli_num_rows($vehicles) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Reg No.</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                mysqli_data_seek($vehicles, 0);
                while($v = mysqli_fetch_assoc($vehicles)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($v['registration_number']) ?></strong></td>
                    <td><?= $v['type'] ?></td>
                    <td><?= $v['capacity'] ?> kg</td>
                    <td>
                        <span class="badge <?=
                            $v['status']==='Available' ? 'badge-delivered' :
                            ($v['status']==='On Delivery' ? 'badge-in-transit' :
                            'badge-failed') ?>">
                            <?= $v['status'] ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="margin:0;display:flex;gap:4px;">
                            <input type="hidden" name="action" value="update_status"/>
                            <input type="hidden" name="vehicle_id"
                                   value="<?= $v['vehicle_id'] ?>"/>
                            <select name="new_status"
                                    style="padding:3px 6px;border:1px solid #d1d5db;
                                           border-radius:4px;font-size:12px;">
                                <option <?= $v['status']==='Available'?'selected':'' ?>>Available</option>
                                <option <?= $v['status']==='On Delivery'?'selected':'' ?>>On Delivery</option>
                                <option <?= $v['status']==='Maintenance'?'selected':'' ?>>Maintenance</option>
                            </select>
                            <button type="submit"
                                    style="background:#2563eb;color:#fff;border:none;
                                           padding:3px 8px;border-radius:4px;
                                           cursor:pointer;font-size:12px;">
                                Save
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>