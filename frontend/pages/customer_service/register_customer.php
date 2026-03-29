<?php
require_once '../db.php';
require_once '../auth.php';
require_role('customer_service');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO customers (name, email, phone, password) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $password);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Customer '$name' registered successfully!";
    } else {
        $error = "Error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}

$customers = mysqli_query($conn,
    "SELECT * FROM customers ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Register Customer — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">CUSTOMER SERVICE</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="register_customer.php" style="color:#fff;background:#334155;">👤 Register Customer</a>
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
        <h1>Register Customer</h1>
        <p>Objective 2 — Add a new customer to the system</p>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="two-col">
        <div class="card">
            <h2>New Customer Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name"
                           placeholder="e.g. John Kamau" required/>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email"
                           placeholder="e.g. john@gmail.com" required/>
                </div>
                <div class="form-group">
                    <label>Phone <span class="required">*</span></label>
                    <input type="text" name="phone"
                           placeholder="e.g. 0712345678" required/>
                </div>
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password"
                           placeholder="Create a password" required/>
                </div>
                <button type="submit" class="btn">Register Customer</button>
            </form>
        </div>
        <div class="card">
            <h2>All Customers (<?= mysqli_num_rows($customers) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th>
                        <th>Phone</th><th>Email</th><th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($c = mysqli_fetch_assoc($customers)): ?>
                <tr>
                    <td>#<?= $c['customer_id'] ?></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= date('d M Y',strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>