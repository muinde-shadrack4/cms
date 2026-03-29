<?php
require_once '../db.php';
require_once '../auth.php';

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
        $success = "Account created! You can now login.";
    } else {
        $error = "Error: Email may already be registered.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Register — Wells Fargo CMS</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#f1f5f9;
               min-height:100vh; display:flex;
               align-items:center; justify-content:center; }
        .box { background:#fff; border:1px solid #e2e8f0;
               border-radius:12px; padding:40px; width:100%;
               max-width:420px; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
        .logo { text-align:center; margin-bottom:28px; }
        .logo-icon { font-size:48px; display:block; margin-bottom:10px; }
        .logo h1 { font-size:20px; color:#1e293b; font-weight:700; }
        .logo p { font-size:12px; color:#64748b; margin-top:4px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:13px; font-weight:600;
                            color:#374151; margin-bottom:6px; }
        .form-group input { width:100%; padding:10px 14px;
                            border:1px solid #d1d5db; border-radius:6px;
                            font-size:14px; outline:none; }
        .form-group input:focus { border-color:#3b82f6; }
        .btn { width:100%; padding:12px; background:#1e293b; color:#fff;
               border:none; border-radius:6px; font-size:15px;
               font-weight:700; cursor:pointer; }
        .alert { padding:10px 14px; border-radius:6px; font-size:13px;
                 margin-bottom:18px; }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-error   { background:#fee2e2; color:#991b1b; }
        .link { text-align:center; font-size:13px; color:#64748b;
                margin-top:16px; }
        .link a { color:#2563eb; text-decoration:none; }
    </style>
</head>
<body>
<div class="box">
    <div class="logo">
        <span class="logo-icon">🚚</span>
        <h1>Create Account</h1>
        <p>Wells Fargo Courier — Customer Registration</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?>
        <a href="../login.php" style="color:#166534;font-weight:bold;">
            Login here →</a>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name"
                   placeholder="e.g. Jane Wanjiru" required/>
        </div>
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email"
                   placeholder="e.g. jane@gmail.com" required/>
        </div>
        <div class="form-group">
            <label>Phone Number *</label>
            <input type="text" name="phone"
                   placeholder="e.g. 0712345678" required/>
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password"
                   placeholder="Create a strong password" required/>
        </div>
        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="link">
        Already have an account?
        <a href="../login.php">Login here</a>
    </div>
</div>
</body>
</html>