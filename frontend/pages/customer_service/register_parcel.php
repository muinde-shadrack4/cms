<?php
require_once '../db.php';
require_once '../auth.php';
require_role('customer_service');

$success = '';
$error   = '';

function generate_tracking() {
    return 'WF-' . date('Y') . '-' . rand(1000000,9999999);
}

function calculate_price($weight, $zone, $service) {
    if ($weight <= 1) $base = 200;
    elseif ($weight <= 5) $base = 200 + (($weight-1)*50);
    elseif ($weight <= 10) $base = 400 + (($weight-5)*80);
    else $base = 800 + (($weight-10)*100);
    $zones = ['CBD'=>1.0,'Westlands'=>1.2,'Eastlands'=>1.3,'Satellite'=>1.5];
    $base *= $zones[$zone] ?? 1.0;
    if ($service==='express') $base *= 1.5;
    elseif ($service==='same-day') $base *= 1.3;
    return round(max($base, 150), 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id       = intval($_POST['customer_id']);
    $recipient_name    = trim($_POST['recipient_name']);
    $recipient_phone   = trim($_POST['recipient_phone']);
    $recipient_address = trim($_POST['recipient_address']);
    $weight            = floatval($_POST['weight']);
    $zone              = $_POST['zone'];
    $service_type      = $_POST['service_type'];
    $tracking          = generate_tracking();
    $price             = calculate_price($weight, $zone, $service_type);
    $status            = 'Booked';

    $stmt = mysqli_prepare($conn,
        "INSERT INTO parcels (tracking_number, customer_id, recipient_name,
         recipient_phone, recipient_address, weight, service_type, zone, price, status)
         VALUES (?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "sisssdssds",
        $tracking, $customer_id, $recipient_name, $recipient_phone,
        $recipient_address, $weight, $service_type, $zone, $price, $status);

    if (mysqli_stmt_execute($stmt)) {
        $pid = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO tracking_updates
             (parcel_id,status,location,notes,updated_by)
             VALUES (?,'Booked','Nairobi CBD Branch','Parcel booked','System')");
        mysqli_stmt_bind_param($stmt2, "i", $pid);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        $success = "Parcel registered! Tracking: $tracking — KES $price";
    } else {
        $error = "Error: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
    }
}

$customers = mysqli_query($conn,
    "SELECT customer_id, name, phone FROM customers ORDER BY name");
$parcels   = mysqli_query($conn,
    "SELECT p.*, c.name AS customer_name FROM parcels p
     JOIN customers c ON p.customer_id=c.customer_id
     ORDER BY p.date_registered DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Book Parcel — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">CUSTOMER SERVICE</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php" style="color:#fff;background:#334155;">📦 Book Parcel</a>
    <a href="track_parcel.php">🔍 Track Parcel</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Role: Customer Service<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>Book Parcel</h1>
        <p>Objective 2 — Register a new shipment</p>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="two-col">
        <div class="card">
            <h2>Parcel Booking Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Sender (Customer) <span class="required">*</span></label>
                    <select name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php while($c = mysqli_fetch_assoc($customers)): ?>
                        <option value="<?= $c['customer_id'] ?>">
                            <?= htmlspecialchars($c['name']) ?>
                            (<?= htmlspecialchars($c['phone']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="section-title">Recipient Details</div>
                <div class="form-group">
                    <label>Recipient Name <span class="required">*</span></label>
                    <input type="text" name="recipient_name" required/>
                </div>
                <div class="form-group">
                    <label>Recipient Phone <span class="required">*</span></label>
                    <input type="text" name="recipient_phone" required/>
                </div>
                <div class="form-group">
                    <label>Delivery Address <span class="required">*</span></label>
                    <input type="text" name="recipient_address" required/>
                </div>
                <div class="section-title">Parcel Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Weight (kg) <span class="required">*</span></label>
                        <input type="number" name="weight"
                               step="0.1" min="0.1" required/>
                    </div>
                    <div class="form-group">
                        <label>Zone <span class="required">*</span></label>
                        <select name="zone" required>
                            <option value="CBD">CBD (x1.0)</option>
                            <option value="Westlands">Westlands (x1.2)</option>
                            <option value="Eastlands">Eastlands (x1.3)</option>
                            <option value="Satellite">Satellite (x1.5)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Service Type <span class="required">*</span></label>
                    <select name="service_type" required>
                        <option value="standard">Standard</option>
                        <option value="express">Express (x1.5)</option>
                        <option value="same-day">Same Day (x1.3)</option>
                    </select>
                </div>
                <div class="info-box">
                    Tracking number and price auto-generated on submit.
                </div>
                <button type="submit" class="btn">Book Parcel</button>
            </form>
        </div>
        <div class="card">
            <h2>Recent Bookings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tracking</th><th>Sender</th>
                        <th>Recipient</th><th>Price</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($p = mysqli_fetch_assoc($parcels)): ?>
                <tr>
                    <td><span class="tracking">
                        <?= htmlspecialchars($p['tracking_number']) ?></span></td>
                    <td><?= htmlspecialchars($p['customer_name']) ?></td>
                    <td><?= htmlspecialchars($p['recipient_name']) ?></td>
                    <td>KES <?= number_format($p['price'],2) ?></td>
                    <td><span class="badge badge-<?= strtolower(
                        str_replace(' ','-',$p['status'])) ?>">
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