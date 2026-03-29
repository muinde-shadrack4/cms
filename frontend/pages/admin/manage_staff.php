<?php
require_once '../db.php';
require_once '../auth.php';
require_role('admin');

$success = '';
$error   = '';

// ADD NEW STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $role      = $_POST['role'];
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (full_name, username, email, phone, password, role)
         VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssss",
        $full_name, $username, $email, $phone, $password, $role);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Staff member '$full_name' added successfully as $role!";
    } else {
        $error = "Error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}

// DEACTIVATE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'deactivate') {
    $user_id = intval($_POST['user_id']);
    $stmt = mysqli_prepare($conn,
        "UPDATE users SET status='inactive' WHERE user_id=? AND role != 'admin'");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = "Staff member deactivated.";
}

// ACTIVATE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'activate') {
    $user_id = intval($_POST['user_id']);
    $stmt = mysqli_prepare($conn,
        "UPDATE users SET status='active' WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = "Staff member activated.";
}

// GET ALL STAFF
$staff = mysqli_query($conn,
    "SELECT * FROM users WHERE role != 'admin' ORDER BY role, full_name");

// COUNT BY ROLE
$drivers    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM users WHERE role='driver' AND status='active'"))['t'];
$dispatch   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM users WHERE role='dispatch' AND status='active'"))['t'];
$warehouse  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM users WHERE role='warehouse' AND status='active'"))['t'];
$cs         = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM users WHERE role='customer_service'
     AND status='active'"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Staff — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">ADMIN PANEL</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="manage_staff.php" style="color:#fff;background:#334155;">👥 Manage Staff</a>
    <a href="manage_vehicles.php">🚗 Manage Vehicles</a>
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
        <h1>Manage Staff</h1>
        <p>Add and manage drivers, dispatch officers, warehouse staff and
           customer service representatives</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- STAFF COUNTS -->
    <div class="stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon">🚗</div>
            <div class="stat-num"><?= $drivers ?></div>
            <div class="stat-label">Drivers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-num"><?= $dispatch ?></div>
            <div class="stat-label">Dispatch Officers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏭</div>
            <div class="stat-num"><?= $warehouse ?></div>
            <div class="stat-label">Warehouse Staff</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎧</div>
            <div class="stat-num"><?= $cs ?></div>
            <div class="stat-label">Customer Service</div>
        </div>
    </div>

    <div class="two-col">
        <!-- ADD STAFF FORM -->
        <div class="card">
            <h2>Add New Staff Member</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add"/>

                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name"
                           placeholder="e.g. James Mwangi" required/>
                </div>
                <div class="form-group">
                    <label>Role <span class="required">*</span></label>
                    <select name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="driver">Driver</option>
                        <option value="dispatch">Dispatch Officer</option>
                        <option value="warehouse">Warehouse Staff</option>
                        <option value="customer_service">Customer Service Rep</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username"
                               placeholder="e.g. james01" required/>
                    </div>
                    <div class="form-group">
                        <label>Phone <span class="required">*</span></label>
                        <input type="text" name="phone"
                               placeholder="e.g. 0712345678" required/>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email"
                           placeholder="e.g. james@wellsfargo.co.ke" required/>
                </div>
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password"
                           placeholder="Set a password for this staff member" required/>
                </div>
                <div class="info-box">
                    Staff will use their username and this password to login.
                </div>
                <button type="submit" class="btn">Add Staff Member</button>
            </form>
        </div>

        <!-- STAFF LIST -->
        <div class="card">
            <h2>All Staff (<?= mysqli_num_rows($staff) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                mysqli_data_seek($staff, 0);
                while($s = mysqli_fetch_assoc($staff)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                    <td>
                        <span class="badge" style="background:#e0e7ff;color:#3730a3;">
                            <?= ucfirst(str_replace('_',' ',$s['role'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($s['username']) ?></td>
                    <td><?= htmlspecialchars($s['phone']) ?></td>
                    <td>
                        <span class="badge <?= $s['status']==='active' ?
                            'badge-delivered':'badge-failed' ?>">
                            <?= $s['status'] ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="margin:0;">
                            <?php if ($s['status'] === 'active'): ?>
                            <input type="hidden" name="action" value="deactivate"/>
                            <input type="hidden" name="user_id"
                                   value="<?= $s['user_id'] ?>"/>
                            <button type="submit"
                                    style="background:#fee2e2;color:#991b1b;
                                           border:none;padding:4px 10px;
                                           border-radius:4px;cursor:pointer;
                                           font-size:12px;">
                                Deactivate
                            </button>
                            <?php else: ?>
                            <input type="hidden" name="action" value="activate"/>
                            <input type="hidden" name="user_id"
                                   value="<?= $s['user_id'] ?>"/>
                            <button type="submit"
                                    style="background:#dcfce7;color:#166534;
                                           border:none;padding:4px 10px;
                                           border-radius:4px;cursor:pointer;
                                           font-size:12px;">
                                Activate
                            </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($staff) === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;
                                           color:#94a3b8;padding:20px;">
                        No staff added yet.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>